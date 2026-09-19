import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import chatB2e4da from './chat'
/**
* @see \App\Http\Controllers\AiChatController::__invoke
 * @see app/Http/Controllers/AiChatController.php:13
 * @route '/ai/chat'
 */
export const chat = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: chat.url(options),
    method: 'post',
})

chat.definition = {
    methods: ["post"],
    url: '/ai/chat',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AiChatController::__invoke
 * @see app/Http/Controllers/AiChatController.php:13
 * @route '/ai/chat'
 */
chat.url = (options?: RouteQueryOptions) => {
    return chat.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AiChatController::__invoke
 * @see app/Http/Controllers/AiChatController.php:13
 * @route '/ai/chat'
 */
chat.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: chat.url(options),
    method: 'post',
})
const ai = {
    chat: Object.assign(chat, chatB2e4da),
}

export default ai