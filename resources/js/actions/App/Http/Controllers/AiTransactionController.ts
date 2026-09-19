import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AiTransactionController::index
 * @see app/Http/Controllers/AiTransactionController.php:13
 * @route '/api/ai/transactions'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/ai/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AiTransactionController::index
 * @see app/Http/Controllers/AiTransactionController.php:13
 * @route '/api/ai/transactions'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AiTransactionController::index
 * @see app/Http/Controllers/AiTransactionController.php:13
 * @route '/api/ai/transactions'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AiTransactionController::index
 * @see app/Http/Controllers/AiTransactionController.php:13
 * @route '/api/ai/transactions'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AiTransactionController::store
 * @see app/Http/Controllers/AiTransactionController.php:27
 * @route '/api/ai/transactions'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/ai/transactions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AiTransactionController::store
 * @see app/Http/Controllers/AiTransactionController.php:27
 * @route '/api/ai/transactions'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AiTransactionController::store
 * @see app/Http/Controllers/AiTransactionController.php:27
 * @route '/api/ai/transactions'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AiTransactionController::update
 * @see app/Http/Controllers/AiTransactionController.php:66
 * @route '/api/ai/transactions/{transaction}'
 */
export const update = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/api/ai/transactions/{transaction}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\AiTransactionController::update
 * @see app/Http/Controllers/AiTransactionController.php:66
 * @route '/api/ai/transactions/{transaction}'
 */
update.url = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    transaction: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        transaction: args.transaction,
                }

    return update.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AiTransactionController::update
 * @see app/Http/Controllers/AiTransactionController.php:66
 * @route '/api/ai/transactions/{transaction}'
 */
update.patch = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\AiTransactionController::destroy
 * @see app/Http/Controllers/AiTransactionController.php:80
 * @route '/api/ai/transactions/{transaction}'
 */
export const destroy = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/api/ai/transactions/{transaction}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\AiTransactionController::destroy
 * @see app/Http/Controllers/AiTransactionController.php:80
 * @route '/api/ai/transactions/{transaction}'
 */
destroy.url = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    transaction: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        transaction: args.transaction,
                }

    return destroy.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AiTransactionController::destroy
 * @see app/Http/Controllers/AiTransactionController.php:80
 * @route '/api/ai/transactions/{transaction}'
 */
destroy.delete = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const AiTransactionController = { index, store, update, destroy }

export default AiTransactionController