<script setup>
import { Sparkles } from 'lucide-vue-next';
</script>

<template>
    <section class="rounded-2xl bg-gradient-to-br from-[#11297A] to-[#2969E6] p-5 text-white shadow-lg shadow-blue-900/10">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15">
                <Sparkles :size="20" />
            </span>
            <div>
                <p class="font-semibold">Your financial companion</p>
                <p class="mt-1 text-sm leading-5 text-blue-100">
                    Get simple guidance for your everyday money decisions.
                </p>
            </div>
        </div>
    </section>
</template>
