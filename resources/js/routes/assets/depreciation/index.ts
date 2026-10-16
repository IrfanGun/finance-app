import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\AssetDepreciationController::update
 * @see app/Http/Controllers/AssetDepreciationController.php:16
 * @route '/assets/{asset}/depreciation'
 */
export const update = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/assets/{asset}/depreciation',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\AssetDepreciationController::update
 * @see app/Http/Controllers/AssetDepreciationController.php:16
 * @route '/assets/{asset}/depreciation'
 */
update.url = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{asset}', parsedArgs.asset.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AssetDepreciationController::update
 * @see app/Http/Controllers/AssetDepreciationController.php:16
 * @route '/assets/{asset}/depreciation'
 */
update.put = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\AssetDepreciationController::status
 * @see app/Http/Controllers/AssetDepreciationController.php:28
 * @route '/assets/{asset}/depreciation/status'
 */
export const status = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

status.definition = {
    methods: ["patch"],
    url: '/assets/{asset}/depreciation/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\AssetDepreciationController::status
 * @see app/Http/Controllers/AssetDepreciationController.php:28
 * @route '/assets/{asset}/depreciation/status'
 */
status.url = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return status.definition.url
            .replace('{asset}', parsedArgs.asset.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AssetDepreciationController::status
 * @see app/Http/Controllers/AssetDepreciationController.php:28
 * @route '/assets/{asset}/depreciation/status'
 */
status.patch = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\AssetDepreciationController::logs
 * @see app/Http/Controllers/AssetDepreciationController.php:43
 * @route '/assets/{asset}/depreciation-logs'
 */
export const logs = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logs.url(args, options),
    method: 'get',
})

logs.definition = {
    methods: ["get","head"],
    url: '/assets/{asset}/depreciation-logs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AssetDepreciationController::logs
 * @see app/Http/Controllers/AssetDepreciationController.php:43
 * @route '/assets/{asset}/depreciation-logs'
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
* @see \App\Http\Controllers\AssetDepreciationController::logs
 * @see app/Http/Controllers/AssetDepreciationController.php:43
 * @route '/assets/{asset}/depreciation-logs'
 */
logs.get = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logs.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AssetDepreciationController::logs
 * @see app/Http/Controllers/AssetDepreciationController.php:43
 * @route '/assets/{asset}/depreciation-logs'
 */
logs.head = (args: { asset: string | number } | [asset: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: logs.url(args, options),
    method: 'head',
})
const depreciation = {
    update: Object.assign(update, update),
status: Object.assign(status, status),
logs: Object.assign(logs, logs),
}

export default depreciation