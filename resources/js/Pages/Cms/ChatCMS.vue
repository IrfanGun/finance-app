<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Bot,
    Send,
    ShieldCheck,
    Sparkles,
} from 'lucide-vue-next';
import ChatLoadMore from '@/Components/Chat/ChatLoadMore.vue';
import ChatMessageList from '@/Components/Chat/ChatMessageList.vue';
import ChatTransactionSetupModal from '@/Components/Chat/ChatTransactionSetupModal.vue';
import { useChatMessages } from '@/Features/Chat/useChatMessages';
import { useChatWorkflow } from '@/Features/Chat/useChatWorkflow';

const props = defineProps({
    conversationId: { type: Number, default: null },
    messages: { type: Array, default: () => [] },
    hasMoreMessages: { type: Boolean, default: false },
    workflow: { type: Object, default: null },
});

const suggestions = [
    'How much did I spend this month?',
    'Help me plan my budget',
    'Give me a saving tip',
];

const {
    messages,
    hasMoreMessages,
    isLoadingMoreMessages,
    loadMoreError,
    draft,
    isTyping,
    messagesContainer,
    appendAssistantMessage,
    loadMoreMessages,
    sendMessage: sendChatMessage,
} = useChatMessages(props);

const {
    isSavingSetup,
    setupResources,
    accountSelectionRequired,
    accountOptions,
    selectedAccountId,
    isSetupOpen,
    canConfirmSetup,
    setupTitle,
    setupSubmitLabel,
    accountTypeLabel,
    handleChatResponse,
    confirmMissingResources,
    continueInChat,
    cancelWorkflow,
    startAccountCreation,
} = useChatWorkflow(props, appendAssistantMessage);

const showSuggestions = computed(() => messages.value.length <= 1);

const sendMessage = async (suggestion = null) => {
    const payload = await sendChatMessage(suggestion, isSetupOpen.value);

    if (payload) {
        handleChatResponse(payload);
    }
};
</script>

<template>
    <Head title="AI Assistant" />

    <div class="chat-cms flex h-full min-h-0 flex-1 flex-col overflow-hidden bg-white">
        <header class="flex shrink-0 items-center justify-between border-b border-[#E9EDF3] px-5 py-4 sm:px-8">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EEF3FF] text-[#2457DA]"><Sparkles :size="19" /></span>
                <div>
                    <h1 class="text-base font-semibold text-[#26354C]">AI Assistant</h1>
                    <p class="mt-0.5 flex items-center gap-1.5 text-xs text-[#16815B]"><i class="h-1.5 w-1.5 rounded-full bg-[#2FA876]"></i>Ready to help with your finances</p>
                </div>
            </div>
            <span class="hidden items-center gap-1.5 text-xs font-medium text-[#8792A2] sm:inline-flex"><ShieldCheck :size="15" /> Private conversation</span>
        </header>

        <div ref="messagesContainer" class="min-h-0 flex-1 overflow-y-auto bg-[#FBFCFE] px-5 py-6 sm:px-10 lg:px-14">
            <div class="mx-auto max-w-5xl space-y-6">
                <div v-if="showSuggestions" class="mb-8 flex flex-col items-center py-8 text-center sm:py-12">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-[#2457DA] to-[#5884F2] text-white shadow-lg shadow-blue-200"><Bot :size="25" /></span>
                    <h2 class="mt-5 text-xl font-semibold text-[#26354C]">What would you like to know?</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-[#7B8798]">Ask about your spending, accounts, or saving goals. I’ll help you make sense of your financial activity.</p>
                    <div class="mt-6 flex flex-wrap justify-center gap-2">
                        <button v-for="suggestion in suggestions" :key="suggestion" type="button" class="rounded-full border border-[#E1E8F5] bg-white px-4 py-2.5 text-xs font-medium text-[#4564A6] transition hover:border-[#B7C9F2] hover:bg-[#EEF3FF] disabled:opacity-50" :disabled="isTyping || isSetupOpen" @click="sendMessage(suggestion)">{{ suggestion }}<ArrowUpRight :size="13" class="ml-1 inline" /></button>
                    </div>
                </div>
                <ChatLoadMore :has-more="hasMoreMessages" :is-loading="isLoadingMoreMessages" :error="loadMoreError" @load-more="loadMoreMessages" />
                <ChatMessageList :messages="messages" :is-typing="isTyping" />
            </div>
        </div>

        <footer class="shrink-0 border-t border-[#E9EDF3] bg-white px-5 py-4 sm:px-8 sm:py-5">
            <form class="mx-auto flex max-w-5xl items-center gap-3 rounded-2xl border border-[#E4E9F1] bg-[#F8F9FB] p-2 pl-4 transition focus-within:border-[#AFC1EE] focus-within:ring-4 focus-within:ring-[#EEF3FF]" @submit.prevent="sendMessage()">
                <input v-model="draft" type="text" placeholder="Ask about your finances…" class="min-w-0 flex-1 border-0 bg-transparent py-2 text-sm text-[#26354C] placeholder:text-[#A0AAB8] focus:ring-0" :disabled="isTyping || isSetupOpen" />
                <button type="submit" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#2457DA] text-white transition hover:bg-[#1947BE] disabled:cursor-not-allowed disabled:opacity-40" :disabled="!draft.trim() || isTyping || isSetupOpen" aria-label="Send message"><Send :size="17" /></button>
            </form>
          
        </footer>

        <ChatTransactionSetupModal
            :is-open="isSetupOpen"
            :setup-title="setupTitle"
            :setup-submit-label="setupSubmitLabel"
            :account-selection-required="accountSelectionRequired"
            :account-options="accountOptions"
            :selected-account-id="selectedAccountId"
            :setup-resources="setupResources"
            :can-confirm-setup="canConfirmSetup"
            :is-saving-setup="isSavingSetup"
            :account-type-label="accountTypeLabel"
            @continue="continueInChat"
            @cancel="cancelWorkflow"
            @confirm="confirmMissingResources"
            @start-account-creation="startAccountCreation"
            @update:selected-account-id="selectedAccountId = $event"
        />
    </div>
</template>

<style scoped>
.chat-cms :deep(.fixed.inset-0.z-50 > section) {
    max-width: 32rem;
}
</style>
