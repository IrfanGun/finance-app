<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Banknote,
    Camera,
    Check,
    Clock3,
    Eye,
    ImagePlus,
    Info,
    MapPin,
    Pencil,
    Plus,
    Search,
    SlidersHorizontal,
    Star,
    ShoppingCart,
    Trash2,
    Utensils,
    Upload,
} from 'lucide-vue-next';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import MealExpenseModal from '@/Components/Meals/MealExpenseModal.vue';
import { useMealFilters } from '@/Features/Meals/useMealFilters';
import { useMealRecordForm } from '@/Features/Meals/useMealRecordForm';
import {
    formatMealPrice,
    getMealStatusLabel,
    weekdays,
} from '@/Features/Meals/lib/mealRecord';
import { dashboard } from '@/routes';
import {
    destroy as cmsMealDestroy,
    index as cmsMealsIndex,
    photo as cmsMealPhoto,
    show as cmsMealShow,
} from '@/routes/cms/meals';
import { store as cmsMealPhotoStore } from '@/routes/cms/meals/photo';
import {
    destroy as mealDestroy,
    index as mealsIndex,
    show as mealShow,
} from '@/routes/meals';

const props = defineProps({
    mealRecords: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
    expenseCategories: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    mealCurrency: { type: String, default: 'IDR' },
    isCms: { type: Boolean, default: false },
});

const formElement = ref(null);
const photoInputElement = ref(null);
const photoPreviews = ref([]);
const photoSelectionError = ref(null);
const isPhotoDropActive = ref(false);
const activePhotoKey = ref(null);
const photoInputRefs = ref({});
const priceFiltersVisible = ref(false);
const sortOrder = ref('name');
const uploadingPhotoFor = ref(null);
const photoUploadErrors = ref({});
const selectedMealForExpense = ref(null);
const {
    availabilityFilter,
    isLoading,
    priceMaxFilter,
    priceMinFilter,
    search,
    typeFilter,
} = useMealFilters(props.filters, props.isCms ? cmsMealsIndex : mealsIndex);
const {
    addDailySession,
    addPrice,
    addSession,
    changeScheduleMode,
    closeForm,
    continueToNextStep,
    currentStep,
    dailySessions,
    editingMeal,
    errors,
    form,
    formVisible,
    hasOpeningHours,
    isFinalStep,
    openCreate,
    openEdit,
    removeDailySession,
    removePrice,
    removeSession,
    save,
    scheduleMode,
    toggleOpeningHours,
    totalSteps,
} = useMealRecordForm(formElement, props.isCms);
const photoUploadValidationError = computed(() => {
    const indexedError = Object.entries(errors.value ?? {})
        .find(([key]) => key.startsWith('photos.')
            || key.startsWith('remove_photo_indexes')
            || key === 'primary_photo_index')?.[1];

    return indexedError ?? errors.value?.photos;
});

const previewPrices = computed(() => (form.prices ?? [])
    .filter((price) => price !== '' && Number.isFinite(Number(price)))
    .map(Number));
const previewPriceText = computed(() => {
    if (!previewPrices.value.length) {
        return 'Estimasi harga';
    }

    const minimum = Math.min(...previewPrices.value);
    const maximum = Math.max(...previewPrices.value);
    const formatCurrency = (value) => `Rp ${new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 2,
    }).format(value)}`;

    return minimum === maximum
        ? formatCurrency(minimum)
        : `${formatCurrency(minimum)} – ${formatCurrency(maximum)}`;
});
const previewScheduleText = computed(() => {
    if (!hasOpeningHours.value) {
        return 'Jadwal belum ditambahkan';
    }

    const sessions = scheduleMode.value === 'everyday'
        ? dailySessions.value
        : form.opening_hours;

    if (!sessions.length) {
        return 'Jam buka belum diketahui';
    }

    return sessions
        .map((session) => {
            const range = `${session.opens_at} – ${session.closes_at}`;

            return scheduleMode.value === 'custom'
                ? `${weekdays[session.day_of_week - 1]?.slice(0, 3)} ${range}`
                : range;
        })
        .join(', ');
});
const existingPhotoIndexes = computed(() => Array.from(
    { length: editingMeal.value?.photo_count ?? 0 },
    (_, index) => index,
));
const photoItems = computed(() => {
    const removedIndexes = form.remove_photo_indexes ?? [];
    const savedPhotos = existingPhotoIndexes.value
        .filter((index) => !removedIndexes.includes(index))
        .map((index) => ({
            key: `saved-${index}`,
            index,
            isSaved: true,
            url: cmsMealPhoto.url(editingMeal.value.id, {
                query: {
                    index,
                    v: editingMeal.value.updated_at,
                },
            }),
        }));
    const selectedPhotos = photoPreviews.value.map((photo, index) => ({
        key: `selected-${index}`,
        index,
        isSaved: false,
        url: photo.url,
    }));

    return [...savedPhotos, ...selectedPhotos];
});
const activePhoto = computed(() => photoItems.value.find(
    (photo) => photo.key === activePhotoKey.value,
) ?? photoItems.value[0] ?? null);
const previewPhotoUrl = computed(() => activePhoto.value?.url ?? null);

const clearPhotoPreviews = () => {
    photoPreviews.value.forEach(({ url }) => URL.revokeObjectURL(url));
    photoPreviews.value = [];
    photoSelectionError.value = null;
    activePhotoKey.value = null;
    isPhotoDropActive.value = false;
};

const createMeal = () => {
    clearPhotoPreviews();
    openCreate();
};

const editMeal = (meal) => {
    clearPhotoPreviews();
    openEdit(meal);
};

const addMealPhotos = (files) => {
    if (!files.length) {
        return;
    }

    const remainingSlots = Math.max(0, 5 - photoItems.value.length);
    const acceptedFiles = files.slice(0, remainingSlots);

    photoSelectionError.value = files.length > remainingSlots
        ? `Maksimal 5 foto per makanan. Hanya ${remainingSlots} foto pertama yang ditambahkan.`
        : null;
    form.photos.push(...acceptedFiles);
    photoPreviews.value.push(...acceptedFiles.map((file) => ({
        name: file.name,
        url: URL.createObjectURL(file),
    })));

    if (!activePhotoKey.value && acceptedFiles.length) {
        activePhotoKey.value = `selected-${photoPreviews.value.length - acceptedFiles.length}`;
    }
};

const selectMealPhotos = (event) => {
    addMealPhotos(Array.from(event.target.files ?? []));
    event.target.value = '';
};

const dropMealPhotos = (event) => {
    isPhotoDropActive.value = false;
    addMealPhotos(Array.from(event.dataTransfer?.files ?? []));
};

const openMealPhotoPicker = () => {
    photoInputElement.value?.click();
};

const setMainPhoto = (photo) => {
    activePhotoKey.value = photo.key;
    form.primary_photo_index = photoItems.value.findIndex(
        (item) => item.key === photo.key,
    );
};

const removePhoto = (photo) => {
    if (photo.isSaved) {
        form.remove_photo_indexes.push(photo.index);
    } else {
        const selectedIndex = photo.index;
        const [removedPreview] = photoPreviews.value.splice(selectedIndex, 1);

        form.photos.splice(selectedIndex, 1);
        URL.revokeObjectURL(removedPreview.url);
    }

    const nextActivePhoto = photoItems.value[0] ?? null;
    activePhotoKey.value = nextActivePhoto?.key ?? null;
    form.primary_photo_index = nextActivePhoto
        ? photoItems.value.findIndex((item) => item.key === nextActivePhoto.key)
        : 0;
};

const recordExpense = (meal) => {
    if (props.isCms) {
        selectedMealForExpense.value = meal;

        return;
    }

    router.visit(dashboard.url({
        query: {
            meal_record_id: meal.id,
        },
    }));
};

const remove = (meal) => {
    if (!window.confirm(`Hapus ${meal.name} dari Meal Record?`)) {
        return;
    }

    const destroyRoute = props.isCms ? cmsMealDestroy : mealDestroy;
    router.delete(destroyRoute.url(meal.id), { preserveScroll: true });
};

const uploadPhoto = (event, meal) => {
    const fileInput = event.target;
    const photo = fileInput.files?.[0];

    if (!photo) {
        return;
    }

    photoUploadErrors.value[meal.id] = null;
    uploadingPhotoFor.value = meal.id;

    router.post(cmsMealPhotoStore.url(meal.id), { photo }, {
        forceFormData: true,
        preserveScroll: true,
        onError: (errors) => {
            photoUploadErrors.value[meal.id] = errors.photo;
        },
        onFinish: () => {
            uploadingPhotoFor.value = null;
            fileInput.value = '';
        },
    });
};

const setPhotoInput = (mealId, element) => {
    if (element) {
        photoInputRefs.value[mealId] = element;
    }
};

const openPhotoPicker = (mealId) => {
    photoInputRefs.value[mealId]?.click();
};

const statusLabel = getMealStatusLabel;
const formatPrice = (meal) => formatMealPrice(meal, props.isCms);
const sortedMealRecords = computed(() => [...props.mealRecords.data].sort((first, second) => {
    if (sortOrder.value === 'price-low') {
        return Number(first.price_min) - Number(second.price_min);
    }

    if (sortOrder.value === 'price-high') {
        return Number(second.price_max) - Number(first.price_max);
    }

    return first.name.localeCompare(second.name, 'id');
}));
</script>

<template>
    <Head :title="isCms ? 'CMS Meals' : formVisible ? (editingMeal ? 'Edit pilihan' : 'Pilihan baru') : 'Meal Record'" />

    <div :class="isCms ? 'min-h-screen w-full space-y-6 text-[#152338]' : 'mx-auto min-h-screen w-full max-w-lg bg-white pb-28 text-app-heading'">
        <header v-if="!formVisible" class="flex flex-wrap items-center justify-between gap-4 px-6 py-5" :class="isCms ? 'rounded-2xl border border-[#E9EDF3] bg-white' : ''">
            <Link v-if="!isCms" :href="dashboard.url()" aria-label="Kembali ke dashboard"><ArrowLeft :size="22" class="text-[#2457DA]" /></Link>
            <div v-if="isCms">
                <p class="text-sm font-medium text-[#8792A2]">{{ $t('Meal Record') }}</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-[#17243A]">{{ $t('Meals') }}</h1>
                <p class="mt-1 text-sm text-[#637083]">{{ mealRecords.total ?? mealRecords.data.length }} pilihan tersimpan</p>
            </div>
            <h1 v-else class="text-lg font-semibold">{{ $t("Meal Record") }}</h1>
            <ButtonPrimary
                type="button"
                :class="isCms ? '!inline-flex !h-11 !min-h-11 !items-center !gap-2 !px-5 !py-0' : '!inline-flex !h-11 !min-h-11 !w-11 !items-center !justify-center !rounded-xl !p-0'"
                aria-label="Tambah pilihan makanan"
                @click="createMeal"
            >
                <Plus :size="18" /> {{ isCms ? $t('Add meal') : '' }}
            </ButtonPrimary>
        </header>

        <main v-if="!formVisible" class="space-y-4" :class="isCms ? '' : 'px-5 pt-5'">
            <p v-if="isLoading" role="status" class="text-center text-xs text-[#637083]">Memuat pilihan makanan…</p>
            <section v-if="isCms" class="space-y-3 rounded-2xl border border-[#E9EDF3] bg-white p-4">
                <div class="grid gap-2 md:grid-cols-[minmax(220px,1.5fr)_minmax(150px,0.9fr)_minmax(170px,0.9fr)_auto]">
                    <label class="flex h-11 items-center gap-2 rounded-xl border border-[#DDE6F5] px-3">
                        <Search :size="17" class="shrink-0 text-[#637083]" />
                        <input v-model="search" type="search" placeholder="Cari makanan atau tempat" class="w-full border-0 p-0 text-sm focus:ring-0" />
                    </label>
                    <select v-model="typeFilter" class="h-11 rounded-xl border border-[#DDE6F5] bg-white px-3 text-sm">
                        <option value="">Semua tipe</option>
                        <option value="buy">Beli jadi</option>
                        <option value="homemade">Masak sendiri</option>
                    </select>
                    <select v-model="availabilityFilter" class="h-11 rounded-xl border border-[#DDE6F5] bg-white px-3 text-sm">
                        <option value="">Semua status tempat</option>
                        <option value="open">Sedang buka</option>
                        <option value="closed">Sedang tutup</option>
                        <option value="unknown">Jam belum diketahui</option>
                    </select>
                    <ButtonPrimary
                        type="button"
                        variant="secondary"
                        class="!inline-flex !h-11 !min-h-11 !items-center !justify-center !gap-2 !whitespace-nowrap !border-[#2457DA] !px-3 !py-0 !text-[#2457DA]"
                        @click="priceFiltersVisible = !priceFiltersVisible"
                    >
                        <SlidersHorizontal :size="16" /> Rentang harga
                    </ButtonPrimary>
                </div>
                <div v-if="priceFiltersVisible" class="grid max-w-xl grid-cols-2 gap-2">
                    <AmountInput v-model="priceMinFilter" placeholder="Harga minimum" class="h-10 rounded-lg border border-[#DDE6F5] bg-white text-sm" />
                    <AmountInput v-model="priceMaxFilter" placeholder="Harga maksimum" class="h-10 rounded-lg border border-[#DDE6F5] bg-white text-sm" />
                </div>
            </section>

            <section v-else class="space-y-3 rounded-2xl bg-[#F5F7FA] p-4">
                <label class="flex h-11 items-center gap-2 rounded-xl border border-[#E9EDF3] bg-white px-3">
                    <Search :size="17" class="shrink-0 text-[#637083]" />
                    <input v-model="search" type="search" placeholder="Cari makanan atau tempat" class="w-full border-0 p-0 text-sm focus:ring-0" />
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <select v-model="typeFilter" class="h-10 rounded-lg border border-[#E9EDF3] bg-white text-sm">
                        <option value="">{{ $t("Semua tipe") }}</option>
                        <option value="buy">{{ $t("Beli jadi") }}</option>
                        <option value="homemade">{{ $t("Masak sendiri") }}</option>
                    </select>
                    <select v-model="availabilityFilter" class="h-10 rounded-lg border border-[#E9EDF3] bg-white text-sm">
                        <option value="">{{ $t("Semua status buka") }}</option>
                        <option value="open">{{ $t("Sedang buka") }}</option>
                        <option value="closed">{{ $t("Sedang tutup") }}</option>
                        <option value="unknown">{{ $t("Jam belum diketahui") }}</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <AmountInput v-model="priceMinFilter" placeholder="Harga minimum" class="h-10 rounded-lg border border-[#E9EDF3] bg-white text-sm" />
                    <AmountInput v-model="priceMaxFilter" placeholder="Harga maksimum" class="h-10 rounded-lg border border-[#E9EDF3] bg-white text-sm" />
                </div>
            </section>

            <p v-if="!mealRecords.data.length" class="rounded-2xl bg-[#F5F7FA] px-5 py-12 text-center text-sm text-[#637083]"> {{ $t("Belum ada pilihan makanan. Simpan tempat atau menu favorit agar mudah dipilih nanti.") }} </p>

            <section v-if="isCms" class="space-y-4 rounded-2xl border border-[#E9EDF3] bg-white p-4 sm:p-5">
                <header class="flex flex-wrap items-center justify-between gap-3 px-1">
                    <h2 class="font-semibold text-[#17243A]">{{ mealRecords.total ?? mealRecords.data.length }} makanan</h2>
                    <label class="flex items-center gap-2 text-sm text-[#637083]">
                        Urutkan:
                        <select v-model="sortOrder" class="h-9 rounded-lg border border-transparent bg-white py-1 pl-1 pr-7 font-medium text-[#17243A] focus:border-[#C9D8FF] focus:ring-0">
                            <option value="name">Nama A–Z</option>
                            <option value="price-low">Harga terendah</option>
                            <option value="price-high">Harga tertinggi</option>
                        </select>
                    </label>
                </header>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article v-for="meal in sortedMealRecords" :key="meal.id" class="relative isolate overflow-hidden rounded-2xl border border-[#E3EAF5] bg-white shadow-[0_8px_24px_rgba(32,73,140,0.06)]">
                        <div
                            class="absolute inset-0 z-0 bg-cover bg-center bg-no-repeat"
                            :style="{ backgroundImage: `url('/images/${meal.has_photo ? 'bg-blue.png' : 'bg-no-food.png'}')` }"
                        />
                        <img
                            v-if="meal.has_photo"
                            :src="cmsMealPhoto.url(meal.id, { query: { index: 0, v: meal.updated_at } })"
                            :alt="`Foto ${meal.name}`"
                            class="absolute inset-x-0 top-0 z-[1] h-[62%] w-full object-cover"
                        />
                        <div
                            v-if="meal.has_photo"
                            class="absolute inset-x-0 top-0 z-[2] h-[70%] bg-gradient-to-b from-transparent via-white/20 to-white"
                        />

                        <input
                            :ref="(element) => setPhotoInput(meal.id, element)"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            :disabled="uploadingPhotoFor === meal.id || meal.photo_count >= 5"
                            @change="uploadPhoto($event, meal)"
                        />
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="absolute right-4 top-4 z-20 !inline-flex !h-9 !min-h-9 !items-center !justify-center !gap-2 !rounded-xl !border-white/80 !bg-white/90 !px-3 !py-0 !text-sm !font-semibold !text-[#2457DA] shadow-sm hover:!bg-white"
                            :disabled="uploadingPhotoFor === meal.id || meal.photo_count >= 5"
                            @click="openPhotoPicker(meal.id)"
                        >
                            <Camera :size="16" />
                            {{ uploadingPhotoFor === meal.id ? 'Mengunggah…' : meal.photo_count >= 5 ? 'Foto lengkap' : 'Tambah foto' }}
                        </ButtonPrimary>
                        <span class="absolute left-4 top-4 z-20 rounded-lg bg-white/90 px-2 py-1 text-xs font-semibold text-[#536783]">
                            {{ meal.photo_count ?? 0 }}/5 foto
                        </span>

                        <div class="relative z-10 flex min-h-[390px] flex-col justify-end gap-4 p-4 pt-16 sm:min-h-[410px] sm:p-5 sm:pt-16">
                            <div class="flex items-end justify-between gap-3">
                                <div class="min-w-0">
                                    <Link :href="cmsMealShow.url(meal.id)" class="block truncate text-xl font-bold tracking-tight text-[#111E42] hover:text-[#2457DA] sm:text-2xl">{{ meal.name }}</Link>
                                    <span class="mt-2 inline-flex rounded-lg bg-[#EDF3FC] px-2.5 py-1 text-xs font-medium text-[#49638F]">
                                        {{ meal.type === 'buy' ? 'Beli jadi' : 'Masak sendiri' }}
                                    </span>
                                    <p v-if="meal.place_name" class="mt-2 truncate text-xs text-[#637083]">{{ meal.place_name }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <ButtonPrimary type="button" variant="secondary" class="!inline-flex !h-9 !min-h-9 !w-9 !items-center !justify-center !p-0" aria-label="Edit makanan" @click="editMeal(meal)">
                                        <Pencil :size="15" />
                                    </ButtonPrimary>
                                    <ButtonPrimary type="button" variant="secondary" class="!inline-flex !h-9 !min-h-9 !w-9 !items-center !justify-center !p-0 !text-[#B34735]" aria-label="Hapus makanan" @click="remove(meal)">
                                        <Trash2 :size="15" />
                                    </ButtonPrimary>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                                <p class="text-lg font-bold tracking-tight text-[#111E42]">{{ formatPrice(meal) }}</p>
                                <span v-if="meal.rating" class="flex items-center gap-1.5 text-sm font-medium text-[#17243A]">
                                    <Star :size="15" class="text-amber-500" :fill="'currentColor'" /> {{ meal.rating }}/5
                                </span>
                                <span v-if="meal.type === 'buy'" class="rounded-full px-3 py-1 text-xs font-medium" :class="meal.opening_status === 'open' ? 'bg-emerald-50 text-emerald-700' : meal.opening_status === 'closed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700'">{{ statusLabel(meal.opening_status) }}</span>
                            </div>
                            <p v-if="meal.address" class="flex items-start gap-2 text-xs leading-5 text-[#637083]"><MapPin :size="14" class="mt-0.5 shrink-0" />{{ meal.address }}</p>
                            <p v-if="photoUploadErrors[meal.id]" class="text-sm font-medium text-red-700" role="alert">{{ photoUploadErrors[meal.id] }}</p>

                            <div class="border-t border-[#DDE7F7] pt-4">
                                <ButtonPrimary type="button" variant="primary" class="!inline-flex !h-11 !min-h-11 !w-full !items-center !justify-center !gap-2 !px-4 !py-0 !text-sm" @click="recordExpense(meal)">
                                    <Plus :size="17" /> {{ $t('Catat pengeluaran') }}
                                </ButtonPrimary>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <div v-else class="space-y-3">
            <article v-for="meal in mealRecords.data" :key="meal.id" class="space-y-3 rounded-xl bg-[#F5F7FA] p-4 transition hover:bg-[#EEF3FF]">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#EEF3FF] text-[#2457DA]"><Utensils :size="19" /></span>
                    <div class="min-w-0 flex-1">
                        <Link :href="(isCms ? cmsMealShow : mealShow).url(meal.id)" class="font-semibold hover:text-[#2457DA]">{{ meal.name }}</Link>
                        <p v-if="meal.place_name" class="mt-0.5 truncate text-sm text-[#637083]">{{ meal.place_name }}</p>
                    </div>
                    <ButtonPrimary type="button" variant="secondary" class="!min-h-0 !p-2" aria-label="Edit" @click="editMeal(meal)">
                        <Pencil :size="16" />
                    </ButtonPrimary>
                    <ButtonPrimary type="button" variant="secondary" class="!min-h-0 !border-red-200 !p-2 !text-[#B34735]" aria-label="Hapus" @click="remove(meal)">
                        <Trash2 :size="16" />
                    </ButtonPrimary>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="rounded-full bg-white px-3 py-1 capitalize">{{ meal.type === 'buy' ? 'Beli jadi' : 'Masak sendiri' }}</span>
                    <span class="font-semibold text-[#152338]">{{ formatPrice(meal) }}</span>
                    <span v-if="meal.rating" class="flex items-center gap-1 font-medium text-amber-600">
                        <Star :size="14" :fill="'currentColor'" />
                        {{ meal.rating }}/5
                    </span>
                    <span v-if="meal.type === 'buy'" class="rounded-full px-3 py-1" :class="meal.opening_status === 'open' ? 'bg-emerald-50 text-emerald-700' : meal.opening_status === 'closed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700'">{{ statusLabel(meal.opening_status) }}</span>
                </div>
                <p v-if="meal.address" class="flex items-start gap-2 text-xs leading-5 text-[#637083]"><MapPin :size="15" class="mt-0.5 shrink-0" />{{ meal.address }}</p>
                <p v-if="meal.impression" class="line-clamp-2 text-sm leading-5 text-[#637083]">{{ meal.impression }}</p>
                <ButtonPrimary type="button" class="w-full" @click="recordExpense(meal)">{{ $t("Catat pengeluaran") }}</ButtonPrimary>
            </article>
            </div>

            <nav v-if="mealRecords.links?.length > 3" class="flex justify-center gap-2 pt-2">
                <Link v-for="link in mealRecords.links" :key="link.label" :href="link.url ?? (isCms ? cmsMealsIndex : mealsIndex).url()" class="rounded-lg px-3 py-2 text-sm" :class="link.active ? 'bg-[#2457DA] text-white' : 'bg-[#F5F7FA] text-[#314158]'" v-html="link.label" />
            </nav>
        </main>

        <section v-else class="space-y-6 pb-8 pt-2" :class="isCms ? '' : 'px-6'">
            <header v-if="isCms" class="flex items-start gap-4">
                <button
                    type="button"
                    class="mt-1 rounded-full p-2 text-[#2457DA] hover:bg-white"
                    aria-label="Kembali ke daftar makanan"
                    @click="closeForm"
                >
                    <ArrowLeft :size="22" />
                </button>
                <div>
                    <p class="text-xs font-medium text-[#7183A5]">Catatan Makanan</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-[#111E42] sm:text-3xl">
                        {{ editingMeal ? 'Edit pilihan makanan' : 'Tambah pilihan makanan' }}
                    </h1>
                    <p class="mt-1 text-sm text-[#637AA3]">
                        Lengkapi informasi makanan favorit kamu. Data ini akan membantu mencatat pengeluaran dengan lebih mudah.
                    </p>
                </div>
            </header>

            <header v-else class="flex items-center gap-4">
                <button
                    type="button"
                    class="rounded-full p-2 text-[#2457DA] hover:bg-[#F5F7FA]"
                    :aria-label="currentStep > 1 ? 'Kembali ke langkah sebelumnya' : 'Kembali ke Meal Record'"
                    @click="currentStep > 1 ? currentStep -= 1 : closeForm()"
                >
                    <ArrowLeft :size="22" />
                </button>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-[#637083]">{{ $t("Meal Record") }}</p>
                    <h1 class="text-xl font-semibold">
                        {{ currentStep === 1 ? (editingMeal ? 'Edit pilihan' : 'Pilihan baru') : currentStep === 2 ? 'Info tempat' : 'Atur jadwal buka' }}
                    </h1>
                </div>
                <span class="shrink-0 text-sm font-medium text-[#637083]"> {{ $t("Step") }} {{ currentStep }} {{ $t("dari") }} {{ totalSteps }}
                </span>
            </header>

            <div v-if="isCms" class="grid grid-cols-[auto_1fr_auto_1fr_auto] items-center gap-3 px-2 sm:gap-5" aria-label="Progres formulir">
                <div
                    v-for="(step, index) in [
                        { title: 'Detail makanan', number: 1 },
                        { title: 'Informasi tempat', number: 2 },
                        { title: 'Jadwal buka', number: 3 },
                    ]"
                    :key="step.number"
                    class="contents"
                >
                    <div class="flex items-center gap-2 text-xs font-semibold sm:gap-3 sm:text-sm" :class="currentStep >= step.number ? 'text-[#075BFF]' : 'text-[#7C8DAE]'">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full" :class="currentStep >= step.number ? 'bg-[#075BFF] text-white' : 'bg-[#E1E8F3] text-[#617394]'">
                            <Check v-if="currentStep > step.number" :size="18" />
                            <span v-else>{{ step.number }}</span>
                        </span>
                        <span class="hidden whitespace-nowrap sm:inline">{{ step.title }}</span>
                    </div>
                    <div v-if="index < 2" class="h-[3px] rounded-full" :class="currentStep > step.number ? 'bg-[#075BFF]' : 'bg-[#C9D7EE]'" />
                </div>
            </div>

            <div v-else class="mt-6 flex gap-2" aria-hidden="true">
                <div
                    v-for="step in totalSteps"
                    :key="step"
                    class="h-1.5 flex-1 rounded-full"
                    :class="currentStep >= step ? 'bg-[#2457DA]' : 'bg-slate-200'"
                />
            </div>

            <div :class="isCms ? 'grid items-stretch gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(350px,0.9fr)]' : ''">
                <form
                    ref="formElement"
                    :class="isCms ? 'space-y-5 rounded-2xl border border-[#E5EBF4] bg-white p-5 shadow-[0_12px_34px_rgba(32,73,140,0.05)] sm:p-7' : 'mt-6 space-y-5'"
                    novalidate
                    @submit.prevent="save"
                >
                        <header v-if="isCms" class="mb-5">
                            <h2 class="text-xl font-bold text-[#111E42]">
                                {{ currentStep === 1 ? 'Detail makanan' : currentStep === 2 ? 'Informasi tempat' : 'Jadwal buka' }}
                            </h2>
                            <p class="mt-1 text-sm text-[#7183A5]">
                                {{ currentStep === 1 ? 'Masukkan informasi dasar makanan yang ingin kamu catat.' : currentStep === 2 ? 'Tambahkan lokasi dan preferensi untuk pilihan beli jadi.' : 'Atur waktu operasional tempat. Bagian ini opsional.' }}
                            </p>
                        </header>

                        <div v-if="currentStep === 1" class="space-y-4">
                            <label class="block" :class="isCms ? 'text-sm font-semibold text-[#17243A]' : 'text-sm font-medium'"> {{ $t("Nama makanan") }} <input v-model="form.name" required maxlength="255" placeholder="Contoh: Nasi ayam geprek" :class="isCms ? 'mt-2 h-11 w-full rounded-lg border border-[#D7E1F1] px-4 font-normal focus:border-[#075BFF] focus:ring-[#075BFF]' : 'mt-1 h-11 w-full rounded-lg border border-slate-300'" />
                                <span v-if="errors.name" class="mt-1 block text-xs text-red-600">{{ errors.name }}</span>
                            </label>
                            <div v-if="isCms" class="block text-sm font-semibold text-[#17243A]">
                                Foto makanan <span class="font-normal text-[#7183A5]">(opsional)</span>
                                <div class="mt-2 grid gap-5 rounded-xl border border-[#D7E1F1] bg-white p-4 xl:grid-cols-2">
                                    <div
                                        class="flex min-h-[300px] flex-col items-center justify-center rounded-xl border-2 border-dashed p-5 text-center transition"
                                        :class="isPhotoDropActive ? 'border-[#075BFF] bg-[#EEF5FF]' : 'border-[#82ACFF] bg-[#F8FBFF]'"
                                        @dragenter.prevent="isPhotoDropActive = true"
                                        @dragover.prevent="isPhotoDropActive = true"
                                        @dragleave.prevent="isPhotoDropActive = false"
                                        @drop.prevent="dropMealPhotos"
                                    >
                                        <input
                                            ref="photoInputElement"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            multiple
                                            :disabled="photoItems.length >= 5"
                                            class="sr-only"
                                            @change="selectMealPhotos"
                                        />
                                        <span class="flex h-20 w-20 items-center justify-center rounded-full bg-[#E7F0FF] text-[#2457DA]">
                                            <Upload :size="34" />
                                        </span>
                                        <p class="mt-5 text-xl font-bold text-[#17243A]">Seret foto ke sini</p>
                                        <p class="mt-1 text-sm font-normal text-[#637083]">atau pilih dari perangkat</p>
                                        <ButtonPrimary
                                            type="button"
                                            class="mt-5 !min-h-11 !px-8"
                                            :disabled="photoItems.length >= 5"
                                            @click="openMealPhotoPicker"
                                        >
                                            Pilih foto
                                        </ButtonPrimary>
                                        <p class="mt-4 text-xs font-normal text-[#637083]">Maksimal 5 foto · JPG, PNG, WebP · 5 MB per foto</p>
                                    </div>

                                    <div class="min-w-0">
                                        <header class="mb-3 flex items-center justify-between gap-3">
                                            <h3 class="text-lg font-bold text-[#17243A]">Foto terunggah</h3>
                                            <span class="rounded-full bg-[#E7F0FF] px-3 py-1.5 text-sm font-bold text-[#2457DA]">
                                                {{ photoItems.length }}/5
                                            </span>
                                        </header>

                                        <div class="relative h-52 overflow-hidden rounded-xl bg-[#F1F5FB]">
                                            <img
                                                v-if="activePhoto"
                                                :src="activePhoto.url"
                                                alt="Foto utama makanan"
                                                class="h-full w-full object-cover"
                                            />
                                            <div v-else class="flex h-full flex-col items-center justify-center gap-2 text-[#9BAAC1]">
                                                <ImagePlus :size="36" />
                                                <span class="text-sm font-medium">Foto akan muncul di sini</span>
                                            </div>
                                            <span
                                                v-if="activePhoto"
                                                class="absolute left-3 top-3 rounded-full bg-[#075BFF] px-3 py-1.5 text-xs font-bold text-white"
                                            >
                                                Foto utama
                                            </span>
                                            <ButtonPrimary
                                                v-if="activePhoto"
                                                type="button"
                                                variant="secondary"
                                                class="absolute right-3 top-3 !inline-flex !h-10 !min-h-10 !w-10 !items-center !justify-center !rounded-full !border-0 !bg-white !p-0 !text-[#637083] shadow-sm"
                                                aria-label="Hapus foto utama"
                                                @click="removePhoto(activePhoto)"
                                            >
                                                <Trash2 :size="17" />
                                            </ButtonPrimary>
                                        </div>

                                        <div class="mt-3 flex flex-wrap items-center gap-2">
                                            <ButtonPrimary
                                                v-for="photo in photoItems"
                                                :key="photo.key"
                                                type="button"
                                                variant="secondary"
                                                class="!h-[72px] !min-h-[72px] !w-[72px] !shrink-0 !overflow-hidden !rounded-xl !border-2 !p-0"
                                                :class="activePhoto?.key === photo.key ? '!border-[#075BFF] !p-1' : '!border-transparent'"
                                                :aria-label="activePhoto?.key === photo.key ? 'Foto utama' : 'Jadikan foto utama'"
                                                @click="setMainPhoto(photo)"
                                            >
                                                <img :src="photo.url" alt="" class="h-full w-full rounded-lg object-cover" />
                                            </ButtonPrimary>
                                            <ButtonPrimary
                                                v-if="photoItems.length < 5"
                                                type="button"
                                                variant="secondary"
                                                class="!inline-flex !h-[72px] !min-h-[72px] !w-[72px] !shrink-0 !items-center !justify-center !rounded-xl !border-2 !border-dashed !border-[#82ACFF] !bg-white !p-0 !text-[#2457DA]"
                                                aria-label="Tambah foto"
                                                @click="openMealPhotoPicker"
                                            >
                                                <Plus :size="24" />
                                            </ButtonPrimary>
                                        </div>
                                        <p v-if="photoItems.length" class="mt-3 text-xs font-normal text-[#7183A5]">
                                            Klik foto untuk menjadikannya foto utama.
                                        </p>
                                    </div>
                                </div>
                                <span v-if="photoSelectionError" class="mt-1 block text-xs text-red-600">{{ photoSelectionError }}</span>
                                <span v-if="photoUploadValidationError" class="mt-1 block text-xs text-red-600">{{ photoUploadValidationError }}</span>
                            </div>
                            <fieldset v-if="isCms" class="space-y-2">
                                <legend class="text-sm font-semibold text-[#17243A]">Tipe makanan</legend>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        v-for="type in [{ value: 'buy', label: 'Beli jadi' }, { value: 'homemade', label: 'Masak sendiri' }]"
                                        :key="type.value"
                                        type="button"
                                        class="flex h-11 items-center justify-center gap-2 rounded-xl border px-3 text-sm font-medium transition"
                                        :class="form.type === type.value ? 'border-[#075BFF] bg-[#F1F6FF] text-[#075BFF]' : 'border-[#D7E1F1] bg-white text-[#536783]'"
                                        @click="form.type = type.value"
                                    >
                                        <ShoppingCart v-if="type.value === 'buy'" :size="17" />
                                        <Utensils v-else :size="17" />
                                        {{ type.label }}
                                    </button>
                                </div>
                            </fieldset>
                            <label v-else class="block text-sm font-medium"> {{ $t("Type") }} <select v-model="form.type" class="mt-1 h-11 w-full rounded-lg border border-slate-300">
                                    <option value="buy">{{ $t("Beli jadi") }}</option>
                                    <option value="homemade">{{ $t("Masak sendiri") }}</option>
                                </select>
                            </label>
                            <section v-if="isCms" class="space-y-3">
                                <h2 class="text-sm font-medium">{{ $t('Daftar harga') }}</h2>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div
                                        v-for="(price, index) in form.prices"
                                        :key="index"
                                        class="flex items-end gap-2"
                                    >
                                        <label class="block min-w-0 flex-1 text-sm font-medium">
                                            {{ $t('Harga') }} {{ index + 1 }}
                                            <AmountInput
                                                v-model="form.prices[index]"
                                                required
                                                min="0"
                                                placeholder="Rp 0"
                                                class="mt-1 h-11 w-full rounded-lg border border-[#D7E1F1]"
                                            />
                                            <span
                                                v-if="errors[`prices.${index}`]"
                                                class="text-xs text-red-600"
                                            >
                                                {{ errors[`prices.${index}`] }}
                                            </span>
                                        </label>
                                        <ButtonPrimary
                                            type="button"
                                            variant="secondary"
                                            class="!min-h-11 !px-3"
                                            :disabled="form.prices.length === 1"
                                            :aria-label="`${$t('Hapus harga')} ${index + 1}`"
                                            @click="removePrice(index)"
                                        >
                                            <Trash2 :size="16" />
                                        </ButtonPrimary>
                                    </div>
                                </div>
                                <p v-if="errors.prices" class="text-xs text-red-600">
                                    {{ errors.prices }}
                                </p>
                                <ButtonPrimary
                                    type="button"
                                    variant="secondary"
                                    class="!inline-flex !items-center !gap-2"
                                    @click="addPrice"
                                >
                                    <Plus :size="16" /> {{ $t('Tambah harga') }}
                                </ButtonPrimary>
                            </section>
                            <div v-else class="grid grid-cols-2 gap-3">
                                <label class="block text-sm font-medium"> {{ $t("Harga minimum") }} <AmountInput v-model="form.price_min" required class="mt-1 h-11 w-full rounded-lg border border-slate-300" />
                                    <span v-if="errors.price_min" class="text-xs text-red-600">{{ errors.price_min }}</span>
                                </label>
                                <label class="block text-sm font-medium"> {{ $t("Harga maksimum") }} <AmountInput v-model="form.price_max" required class="mt-1 h-11 w-full rounded-lg border border-slate-300" />
                                    <span v-if="errors.price_max" class="text-xs text-red-600">{{ errors.price_max }}</span>
                                </label>
                            </div>
                            <fieldset class="space-y-2">
                                <legend class="text-sm font-semibold text-[#17243A]">{{ $t("Rating (opsional)") }}</legend>
                                <div class="grid w-full grid-cols-5 gap-1" role="radiogroup" aria-label="Rating bintang">
                                    <button
                                        v-for="star in 5"
                                        :key="star"
                                        type="button"
                                        class="flex w-full items-center justify-center rounded-md py-2 text-amber-500 hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-400"
                                        :aria-label="`${star} dari 5 bintang`"
                                        :aria-pressed="Number(form.rating ?? 0) === star"
                                        @click="form.rating = star"
                                    >
                                        <Star
                                            :size="26"
                                            :fill="Number(form.rating ?? 0) >= star ? 'currentColor' : 'none'"
                                        />
                                    </button>
                                </div>
                                <p class="text-xs text-[#637083]">
                                    {{ form.rating ? `${form.rating} dari 5` : 'Belum diberi rating' }}
                                </p>
                            </fieldset>
                        </div>

                        <div v-else-if="currentStep === 2 && form.type === 'buy'" class="space-y-5">
                            <div>
                                <h2 class="text-lg font-semibold">{{ $t("Info tempat") }}</h2>
                                <p class="mt-1 text-sm leading-5 text-[#637083]"> {{ $t("Tambahkan lokasi dan preferensi untuk pilihan beli jadi.") }} </p>
                            </div>
                            <label class="block text-sm font-semibold text-[#17243A]"> {{ $t("Nama tempat (opsional)") }} <input v-model="form.place_name" maxlength="255" placeholder="Nama tempat makan" class="mt-2 h-11 w-full rounded-lg border border-[#D7E1F1] px-4 font-normal" />
                            </label>
                            <label class="block text-sm font-semibold text-[#17243A]"> {{ $t("Alamat (opsional)") }} <textarea v-model="form.address" rows="3" placeholder="Alamat tempat makan" class="mt-2 w-full rounded-lg border border-[#D7E1F1] px-4 py-3 font-normal" />
                            </label>
                            <label class="block text-sm font-semibold text-[#17243A]"> {{ $t("Impresi atau preferensi (opsional)") }} <textarea v-model="form.impression" rows="3" class="mt-2 w-full rounded-lg border border-[#D7E1F1] px-4 py-3 font-normal" placeholder="Contoh: pedas, porsi besar, cocok untuk makan siang" />
                            </label>
                        </div>

                        <div v-else class="space-y-4">
                            <section v-if="form.type === 'buy'" class="space-y-3 rounded-xl p-1" :class="isCms ? 'sm:space-y-4' : 'bg-[#F7F8FA] p-3'">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-semibold">{{ $t("Jadwal buka (opsional)") }}</h3>
                                </div>
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg p-3 text-sm font-semibold" :class="isCms ? 'text-[#17243A]' : 'bg-white'">
                                    <input
                                        type="checkbox"
                                        :checked="hasOpeningHours"
                                        class="rounded border-slate-300 text-[#2457DA] focus:ring-[#2457DA]"
                                        @change="toggleOpeningHours($event.target.checked)"
                                    /> {{ $t("Cantumkan jam buka") }} </label>
                                <template v-if="hasOpeningHours">
                                <fieldset class="grid grid-cols-2 gap-2 overflow-hidden rounded-xl border border-[#D7E1F1]">
                                    <legend class="sr-only">{{ $t("Pola jadwal buka") }}</legend>
                                    <label class="flex cursor-pointer items-center justify-center gap-2 p-3 text-sm" :class="scheduleMode === 'everyday' ? 'bg-[#075BFF] text-white' : 'bg-white text-[#637083]'">
                                        <input type="radio" name="schedule_mode" value="everyday" :checked="scheduleMode === 'everyday'" class="sr-only" @change="changeScheduleMode('everyday')" /> {{ isCms ? 'Jam sama setiap hari' : $t("Setiap hari") }} </label>
                                    <label class="flex cursor-pointer items-center justify-center gap-2 p-3 text-sm" :class="scheduleMode === 'custom' ? 'bg-[#075BFF] text-white' : 'bg-white text-[#637083]'">
                                        <input type="radio" name="schedule_mode" value="custom" :checked="scheduleMode === 'custom'" class="sr-only" @change="changeScheduleMode('custom')" /> {{ $t("Atur per hari") }} </label>
                                </fieldset>
                                <p class="text-xs leading-5 text-[#637083]">{{ $t("Jika jam tutup lebih awal dari jam buka, otomatis dianggap tutup keesokan hari.") }}</p>

                                <template v-if="scheduleMode === 'everyday'">
                                    <p class="text-xs leading-5 text-[#637083]">Rentang jam yang ditambahkan berlaku Senin sampai Minggu. Tambahkan beberapa rentang jika ada jeda, misalnya pagi dan malam.</p>
                                    <p v-if="!dailySessions.length" class="text-xs text-[#637083]">{{ $t("Jam buka belum diketahui sampai rentang waktu ditambahkan.") }}</p>
                                    <div v-for="(session, index) in dailySessions" :key="index" class="rounded-xl border border-[#D7E1F1] bg-white p-3">
                                        <div class="grid grid-cols-[1fr_auto_1fr_auto] items-center gap-2">
                                            <input v-model="session.opens_at" required type="time" class="h-10 min-w-0 rounded-lg border border-slate-300 px-2 text-sm" />
                                            <span class="text-xs">{{ $t("s/d") }}</span>
                                            <input v-model="session.closes_at" required type="time" class="h-10 min-w-0 rounded-lg border border-slate-300 px-2 text-sm" />
                                            <ButtonPrimary type="button" variant="secondary" class="!min-h-0 !border-red-200 !px-2 !py-1 !text-xs !text-rose-600" @click="removeDailySession(index)">{{ $t("Delete") }}</ButtonPrimary>
                                        </div>
                                    </div>
                                    <div v-if="isCms" class="flex flex-wrap gap-2 pt-1">
                                        <span v-for="day in weekdays" :key="day" class="inline-flex items-center gap-2 rounded-lg bg-[#EEF5FF] px-3 py-2 text-xs font-medium text-[#075BFF]">
                                            <Check :size="14" /> {{ day.slice(0, 3) }}
                                        </span>
                                    </div>
                                    <ButtonPrimary type="button" variant="secondary" class="!min-h-0 !w-full !border-[#075BFF] !px-3 !py-2 !text-sm !text-[#075BFF]" @click="addDailySession"><Plus :size="16" /> Tambah rentang waktu</ButtonPrimary>
                                    <p v-if="isCms" class="flex items-start gap-2 rounded-xl bg-[#EAF3FF] p-3 text-xs leading-5 text-[#45648F]">
                                        <Info :size="16" class="mt-0.5 shrink-0 text-[#075BFF]" />
                                        Jika waktu tutup lebih awal dari waktu buka, jadwal dilanjutkan keesokan hari.
                                    </p>
                                </template>

                                <template v-else>
                                    <p v-if="!form.opening_hours.length" class="text-xs text-[#637083]">{{ $t("Jam buka belum diketahui sampai jadwal ditambahkan.") }}</p>
                                    <div v-for="day in weekdays" :key="day" class="space-y-2 rounded-lg bg-white p-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium">{{ day }}</span>
                                            <ButtonPrimary type="button" variant="secondary" class="!min-h-0 !px-3 !py-1 !text-xs" @click="addSession(weekdays.indexOf(day) + 1)">{{ $t("+ sesi") }}</ButtonPrimary>
                                        </div>
                                        <div v-for="session in form.opening_hours.map((item, itemIndex) => ({ item, itemIndex })).filter(({ item }) => item.day_of_week === weekdays.indexOf(day) + 1)" :key="session.itemIndex" class="grid grid-cols-[1fr_auto_1fr_auto] items-center gap-2">
                                            <input v-model="form.opening_hours[session.itemIndex].opens_at" required type="time" class="h-10 min-w-0 rounded-lg border border-slate-300 px-2 text-sm" />
                                            <span class="text-xs">{{ $t("s/d") }}</span>
                                            <input v-model="form.opening_hours[session.itemIndex].closes_at" required type="time" class="h-10 min-w-0 rounded-lg border border-slate-300 px-2 text-sm" />
                                            <ButtonPrimary type="button" variant="secondary" class="!min-h-0 !border-red-200 !px-2 !py-1 !text-xs !text-rose-600" @click="removeSession(session.itemIndex)">{{ $t("Delete") }}</ButtonPrimary>
                                        </div>
                                    </div>
                                </template>
                                <p v-if="Object.keys(errors).some((key) => key.startsWith('opening_hours.'))" class="text-xs text-red-600"> {{ $t("Rentang jam buka tidak valid atau bertumpang tindih. Periksa kembali jam buka dan tutup.") }} </p>
                                </template>
                                <p v-else class="text-xs leading-5 text-[#637083]"> {{ $t("Centang untuk menambahkan rentang jam buka.") }} </p>
                            </section>
                            <p v-else class="rounded-xl bg-[#F7F8FA] p-4 text-sm leading-6 text-[#637083]">{{ $t("Pilihan masak sendiri tidak memerlukan jadwal buka.") }}</p>
                        </div>

                        <p v-if="Object.keys(errors).length" class="text-sm text-red-600">{{ $t("Periksa kembali kolom yang ditandai.") }}</p>
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <ButtonPrimary
                                v-if="currentStep > 1"
                                type="button"
                                variant="secondary"
                                class="w-full"
                                @click="currentStep -= 1"
                            > {{ isCms ? 'Kembali' : $t("Back") }} </ButtonPrimary>
                            <ButtonPrimary
                                v-if="!isFinalStep"
                                type="button"
                                :class="currentStep === 1 ? 'col-span-2 w-full' : 'w-full'"
                                @click="continueToNextStep"
                            >
                                {{ currentStep === 1 ? 'Lanjut ke informasi tempat' : 'Lanjut ke jadwal buka' }}
                                <ArrowRight v-if="isCms" :size="17" />
                            </ButtonPrimary>
                            <ButtonPrimary
                                v-else
                                type="submit"
                                :disabled="form.processing"
                                :class="currentStep === 1 ? 'col-span-2 w-full' : 'w-full'"
                            >
                                {{ form.processing ? 'Menyimpan…' : isCms ? 'Simpan pilihan makanan' : 'Simpan pilihan' }}
                            </ButtonPrimary>
                        </div>
                </form>
                <aside v-if="isCms" class="h-fit rounded-2xl border border-[#E5EBF4] bg-white p-5 shadow-[0_12px_34px_rgba(32,73,140,0.05)] sm:p-7 xl:sticky xl:top-24">
                    <header class="flex items-start gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#EEF5FF] text-[#075BFF]"><Eye :size="19" /></span>
                        <div>
                            <h2 class="font-bold text-[#111E42]">Pratinjau pilihan</h2>
                            <p class="mt-1 text-xs leading-5 text-[#7183A5]">Ini adalah tampilan sementara dari makanan yang kamu buat.</p>
                        </div>
                    </header>
                    <div class="relative mt-5 flex h-48 items-center justify-center overflow-hidden rounded-2xl bg-[#E8F1FF] sm:h-56">
                        <img
                            v-if="previewPhotoUrl"
                            :src="previewPhotoUrl"
                            :alt="form.name || 'Foto makanan'"
                            class="absolute inset-0 h-full w-full object-cover"
                        />
                        <img
                            v-else
                            :src="'/images/bg-blue.png'"
                            alt=""
                            class="absolute inset-0 h-full w-full object-cover"
                        />
                        <ImagePlus v-if="!previewPhotoUrl" :size="42" class="relative text-[#4B8CFF]" />
                    </div>
                    <h3 class="mt-4 truncate text-xl font-bold tracking-tight text-[#111E42]">
                        {{ form.name || 'Nama makanan' }}
                    </h3>
                    <span class="mt-2 inline-flex items-center gap-2 rounded-lg bg-[#EEF5FF] px-3 py-1.5 text-xs font-medium text-[#075BFF]">
                        <ShoppingCart v-if="form.type === 'buy'" :size="15" />
                        <Utensils v-else :size="15" />
                        {{ form.type === 'buy' ? 'Beli jadi' : 'Masak sendiri' }}
                    </span>
                    <dl class="mt-5 divide-y divide-[#E8EDF5] border-y border-[#E8EDF5]">
                        <div v-if="form.type === 'buy' && hasOpeningHours" class="flex items-center justify-between gap-3 py-3 text-sm">
                            <dt class="flex items-center gap-2 text-[#7183A5]"><Clock3 :size="17" /> Jadwal</dt>
                            <dd class="text-right font-semibold text-[#111E42]">{{ previewScheduleText }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 py-3 text-sm">
                            <dt class="flex items-center gap-2 text-[#7183A5]"><Banknote :size="17" /> Estimasi harga</dt>
                            <dd class="text-right font-semibold text-[#111E42]">{{ previewPriceText }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 py-3 text-sm">
                            <dt class="flex items-center gap-2 text-[#7183A5]"><Star :size="17" /> Rating</dt>
                            <dd class="flex items-center gap-1 font-semibold text-[#111E42]">
                                <Star :size="16" class="text-amber-500" :fill="form.rating ? 'currentColor' : 'none'" />
                                {{ form.rating ? `${form.rating}/5` : 'Belum dinilai' }}
                            </dd>
                        </div>
                        <div v-if="form.type === 'buy'" class="flex items-center justify-between gap-3 py-3 text-sm">
                            <dt class="flex items-center gap-2 text-[#7183A5]"><MapPin :size="17" /> Tempat</dt>
                            <dd class="truncate text-right font-medium text-[#7183A5]">{{ form.place_name || 'Belum ditambahkan' }}</dd>
                        </div>
                    </dl>
                </aside>
            </div>
        </section>

        <MealExpenseModal
            v-if="isCms && selectedMealForExpense"
            :meal-record="selectedMealForExpense"
            :currency="mealCurrency"
            :expense-categories="expenseCategories"
            :accounts="accounts"
            @close="selectedMealForExpense = null"
            @saved="selectedMealForExpense = null"
        />
        <MobileBottomNav v-if="!isCms" />
    </div>
</template>
