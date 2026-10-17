import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { store as mealStore, update as mealUpdate } from '@/routes/meals';
import {
    createEmptyMealForm,
    getOpeningHoursForForm,
    getScheduleMode,
    prepareMealFormData,
    weekdays,
} from './lib/mealRecord';

export function useMealRecordForm(formElement) {
    const formVisible = ref(false);
    const currentStep = ref(1);
    const hasOpeningHours = ref(false);
    const editingMeal = ref(null);
    const scheduleMode = ref('everyday');
    const dailySessions = ref([]);
    const form = useForm(createEmptyMealForm());
    const errors = computed(() => form.errors);
    const totalSteps = computed(() => (form.type === 'buy' ? 3 : 1));
    const isFinalStep = computed(() => currentStep.value === totalSteps.value);

    const scrollToForm = () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    };

    const openCreate = () => {
        editingMeal.value = null;
        currentStep.value = 1;
        hasOpeningHours.value = false;
        scheduleMode.value = 'everyday';
        dailySessions.value = [];
        form.clearErrors();
        form.defaults(createEmptyMealForm());
        form.reset();
        formVisible.value = true;
        scrollToForm();
    };

    const openEdit = (meal) => {
        editingMeal.value = meal;
        currentStep.value = 1;
        form.clearErrors();

        const openingHours = getOpeningHoursForForm(meal.opening_hours);
        const schedule = getScheduleMode(openingHours);

        hasOpeningHours.value = openingHours.length > 0;
        scheduleMode.value = schedule.mode;
        dailySessions.value = schedule.dailySessions;
        form.defaults({
            name: meal.name,
            place_name: meal.place_name ?? '',
            type: meal.type,
            price_min: meal.price_min,
            price_max: meal.price_max,
            rating: meal.rating,
            address: meal.address ?? '',
            impression: meal.impression ?? '',
            opening_hours: openingHours,
        });
        form.reset();
        formVisible.value = true;
        scrollToForm();
    };

    const closeForm = () => {
        if (form.processing) {
            return;
        }

        formVisible.value = false;
    };

    const addSession = (day) => {
        form.opening_hours.push({
            day_of_week: day,
            opens_at: '09:00',
            closes_at: '17:00',
        });
    };

    const removeSession = (index) => {
        form.opening_hours.splice(index, 1);
    };

    const addDailySession = () => {
        dailySessions.value.push({
            opens_at: '09:00',
            closes_at: '17:00',
        });
    };

    const removeDailySession = (index) => {
        dailySessions.value.splice(index, 1);
    };

    const toggleOpeningHours = (enabled) => {
        hasOpeningHours.value = enabled;

        if (!enabled) {
            dailySessions.value = [];
            form.opening_hours = [];

            return;
        }

        if (scheduleMode.value === 'everyday' && dailySessions.value.length === 0) {
            addDailySession();

            return;
        }

        if (scheduleMode.value === 'custom' && form.opening_hours.length === 0) {
            addSession(1);
        }
    };

    const changeScheduleMode = (mode) => {
        if (mode === scheduleMode.value) {
            return;
        }

        if (mode === 'everyday') {
            const firstScheduledDay = weekdays
                .map((day, index) => form.opening_hours.filter(
                    (session) => session.day_of_week === index + 1,
                ))
                .find((sessions) => sessions.length > 0);

            if (firstScheduledDay) {
                dailySessions.value = firstScheduledDay.map((session) => ({
                    opens_at: session.opens_at,
                    closes_at: session.closes_at,
                }));
            }
        } else if (form.opening_hours.length === 0 && dailySessions.value.length > 0) {
            form.opening_hours = weekdays.flatMap((day, index) => dailySessions.value.map((session) => ({
                day_of_week: index + 1,
                opens_at: session.opens_at,
                closes_at: session.closes_at,
            })));
        }

        scheduleMode.value = mode;
    };

    const continueToNextStep = () => {
        if (!formElement.value?.reportValidity()) {
            return;
        }

        currentStep.value += 1;
    };

    const save = () => {
        if (!formElement.value?.reportValidity()) {
            return;
        }

        form.transform((data) => prepareMealFormData(data, {
            hasOpeningHours: hasOpeningHours.value,
            scheduleMode: scheduleMode.value,
            dailySessions: dailySessions.value,
        }));

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                formVisible.value = false;
                currentStep.value = 1;
            },
            onError: (validationErrors) => {
                const errorFields = Object.keys(validationErrors);

                if (errorFields.some((field) => ['name', 'type', 'price_min', 'price_max', 'rating'].includes(field))) {
                    currentStep.value = 1;

                    return;
                }

                if (errorFields.some((field) => ['place_name', 'address', 'impression'].includes(field))) {
                    currentStep.value = 2;

                    return;
                }

                if (errorFields.some((field) => field.startsWith('opening_hours.'))) {
                    currentStep.value = 3;
                }
            },
        };

        if (editingMeal.value) {
            form.put(mealUpdate.url(editingMeal.value.id), options);

            return;
        }

        form.post(mealStore.url(), options);
    };

    return {
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
    };
}
