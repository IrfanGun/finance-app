import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
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
const login = {
    store: Object.assign(store, store),
}

export default login