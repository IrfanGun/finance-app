import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
 * @see routes/web.php:152
 * @route '/cms/receipt/scan'
 */
export const scan = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: scan.url(options),
    method: 'get',
})

scan.definition = {
    methods: ["get","head"],
    url: '/cms/receipt/scan',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see routes/web.php:152
 * @route '/cms/receipt/scan'
 */
scan.url = (options?: RouteQueryOptions) => {
    return scan.definition.url + queryParams(options)
}

/**
 * @see routes/web.php:152
 * @route '/cms/receipt/scan'
 */
scan.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: scan.url(options),
    method: 'get',
})
/**
 * @see routes/web.php:152
 * @route '/cms/receipt/scan'
 */
scan.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: scan.url(options),
    method: 'head',
})
const receipt = {
    scan: Object.assign(scan, scan),
}

export default receipt