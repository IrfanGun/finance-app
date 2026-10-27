<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BriefcaseBusiness, CalendarDays, ChevronRight, Plus, Search } from 'lucide-vue-next';
import CmsModal from '@/Components/CmsModal.vue';
import { store } from '@/routes/assets';
import { show } from '@/routes/cms/assets';

const props = defineProps({
    assets: { type: Object, default: () => ({ data: [], links: [] }) },
    currencyOptions: { type: Array, default: () => ['IDR'] },
});
const open = ref(false);
const search = ref('');
const today = new Intl.DateTimeFormat('sv-SE', { timeZone: 'Asia/Jakarta' }).format(new Date());
const form = useForm({ name: '', description: '', currency: props.currencyOptions[0] ?? 'IDR', purchase_price: '', purchase_date: today, depreciation: null });
const filteredAssets = computed(() => props.assets.data.filter((asset) => asset.name.toLowerCase().includes(search.value.toLowerCase())));
const money = (value, currency = 'IDR') => new Intl.NumberFormat('id-ID', { style: 'currency', currency, maximumFractionDigits: 2 }).format(Number(value ?? 0));
const create = () => { form.reset(); form.clearErrors(); form.currency = props.currencyOptions[0] ?? 'IDR'; form.purchase_date = today; open.value = true; };
const save = () => form.post(store.url(), { onSuccess: () => { open.value = false; form.reset(); } });
</script>

<template>

    <Head title="CMS Assets" />
    <div class="space-y-7">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-[#8792A2]">{{ $t("Long-term value") }}</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-[#17243A]">{{ $t("Assets") }}</h1>
            </div>
            <button
                class="inline-flex h-11 items-center gap-2 rounded-xl bg-[#2457DA] px-4 text-sm font-semibold text-white shadow-sm shadow-blue-200 hover:bg-[#1947BE]"
                @click="create">
                <Plus :size="17" /> {{ $t("Add asset") }} </button>
        </header>
        <section class="grid gap-4 sm:grid-cols-3">
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="text-sm text-[#8792A2]">{{ $t("Assets on this page") }}</p>
                <p class="mt-3 text-2xl font-semibold text-[#17243A]">{{ assets.data.length }}</p>
            </article>
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="text-sm text-[#8792A2]">{{ $t("Estimated value") }}</p>
                <p class="mt-3 text-xl font-semibold text-[#17243A]">{{money(assets.data.reduce((total, asset) => total
                    + Number(asset.current_value ?? 0), 0))}}</p>
            </article>
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="text-sm text-[#8792A2]">{{ $t("Purchase value") }}</p>
                <p class="mt-3 text-xl font-semibold text-[#17243A]">{{money(assets.data.reduce((total, asset) => total
                    + Number(asset.purchase_price ?? 0), 0))}}</p>
            </article>
        </section>
        <section
            class="overflow-hidden rounded-2xl border border-[#E9EDF3] bg-white shadow-[0_4px_18px_rgba(23,36,58,0.03)]">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#EEF1F5] px-6 py-4">
                <div>
                    <h2 class="font-semibold text-[#17243A]">{{ $t("Asset portfolio") }}</h2>
                </div><label
                    class="flex h-10 w-full items-center gap-2 rounded-lg border border-[#E7EBF2] px-3 sm:w-64">
                    <Search :size="16" class="text-[#98A2B3]" /><input v-model="search"
                        class="w-full border-0 p-0 text-sm outline-none focus:ring-0" placeholder="Search assets" />
                </label>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left">
                    <thead class="bg-[#FAFBFC] text-[11px] font-semibold uppercase tracking-[0.12em] text-[#98A2B3]">
                        <tr>
                            <th class="px-6 py-3">{{ $t("Asset") }}</th>
                            <th class="px-5 py-3">{{ $t("Purchase date") }}</th>
                            <th class="px-5 py-3 text-right">{{ $t("Purchase price") }}</th>
                            <th class="px-5 py-3 text-right">{{ $t("Estimated value") }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0F2F6]">
                        <tr v-for="asset in filteredAssets" :key="asset.id" class="transition hover:bg-[#FCFDFE]">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3"><span
                                        class="rounded-xl bg-[#EEF3FF] p-2.5 text-[#2457DA]">
                                        <BriefcaseBusiness :size="18" />
                                    </span><span><span class="block text-sm font-semibold text-[#26354C]">{{ asset.name
                                            }}</span><span class="mt-1 block text-xs capitalize text-[#98A2B3]">{{
                                                asset.status }}<template v-if="asset.depreciation"> · value {{
                                                asset.depreciation.value_direction === 'increase' ? 'growth' : 'change'
                                                }} scheduled</template></span></span>
                                </div>
                            </td>
                            <td class="px-5 py-4"><span class="flex items-center gap-2 text-sm text-[#7B8798]">
                                    <CalendarDays :size="15" />{{ asset.purchase_date }}
                                </span></td>
                            <td class="px-5 py-4 text-right text-sm text-[#637083]">{{ money(asset.purchase_price,
                                asset.currency) }}</td>
                            <td class="px-5 py-4 text-right text-sm font-semibold text-[#26354C]">{{
                                money(asset.current_value, asset.currency) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="show.url(asset.id)"
                                    class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold text-[#2457DA] hover:bg-[#EEF3FF]"> {{ $t("Details") }} <ChevronRight :size="16" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!filteredAssets.length" class="px-6 py-12 text-center"><span
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#EEF3FF] text-[#2457DA]">
                    <BriefcaseBusiness :size="21" />
                </span>
                <p class="mt-3 text-sm font-semibold text-[#435065]">{{ search ? 'No matching assets' : 'No assets yet'
                    }}</p>
            </div>
            <nav v-if="assets.links?.length > 3" class="flex justify-end gap-2 border-t border-[#EEF1F5] px-6 py-4"
                aria-label="Asset pages">
                <Link v-for="link in assets.links" :key="`${link.label}-${link.url}`" :href="link.url || '#'"
                    :class="['rounded-lg px-3 py-2 text-sm', link.active ? 'bg-[#2457DA] text-white' : 'bg-[#F5F7FA] text-[#314158]', !link.url ? 'pointer-events-none opacity-40' : '']"
                    v-html="link.label" />
            </nav>
        </section>
        <CmsModal :show="open" label="Add asset" @close="open = false">
            <form class="my-auto max-h-[calc(100dvh-2rem)] w-full max-w-xl space-y-5 overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl sm:max-h-[calc(100dvh-3rem)]"
                @submit.prevent="save">
                <div>
                    <p class="text-sm text-[#8792A2]">{{ $t("Asset details") }}</p>
                    <h2 class="mt-1 text-xl font-semibold text-[#17243A]">{{ $t("Add an asset") }}</h2>
                    <p class="mt-2 text-sm text-[#637083]">{{ $t("You can configure value changes from the asset detail page after saving.") }}</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2"><label
                        class="text-sm font-medium text-[#435065] sm:col-span-2">{{ $t("Name") }}<input v-model="form.name" required
                            maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm"
                            placeholder="e.g. Laptop" /></label><label
                        class="text-sm font-medium text-[#435065]">{{ $t("Currency") }}<select v-model="form.currency"
                            class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] bg-white px-3 text-sm">
                            <option v-for="currency in currencyOptions" :key="currency" :value="currency">{{ currency }}
                            </option>
                        </select></label><label class="text-sm font-medium text-[#435065]">{{ $t("Purchase price") }}<AmountInput
                            v-model="form.purchase_price" required
                            class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm" /></label><label
                        class="text-sm font-medium text-[#435065] sm:col-span-2">{{ $t("Purchase date") }}<input
                            v-model="form.purchase_date" type="date" :max="today" required
                            class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm" /></label><label
                        class="text-sm font-medium text-[#435065] sm:col-span-2">{{ $t("Description") }}<textarea
                            v-model="form.description" rows="3"
                            class="mt-2 w-full rounded-lg border border-[#DDE3EC] px-3 py-2 text-sm"
                            placeholder="Optional details" /></label></div>
                <p v-if="Object.keys(form.errors).length" class="text-sm text-red-600">{{ Object.values(form.errors)[0]
                    }}</p>
                <div class="flex justify-end gap-3"><button type="button"
                        class="rounded-xl border border-[#DDE3EC] px-4 py-2.5 text-sm font-semibold text-[#637083]"
                        @click="open = false">{{ $t("Cancel") }}</button><button
                        class="rounded-xl bg-[#2457DA] px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                        :disabled="form.processing">{{ form.processing ? 'Saving…' : 'Save asset' }}</button></div>
            </form>
        </CmsModal>
    </div>
</template>
