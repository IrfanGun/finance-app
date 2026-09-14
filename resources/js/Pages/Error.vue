<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ShieldAlert } from 'lucide-vue-next';
import { dashboard } from '@/routes';

defineProps({
    status: { type: Number, default: 403 },
    message: { type: String, default: 'Anda tidak memiliki akses ke halaman ini.' },
});
</script>

<template>

    <Head :title="`${status} - Akses ditolak`" />
    <main class="flex min-h-screen w-full items-center justify-center bg-[#F5F7FA] px-6 text-center text-app-heading">
        <section class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-50 text-red-500">
                <ShieldAlert :size="32" />
            </div>
            <p class="mt-5 text-sm font-semibold text-red-500">Error {{ status }}</p>
            <h1 class="mt-2 text-2xl font-semibold">Akses ditolak</h1>
            <p class="mt-3 text-sm leading-6 text-[#637083]">{{ message }}</p>
            <Link :href="dashboard.url()"
                class="mt-6 flex h-12 items-center justify-center gap-2 rounded-xl bg-[#2457DA] font-semibold text-white">
                <ArrowLeft :size="18" /> Kembali ke Dashboard
            </Link>
        </section>
    </main>
</template>
