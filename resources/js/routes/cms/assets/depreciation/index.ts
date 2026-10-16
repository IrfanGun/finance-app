import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
 * @see routes/web.php:133
 * @route '/cms/assets/{asset}/depreciation-logs'
 */
export const logs = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logs.url(args, options),
    method: 'get',
})

logs.definition = {
    methods: ["get","head"],
    url: '/cms/assets/{asset}/depreciation-logs',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see routes/web.php:133
 * @route '/cms/assets/{asset}/depreciation-logs'
 */
logs.url = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return logs.definition.url
            .replace('{asset}', parsedArgs.asset.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
 * @see routes/web.php:133
 * @route '/cms/assets/{asset}/depreciation-logs'
 */
logs.get = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logs.url(args, options),
    method: 'get',
})
/**
 * @see routes/web.php:133
 * @route '/cms/assets/{asset}/depreciation-logs'
 */
logs.head = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: logs.url(args, options),
    method: 'head',
})
const depreciation = {
    logs: Object.assign(logs, logs),
}

export default depreciation