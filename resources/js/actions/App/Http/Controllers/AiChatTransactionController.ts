import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AiChatTransactionController::confirm
 * @see app/Http/Controllers/AiChatTransactionController.php:11
 * @route '/ai/chat/transactions/confirm'
 */
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/ai/chat/transactions/confirm',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AiChatTransactionController::confirm
 * @see app/Http/Controllers/AiChatTransactionController.php:11
 * @route '/ai/chat/transactions/confirm'
 */
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AiChatTransactionController::confirm
 * @see app/Http/Controllers/AiChatTransactionController.php:11
 * @route '/ai/chat/transactions/confirm'
 */
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})
const AiChatTransactionController = { confirm }

export default AiChatTransactionController