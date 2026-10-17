import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { index as mealsIndex } from '@/routes/meals';

export function useMealFilters(filters = {}) {
    const isLoading = ref(false);
    const search = ref(filters.search ?? '');
    const typeFilter = ref(filters.type ?? '');
    const availabilityFilter = ref(filters.availability ?? '');
    const priceMinFilter = ref(filters.price_min ?? '');
    const priceMaxFilter = ref(filters.price_max ?? '');

    watch(
        [search, typeFilter, availabilityFilter, priceMinFilter, priceMaxFilter],
        () => {
            router.get(mealsIndex.url(), {
                search: search.value || undefined,
                type: typeFilter.value || undefined,
                availability: availabilityFilter.value || undefined,
                price_min: priceMinFilter.value || undefined,
                price_max: priceMaxFilter.value || undefined,
            }, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onStart: () => {
                    isLoading.value = true;
                },
                onFinish: () => {
                    isLoading.value = false;
                },
            });
        },
    );

    return {
        availabilityFilter,
        isLoading,
        priceMaxFilter,
        priceMinFilter,
        search,
        typeFilter,
    };
}
