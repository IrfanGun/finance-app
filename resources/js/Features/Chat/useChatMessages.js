import { nextTick, onMounted, ref, watch } from 'vue';
import { chat as aiChat } from '@/routes/ai';
import { chat as chatPage } from '@/routes';
import { chatRequestHeaders } from './chatRequestHeaders';

const fallbackMessages = [
    {
        id: 1,
        role: 'assistant',
        text: 'Hi! I’m your finance assistant. Ask me anything about your spending, budget, or saving goals.',
        time: 'Now',
    },
];

export function useChatMessages(props) {
    const messages = ref(props.messages.length > 0 ? props.messages : fallbackMessages);
    const hasMoreMessages = ref(props.hasMoreMessages);
    const isLoadingMoreMessages = ref(false);
    const loadMoreError = ref('');
    const draft = ref('');
    const isTyping = ref(false);
    const failedRequest = ref(null);
    const messagesContainer = ref(null);
    const chatEndpoint = aiChat.url();

    const scrollToLatestMessage = async () => {
        await nextTick();
        await new Promise((resolve) => requestAnimationFrame(resolve));

        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    };

    const appendAssistantMessage = async (text, idOffset = 1) => {
        messages.value.push({
            id: Date.now() + idOffset,
            role: 'assistant',
            text,
            time: 'Now',
        });

        await scrollToLatestMessage();
    };

    const loadMoreMessages = async () => {
        if (
            !hasMoreMessages.value
            || isLoadingMoreMessages.value
            || messages.value.length === 0
        ) {
            return;
        }

        const oldestMessageId = messages.value[0].id;

        if (typeof oldestMessageId !== 'number') {
            return;
        }

        isLoadingMoreMessages.value = true;
        loadMoreError.value = '';
        const previousScrollHeight = messagesContainer.value?.scrollHeight ?? 0;
        const previousScrollTop = messagesContainer.value?.scrollTop ?? 0;

        try {
            const response = await fetch(chatPage.url({
                query: {
                    conversation_id: props.conversationId,
                    before_message_id: oldestMessageId,
                },
            }), {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                },
            });
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message ?? 'Chat sebelumnya gagal dimuat.');
            }

            const olderMessages = Array.isArray(payload.messages)
                ? payload.messages
                : [];

            messages.value = [...olderMessages, ...messages.value];
            hasMoreMessages.value = payload.has_more === true;
            await nextTick();

            if (messagesContainer.value) {
                messagesContainer.value.scrollTop = previousScrollTop
                    + messagesContainer.value.scrollHeight
                    - previousScrollHeight;
            }
        } catch (error) {
            loadMoreError.value = error instanceof Error
                ? error.message
                : 'Chat sebelumnya gagal dimuat.';
        } finally {
            isLoadingMoreMessages.value = false;
        }
    };

    const sendMessage = async (suggestion = null, isSetupOpen = false) => {
        const text = (suggestion ?? draft.value).trim();

        if (!text || isTyping.value || isSetupOpen) {
            return null;
        }

        const retryingFailedRequest = failedRequest.value?.text === text;
        const clientRequestId = retryingFailedRequest
            ? failedRequest.value.id
            : typeof globalThis.crypto?.randomUUID === 'function'
                ? globalThis.crypto.randomUUID()
                : `${Date.now()}-${Math.random().toString(36).slice(2)}`;

        if (!retryingFailedRequest) {
            messages.value.push({
                id: clientRequestId,
                role: 'user',
                text,
                time: 'Now',
            });
        }

        failedRequest.value = null;
        draft.value = '';
        isTyping.value = true;
        await scrollToLatestMessage();

        try {
            const response = await fetch(chatEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: chatRequestHeaders(),
                body: JSON.stringify({
                    message: text,
                    conversation_id: props.conversationId,
                    client_request_id: clientRequestId,
                }),
            });
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.detail ?? payload.message ?? 'AI service gagal memproses pesan.');
            }

            if (typeof payload.response !== 'string' || !payload.response.trim()) {
                throw new Error('AI service mengembalikan response yang tidak valid.');
            }

            await appendAssistantMessage(payload.response);

            return payload;
        } catch (error) {
            await appendAssistantMessage(
                error instanceof Error
                    ? error.message
                    : 'Maaf, AI service tidak dapat dihubungi saat ini.',
            );
            failedRequest.value = {
                id: clientRequestId,
                text,
            };

            return null;
        } finally {
            isTyping.value = false;
        }
    };

    watch(
        () => props.messages,
        (serverMessages) => {
            messages.value = Array.isArray(serverMessages) && serverMessages.length > 0
                ? serverMessages
                : fallbackMessages;
        },
        { deep: true },
    );

    watch(
        () => props.hasMoreMessages,
        (hasMore) => {
            hasMoreMessages.value = hasMore;
        },
    );

    onMounted(scrollToLatestMessage);

    return {
        messages,
        hasMoreMessages,
        isLoadingMoreMessages,
        loadMoreError,
        draft,
        isTyping,
        messagesContainer,
        scrollToLatestMessage,
        appendAssistantMessage,
        loadMoreMessages,
        sendMessage,
    };
}
