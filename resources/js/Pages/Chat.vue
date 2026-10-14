<script setup>
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import ChatComposer from '@/Components/Chat/ChatComposer.vue';
import ChatHeader from '@/Components/Chat/ChatHeader.vue';
import ChatLoadMore from '@/Components/Chat/ChatLoadMore.vue';
import ChatMessageList from '@/Components/Chat/ChatMessageList.vue';
import ChatTransactionSetupModal from '@/Components/Chat/ChatTransactionSetupModal.vue';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
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

const sendMessage = async (suggestion = null) => {
    const payload = await sendChatMessage(suggestion, isSetupOpen.value);

    if (payload) {
        handleChatResponse(payload);
    }
};
</script>

<template>
    <Head title="Chat" />

    <div class="mx-auto flex h-[100dvh] min-h-0 w-full max-w-lg flex-col overflow-hidden bg-[#F8FAFC] pb-28 text-app-heading">
        <ChatHeader :dashboard-url="dashboard.url()" />

        <main ref="messagesContainer" class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain px-6 pb-20 pt-6">
            <ChatLoadMore
                :has-more="hasMoreMessages"
                :is-loading="isLoadingMoreMessages"
                :error="loadMoreError"
                @load-more="loadMoreMessages"
            />
            <ChatMessageList :messages="messages" :is-typing="isTyping" />
        </main>

        <ChatComposer
            v-model:draft="draft"
            :suggestions="suggestions"
            :is-typing="isTyping"
            :is-setup-open="isSetupOpen"
            @send="sendMessage"
        />

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

        <MobileBottomNav />
    </div>
</template>
