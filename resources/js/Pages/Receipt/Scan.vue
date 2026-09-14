<script setup lang="ts">
import {
    computed,
    nextTick,
    onUnmounted,
    ref,
} from 'vue';
import {
    Head,
    Link,
} from '@inertiajs/vue3';
import {
    ArrowLeft,
    Camera,
    LoaderCircle,
    ScanLine,
} from 'lucide-vue-next';

import { dashboard } from '@/routes';
import receipt from '@/routes/receipt';
import { store as transactionStore } from '@/actions/App/Http/Controllers/TransactionController';
import {
    useTransactionForm,
    type TransactionOption,
} from '@/Features/Transactions/useTransactionForm';

const props = defineProps<{
    categories: TransactionOption[];
    accounts: TransactionOption[];
}>();

type Detection = {
    confidence: number;
    x: number;
    y: number;
    width: number;
    height: number;
};

const fileInput = ref<HTMLInputElement | null>(null);
const video = ref<HTMLVideoElement | null>(null);
const cameraActive = ref(false);
const cameraStream = ref<MediaStream | null>(null);
const selectedFile = ref<File | null>(null);
const sourceUrl = ref('');
const ocrText = ref('');
const totalAmount = ref<number | null>(null);
const detection = ref<Detection | null>(null);
const errorMessage = ref('');
const statusMessage = ref('');
const isProcessing = ref(false);
const showScanModal = ref(false);

const editableTotal = computed({
    get(): string {
        return totalAmount.value === null
            ? ''
            : String(totalAmount.value);
    },
    set(value: string): void {
        const digits = value.replace(/[^0-9]/g, '');

        totalAmount.value = digits
            ? Number(digits)
            : null;
    },
});

const transactionTitle = ref('Pengeluaran');
const transactionCategory = ref<number | string>(
    props.categories.find(
        (category) => category.name.toLowerCase() === 'other',
    )?.id ?? props.categories[0]?.id ?? '',
);
const transactionForm = useTransactionForm(
    props.categories,
    props.accounts,
);

const confirmTransaction = (): void => {
    transactionForm.amount = editableTotal.value;
    transactionForm.title = transactionTitle.value || 'Pengeluaran';
    transactionForm.category_id = Number(transactionCategory.value);

    transactionForm.post(
        transactionStore.url(),
        {
            preserveScroll: true,
            onSuccess: () => {
                showScanModal.value = false;
            },
        },
    );
};

function revokeUrl(url: string): void {
    if (url) {
        URL.revokeObjectURL(url);
    }
}

function resetScanResult(): void {
    ocrText.value = '';
    totalAmount.value = null;
    detection.value = null;
    errorMessage.value = '';
    showScanModal.value = false;
}

function openPicker(): void {
    fileInput.value?.click();
}

function setSourceFile(file: File): void {
    revokeUrl(sourceUrl.value);
    selectedFile.value = file;
    sourceUrl.value = URL.createObjectURL(file);
    resetScanResult();
}

function handleFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    if (!file.type.startsWith('image/')) {
        errorMessage.value = 'Pilih file gambar JPG, PNG, atau WebP.';

        return;
    }

    stopCamera();
    setSourceFile(file);
    statusMessage.value = 'Gambar siap dipindai.';
}

async function openCamera(): Promise<void> {
    errorMessage.value = '';
    statusMessage.value = '';

    if (!navigator.mediaDevices?.getUserMedia) {
        errorMessage.value = 'Browser tidak mendukung akses kamera.';

        return;
    }

    try {
        cameraStream.value = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: {
                facingMode: {
                    ideal: 'environment',
                },
                aspectRatio: {
                    ideal: 9 / 16,
                },
                width: {
                    ideal: 1280,
                    max: 1920,
                },
                height: {
                    ideal: 1920,
                    max: 1920,
                },
            },
        });
    } catch (error) {
        console.error('Camera error:', error);
        errorMessage.value = error instanceof Error
            ? `Camera error: ${error.name} - ${error.message}`
            : 'Camera tidak dapat dibuka.';

        return;
    }

    cameraActive.value = true;
    statusMessage.value = 'Arahkan seluruh struk ke dalam kamera.';

    await nextTick();

    if (!video.value || !cameraStream.value) {
        errorMessage.value = 'Video element tidak ditemukan.';
        stopCamera();

        return;
    }

    video.value.srcObject = cameraStream.value;

    try {
        await video.value.play();
    } catch (error) {
        console.error('Video play error:', error);
        errorMessage.value = 'Camera terbuka tetapi video gagal diputar.';
        stopCamera();
    }
}

function stopCamera(): void {
    cameraStream.value
        ?.getTracks()
        .forEach((track) => track.stop());

    cameraStream.value = null;

    if (video.value) {
        video.value.srcObject = null;
    }

    cameraActive.value = false;
}

function captureCameraImage(): Promise<File> {
    return new Promise((resolve, reject) => {
        const currentVideo = video.value;

        if (
            !currentVideo ||
            currentVideo.readyState < HTMLMediaElement.HAVE_CURRENT_DATA ||
            currentVideo.videoWidth === 0 ||
            currentVideo.videoHeight === 0
        ) {
            reject(new Error('Kamera belum siap mengambil foto.'));

            return;
        }

        const sourceWidth = currentVideo.videoWidth;
        const sourceHeight = currentVideo.videoHeight;
        const portraitRatio = 9 / 16;
        const sourceRatio = sourceWidth / sourceHeight;
        const cropWidth = sourceRatio > portraitRatio
            ? sourceHeight * portraitRatio
            : sourceWidth;
        const cropHeight = sourceRatio > portraitRatio
            ? sourceHeight
            : sourceWidth / portraitRatio;
        const sourceX = (sourceWidth - cropWidth) / 2;
        const sourceY = (sourceHeight - cropHeight) / 2;
        const canvas = document.createElement('canvas');
        canvas.width = 720;
        canvas.height = 1280;
        const context = canvas.getContext('2d');

        if (!context) {
            reject(new Error('Canvas tidak tersedia di browser ini.'));

            return;
        }

        context.drawImage(
            currentVideo,
            sourceX,
            sourceY,
            cropWidth,
            cropHeight,
            0,
            0,
            canvas.width,
            canvas.height,
        );

        canvas.toBlob(
            (blob) => {
                if (!blob) {
                    reject(new Error('Foto tidak dapat diambil.'));

                    return;
                }

                resolve(
                    new File(
                        [blob],
                        'receipt.jpg',
                        { type: 'image/jpeg' },
                    ),
                );
            },
            'image/jpeg',
            0.92,
        );
    });
}

async function captureReceipt(): Promise<void> {
    if (isProcessing.value) {
        return;
    }

    try {
        const file = await captureCameraImage();

        stopCamera();
        setSourceFile(file);
        statusMessage.value = 'Foto berhasil diambil. Mengirim ke server...';

        await scanReceipt(file);
    } catch (error) {
        console.error('Capture error:', error);
        errorMessage.value = error instanceof Error
            ? error.message
            : 'Foto tidak dapat diproses.';
    }
}

function getCsrfToken(): string {
    return document
        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.content ?? '';
}

function getErrorMessage(payload: unknown): string {
    if (
        payload &&
        typeof payload === 'object' &&
        'message' in payload &&
        typeof payload.message === 'string'
    ) {
        return payload.message;
    }

    return 'Scan OCR gagal dilakukan.';
}

async function scanReceipt(
    file: File | null = selectedFile.value,
): Promise<void> {
    if (!file || isProcessing.value) {
        return;
    }

    isProcessing.value = true;
    showScanModal.value = true;
    errorMessage.value = '';
    ocrText.value = '';
    totalAmount.value = null;
    detection.value = null;
    statusMessage.value = 'Server sedang mendeteksi dan membaca struk...';

    const formData = new FormData();
    formData.append('image', file, file.name || 'receipt.jpg');

    try {
        const response = await fetch(
            receipt.ocr.url(),
            {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            },
        );
        const payload: unknown = await response.json().catch(() => null);

        if (!response.ok) {
            throw new Error(getErrorMessage(payload));
        }

        if (!payload || typeof payload !== 'object') {
            throw new Error('Respons OCR server tidak valid.');
        }

        const result = payload as {
            text?: unknown;
            amount?: unknown;
            detection?: unknown;
        };

        ocrText.value = typeof result.text === 'string'
            ? result.text
            : '';
        totalAmount.value = typeof result.amount === 'number'
            ? result.amount
            : null;

        if (
            result.detection &&
            typeof result.detection === 'object'
        ) {
            detection.value = result.detection as Detection;
        }

        statusMessage.value =
            'Selesai. Periksa hasil OCR sebelum menyimpan transaksi.';
    } catch (error) {
        console.error('OCR API error:', error);
        errorMessage.value = error instanceof Error
            ? error.message
            : 'Scan OCR gagal dilakukan.';
        statusMessage.value = '';
    } finally {
        isProcessing.value = false;
        showScanModal.value = !errorMessage.value;
    }
}

onUnmounted(() => {
    stopCamera();
    revokeUrl(sourceUrl.value);
});
</script>

<template>
    <Head title="Scan receipt" />

    <main class="min-h-screen bg-app px-6 py-8 text-app-heading">
        <div class="mx-auto flex w-full max-w-lg flex-col gap-6">
            <div class="flex items-center gap-4">
                <Link
                    :href="dashboard.url()"
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-700 shadow-sm"
                    aria-label="Back to dashboard"
                >
                    <ArrowLeft :size="20" />
                </Link>

                <div>
                    <p class="text-sm text-slate-500">Expense capture</p>
                    <h1 class="text-2xl font-semibold">Scan receipt</h1>
                </div>
            </div>

            <section class="rounded-2xl bg-white p-5 shadow-sm">
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="handleFile"
                />

                <div
                    v-if="cameraActive"
                    class="relative mx-auto aspect-[9/16] max-h-[70vh] overflow-hidden rounded-xl bg-slate-900"
                >
                    <video
                        ref="video"
                        muted
                        playsinline
                        autoplay
                        class="block h-full w-full object-cover"
                    />

                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center px-8 py-12">
                        <div class="relative h-[72%] w-[78%] rounded-xl border-2 border-white/90">
                            <span class="absolute -left-0.5 -top-0.5 h-8 w-8 rounded-tl-lg border-l-4 border-t-4 border-blue-400" />
                            <span class="absolute -right-0.5 -top-0.5 h-8 w-8 rounded-tr-lg border-r-4 border-t-4 border-blue-400" />
                            <span class="absolute -bottom-0.5 -left-0.5 h-8 w-8 rounded-bl-lg border-b-4 border-l-4 border-blue-400" />
                            <span class="absolute -bottom-0.5 -right-0.5 h-8 w-8 rounded-br-lg border-b-4 border-r-4 border-blue-400" />

                            <p class="absolute -bottom-9 left-1/2 w-max -translate-x-1/2 rounded-full bg-black/65 px-3 py-1 text-xs font-medium text-white">
                                Sejajarkan seluruh struk di dalam kotak
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="flex min-h-64 w-full flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-blue-200 bg-blue-50 px-6 text-center"
                >
                    <img
                        v-if="sourceUrl"
                        :src="sourceUrl"
                        alt="Receipt preview"
                        class="max-h-72 w-full rounded-lg object-contain"
                    />

                    <template v-else>
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white">
                            <Camera :size="26" />
                        </span>
                        <span class="font-medium text-slate-800">Scan receipt</span>
                        <span class="text-sm text-slate-500">
                            Foto akan diproses oleh server
                        </span>
                    </template>
                </div>

                <div v-if="cameraActive" class="mt-4 grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="h-12 rounded-xl border border-slate-300 text-slate-700"
                        @click="stopCamera"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        class="flex h-12 items-center justify-center gap-2 rounded-xl bg-blue-600 font-medium text-white disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="isProcessing"
                        @click="captureReceipt"
                    >
                        <Camera :size="19" />
                        Ambil foto
                    </button>
                </div>

                <div v-else class="mt-3 grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="h-11 rounded-xl bg-blue-600 font-medium text-white"
                        @click="openCamera"
                    >
                        Kamera
                    </button>

                    <button
                        type="button"
                        class="h-11 rounded-xl border border-blue-200 font-medium text-blue-600"
                        @click="openPicker"
                    >
                        Galeri
                    </button>
                </div>

                <button
                    v-if="!cameraActive && selectedFile"
                    type="button"
                    class="mt-4 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 font-medium text-white disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isProcessing"
                    @click="scanReceipt()"
                >
                    <LoaderCircle v-if="isProcessing" :size="19" class="animate-spin" />
                    <ScanLine v-else :size="19" />
                    {{ isProcessing ? 'Scanning...' : 'Scan OCR' }}
                </button>
            </section>

            <p v-if="statusMessage" class="rounded-xl bg-blue-50 p-4 text-sm text-blue-700">
                {{ statusMessage }}
            </p>

            <p v-if="errorMessage" class="rounded-xl bg-red-50 p-4 text-sm text-red-700">
                {{ errorMessage }}
            </p>
        </div>
    </main>

    <div
        v-if="showScanModal || isProcessing"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-6"
        role="dialog"
        aria-modal="true"
    >
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div v-if="isProcessing" class="flex flex-col items-center gap-4 py-8 text-center">
                <LoaderCircle :size="44" class="animate-spin text-blue-600" />
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Processing...</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Server sedang mendeteksi struk dan membaca total.
                    </p>
                </div>
            </div>

            <div v-else>
                <h2 class="text-lg font-semibold text-slate-900">Total transaksi</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Periksa atau koreksi nominal sebelum melanjutkan.
                </p>

                <label class="mt-5 block rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <span class="block text-sm font-bold uppercase text-blue-800">TOTAL</span>
                    <input
                        v-model="editableTotal"
                        inputmode="numeric"
                        autofocus
                        class="mt-2 w-full border-0 bg-transparent p-0 text-right text-2xl font-bold text-slate-900 focus:ring-0"
                    />
                </label>

                <label class="mt-4 block text-sm font-medium text-slate-700">
                    Title
                    <input
                        v-model="transactionTitle"
                        type="text"
                        placeholder="Pengeluaran"
                        class="mt-2 h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                </label>

                <label class="mt-4 block text-sm font-medium text-slate-700">
                    Category
                    <select
                        v-model="transactionCategory"
                        class="mt-2 h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option
                            v-for="category in props.categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                </label>

                <label class="mt-4 block text-sm font-medium text-slate-700">
                    Account
                    <select
                        v-model="transactionForm.account_id"
                        class="mt-2 h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option
                            v-for="account in props.accounts"
                            :key="account.id"
                            :value="account.id"
                        >
                            {{ account.name }}
                        </option>
                    </select>
                </label>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="h-12 rounded-xl border border-slate-300 font-medium text-slate-700 hover:bg-slate-50"
                        @click="showScanModal = false"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="h-12 rounded-xl bg-blue-600 font-medium text-white hover:bg-blue-700"
                        :disabled="transactionForm.processing"
                        @click="confirmTransaction"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
