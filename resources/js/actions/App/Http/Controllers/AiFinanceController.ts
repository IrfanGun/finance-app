import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AiFinanceController::summary
 * @see app/Http/Controllers/AiFinanceController.php:43
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
 * @see app/Http/Controllers/AiFinanceController.php:43
 * @route '/api/ai/finance/summary'
 */
summary.url = (options?: RouteQueryOptions) => {
    return summary.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AiFinanceController::summary
 * @see app/Http/Controllers/AiFinanceController.php:43
 * @route '/api/ai/finance/summary'
 */
summary.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AiFinanceController::summary
 * @see app/Http/Controllers/AiFinanceController.php:43
 * @route '/api/ai/finance/summary'
 */
summary.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: summary.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AiFinanceController::mealRecords
 * @see app/Http/Controllers/AiFinanceController.php:17
 * @route '/api/ai/meal-records'
 */
export const mealRecords = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mealRecords.url(options),
    method: 'get',
})

mealRecords.definition = {
    methods: ["get","head"],
    url: '/api/ai/meal-records',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AiFinanceController::mealRecords
 * @see app/Http/Controllers/AiFinanceController.php:17
 * @route '/api/ai/meal-records'
 */
mealRecords.url = (options?: RouteQueryOptions) => {
    return mealRecords.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AiFinanceController::mealRecords
 * @see app/Http/Controllers/AiFinanceController.php:17
 * @route '/api/ai/meal-records'
 */
mealRecords.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mealRecords.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AiFinanceController::mealRecords
 * @see app/Http/Controllers/AiFinanceController.php:17
 * @route '/api/ai/meal-records'
 */
mealRecords.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: mealRecords.url(options),
    method: 'head',
})
const AiFinanceController = { summary, mealRecords }

export default AiFinanceController