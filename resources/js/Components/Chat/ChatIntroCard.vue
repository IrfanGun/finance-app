<script setup>
import { Sparkles } from 'lucide-vue-next';
</script>

<template>
    <section class="rounded-2xl bg-gradient-to-br from-[#11297A] to-[#2969E6] p-5 text-white shadow-lg shadow-blue-900/10">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15">
                <Sparkles :size="20" />
            </span>
        </div>
    </section>
</template>
