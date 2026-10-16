import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
 * @see routes/web.php:151
 * @route '/cms/profile'
 */
export const edit = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/cms/profile',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see routes/web.php:151
 * @route '/cms/profile'
 */
edit.url = (options?: RouteQueryOptions) => {
    return edit.definition.url + queryParams(options)
}

/**
 * @see routes/web.php:151
 * @route '/cms/profile'
 */
edit.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})
/**
 * @see routes/web.php:151
 * @route '/cms/profile'
 */
edit.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(options),
    method: 'head',
})
const profile = {
    edit: Object.assign(edit, edit),
}

export default profile