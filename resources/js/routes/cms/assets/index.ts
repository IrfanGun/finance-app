import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
import depreciation from './depreciation'
/**
 * @see routes/web/cms.php:82
 * @route '/cms/assets'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cms/assets',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see routes/web/cms.php:82
 * @route '/cms/assets'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
 * @see routes/web/cms.php:82
 * @route '/cms/assets'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
 * @see routes/web/cms.php:82
 * @route '/cms/assets'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
 * @see routes/web/cms.php:90
 * @route '/cms/assets/{asset}'
 */
export const show = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/cms/assets/{asset}',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see routes/web/cms.php:90
 * @route '/cms/assets/{asset}'
 */
show.url = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { asset: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    asset: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        asset: args.asset,
                }

    return show.definition.url
            .replace('{asset}', parsedArgs.asset.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
 * @see routes/web/cms.php:90
 * @route '/cms/assets/{asset}'
 */
show.get = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
 * @see routes/web/cms.php:90
 * @route '/cms/assets/{asset}'
 */
show.head = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
 * @see routes/web/cms.php:99
 * @route '/cms/assets/{asset}/edit'
 */
export const edit = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/cms/assets/{asset}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see routes/web/cms.php:99
 * @route '/cms/assets/{asset}/edit'
 */
edit.url = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { asset: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    asset: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        asset: args.asset,
                }

    return edit.definition.url
            .replace('{asset}', parsedArgs.asset.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
 * @see routes/web/cms.php:99
 * @route '/cms/assets/{asset}/edit'
 */
edit.get = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})
/**
 * @see routes/web/cms.php:99
 * @route '/cms/assets/{asset}/edit'
 */
edit.head = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})
const assets = {
    index: Object.assign(index, index),
show: Object.assign(show, show),
edit: Object.assign(edit, edit),
depreciation: Object.assign(depreciation, depreciation),
}

export default assets