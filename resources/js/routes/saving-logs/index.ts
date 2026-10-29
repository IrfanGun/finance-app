import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\SavingController::reverse
 * @see app/Http/Controllers/SavingController.php:312
 * @route '/saving-logs/{log}/reverse'
 */
export const reverse = (args: { log: string | number } | [log: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reverse.url(args, options),
    method: 'post',
})

reverse.definition = {
    methods: ["post"],
    url: '/saving-logs/{log}/reverse',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SavingController::reverse
 * @see app/Http/Controllers/SavingController.php:312
 * @route '/saving-logs/{log}/reverse'
 */
reverse.url = (args: { log: string | number } | [log: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { log: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    log: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        log: args.log,
                }

    return reverse.definition.url
            .replace('{log}', parsedArgs.log.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::reverse
 * @see app/Http/Controllers/SavingController.php:312
 * @route '/saving-logs/{log}/reverse'
 */
reverse.post = (args: { log: string | number } | [log: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reverse.url(args, options),
    method: 'post',
})
const savingLogs = {
    reverse: Object.assign(reverse, reverse),
}

export default savingLogs