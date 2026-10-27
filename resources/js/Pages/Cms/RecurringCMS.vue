<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Bell, CalendarClock } from 'lucide-vue-next';

const props = defineProps({
    reminders: { type: Array, default: () => [] },
});

const typeFilter = ref('all');
const filters = [
    { label: 'All', value: 'all' },
    { label: 'Expense', value: 'expense' },
    { label: 'Income', value: 'income' },
];
const filteredReminders = computed(() => typeFilter.value === 'all'
    ? props.reminders
    : props.reminders.filter((reminder) => reminder.type === typeFilter.value));
const activeReminders = computed(() => props.reminders.filter((reminder) => reminder.status === 'active').length);
const pausedReminders = computed(() => props.reminders.filter((reminder) => reminder.status === 'paused').length);

const money = (value, currency = 'IDR') => value === null || value === undefined || value === ''
    ? 'Not set'
    : new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(value));

const frequencyLabel = (reminder) => {
    const labels = {
        daily: 'day',
        weekly: 'week',
        monthly: 'month',
        yearly: 'year',
    };

    return reminder.interval === 1
        ? `Every ${labels[reminder.frequency]}`
        : `Every ${reminder.interval} ${labels[reminder.frequency]}s`;
};

const statusLabels = {
    active: 'Active',
    paused: 'Paused',
    ended: 'Ended',
};
const priorityLabels = {
    low: 'Low',
    medium: 'Medium',
    high: 'High',
};
</script>

<template>
    <Head title="CMS Recurring reminders" />

    <div class="space-y-7">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-[#8792A2]">{{ $t("Scheduled activity") }}</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-[#17243A]"> {{ $t("Recurring reminders") }} </h1>
            </div>
            <div class="flex items-center gap-2 text-sm text-[#637083]">
                <CalendarClock :size="18" class="text-[#2457DA]" />
                <span>{{ reminders.length }} {{ $t("total reminders") }}</span>
            </div>
        </header>

        <section class="grid gap-4 sm:grid-cols-3">
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="text-sm text-[#8792A2]">{{ $t("Total reminders") }}</p>
                <p class="mt-3 text-2xl font-semibold text-[#17243A]">{{ reminders.length }}</p>
            </article>
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="text-sm text-[#8792A2]">{{ $t("Active") }}</p>
                <p class="mt-3 text-2xl font-semibold text-[#16815B]">{{ activeReminders }}</p>
            </article>
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="text-sm text-[#8792A2]">{{ $t("Paused") }}</p>
                <p class="mt-3 text-2xl font-semibold text-[#765723]">{{ pausedReminders }}</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-[#E9EDF3] bg-white">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#EEF1F5] px-6 py-4">
                <div>
                    <h2 class="font-semibold text-[#17243A]">{{ $t("All recurring reminders") }}</h2>
                    <p class="mt-1 text-sm text-[#8792A2]">{{ $t("Review schedules, estimated amounts, and priority.") }}</p>
                </div>
                <div class="flex gap-2" role="group" aria-label="Filter reminders by type">
                    <ButtonPrimary
                        v-for="filter in filters"
                        :key="filter.value"
                        type="button"
                        :variant="typeFilter === filter.value ? 'primary' : 'secondary'"
                        :aria-pressed="typeFilter === filter.value"
                        class="!min-h-0 !rounded-full !px-4 !py-2 !text-xs"
                        @click="typeFilter = filter.value"
                    >
                        {{ filter.label }}
                    </ButtonPrimary>
                </div>
            </div>

            <div v-if="filteredReminders.length" class="overflow-x-auto">
                <table class="w-full min-w-[880px] text-left">
                    <thead class="bg-[#FAFBFC] text-[11px] font-semibold uppercase tracking-[0.12em] text-[#98A2B3]">
                        <tr>
                            <th class="px-6 py-3">{{ $t("Reminder") }}</th>
                            <th class="px-5 py-3">{{ $t("Type") }}</th>
                            <th class="px-5 py-3">{{ $t("Priority") }}</th>
                            <th class="px-5 py-3">{{ $t("Schedule") }}</th>
                            <th class="px-5 py-3 text-right">{{ $t("Estimated amount") }}</th>
                            <th class="px-6 py-3">{{ $t("Status") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0F2F6]">
                        <tr v-for="reminder in filteredReminders" :key="reminder.id" class="hover:bg-[#FCFDFE]">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="rounded-xl bg-[#EEF3FF] p-2.5 text-[#2457DA]">
                                        <Bell :size="17" />
                                    </span>
                                    <span>
                                        <span class="block font-medium text-[#17243A]">{{ reminder.title }}</span>
                                        <span class="mt-1 block text-xs text-[#8792A2]">
                                            {{ reminder.account?.name || 'Choose account when recording' }}
                                        </span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm capitalize text-[#637083]">
                                {{ reminder.type }}
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="reminder.priority === 'high'
                                        ? 'bg-[#FFF2F0] text-[#B34735]'
                                        : reminder.priority === 'low'
                                            ? 'bg-[#EEF3FF] text-[#2457DA]'
                                            : 'bg-[#FFF7E8] text-[#765723]'"
                                >
                                    {{ priorityLabels[reminder.priority] || priorityLabels.medium }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-sm text-[#637083]">
                                <span class="block">{{ frequencyLabel(reminder) }}</span>
                                <span class="mt-1 block text-xs text-[#8792A2]"> {{ $t("Next:") }} {{ reminder.next_reminder_at || 'Not scheduled' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right text-sm font-medium text-[#17243A]">
                                {{ money(reminder.amount, reminder.account?.currency || 'IDR') }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="reminder.status === 'active'
                                        ? 'bg-[#E6F5EE] text-[#16815B]'
                                        : reminder.status === 'paused'
                                            ? 'bg-[#FFF7E8] text-[#765723]'
                                            : 'bg-[#F1F4F9] text-[#637083]'"
                                >
                                    {{ statusLabels[reminder.status] || reminder.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="px-6 py-14 text-center">
                <Bell :size="30" class="mx-auto text-[#98A2B3]" />
                <h3 class="mt-3 font-semibold text-[#17243A]">{{ $t("No recurring reminders found") }}</h3>
                <p class="mt-1 text-sm text-[#8792A2]">{{ $t("Try another type filter or create a reminder from the app.") }}</p>
            </div>
        </section>
    </div>
</template>
