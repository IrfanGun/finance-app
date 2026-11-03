<script setup>
import { computed, ref } from 'vue';
import {
    CalendarDays,
    ChevronLeft,
    ChevronRight,
} from 'lucide-vue-next';
import { useTranslation } from '@/Composables/useTranslation';

const props = defineProps({
    placeholder: { type: String, default: 'Select date' },
    required: Boolean,
    min: { type: String, default: '' },
    max: { type: String, default: '' },
});

const { locale, t } = useTranslation();
const model = defineModel({ type: String, default: '' });
const open = ref(false);
const draft = ref(model.value);
const visibleMonth = ref(new Date());

const formatDateKey = (year, month, day) => `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

const monthLabel = computed(() => visibleMonth.value.toLocaleDateString(
    locale.value === 'id' ? 'id-ID' : 'en-US',
    { month: 'long', year: 'numeric' },
));
const selectedDateLabel = computed(() => model.value
    ? new Intl.DateTimeFormat(
        locale.value === 'id' ? 'id-ID' : 'en-US',
        { dateStyle: 'long' },
    ).format(new Date(`${model.value}T12:00:00`))
    : props.placeholder);
const weekdayLabels = computed(() => Array.from({ length: 7 }, (_, index) => {
    const date = new Date(2023, 0, index + 1);

    return new Intl.DateTimeFormat(
        locale.value === 'id' ? 'id-ID' : 'en-US',
        { weekday: 'short' },
    ).format(date);
}));
const calendarDays = computed(() => {
    const year = visibleMonth.value.getFullYear();
    const month = visibleMonth.value.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    return [
        ...Array.from({ length: firstDay }, () => null),
        ...Array.from({ length: daysInMonth }, (_, index) => formatDateKey(year, month, index + 1)),
    ];
});
const canGoToPreviousMonth = computed(() => {
    const year = visibleMonth.value.getFullYear();
    const month = visibleMonth.value.getMonth() - 1;
    const monthEndDate = new Date(year, month + 1, 0);
    const monthEnd = formatDateKey(
        monthEndDate.getFullYear(),
        monthEndDate.getMonth(),
        monthEndDate.getDate(),
    );

    return !props.min || monthEnd >= props.min;
});
const canGoToNextMonth = computed(() => {
    const year = visibleMonth.value.getFullYear();
    const month = visibleMonth.value.getMonth() + 1;
    const monthStartDate = new Date(year, month, 1);
    const monthStart = formatDateKey(
        monthStartDate.getFullYear(),
        monthStartDate.getMonth(),
        monthStartDate.getDate(),
    );

    return !props.max || monthStart <= props.max;
});

const isDateAllowed = (date) => Boolean(date)
    && (!props.min || date >= props.min)
    && (!props.max || date <= props.max);

const openPicker = () => {
    const currentDateIsAllowed = isDateAllowed(model.value);
    const initialDate = currentDateIsAllowed
        ? model.value
        : props.min || props.max || formatDateKey(
            new Date().getFullYear(),
            new Date().getMonth(),
            new Date().getDate(),
        );

    draft.value = currentDateIsAllowed ? model.value : '';
    visibleMonth.value = new Date(`${initialDate}T12:00:00`);
    open.value = true;
};

const confirm = () => {
    if (!isDateAllowed(draft.value)) {
        return;
    }

    model.value = draft.value;
    open.value = false;
};

const changeMonth = (offset) => {
    visibleMonth.value = new Date(
        visibleMonth.value.getFullYear(),
        visibleMonth.value.getMonth() + offset,
        1,
    );
};
</script>

<template>
    <button
        type="button"
        class="mt-2 flex h-12 w-full items-center justify-between rounded-lg border border-[#D4DCEB] bg-white px-4 text-left text-sm font-normal text-[#17243A] transition hover:border-[#2463F5] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2463F5]/20"
        :class="model ? 'text-[#152338]' : 'text-[#637083]'"
        :aria-label="selectedDateLabel"
        @click="openPicker"
    >
        <span>{{ selectedDateLabel }}</span>
        <CalendarDays :size="18" class="shrink-0 text-[#687A9B]" />
    </button>

    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#152338]/40 px-4"
        @click.self="open = false"
    >
        <section
            role="dialog"
            aria-modal="true"
            class="w-full max-w-sm rounded-2xl bg-white p-5 text-[#152338] shadow-2xl sm:p-6"
        >
            <header class="flex items-center justify-between gap-4">
                <h2 class="text-lg font-semibold">{{ t('Select date') }}</h2>
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="!inline-flex !h-9 !min-h-9 !w-9 !items-center !justify-center !rounded-lg !p-0"
                    :aria-label="t('Close')"
                    @click="open = false"
                >
                    <span aria-hidden="true" class="text-xl leading-none">&times;</span>
                </ButtonPrimary>
            </header>

            <div class="mt-5 flex items-center justify-between gap-3">
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="!inline-flex !h-9 !min-h-9 !w-9 !items-center !justify-center !rounded-lg !p-0"
                    :disabled="!canGoToPreviousMonth"
                    :aria-label="t('Previous month')"
                    @click="changeMonth(-1)"
                >
                    <ChevronLeft :size="18" />
                </ButtonPrimary>
                <strong class="text-sm capitalize">{{ monthLabel }}</strong>
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="!inline-flex !h-9 !min-h-9 !w-9 !items-center !justify-center !rounded-lg !p-0"
                    :disabled="!canGoToNextMonth"
                    :aria-label="t('Next month')"
                    @click="changeMonth(1)"
                >
                    <ChevronRight :size="18" />
                </ButtonPrimary>
            </div>

            <div class="mt-4 grid grid-cols-7 text-center text-xs font-medium text-[#687A9B]">
                <span v-for="(day, index) in weekdayLabels" :key="`${day}-${index}`">
                    {{ day }}
                </span>
            </div>
            <div class="mt-2 grid grid-cols-7 gap-y-1 text-center">
                <button
                    v-for="(day, index) in calendarDays"
                    :key="`${day}-${index}`"
                    type="button"
                    class="mx-auto flex h-9 w-9 items-center justify-center rounded-full text-sm transition disabled:cursor-not-allowed disabled:text-[#CBD5E1]"
                    :class="day === draft
                        ? 'bg-[#2457DA] font-semibold text-white'
                        : 'text-[#344563] hover:bg-[#EEF3FF] disabled:hover:bg-transparent'"
                    :disabled="!isDateAllowed(day)"
                    :aria-pressed="day === draft"
                    @click="draft = day"
                >
                    {{ day ? Number(day.slice(-2)) : '' }}
                </button>
            </div>

            <ButtonPrimary
                type="button"
                class="mt-5 w-full !rounded-xl"
                :disabled="!isDateAllowed(draft)"
                @click="confirm"
            >{{ t('Use this date') }}</ButtonPrimary>
        </section>
    </div>

    <input
        v-if="required"
        :value="model"
        required
        tabindex="-1"
        class="pointer-events-none absolute h-0 w-0 opacity-0"
        aria-hidden="true"
    />
</template>
