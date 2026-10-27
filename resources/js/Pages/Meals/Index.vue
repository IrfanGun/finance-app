<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    MapPin,
    Pencil,
    Plus,
    Search,
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
    destroy as mealDestroy,
    index as mealsIndex,
    show as mealShow,
} from '@/routes/meals';

const props = defineProps({
    mealRecords: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const formElement = ref(null);
const {
    availabilityFilter,
    isLoading,
    priceMaxFilter,
    priceMinFilter,
    search,
    typeFilter,
} = useMealFilters(props.filters);
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
} = useMealRecordForm(formElement);

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

    router.delete(mealDestroy.url(meal.id), { preserveScroll: true });
};

const statusLabel = getMealStatusLabel;
const formatPrice = formatMealPrice;
</script>

<template>
    <Head :title="formVisible ? (editingMeal ? 'Edit pilihan' : 'Pilihan baru') : 'Meal Record'" />

    <div class="mx-auto min-h-screen w-full max-w-lg bg-white pb-28 text-app-heading">
        <header v-if="!formVisible" class="flex items-center justify-between px-6 py-5">
            <Link :href="dashboard.url()" aria-label="Kembali ke dashboard"><ArrowLeft :size="22" class="text-[#2457DA]" /></Link>
            <h1 class="text-lg font-semibold">{{ $t("Meal Record") }}</h1>
            <ButtonPrimary
                type="button"
                class="!min-h-0 !w-10 !rounded-full !p-2"
                aria-label="Tambah pilihan makanan"
                @click="openCreate"
            >
                <Plus :size="18" />
            </ButtonPrimary>
        </header>

        <main v-if="!formVisible" class="space-y-4 px-5 pt-5">
            <p v-if="isLoading" role="status" class="text-center text-xs text-[#637083]">Memuat pilihan makanan…</p>
            <section class="space-y-3 rounded-2xl bg-[#F5F7FA] p-4">
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

            <article v-for="meal in mealRecords.data" :key="meal.id" class="space-y-3 rounded-xl bg-[#F5F7FA] p-4 transition hover:bg-[#EEF3FF]">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#EEF3FF] text-[#2457DA]"><Utensils :size="19" /></span>
                    <div class="min-w-0 flex-1">
                        <Link :href="mealShow.url(meal.id)" class="font-semibold hover:text-[#2457DA]">{{ meal.name }}</Link>
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

            <nav v-if="mealRecords.links?.length > 3" class="flex justify-center gap-2 pt-2">
                <Link v-for="link in mealRecords.links" :key="link.label" :href="link.url ?? mealsIndex.url()" class="rounded-lg px-3 py-2 text-sm" :class="link.active ? 'bg-[#2457DA] text-white' : 'bg-[#F5F7FA] text-[#314158]'" v-html="link.label" />
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

        <MobileBottomNav />
    </div>
</template>
