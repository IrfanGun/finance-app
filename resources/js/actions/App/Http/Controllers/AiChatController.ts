import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AiChatController::__invoke
 * @see app/Http/Controllers/AiChatController.php:13
 * @route '/ai/chat'
 */
const AiChatController = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: AiChatController.url(options),
    method: 'post',
})

AiChatController.definition = {
    methods: ["post"],
    url: '/ai/chat',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AiChatController::__invoke
 * @see app/Http/Controllers/AiChatController.php:13
 * @route '/ai/chat'
 */
AiChatController.url = (options?: RouteQueryOptions) => {
    return AiChatController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AiChatController::__invoke
 * @see app/Http/Controllers/AiChatController.php:13
 * @route '/ai/chat'
 */
AiChatController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: AiChatController.url(options),
    method: 'post',
})
export default AiChatController