<script setup>
import { nextTick, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Bot, MessageCircle, Send, Sparkles, UserRound } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';

const suggestions = [
    'How much did I spend this month?',
    'Help me plan my budget',
    'Give me a saving tip',
];

const messages = ref([
    {
        id: 1,
        role: 'assistant',
        text: 'Hi! I’m your finance assistant. Ask me anything about your spending, budget, or saving goals.',
        time: 'Now',
    },
]);
const draft = ref('');
const isTyping = ref(false);
const messagesContainer = ref(null);

const scrollToLatestMessage = async () => {
    await nextTick();

    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const assistantReply = (message) => {
    const normalizedMessage = message.toLowerCase();

    if (normalizedMessage.includes('spend') || normalizedMessage.includes('expense')) {
        return 'I can help you review your expenses. Open Activity to see the latest transactions and spot your biggest spending categories.';
    }

    if (normalizedMessage.includes('budget')) {
        return 'A good starting point is to set a monthly limit for your top three spending categories, then check in weekly.';
    }

    if (normalizedMessage.includes('saving') || normalizedMessage.includes('save')) {
        return 'Try setting aside your savings as soon as income arrives. Even a small, consistent amount can make a difference over time.';
    }

    return 'Thanks for sharing. I’m ready to help you understand your money and make a simple plan.';
};

const sendMessage = async (suggestion = null) => {
    const text = (suggestion ?? draft.value).trim();

    if (!text || isTyping.value) {
        return;
    }

    messages.value.push({
        id: Date.now(),
        role: 'user',
        text,
        time: 'Now',
    });
    draft.value = '';
    isTyping.value = true;
    await scrollToLatestMessage();

    window.setTimeout(async () => {
        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            text: assistantReply(text),
            time: 'Now',
        });
        isTyping.value = false;
        await scrollToLatestMessage();
    }, 650);
};
</script>

<template>
    <Head title="Chat" />

    <div class="mx-auto flex h-[100dvh] min-h-0 w-full max-w-lg flex-col overflow-hidden bg-[#F8FAFC] pb-28 text-app-heading">
        <header class="flex items-center justify-between border-b border-[#E9EDF3] bg-white px-6 py-5">
            <Link :href="dashboard.url()" class="flex h-10 w-10 items-center justify-center rounded-full bg-[#EEF3FF] text-[#2457DA]" aria-label="Back to dashboard">
                <ArrowLeft :size="20" />
            </Link>
            <div class="text-center">
                <h1 class="text-lg font-semibold">Chat</h1>
                <p class="mt-0.5 flex items-center justify-center gap-1 text-xs text-[#16815B]"><span class="h-1.5 w-1.5 rounded-full bg-[#16815B]"></span>Online now</p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#2457DA] text-white"><MessageCircle :size="20" /></span>
        </header>

        <main ref="messagesContainer" class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain px-6 pb-[190px] pt-6">
            <section class="rounded-2xl bg-gradient-to-br from-[#11297A] to-[#2969E6] p-5 text-white shadow-lg shadow-blue-900/10">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15"><Sparkles :size="20" /></span>
                    <div>
                        <p class="font-semibold">Your financial companion</p>
                        <p class="mt-1 text-sm leading-5 text-blue-100">Get simple guidance for your everyday money decisions.</p>
                    </div>
                </div>
            </section>

            <div class="space-y-4">
                <div v-for="message in messages" :key="message.id" class="flex items-end gap-2" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                    <span v-if="message.role === 'assistant'" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#E8EEFF] text-[#2457DA]"><Bot :size="17" /></span>
                    <div class="max-w-[82%]">
                        <div class="rounded-2xl px-4 py-3 text-sm leading-5" :class="message.role === 'user' ? 'rounded-br-md bg-[#2457DA] text-white' : 'rounded-bl-md bg-white text-[#314158] shadow-sm ring-1 ring-[#EEF1F5]'">
                            {{ message.text }}
                        </div>
                        <p class="mt-1 px-1 text-[10px] text-[#94A0B2]" :class="message.role === 'user' ? 'text-right' : ''">{{ message.time }}</p>
                    </div>
                    <span v-if="message.role === 'user'" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#DCE6FF] text-[#2457DA]"><UserRound :size="16" /></span>
                </div>

                <div v-if="isTyping" class="flex items-end gap-2">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#E8EEFF] text-[#2457DA]"><Bot :size="17" /></span>
                    <div class="flex gap-1 rounded-2xl rounded-bl-md bg-white px-4 py-4 shadow-sm ring-1 ring-[#EEF1F5]">
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#8BA5EF]"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#8BA5EF] [animation-delay:120ms]"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#8BA5EF] [animation-delay:240ms]"></span>
                    </div>
                </div>
            </div>
        </main>

        <section class="fixed inset-x-0 bottom-[84px] z-20 mx-auto w-full max-w-lg space-y-3 border-t border-[#E9EDF3] bg-white px-6 py-4 shadow-[0_-4px_18px_rgba(21,35,56,0.05)]">
            <div class="flex min-w-0 max-w-full gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&amp;::-webkit-scrollbar]:hidden">
                <button v-for="suggestion in suggestions" :key="suggestion" type="button" class="shrink-0 whitespace-nowrap rounded-full border border-[#DCE6FF] bg-[#F7F9FF] px-3 py-2 text-xs font-medium text-[#2457DA] transition hover:bg-[#EEF3FF] disabled:opacity-50" :disabled="isTyping" @click="sendMessage(suggestion)">
                    {{ suggestion }}
                </button>
            </div>
            <form class="flex min-w-0 items-center gap-2 rounded-2xl bg-[#F5F7FA] p-2" @submit.prevent="sendMessage()">
                <input v-model="draft" type="text" placeholder="Ask about your finances..." class="min-w-0 flex-1 border-0 bg-transparent px-2 text-sm text-[#152338] placeholder:text-[#94A0B2] focus:ring-0" :disabled="isTyping" />
                <button type="submit" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#2457DA] text-white transition hover:bg-[#1D43B6] disabled:cursor-not-allowed disabled:opacity-50" :disabled="!draft.trim() || isTyping" aria-label="Send message">
                    <Send :size="18" />
                </button>
            </form>
            <p class="text-center text-[10px] text-[#94A0B2]">Assistant suggestions are for general guidance only.</p>
        </section>

        <MobileBottomNav />
    </div>
</template>
