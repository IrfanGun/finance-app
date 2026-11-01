<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Camera,
    MapPin,
    Pencil,
    Plus,
    Search,
    SlidersHorizontal,
    Star,
    Trash2,
    Utensils,
} from 'lucide-vue-next';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
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
    isCms: { type: Boolean, default: false },
});

const formElement = ref(null);
const photoInputRefs = ref({});
const priceFiltersVisible = ref(false);
const sortOrder = ref('name');
const uploadingPhotoFor = ref(null);
const photoUploadErrors = ref({});
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
    removeSession,
    save,
    scheduleMode,
    toggleOpeningHours,
    totalSteps,
} = useMealRecordForm(formElement, props.isCms);

const recordExpense = (meal) => {
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
const formatPrice = formatMealPrice;
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
                @click="openCreate"
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

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article v-for="meal in sortedMealRecords" :key="meal.id" class="relative isolate overflow-hidden rounded-2xl border border-[#E3EAF5] bg-white shadow-[0_8px_24px_rgba(32,73,140,0.06)]">
                        <div
                            class="absolute inset-0 z-0 bg-cover bg-center bg-no-repeat"
                            :style="{ backgroundImage: `url('/images/${meal.has_photo ? 'bg-blue.png' : 'bg-no-food.png'}')` }"
                        />
                        <img
                            v-if="meal.has_photo"
                            :src="cmsMealPhoto.url(meal.id, { query: { v: meal.updated_at } })"
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
                            :disabled="uploadingPhotoFor === meal.id"
                            @change="uploadPhoto($event, meal)"
                        />
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="absolute right-4 top-4 z-20 !inline-flex !h-9 !min-h-9 !items-center !justify-center !gap-2 !rounded-xl !border-white/80 !bg-white/90 !px-3 !py-0 !text-sm !font-semibold !text-[#2457DA] shadow-sm hover:!bg-white"
                            :disabled="uploadingPhotoFor === meal.id"
                            @click="openPhotoPicker(meal.id)"
                        >
                            <Camera :size="16" />
                            {{ uploadingPhotoFor === meal.id ? 'Mengunggah…' : meal.has_photo ? 'Ganti foto' : 'Tambah foto' }}
                        </ButtonPrimary>

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
                                    <ButtonPrimary type="button" variant="secondary" class="!inline-flex !h-9 !min-h-9 !w-9 !items-center !justify-center !p-0" aria-label="Edit makanan" @click="openEdit(meal)">
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
                    <ButtonPrimary type="button" variant="secondary" class="!min-h-0 !p-2" aria-label="Edit" @click="openEdit(meal)">
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

        <section v-else class="px-6 pb-8 pt-3">
            <header class="flex items-center gap-4">
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

            <div class="mt-6 flex gap-2" aria-hidden="true">
                <div
                    v-for="step in totalSteps"
                    :key="step"
                    class="h-1.5 flex-1 rounded-full"
                    :class="currentStep >= step ? 'bg-[#2457DA]' : 'bg-slate-200'"
                />
            </div>

            <form
                ref="formElement"
                class="mt-6 space-y-5"
                novalidate
                @submit.prevent="save"
            >

                        <div v-if="currentStep === 1" class="space-y-4">
                            <label class="block text-sm font-medium"> {{ $t("Nama makanan") }} <input v-model="form.name" required maxlength="255" class="mt-1 h-11 w-full rounded-lg border border-slate-300" />
                                <span v-if="errors.name" class="text-xs text-red-600">{{ errors.name }}</span>
                            </label>
                            <label class="block text-sm font-medium"> {{ $t("Type") }} <select v-model="form.type" class="mt-1 h-11 w-full rounded-lg border border-slate-300">
                                    <option value="buy">{{ $t("Beli jadi") }}</option>
                                    <option value="homemade">{{ $t("Masak sendiri") }}</option>
                                </select>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="block text-sm font-medium"> {{ $t("Harga minimum") }} <AmountInput v-model="form.price_min" required class="mt-1 h-11 w-full rounded-lg border border-slate-300" />
                                    <span v-if="errors.price_min" class="text-xs text-red-600">{{ errors.price_min }}</span>
                                </label>
                                <label class="block text-sm font-medium"> {{ $t("Harga maksimum") }} <AmountInput v-model="form.price_max" required class="mt-1 h-11 w-full rounded-lg border border-slate-300" />
                                    <span v-if="errors.price_max" class="text-xs text-red-600">{{ errors.price_max }}</span>
                                </label>
                            </div>
                            <fieldset class="space-y-2">
                                <legend class="text-sm font-medium">{{ $t("Rating (opsional)") }}</legend>
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

                        <div v-else-if="currentStep === 2 && form.type === 'buy'" class="space-y-4">
                            <div>
                                <h2 class="text-lg font-semibold">{{ $t("Info tempat") }}</h2>
                                <p class="mt-1 text-sm leading-5 text-[#637083]"> {{ $t("Tambahkan lokasi dan preferensi untuk pilihan beli jadi.") }} </p>
                            </div>
                            <label class="block text-sm font-medium"> {{ $t("Nama tempat (opsional)") }} <input v-model="form.place_name" maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4" />
                            </label>
                            <label class="block text-sm font-medium"> {{ $t("Alamat (opsional)") }} <textarea v-model="form.address" rows="2" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3" />
                            </label>
                            <label class="block text-sm font-medium"> {{ $t("Impresi atau preferensi (opsional)") }} <textarea v-model="form.impression" rows="3" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3" placeholder="Contoh: pedas, porsi besar, cocok untuk makan siang" />
                            </label>
                        </div>

                        <div v-else class="space-y-4">
                            <section v-if="form.type === 'buy'" class="space-y-3 rounded-xl bg-[#F7F8FA] p-3">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-semibold">{{ $t("Jadwal buka (opsional)") }}</h3>
                                </div>
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg bg-white p-3 text-sm font-medium">
                                    <input
                                        type="checkbox"
                                        :checked="hasOpeningHours"
                                        class="rounded border-slate-300 text-[#2457DA] focus:ring-[#2457DA]"
                                        @change="toggleOpeningHours($event.target.checked)"
                                    /> {{ $t("Cantumkan jam buka") }} </label>
                                <template v-if="hasOpeningHours">
                                <fieldset class="grid grid-cols-2 gap-2">
                                    <legend class="sr-only">{{ $t("Pola jadwal buka") }}</legend>
                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border p-3 text-sm" :class="scheduleMode === 'everyday' ? 'border-[#2457DA] bg-[#EEF3FF] text-[#2457DA]' : 'border-slate-200 bg-white text-[#637083]'">
                                        <input type="radio" name="schedule_mode" value="everyday" :checked="scheduleMode === 'everyday'" class="border-slate-300 text-[#2457DA] focus:ring-[#2457DA]" @change="changeScheduleMode('everyday')" /> {{ $t("Setiap hari") }} </label>
                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border p-3 text-sm" :class="scheduleMode === 'custom' ? 'border-[#2457DA] bg-[#EEF3FF] text-[#2457DA]' : 'border-slate-200 bg-white text-[#637083]'">
                                        <input type="radio" name="schedule_mode" value="custom" :checked="scheduleMode === 'custom'" class="border-slate-300 text-[#2457DA] focus:ring-[#2457DA]" @change="changeScheduleMode('custom')" /> {{ $t("Atur per hari") }} </label>
                                </fieldset>
                                <p class="text-xs leading-5 text-[#637083]">{{ $t("Jika jam tutup lebih awal dari jam buka, otomatis dianggap tutup keesokan hari.") }}</p>

                                <template v-if="scheduleMode === 'everyday'">
                                    <p class="text-xs leading-5 text-[#637083]">Rentang jam yang ditambahkan berlaku Senin sampai Minggu. Tambahkan beberapa rentang jika ada jeda, misalnya pagi dan malam.</p>
                                    <p v-if="!dailySessions.length" class="text-xs text-[#637083]">{{ $t("Jam buka belum diketahui sampai rentang waktu ditambahkan.") }}</p>
                                    <div v-for="(session, index) in dailySessions" :key="index" class="rounded-lg bg-white p-3">
                                        <div class="grid grid-cols-[1fr_auto_1fr_auto] items-center gap-2">
                                            <input v-model="session.opens_at" required type="time" class="h-10 min-w-0 rounded-lg border border-slate-300 px-2 text-sm" />
                                            <span class="text-xs">{{ $t("s/d") }}</span>
                                            <input v-model="session.closes_at" required type="time" class="h-10 min-w-0 rounded-lg border border-slate-300 px-2 text-sm" />
                                            <ButtonPrimary type="button" variant="secondary" class="!min-h-0 !border-red-200 !px-2 !py-1 !text-xs !text-rose-600" @click="removeDailySession(index)">{{ $t("Delete") }}</ButtonPrimary>
                                        </div>
                                    </div>
                                    <ButtonPrimary type="button" variant="secondary" class="!min-h-0 !px-3 !py-1 !text-xs" @click="addDailySession">{{ $t("+ tambah rentang waktu") }}</ButtonPrimary>
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
                            > {{ $t("Back") }} </ButtonPrimary>
                            <ButtonPrimary
                                v-if="!isFinalStep"
                                type="button"
                                :class="currentStep === 1 ? 'col-span-2 w-full' : 'w-full'"
                                @click="continueToNextStep"
                            >
                                {{ currentStep === 1 ? 'Lanjut ke info tempat' : 'Lanjut ke jadwal buka' }}
                            </ButtonPrimary>
                            <ButtonPrimary
                                v-else
                                type="submit"
                                :disabled="form.processing"
                                :class="currentStep === 1 ? 'col-span-2 w-full' : 'w-full'"
                            >
                                {{ form.processing ? 'Menyimpan…' : 'Simpan pilihan' }}
                            </ButtonPrimary>
                        </div>
                </form>
        </section>

        <MobileBottomNav v-if="!isCms" />
    </div>
</template>
