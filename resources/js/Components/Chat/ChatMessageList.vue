<script setup>
import { Bot, UserRound } from 'lucide-vue-next';

defineProps({
    messages: {
        type: Array,
        required: true,
    },
    isTyping: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="message in messages"
            :key="message.id"
            class="flex items-end gap-2"
            :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
        >
            <span
                v-if="message.role === 'assistant'"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#E8EEFF] text-[#2457DA]"
            >
                <Bot :size="17" />
            </span>

            <div class="max-w-[82%]">
                <div
                    class="rounded-2xl px-4 py-3 text-sm leading-5"
                    :class="message.role === 'user'
                        ? 'rounded-br-md bg-[#2457DA] text-white'
                        : 'rounded-bl-md bg-white text-[#314158] shadow-sm ring-1 ring-[#EEF1F5]'"
                >
                    {{ message.text }}
                </div>
                <p
                    class="mt-1 px-1 text-[10px] text-[#94A0B2]"
                    :class="message.role === 'user' ? 'text-right' : ''"
                >
                    {{ message.time }}
                </p>
            </div>

            <span
                v-if="message.role === 'user'"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#DCE6FF] text-[#2457DA]"
            >
                <UserRound :size="16" />
            </span>
        </div>

        <div v-if="isTyping" class="flex items-end gap-2">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#E8EEFF] text-[#2457DA]">
                <Bot :size="17" />
            </span>
            <div class="flex gap-1 rounded-2xl rounded-bl-md bg-white px-4 py-4 shadow-sm ring-1 ring-[#EEF1F5]">
                <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#8BA5EF]"></span>
                <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#8BA5EF] [animation-delay:120ms]"></span>
                <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#8BA5EF] [animation-delay:240ms]"></span>
            </div>
        </div>
    </div>
</template>
