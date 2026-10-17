import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MealRecordController::index
 * @see app/Http/Controllers/MealRecordController.php:15
 * @route '/meals'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/meals',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MealRecordController::index
 * @see app/Http/Controllers/MealRecordController.php:15
 * @route '/meals'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::index
 * @see app/Http/Controllers/MealRecordController.php:15
 * @route '/meals'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MealRecordController::index
 * @see app/Http/Controllers/MealRecordController.php:15
 * @route '/meals'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MealRecordController::store
 * @see app/Http/Controllers/MealRecordController.php:42
 * @route '/meals'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/meals',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MealRecordController::store
 * @see app/Http/Controllers/MealRecordController.php:42
 * @route '/meals'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::store
 * @see app/Http/Controllers/MealRecordController.php:42
 * @route '/meals'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MealRecordController::show
 * @see app/Http/Controllers/MealRecordController.php:51
 * @route '/meals/{meal}'
 */
export const show = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/meals/{meal}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MealRecordController::show
 * @see app/Http/Controllers/MealRecordController.php:51
 * @route '/meals/{meal}'
 */
show.url = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{meal}', parsedArgs.meal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::show
 * @see app/Http/Controllers/MealRecordController.php:51
 * @route '/meals/{meal}'
 */
show.get = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MealRecordController::show
 * @see app/Http/Controllers/MealRecordController.php:51
 * @route '/meals/{meal}'
 */
show.head = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MealRecordController::update
 * @see app/Http/Controllers/MealRecordController.php:63
 * @route '/meals/{meal}'
 */
export const update = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/meals/{meal}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\MealRecordController::update
 * @see app/Http/Controllers/MealRecordController.php:63
 * @route '/meals/{meal}'
 */
update.url = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{meal}', parsedArgs.meal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::update
 * @see app/Http/Controllers/MealRecordController.php:63
 * @route '/meals/{meal}'
 */
update.put = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})
/**
* @see \App\Http\Controllers\MealRecordController::update
 * @see app/Http/Controllers/MealRecordController.php:63
 * @route '/meals/{meal}'
 */
update.patch = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\MealRecordController::destroy
 * @see app/Http/Controllers/MealRecordController.php:74
 * @route '/meals/{meal}'
 */
export const destroy = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/meals/{meal}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\MealRecordController::destroy
 * @see app/Http/Controllers/MealRecordController.php:74
 * @route '/meals/{meal}'
 */
destroy.url = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return destroy.definition.url
            .replace('{meal}', parsedArgs.meal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::destroy
 * @see app/Http/Controllers/MealRecordController.php:74
 * @route '/meals/{meal}'
 */
destroy.delete = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const MealRecordController = { index, store, show, update, destroy }

export default MealRecordController