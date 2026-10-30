import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
 * @see routes/web/cms.php:178
 * @route '/cm/recurring'
 */
export const recurring = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recurring.url(options),
    method: 'get',
})

recurring.definition = {
    methods: ["get","head"],
    url: '/cm/recurring',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see routes/web/cms.php:178
 * @route '/cm/recurring'
 */
recurring.url = (options?: RouteQueryOptions) => {
    return recurring.definition.url + queryParams(options)
}

/**
 * @see routes/web/cms.php:178
 * @route '/cm/recurring'
 */
recurring.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recurring.url(options),
    method: 'get',
})
/**
 * @see routes/web/cms.php:178
 * @route '/cm/recurring'
 */
recurring.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: recurring.url(options),
    method: 'head',
})
const cm = {
    recurring: Object.assign(recurring, recurring),
}

export default cm