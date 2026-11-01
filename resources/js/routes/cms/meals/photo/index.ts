import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MealRecordController::store
 * @see app/Http/Controllers/MealRecordController.php:92
 * @route '/cms/meals/{meal}/photo'
 */
export const store = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cms/meals/{meal}/photo',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MealRecordController::store
 * @see app/Http/Controllers/MealRecordController.php:92
 * @route '/cms/meals/{meal}/photo'
 */
store.url = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meal: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meal: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meal: args.meal,
                }

    return store.definition.url
            .replace('{meal}', parsedArgs.meal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::store
 * @see app/Http/Controllers/MealRecordController.php:92
 * @route '/cms/meals/{meal}/photo'
 */
store.post = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})