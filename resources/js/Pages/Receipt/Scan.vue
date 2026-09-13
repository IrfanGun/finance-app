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
    FileImage,
    LoaderCircle,
    ScanLine,
} from 'lucide-vue-next';

import { PaddleOCR } from '@paddleocr/paddleocr-js';
import * as ort from 'onnxruntime-web/wasm';

import { dashboard } from '@/routes';
import { store as transactionStore } from '@/actions/App/Http/Controllers/TransactionController';
import {
    useTransactionForm,
    type TransactionOption,
} from '@/Features/Transactions/useTransactionForm';

const props = defineProps<{
    categories: TransactionOption[];
    accounts: TransactionOption[];
}>();

/*
|--------------------------------------------------------------------------
| YOLO Configuration
|--------------------------------------------------------------------------
*/

const inputSize = 640;

const confidenceThreshold = 0.25;

/**
 * Confidence minimum untuk auto capture.
 *
 * Detection boleh mulai dari 0.35,
 * tetapi auto capture hanya ketika confidence tinggi.
 */
const autoCaptureThreshold = 0.60;

/**
 * Berapa kali bounding box harus stabil
 * sebelum kamera mengambil foto.
 */
const requiredStableFrames = 3;

/**
 * Interval detection.
 *
 * 250ms = sekitar 4 inference / detik.
 */
const detectionInterval = 250;

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type Detection = {
    confidence: number;

    x: number;
    y: number;

    width: number;
    height: number;
};

/*
|--------------------------------------------------------------------------
| Vue state
|--------------------------------------------------------------------------
*/

const fileInput = ref<HTMLInputElement | null>(null);

const video = ref<HTMLVideoElement | null>(null);

const cameraActive = ref(false);

const sourceUrl = ref('');

const croppedUrl = ref('');

const ocrText = ref('');

const totalAmount = ref<number | null>(null);

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

const errorMessage = ref('');

const statusMessage = ref('');

const isProcessing = ref(false);

const showScanModal = ref(false);

function getRuntimeAssetUrl(assetPath: string): string {
    return new URL(
        assetPath,
        document.location.href,
    ).toString();
}

const detection = ref<Detection | null>(null);

/*
|--------------------------------------------------------------------------
| Internal state
|--------------------------------------------------------------------------
*/

let paddleOcr:
    Awaited<ReturnType<typeof PaddleOCR.create>> | null = null;

let cameraStream: MediaStream | null = null;

/**
 * Session ONNX disimpan agar model tidak reload
 * setiap inference.
 */
let detectionSession: ort.InferenceSession | null = null;

/**
 * Timeout untuk live detection.
 */
let detectionFrameId: number | null = null;

/**
 * State stabilitas detection.
 */
let stableDetectionCount = 0;

let lastDetection: Detection | null = null;

/**
 * Mencegah multiple auto capture.
 */
let autoCaptured = false;

/*
|--------------------------------------------------------------------------
| URL helper
|--------------------------------------------------------------------------
*/

function revokeUrl(url: string) {
    if (url) {
        URL.revokeObjectURL(url);
    }
}

/*
|--------------------------------------------------------------------------
| ONNX Session
|--------------------------------------------------------------------------
*/

async function getDetectionSession() {
    if (!detectionSession) {
        ort.env.wasm.wasmPaths = {
            mjs: getRuntimeAssetUrl(
                '/onnxruntime/ort-wasm-simd-threaded.mjs',
            ),
            wasm: getRuntimeAssetUrl(
                '/onnxruntime/ort-wasm-simd-threaded.wasm',
            ),
        };
        ort.env.wasm.proxy = false;
        ort.env.wasm.numThreads = 1;

        statusMessage.value = 'Memuat model YOLO…';

        detectionSession = await ort.InferenceSession.create(
            getRuntimeAssetUrl(
                '/models/YOLOv8_receipt.onnx',
            ),
            {
                executionProviders: ['wasm'],
            },
        );
    }

    return detectionSession;
}

async function getPaddleOcr() {
    if (!paddleOcr) {
        paddleOcr =
            await PaddleOCR.create({
                lang: 'en',
                ocrVersion: 'PP-OCRv5',
                worker: false,
                textDetectionModelName: 'PP-OCRv5_mobile_det',
                textDetectionModelAsset: {
                    url: getRuntimeAssetUrl(
                        '/paddleocr/PP-OCRv5_mobile_det.tar',
                    ),
                },
                textRecognitionModelName: 'PP-OCRv5_mobile_rec',
                textRecognitionModelAsset: {
                    url: getRuntimeAssetUrl(
                        '/paddleocr/PP-OCRv5_mobile_rec.tar',
                    ),
                },
                ortOptions: {
                    backend: 'wasm',
                    wasmPaths: getRuntimeAssetUrl(
                        '/onnxruntime/',
                    ),
                    numThreads: 1,
                    simd: true,
                },
            });
    }

    return paddleOcr;
}

function extractAmount(
    lines: string[],
    labelPattern: RegExp,
) {
    for (let index = 0; index < lines.length; index += 1) {
        if (!labelPattern.test(lines[index])) {
            continue;
        }

        const candidate =
            lines
                .slice(
                    Math.max(
                        0,
                        index - 1,
                    ),
                    index + 4,
                )
                .join(' ');

        const matches = candidate.match(
            /(?:rp\.?\s*)?\d{1,3}(?:[.,]\d{3})+|(?:rp\.?\s*)?\d{3,}/gi,
        );

        const rawAmount =
            matches?.sort(
                (first, second) =>
                    second.replace(/\D/g, '').length -
                    first.replace(/\D/g, '').length,
            )[0];

        if (!rawAmount) {
            continue;
        }

        const digits =
            rawAmount.replace(/\D/g, '');

        if (digits.length >= 3) {
            return Number(digits);
        }
    }

    return null;
}

function extractReceiptAmounts(
    text: string,
) {
    const lines =
        text
            .split(/\r?\n/)
            .map((line) => line.trim())
            .filter(Boolean);

    totalAmount.value =
        extractAmount(
            lines,
            /\b(t[o0]ta[l1]|jumlah|j[uy]mlah)\b/i,
        );

    if (totalAmount.value === null) {
        totalAmount.value =
            extractAmount(
                lines,
                /\b(bayar|bayer|dibayar|tunai|cash)\b/i,
            );
    }
}

function extractReceiptTotal(
    items: Array<{
        text: string;
    }>,
) {
    const lines =
        items
            .map((item) => item.text.trim())
            .filter(Boolean);

    const total =
        extractAmount(
            lines,
            /\b(t[o0]ta[l1]|jumlah|j[uy]mlah|bayar|bayer)\b/i,
        );

    return total;
}

function formatAmount(
    amount: number | null,
) {
    if (amount === null) {
        return 'Tidak ditemukan';
    }

    return new Intl.NumberFormat(
        'id-ID',
        {
            maximumFractionDigits: 0,
        },
    ).format(amount);
}

/*
|--------------------------------------------------------------------------
| File Picker
|--------------------------------------------------------------------------
*/

function openPicker() {
    fileInput.value?.click();
}

/*
|--------------------------------------------------------------------------
| Camera
|--------------------------------------------------------------------------
*/

async function openCamera() {
    errorMessage.value = '';
    statusMessage.value = '';

    if (!navigator.mediaDevices?.getUserMedia) {
        errorMessage.value =
            'Browser tidak mendukung akses kamera.';

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | 1. AKSES CAMERA
    |--------------------------------------------------------------------------
    */

    try {
        cameraStream =
            await navigator.mediaDevices.getUserMedia({
                audio: false,

                video: {
                    facingMode: {
                        ideal: 'environment',
                    },

                    aspectRatio: {
                        ideal: 9 / 16,
                    },

                    width: {
                        ideal: 1080,
                    },

                    height: {
                        ideal: 1920,
                    },
                },
            });
    } catch (error) {
        console.error(
            'Camera error:',
            error,
        );

        errorMessage.value =
            error instanceof Error
                ? `Camera error: ${error.name} - ${error.message}`
                : 'Camera tidak dapat dibuka.';

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | 2. TAMPILKAN CAMERA
    |--------------------------------------------------------------------------
    */

    cameraActive.value = true;

    await nextTick();

    if (!video.value) {
        errorMessage.value =
            'Video element tidak ditemukan.';

        return;
    }

    video.value.srcObject =
        cameraStream;

    try {
        await video.value.play();
    } catch (error) {
        console.error(
            'Video play error:',
            error,
        );

        errorMessage.value =
            'Camera terbuka tetapi video gagal diputar.';

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | 3. RESET DETECTION
    |--------------------------------------------------------------------------
    */

    autoCaptured = false;

    stableDetectionCount = 0;

    lastDetection = null;

    detection.value = null;

    /*
    |--------------------------------------------------------------------------
    | 4. LOAD YOLO
    |--------------------------------------------------------------------------
    */

    try {
        statusMessage.value =
            'Memuat model YOLO…';

        await getDetectionSession();
    } catch (error) {
        console.error(
            'YOLO MODEL ERROR:',
            error,
        );

        errorMessage.value =
            error instanceof Error
                ? `YOLO gagal dimuat: ${error.message}`
                : 'YOLO gagal dimuat.';

        stopCamera();

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | 5. START LIVE DETECTION
    |--------------------------------------------------------------------------
    */

    statusMessage.value =
        'Arahkan kamera ke struk. Foto akan diambil otomatis.';

    detectCameraFrame();
}

function stopCamera() {
    /*
    |--------------------------------------------------------------------------
    | Stop detection loop
    |--------------------------------------------------------------------------
    */

    if (detectionFrameId !== null) {
        clearTimeout(detectionFrameId);

        detectionFrameId = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Stop camera
    |--------------------------------------------------------------------------
    */

    cameraStream
        ?.getTracks()
        .forEach((track) => track.stop());

    cameraStream = null;

    cameraActive.value = false;

    /*
    |--------------------------------------------------------------------------
    | Reset detection stability
    |--------------------------------------------------------------------------
    */

    stableDetectionCount = 0;

    lastDetection = null;
}

/*
|--------------------------------------------------------------------------
| File Upload
|--------------------------------------------------------------------------
*/

function handleFile(event: Event) {
    const input =
        event.target as HTMLInputElement;

    const file =
        input.files?.[0];

    if (!file) {
        return;
    }

    if (!file.type.startsWith('image/')) {
        errorMessage.value =
            'Pilih file gambar JPG, PNG, atau WebP.';

        return;
    }

    stopCamera();

    revokeUrl(sourceUrl.value);
    revokeUrl(croppedUrl.value);

    sourceUrl.value =
        URL.createObjectURL(file);

    croppedUrl.value = '';

    ocrText.value = '';

    totalAmount.value = null;


    detection.value = null;

    errorMessage.value = '';

    statusMessage.value =
        'Gambar siap dipindai.';
}

/*
|--------------------------------------------------------------------------
| Image loader
|--------------------------------------------------------------------------
*/

async function loadImage(
    url: string,
): Promise<HTMLImageElement> {
    const image =
        new Image();

    image.src =
        url;

    await new Promise<void>(
        (resolve, reject) => {
            image.onload =
                () => resolve();

            image.onerror =
                () =>
                    reject(
                        new Error(
                            'Gambar tidak dapat dibaca.',
                        ),
                    );
        },
    );

    return image;
}

/*
|--------------------------------------------------------------------------
| YOLO Preprocessing
|--------------------------------------------------------------------------
*/

function imageTensor(
    image: HTMLImageElement,
) {
    /*
    |--------------------------------------------------------------------------
    | Letterbox
    |--------------------------------------------------------------------------
    */

    const scale =
        Math.min(
            inputSize /
            image.naturalWidth,

            inputSize /
            image.naturalHeight,
        );

    const width =
        Math.round(
            image.naturalWidth *
            scale,
        );

    const height =
        Math.round(
            image.naturalHeight *
            scale,
        );

    const offsetX =
        Math.floor(
            (inputSize - width) /
            2,
        );

    const offsetY =
        Math.floor(
            (inputSize - height) /
            2,
        );

    const canvas =
        document.createElement(
            'canvas',
        );

    canvas.width =
        inputSize;

    canvas.height =
        inputSize;

    const context =
        canvas.getContext('2d');

    if (!context) {
        throw new Error(
            'Canvas tidak tersedia di browser ini.',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Background letterbox
    |--------------------------------------------------------------------------
    */

    context.fillStyle =
        '#808080';

    context.fillRect(
        0,
        0,
        inputSize,
        inputSize,
    );

    /*
    |--------------------------------------------------------------------------
    | Draw image
    |--------------------------------------------------------------------------
    */

    context.drawImage(
        image,
        offsetX,
        offsetY,
        width,
        height,
    );

    const pixels =
        context.getImageData(
            0,
            0,
            inputSize,
            inputSize,
        ).data;

    /*
    |--------------------------------------------------------------------------
    | HWC -> CHW
    |--------------------------------------------------------------------------
    */

    const data =
        new Float32Array(
            3 *
            inputSize *
            inputSize,
        );

    const imageArea =
        inputSize *
        inputSize;

    for (
        let index = 0;
        index < imageArea;
        index += 1
    ) {
        /*
        |--------------------------------------------------------------------------
        | RGB normalization
        |--------------------------------------------------------------------------
        */

        data[index] =
            pixels[
            index * 4
            ] / 255;

        data[
            imageArea +
            index
        ] =
            pixels[
            index * 4 + 1
            ] / 255;

        data[
            2 *
            imageArea +
            index
        ] =
            pixels[
            index * 4 + 2
            ] / 255;
    }

    return {
        tensor:
            new ort.Tensor(
                'float32',
                data,
                [
                    1,
                    3,
                    inputSize,
                    inputSize,
                ],
            ),

        scale,

        offsetX,

        offsetY,

        canvas,
    };
}

/*
|--------------------------------------------------------------------------
| YOLO Output Decoder
|--------------------------------------------------------------------------
*/

function decodeOutput(
    output: ort.Tensor,

    image: HTMLImageElement,

    scale: number,

    offsetX: number,

    offsetY: number,
): Detection | null {
    const dimensions =
        output.dims;

    const values =
        output.data as Float32Array;

    /*
    |--------------------------------------------------------------------------
    | Support:
    |
    | [1, channels, candidates]
    | atau
    | [1, candidates, channels]
    |--------------------------------------------------------------------------
    */

    const channelsFirst =
        dimensions.length === 3 &&
        dimensions[1] <
        dimensions[2];

    const channels =
        channelsFirst
            ? dimensions[1]
            : dimensions[2];

    const candidates =
        channelsFirst
            ? dimensions[2]
            : dimensions[1];

    let best:
        | {
            confidence: number;

            x: number;
            y: number;

            width: number;
            height: number;
        }
        | null = null;

    for (
        let candidate = 0;
        candidate < candidates;
        candidate += 1
    ) {
        const valueAt = (
            channel: number,
        ) =>
            channelsFirst
                ? values[
                channel *
                candidates +
                candidate
                ]
                : values[
                candidate *
                channels +
                channel
                ];

        /*
        |--------------------------------------------------------------------------
        | YOLOv8 output:
        |
        | 0 = cx
        | 1 = cy
        | 2 = width
        | 3 = height
        | 4+ = classes
        |--------------------------------------------------------------------------
        */

        const classScores =
            Array.from(
                {
                    length:
                        Math.max(
                            channels -
                            4,
                            1,
                        ),
                },

                (_, index) =>
                    valueAt(
                        index +
                        4,
                    ),
            );

        const confidence =
            Math.max(
                ...classScores,
            );

        if (
            confidence <
            confidenceThreshold
        ) {
            continue;
        }

        if (
            best &&
            confidence <=
            best.confidence
        ) {
            continue;
        }

        const centerX =
            valueAt(0);

        const centerY =
            valueAt(1);

        const width =
            valueAt(2);

        const height =
            valueAt(3);

        /*
        |--------------------------------------------------------------------------
        | Map 640x640 coordinates back to original image.
        |--------------------------------------------------------------------------
        */

        const x =
            Math.max(
                0,

                (
                    centerX -
                    width / 2 -
                    offsetX
                ) / scale,
            );

        const y =
            Math.max(
                0,

                (
                    centerY -
                    height / 2 -
                    offsetY
                ) / scale,
            );

        best = {
            confidence,

            x,

            y,

            width:
                Math.min(
                    width /
                    scale,

                    image.naturalWidth -
                    x,
                ),

            height:
                Math.min(
                    height /
                    scale,

                    image.naturalHeight -
                    y,
                ),
        };
    }

    return best;
}

/*
|--------------------------------------------------------------------------
| Crop
|--------------------------------------------------------------------------
*/

function cropImage(
    image: HTMLImageElement,

    box: Detection,
) {
    const paddingX =
        box.width *
        0.12;

    const paddingY =
        box.height *
        0.12;

    const x =
        Math.max(
            0,
            box.x - paddingX,
        );

    const y =
        Math.max(
            0,
            box.y - paddingY,
        );

    const right =
        Math.min(
            image.naturalWidth,
            box.x + box.width + paddingX,
        );

    const bottom =
        Math.min(
            image.naturalHeight,
            box.y + box.height + paddingY,
        );

    const cropWidth =
        Math.max(
            1,
            Math.round(right - x),
        );

    const cropHeight =
        Math.max(
            1,
            Math.round(bottom - y),
        );

    const canvas =
        document.createElement(
            'canvas',
        );

    canvas.width =
        Math.max(
            1,
            cropWidth,
        );

    canvas.height =
        Math.max(
            1,
            cropHeight,
        );

    const context =
        canvas.getContext('2d');

    if (!context) {
        throw new Error(
            'Canvas tidak tersedia.',
        );
    }

    context.drawImage(
        image,

        x,
        y,

        right - x,
        bottom - y,

        0,
        0,

        canvas.width,
        canvas.height,
    );

    return canvas;
}

/*
|--------------------------------------------------------------------------
| Video frame -> image
|--------------------------------------------------------------------------
*/

function getPortraitCrop(
    width: number,
    height: number,
) {
    const portraitRatio =
        2 / 3;

    let cropWidth =
        width;

    let cropHeight =
        width /
        portraitRatio;

    if (cropHeight > height) {
        cropHeight =
            height;

        cropWidth =
            height *
            portraitRatio;
    }

    return {
        x: (width - cropWidth) / 2,
        y: (height - cropHeight) / 2,
        width: cropWidth,
        height: cropHeight,
    };
}

function videoFrameToImage():
    Promise<HTMLImageElement | null> {
    return new Promise(
        (resolve) => {
            if (
                !video.value ||
                video.value
                    .readyState <
                HTMLMediaElement.HAVE_CURRENT_DATA
            ) {
                resolve(null);

                return;
            }

            const canvas =
                document.createElement(
                    'canvas',
                );

            const crop =
                getPortraitCrop(
                    video.value.videoWidth,
                    video.value.videoHeight,
                );

            canvas.width =
                Math.round(crop.width);

            canvas.height =
                Math.round(crop.height);

            const context =
                canvas.getContext(
                    '2d',
                );

            if (!context) {
                resolve(null);

                return;
            }

            context.drawImage(
                video.value,

                crop.x,
                crop.y,
                crop.width,
                crop.height,
                0,
                0,
                canvas.width,
                canvas.height,
            );

            const image =
                new Image();

            image.onload =
                () =>
                    resolve(
                        image,
                    );

            image.onerror =
                () =>
                    resolve(
                        null,
                    );

            /*
            |--------------------------------------------------------------------------
            | Gunakan kualitas lebih rendah untuk live inference
            |
            | Ini bukan hasil foto final.
            |--------------------------------------------------------------------------
            */

            image.src =
                canvas.toDataURL(
                    'image/jpeg',
                    0.75,
                );
        },
    );
}

/*
|--------------------------------------------------------------------------
| Bounding box stability
|--------------------------------------------------------------------------
*/

function isDetectionStable(
    current: Detection,

    previous: Detection | null,
) {
    if (!previous) {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Tolerance pixel.
    |--------------------------------------------------------------------------
    */

    // Camera frames naturally move a few pixels between inferences. Use a
    // relative tolerance so a large receipt is not judged unstable by a
    // small hand movement.
    const positionTolerance =
        Math.max(
            50,
            current.width * 0.08,
            current.height * 0.08,
        );

    const sizeTolerance =
        Math.max(
            70,
            current.width * 0.12,
            current.height * 0.12,
        );

    const stablePosition =
        Math.abs(
            current.x -
            previous.x,
        ) <
        positionTolerance &&
        Math.abs(
            current.y -
            previous.y,
        ) <
        positionTolerance;

    const stableSize =
        Math.abs(
            current.width -
            previous.width,
        ) <
        sizeTolerance &&
        Math.abs(
            current.height -
            previous.height,
        ) <
        sizeTolerance;

    return (
        stablePosition &&
        stableSize
    );
}

/*
|--------------------------------------------------------------------------
| Detection scheduler
|--------------------------------------------------------------------------
*/

function scheduleNextDetection() {
    if (
        !cameraActive.value ||
        autoCaptured
    ) {
        return;
    }

    detectionFrameId =
        window.setTimeout(
            () => {
                detectCameraFrame();
            },

            detectionInterval,
        );
}

/*
|--------------------------------------------------------------------------
| Live YOLO detection
|--------------------------------------------------------------------------
*/

async function detectCameraFrame() {
    if (
        !cameraActive.value ||
        autoCaptured ||
        isProcessing.value
    ) {
        return;
    }

    try {
        /*
        |--------------------------------------------------------------------------
        | Ambil satu frame dari kamera.
        |--------------------------------------------------------------------------
        */

        const image =
            await videoFrameToImage();

        if (!image) {
            scheduleNextDetection();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get loaded ONNX model.
        |--------------------------------------------------------------------------
        */

        const session =
            await getDetectionSession();

        /*
        |--------------------------------------------------------------------------
        | Preprocess.
        |--------------------------------------------------------------------------
        */

        const prepared =
            imageTensor(
                image,
            );

        /*
        |--------------------------------------------------------------------------
        | Inference.
        |--------------------------------------------------------------------------
        */

        const results =
            await session.run({
                [session
                    .inputNames[0]]:
                    prepared.tensor,
            });

        const output =
            results[
            session
                .outputNames[0]
            ];

        if (!output) {
            scheduleNextDetection();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Decode.
        |--------------------------------------------------------------------------
        */

        const detected =
            decodeOutput(
                output,

                image,

                prepared.scale,

                prepared.offsetX,

                prepared.offsetY,
            );

        detection.value =
            detected;

        /*
        |--------------------------------------------------------------------------
        | Tidak ada receipt.
        |--------------------------------------------------------------------------
        */

        if (!detected) {
            stableDetectionCount =
                0;

            lastDetection =
                null;

            statusMessage.value =
                'Arahkan seluruh struk ke dalam kamera.';

            scheduleNextDetection();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Receipt ditemukan tetapi confidence belum cukup.
        |--------------------------------------------------------------------------
        */

        if (
            detected.confidence <
            autoCaptureThreshold
        ) {
            stableDetectionCount =
                0;

            lastDetection =
                detected;

            statusMessage.value =
                `Struk terdeteksi ${Math.round(
                    detected.confidence *
                    100,
                )}%. Dekatkan atau stabilkan kamera.`;

            scheduleNextDetection();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Check stability.
        |--------------------------------------------------------------------------
        */

        if (
            isDetectionStable(
                detected,
                lastDetection,
            )
        ) {
            stableDetectionCount++;
        } else {
            stableDetectionCount =
                0;
        }

        lastDetection =
            detected;

        const progress =
            Math.min(
                100,

                Math.round(
                    (
                        stableDetectionCount /
                        requiredStableFrames
                    ) *
                    100,
                ),
            );

        statusMessage.value =
            `Struk terdeteksi ${Math.round(
                detected.confidence *
                100,
            )}% — tahan kamera ${progress}%`;

        /*
        |--------------------------------------------------------------------------
        | AUTO CAPTURE
        |--------------------------------------------------------------------------
        */

        if (
            stableDetectionCount >=
            requiredStableFrames
        ) {
            const candidateCrop =
                cropImage(
                    image,
                    detected,
                );

            const [candidateOcr] =
                await (
                    await getPaddleOcr()
                ).predict(
                    candidateCrop,
                );

            const candidateTotal =
                candidateOcr
                    ? extractReceiptTotal(
                        candidateOcr.items,
                    )
                    : null;

            if (
                candidateTotal === null
            ) {
                stableDetectionCount =
                    0;

                statusMessage.value =
                    'Struk belum tervalidasi. Pastikan teks Total atau Jumlah dan nominalnya terlihat jelas.';

                scheduleNextDetection();

                return;
            }

            totalAmount.value =
                candidateTotal;

            statusMessage.value =
                `TOTAL ${formatAmount(
                    candidateTotal,
                )} terbaca. Menyiapkan foto…`;

            autoCaptured =
                true;

            statusMessage.value =
                'Struk stabil. Mengambil foto…';

            await autoCaptureReceipt(
                candidateTotal,
            );

            return;
        }
    } catch (error) {
        console.error(
            'Live detection error:',
            error,
        );

        statusMessage.value =
            'Mencoba mendeteksi struk…';
    }

    scheduleNextDetection();
}

/*
|--------------------------------------------------------------------------
| AUTO CAPTURE
|--------------------------------------------------------------------------
*/

async function autoCaptureReceipt(
    validatedTotal: number | null = null,
) {
    if (
        !video.value ||
        video.value.readyState <
        HTMLMediaElement.HAVE_CURRENT_DATA
    ) {
        autoCaptured =
            false;

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Capture FULL RESOLUTION frame.
    |--------------------------------------------------------------------------
    */

    const canvas =
        document.createElement(
            'canvas',
        );

    const crop =
        getPortraitCrop(
            video.value.videoWidth,
            video.value.videoHeight,
        );

    canvas.width =
        Math.round(crop.width);

    canvas.height =
        Math.round(crop.height);

    const context =
        canvas.getContext('2d');

    if (!context) {
        autoCaptured =
            false;

        return;
    }

    context.drawImage(
        video.value,

        crop.x,
        crop.y,
        crop.width,
        crop.height,
        0,
        0,
        canvas.width,
        canvas.height,
    );

    /*
    |--------------------------------------------------------------------------
    | Convert to JPEG.
    |--------------------------------------------------------------------------
    */

    canvas.toBlob(
        async (blob) => {
            if (!blob) {
                autoCaptured =
                    false;

                errorMessage.value =
                    'Foto tidak dapat diambil.';

                return;
            }

            revokeUrl(
                sourceUrl.value,
            );

            revokeUrl(
                croppedUrl.value,
            );

            sourceUrl.value =
                URL.createObjectURL(
                    blob,
                );

            croppedUrl.value =
                '';

            ocrText.value =
                '';

            /*
            |--------------------------------------------------------------------------
            | Stop camera.
            |--------------------------------------------------------------------------
            */

            stopCamera();

            statusMessage.value =
                'Foto berhasil diambil. Memproses struk…';

            await nextTick();

            /*
            |--------------------------------------------------------------------------
            | Automatically run OCR.
            |--------------------------------------------------------------------------
            */

            await scanReceipt(
                validatedTotal,
            );
        },

        'image/jpeg',

        0.95,
    );
}

/*
|--------------------------------------------------------------------------
| Manual scan
|
| Digunakan untuk:
| - gallery upload
| - image yang sudah captured
|--------------------------------------------------------------------------
*/

async function scanReceipt(
    validatedTotal: number | null = null,
) {
    if (
        !sourceUrl.value ||
        isProcessing.value
    ) {
        return;
    }

    isProcessing.value =
        true;

    showScanModal.value =
        true;

    errorMessage.value =
        '';

    ocrText.value =
        '';

    totalAmount.value =
        validatedTotal;


    try {
        /*
        |--------------------------------------------------------------------------
        | Reuse ONNX session.
        |--------------------------------------------------------------------------
        */

        statusMessage.value =
            'Mendeteksi area struk…';

        const session =
            await getDetectionSession();

        /*
        |--------------------------------------------------------------------------
        | Load source image.
        |--------------------------------------------------------------------------
        */

        const image =
            await loadImage(
                sourceUrl.value,
            );

        /*
        |--------------------------------------------------------------------------
        | Prepare tensor.
        |--------------------------------------------------------------------------
        */

        const prepared =
            imageTensor(
                image,
            );

        /*
        |--------------------------------------------------------------------------
        | YOLO inference.
        |--------------------------------------------------------------------------
        */

        const result =
            await session.run({
                [session
                    .inputNames[0]]:
                    prepared.tensor,
            });

        const output =
            result[
            session
                .outputNames[0]
            ];

        if (!output) {
            throw new Error(
                'Model tidak mengembalikan hasil deteksi.',
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Decode.
        |--------------------------------------------------------------------------
        */

        detection.value =
            decodeOutput(
                output,

                image,

                prepared.scale,

                prepared.offsetX,

                prepared.offsetY,
            );

        if (
            !detection.value
        ) {
            throw new Error(
                'Struk tidak terdeteksi. Pastikan struk terlihat jelas dan tidak terpotong.',
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Crop.
        |--------------------------------------------------------------------------
        */

        statusMessage.value =
            'Struk terdeteksi. Menjalankan OCR…';

        const crop =
            cropImage(
                image,
                detection.value,
            );

        croppedUrl.value =
            crop.toDataURL(
                'image/jpeg',
                0.95,
            );

        /*
        |--------------------------------------------------------------------------
        | PaddleOCR.
        |--------------------------------------------------------------------------
        */

        const [ocrResult] =
            await (
                await getPaddleOcr()
            ).predict(
                crop,
            );

        if (!ocrResult) {
            throw new Error(
                'PaddleOCR tidak mengembalikan hasil.',
            );
        }

        ocrText.value =
            ocrResult.items
                .sort(
                    (first, second) =>
                        Math.min(
                            ...first.poly.map(
                                (point) => point[1],
                            ),
                        ) -
                        Math.min(
                            ...second.poly.map(
                                (point) => point[1],
                            ),
                        ),
                )
                .map(
                    (item) => item.text.trim(),
                )
                .filter(Boolean)
                .join('\n');

        extractReceiptAmounts(
            ocrText.value,
        );

        if (
            totalAmount.value === null &&
            validatedTotal !== null
        ) {
            totalAmount.value =
                validatedTotal;
        }

        statusMessage.value =
            'Selesai. Periksa hasil OCR sebelum menyimpan transaksi.';
    } catch (error) {
        console.error(error);

        errorMessage.value =
            error instanceof Error
                ? error.message
                : 'Scan OCR gagal dilakukan.';

        statusMessage.value =
            '';
    } finally {
        isProcessing.value =
            false;

        showScanModal.value =
            totalAmount.value !== null &&
            !errorMessage.value;
    }
}

/*
|--------------------------------------------------------------------------
| Cleanup
|--------------------------------------------------------------------------
*/

onUnmounted(
    async () => {
        stopCamera();

        revokeUrl(
            sourceUrl.value,
        );

        revokeUrl(
            croppedUrl.value,
        );

        if (paddleOcr) {
            await paddleOcr.dispose();

            paddleOcr = null;
        }

        detectionSession =
            null;
    },
);
</script>

<template>

    <Head title="Scan receipt" />

    <main class="min-h-screen bg-app px-6 py-8 text-app-heading">
        <div class="mx-auto flex w-full max-w-lg flex-col gap-6">
            <!-- HEADER -->
            <div class="flex items-center gap-4">
                <Link :href="dashboard.url()"
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-700 shadow-sm"
                    aria-label="Back to dashboard">
                    <ArrowLeft :size="20" />
                </Link>

                <div>
                    <p class="text-sm text-slate-500">
                        Expense capture
                    </p>

                    <h1 class="text-2xl font-semibold">
                        Scan receipt
                    </h1>
                </div>
            </div>

            <!-- CAMERA -->
            <section class="rounded-2xl bg-white p-5 shadow-sm">
                <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden"
                    @change="handleFile" />

                <!-- LIVE CAMERA -->
                <div v-if="cameraActive" class="relative overflow-hidden rounded-xl bg-black">
                    <video ref="video" muted playsinline autoplay
                        class="aspect-[9/16] max-h-[70vh] w-full object-contain" />

                    <!-- Receipt positioning guide -->
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center px-8 py-12">
                        <div class="relative h-[72%] w-[78%] rounded-xl border-2 border-white/90 shadow-[0_0_0_9999px_rgba(0,0,0,0.28)]">
                            <span class="absolute -left-0.5 -top-0.5 h-8 w-8 rounded-tl-lg border-l-4 border-t-4 border-blue-400" />
                            <span class="absolute -right-0.5 -top-0.5 h-8 w-8 rounded-tr-lg border-r-4 border-t-4 border-blue-400" />
                            <span class="absolute -bottom-0.5 -left-0.5 h-8 w-8 rounded-bl-lg border-b-4 border-l-4 border-blue-400" />
                            <span class="absolute -bottom-0.5 -right-0.5 h-8 w-8 rounded-br-lg border-b-4 border-r-4 border-blue-400" />

                            <p class="absolute -bottom-9 left-1/2 w-max -translate-x-1/2 rounded-full bg-black/65 px-3 py-1 text-xs font-medium text-white">
                                Sejajarkan struk di dalam kotak
                            </p>

                            <p v-if="statusMessage.startsWith('Struk terdeteksi')"
                                class="absolute -bottom-[4.5rem] left-1/2 w-max -translate-x-1/2 text-xs font-medium text-white">
                                {{ statusMessage }}
                            </p>
                        </div>
                    </div>

                    <!-- Detection indicator -->
                    <div v-if="detection"
                        class="absolute left-3 top-3 rounded-full bg-black/60 px-3 py-1 text-xs font-medium text-white">
                        {{
                            Math.round(
                                detection.confidence *
                                100,
                            )
                        }}%
                    </div>
                </div>

                <!-- PREVIEW / CAMERA START -->
                <button v-else type="button"
                    class="flex min-h-64 w-full flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-blue-200 bg-blue-50 px-6 text-center"
                    @click="openCamera">
                    <img v-if="sourceUrl" :src="sourceUrl" alt="Receipt preview"
                        class="max-h-72 w-full rounded-lg object-contain" />

                    <template v-else>
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white">
                            <Camera :size="26" />
                        </span>

                        <span class="font-medium text-slate-800">
                            Scan receipt
                        </span>

                        <span class="text-sm text-slate-500">
                            Camera akan mengambil foto
                            otomatis
                        </span>
                    </template>
                </button>

                <!-- CAMERA CANCEL -->
                <div v-if="cameraActive" class="mt-4">
                    <button type="button" class="h-12 w-full rounded-xl border border-slate-300 text-slate-700"
                        @click="stopCamera">
                        Batal
                    </button>
                </div>

                <!-- GALLERY -->
                <button v-else type="button" class="mt-3 w-full text-sm font-medium text-blue-600" @click="openPicker">
                    Pilih dari galeri
                </button>

                <!-- MANUAL SCAN FOR UPLOADED IMAGE -->
                <button v-if="
                    !cameraActive &&
                    sourceUrl
                " type="button"
                    class="mt-4 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 font-medium text-white disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isProcessing" @click="() => scanReceipt()">
                    <LoaderCircle v-if="isProcessing" :size="19" class="animate-spin" />

                    <ScanLine v-else :size="19" />

                    {{
                        isProcessing
                            ? 'Scanning…'
                            : 'Scan OCR'
                    }}
                </button>
            </section>

            <!-- STATUS -->
            <!-- ERROR -->
            <p v-if="errorMessage" class="rounded-xl bg-red-50 p-4 text-sm text-red-700">
                {{ errorMessage }}
            </p>

            <!-- RESULT -->
            <!-- <section v-if="croppedUrl || ocrText" class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2">
                    <FileImage :size="18" class="text-blue-600" />

                    <h2 class="font-semibold">
                        Hasil scan
                    </h2>
                </div>

                <img v-if="croppedUrl" :src="croppedUrl" alt="Detected receipt crop"
                    class="mt-4 max-h-64 w-full rounded-lg bg-slate-100 object-contain" />

                <p v-if="detection" class="mt-3 text-xs text-slate-500">
                    Confidence:
                    {{
                        Math.round(
                            detection.confidence *
                            100,
                        )
                    }}%
                </p>

                <label class="mt-4 block text-sm font-medium text-slate-700">
                    Teks OCR

                    <textarea v-model="ocrText" rows="8"
                        class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm"
                        placeholder="Teks hasil scan akan muncul di sini…" />
                </label>
            </section> -->
        </div>
    </main>

    <div v-if="showScanModal || isProcessing"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-6"
        role="dialog" aria-modal="true">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div v-if="isProcessing" class="flex flex-col items-center gap-4 py-8 text-center">
                <LoaderCircle :size="44" class="animate-spin text-blue-600" />

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Processing...
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Sedang mendeteksi struk dan membaca total.
                    </p>
                </div>
            </div>

            <div v-else>
                <h2 class="text-lg font-semibold text-slate-900">
                    Total transaksi
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Periksa atau koreksi nominal sebelum melanjutkan.
                </p>

                <label class="mt-5 block rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <span class="block text-sm font-bold uppercase text-blue-800">
                        TOTAL
                    </span>

                    <input v-model="editableTotal" inputmode="numeric" autofocus
                        class="mt-2 w-full border-0 bg-transparent p-0 text-right text-2xl font-bold text-slate-900 focus:ring-0" />
                </label>

                <label class="mt-4 block text-sm font-medium text-slate-700">
                    Title

                    <input v-model="transactionTitle" type="text" placeholder="Pengeluaran"
                        class="mt-2 h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </label>

                <label class="mt-4 block text-sm font-medium text-slate-700">
                    Category

                    <select v-model="transactionCategory"
                        class="mt-2 h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option v-for="category in props.categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </label>

                <label class="mt-4 block text-sm font-medium text-slate-700">
                    Account

                    <select v-model="transactionForm.account_id"
                        class="mt-2 h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option v-for="account in props.accounts" :key="account.id" :value="account.id">
                            {{ account.name }}
                        </option>
                    </select>
                </label>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <button type="button"
                        class="h-12 rounded-xl border border-slate-300 font-medium text-slate-700 hover:bg-slate-50"
                        @click="showScanModal = false">
                        Cancel
                    </button>

                    <button type="button"
                        class="h-12 rounded-xl bg-blue-600 font-medium text-white hover:bg-blue-700"
                        :disabled="transactionForm.processing"
                        @click="confirmTransaction">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
