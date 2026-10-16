import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::destroy
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:32
 * @route '/cms/logout'
 */
export const destroy = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

destroy.definition = {
    methods: ["post"],
    url: '/cms/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::destroy
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:32
 * @route '/cms/logout'
 */
destroy.url = (options?: RouteQueryOptions) => {
    return destroy.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::destroy
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:32
 * @route '/cms/logout'
 */
destroy.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::create
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:16
 * @route '/cms/login'
 */
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/cms/login',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::create
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:16
 * @route '/cms/login'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::create
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:16
 * @route '/cms/login'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::create
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:16
 * @route '/cms/login'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::store
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:24
 * @route '/cms/login'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cms/login',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::store
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:24
 * @route '/cms/login'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::store
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:24
 * @route '/cms/login'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})
const AuthenticatedSessionController = { destroy, create, store }

export default AuthenticatedSessionController