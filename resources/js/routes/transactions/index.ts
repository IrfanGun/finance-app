import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\TransactionController::index
 * @see app/Http/Controllers/TransactionController.php:14
 * @route '/transactions'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TransactionController::index
 * @see app/Http/Controllers/TransactionController.php:14
 * @route '/transactions'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::index
 * @see app/Http/Controllers/TransactionController.php:14
 * @route '/transactions'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\TransactionController::index
 * @see app/Http/Controllers/TransactionController.php:14
 * @route '/transactions'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TransactionController::store
 * @see app/Http/Controllers/TransactionController.php:51
 * @route '/transactions'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/transactions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TransactionController::store
 * @see app/Http/Controllers/TransactionController.php:51
 * @route '/transactions'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::store
 * @see app/Http/Controllers/TransactionController.php:51
 * @route '/transactions'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::voidMethod
 * @see app/Http/Controllers/TransactionController.php:69
 * @route '/transactions/{transaction}/void'
 */
export const voidMethod = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: voidMethod.url(args, options),
    method: 'post',
})

voidMethod.definition = {
    methods: ["post"],
    url: '/transactions/{transaction}/void',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TransactionController::voidMethod
 * @see app/Http/Controllers/TransactionController.php:69
 * @route '/transactions/{transaction}/void'
 */
voidMethod.url = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return voidMethod.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::voidMethod
 * @see app/Http/Controllers/TransactionController.php:69
 * @route '/transactions/{transaction}/void'
 */
voidMethod.post = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: voidMethod.url(args, options),
    method: 'post',
})
const transactions = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
void: Object.assign(voidMethod, voidMethod),
}

export default transactions