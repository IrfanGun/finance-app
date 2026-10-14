import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AiFinanceController::summary
 * @see app/Http/Controllers/AiFinanceController.php:11
 * @route '/api/ai/finance/summary'
 */
export const summary = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(options),
    method: 'get',
})

summary.definition = {
    methods: ["get","head"],
    url: '/api/ai/finance/summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AiFinanceController::summary
 * @see app/Http/Controllers/AiFinanceController.php:11
 * @route '/api/ai/finance/summary'
 */
summary.url = (options?: RouteQueryOptions) => {
    return summary.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AiFinanceController::summary
 * @see app/Http/Controllers/AiFinanceController.php:11
 * @route '/api/ai/finance/summary'
 */
summary.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AiFinanceController::summary
 * @see app/Http/Controllers/AiFinanceController.php:11
 * @route '/api/ai/finance/summary'
 */
summary.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: summary.url(options),
    method: 'head',
})
const AiFinanceController = { summary }

export default AiFinanceController