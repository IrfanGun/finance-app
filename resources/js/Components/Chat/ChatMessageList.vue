<script setup>
import { computed } from 'vue';
import { Bot, UserRound } from 'lucide-vue-next';
import ChatCategoryFrequencyChart from '@/Components/Chat/ChatCategoryFrequencyChart.vue';

const props = defineProps({
    messages: {
        type: Array,
        required: true,
    },
    isTyping: {
        type: Boolean,
        default: false,
    },
});

const categoryFrequencyRowPattern =
    /-\s+\*\*(.+?)\*\*\s*[\-\u2013\u2014]\s*(\d+)\s+transaksi\s*[\u00b7\u2022]\s*total nilai\s+\*\*Rp\s*([\d.,]+)\*\*/g;

const categoryFrequencyByMessage = computed(() => new Map(
    props.messages.map((message) => [
        message.id,
        parseCategoryFrequencySummary(message.text),
    ]),
));

function parseCategoryFrequencySummary(message) {
    const content = String(message ?? '');

    if (!content.includes('Kategori terbanyak:')) {
        return null;
    }

    categoryFrequencyRowPattern.lastIndex = 0;
    const categories = Array.from(
        content.matchAll(categoryFrequencyRowPattern),
        (match) => ({
            name: match[1].trim(),
            count: Number(match[2]),
            amount: Number.parseInt(match[3].replace(/\D/g, ''), 10) || 0,
        }),
    );

    return categories.length > 0 ? categories : null;
}

function categoryFrequencyFor(message) {
    return categoryFrequencyByMessage.value.get(message.id) ?? null;
}

function assistantMessageContent(message) {
    if (!categoryFrequencyFor(message)) {
        return message.text;
    }

    return String(message.text).split(/\r?\n/, 1)[0];
}

function formatAssistantMessage(message) {
    const safeMessage = String(message ?? '').replace(/[&<>"']/g, (character) => {
        const entities = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        };

        return entities[character];
    });

    return safeMessage
        .replace(
            /\*\*(.+?)\*\*/g,
            '<strong class="font-semibold text-[#1D4ED8]">$1</strong>',
        )
        .replace(
            /`(█+)`/g,
            '<span class="rounded bg-[#E8EEFF] px-1 font-mono font-bold tracking-wider text-[#2457DA]">$1</span>',
        );
}
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
                    <div
                        v-if="message.role === 'assistant'"
                        class="break-words"
                    >
                        <div
                            class="whitespace-pre-line"
                            v-html="formatAssistantMessage(assistantMessageContent(message))"
                        ></div>
                        <ChatCategoryFrequencyChart
                            v-if="categoryFrequencyFor(message)"
                            :categories="categoryFrequencyFor(message)"
                        />
                    </div>
                    <div v-else class="whitespace-pre-line break-words">
                        {{ message.text }}
                    </div>
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
