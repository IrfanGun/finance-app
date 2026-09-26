<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, MessageCircle } from 'lucide-vue-next';

defineProps({
    dashboardUrl: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <header class="flex items-center justify-between border-b border-[#E9EDF3] bg-white px-6 py-5">
        <Link
            :href="dashboardUrl"
            class="flex h-10 w-10 items-center justify-center rounded-full bg-[#EEF3FF] text-[#2457DA]"
            aria-label="Back to dashboard"
        >
            <ArrowLeft :size="20" />
        </Link>

        <div class="text-center">
            <h1 class="text-lg font-semibold">Chat</h1>
            <p class="mt-0.5 flex items-center justify-center gap-1 text-xs text-[#16815B]">
                <span class="h-1.5 w-1.5 rounded-full bg-[#16815B]"></span>
                Online now
            </p>
        </div>

        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#2457DA] text-white">
            <MessageCircle :size="20" />
        </span>
    </header>
</template>
