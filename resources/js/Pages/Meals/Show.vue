<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, MapPin, Star } from 'lucide-vue-next';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import { dashboard } from '@/routes';
import { index as mealsIndex } from '@/routes/meals';

const props = defineProps({
    mealRecord: { type: Object, required: true },
    openingStatus: { type: String, default: 'unknown' },
});

const weekdays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
const money = (amount) => new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 2,
}).format(Number(amount ?? 0));
const statusLabel = {
    open: 'Sedang buka',
    closed: 'Sedang tutup',
    unknown: 'Jam buka belum diketahui',
};
const price = Number(props.mealRecord.price_min) === Number(props.mealRecord.price_max)
    ? `Rp ${money(props.mealRecord.price_min)}`
    : `Rp ${money(props.mealRecord.price_min)} – ${money(props.mealRecord.price_max)}`;

const recordExpense = () => {
    router.visit(dashboard.url({
        query: {
            meal_record_id: props.mealRecord.id,
        },
    }));
};
</script>

<template>
    <Head :title="mealRecord.name" />
    <div class="mx-auto min-h-screen w-full max-w-lg bg-[#F7F8FA] pb-28 text-[#152338]">
        <header class="flex items-center justify-between bg-white px-6 py-5">
            <Link :href="mealsIndex.url()" aria-label="Kembali ke Meal Record"><ArrowLeft :size="22" class="text-[#2457DA]" /></Link>
            <h1 class="text-lg font-semibold">Detail makanan</h1>
            <span class="w-6" />
        </header>
        <main class="space-y-4 px-5 pt-5">
            <section class="space-y-3 rounded-2xl bg-white p-5">
                <h2 class="text-2xl font-semibold">{{ mealRecord.name }}</h2>
                <p v-if="mealRecord.place_name" class="text-sm text-[#637083]">{{ mealRecord.place_name }}</p>
                <div v-if="mealRecord.rating" class="flex items-center gap-1 text-amber-500" :aria-label="`Rating ${mealRecord.rating} dari 5 bintang`">
                    <Star
                        v-for="star in 5"
                        :key="star"
                        :size="18"
                        :fill="mealRecord.rating >= star ? 'currentColor' : 'none'"
                    />
                    <span class="ml-1 text-sm font-medium text-[#637083]">{{ mealRecord.rating }}/5</span>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-slate-100 px-3 py-1">{{ mealRecord.type === 'buy' ? 'Beli jadi' : 'Masak sendiri' }}</span>
                    <span class="rounded-full bg-[#EEF3FF] px-3 py-1 font-semibold text-[#2457DA]">{{ price }}</span>
                    <span v-if="mealRecord.type === 'buy'" class="rounded-full px-3 py-1" :class="openingStatus === 'open' ? 'bg-emerald-50 text-emerald-700' : openingStatus === 'closed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700'">{{ statusLabel[openingStatus] }}</span>
                </div>
                <p v-if="mealRecord.address" class="flex items-start gap-2 text-sm leading-6 text-[#637083]"><MapPin :size="17" class="mt-1 shrink-0" />{{ mealRecord.address }}</p>
                <p v-if="mealRecord.impression" class="whitespace-pre-line text-sm leading-6 text-[#637083]">{{ mealRecord.impression }}</p>
            </section>

            <section v-if="mealRecord.type === 'buy'" class="space-y-3 rounded-2xl bg-white p-5">
                <h3 class="font-semibold">Jadwal buka</h3>
                <p v-if="!mealRecord.opening_hours.length" class="text-sm text-[#637083]">Jam buka belum diketahui.</p>
                <div v-for="day in weekdays" :key="day" class="border-b border-[#EEF1F5] pb-2 last:border-0">
                    <p class="text-sm font-medium">{{ day }}</p>
                    <div v-for="session in mealRecord.opening_hours.filter((item) => item.day_of_week === weekdays.indexOf(day) + 1)" :key="session.id" class="text-sm text-[#637083]">
                        {{ session.opens_at.slice(0, 5) }}–{{ session.closes_at.slice(0, 5) }}<span v-if="session.closes_next_day"> (hari berikutnya)</span>
                    </div>
                    <p v-if="!mealRecord.opening_hours.some((item) => item.day_of_week === weekdays.indexOf(day) + 1)" class="text-sm text-[#637083]">Tutup</p>
                </div>
            </section>

            <ButtonPrimary
                type="button"
                class="w-full"
                @click="recordExpense"
            >
                Catat pengeluaran
            </ButtonPrimary>
            <ButtonPrimary
                type="button"
                variant="secondary"
                class="w-full"
                @click="router.visit(dashboard.url())"
            >
                Kembali ke dashboard
            </ButtonPrimary>
        </main>
        <MobileBottomNav />
    </div>
</template>
