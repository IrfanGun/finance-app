import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\BudgetPlanController::fund
 * @see app/Http/Controllers/BudgetPlanController.php:171
 * @route '/budget-periods/{period}/fund'
 */
export const fund = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: fund.url(args, options),
    method: 'post',
})

fund.definition = {
    methods: ["post"],
    url: '/budget-periods/{period}/fund',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::fund
 * @see app/Http/Controllers/BudgetPlanController.php:171
 * @route '/budget-periods/{period}/fund'
 */
fund.url = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { period: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    period: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        period: args.period,
                }

    return fund.definition.url
            .replace('{period}', parsedArgs.period.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::fund
 * @see app/Http/Controllers/BudgetPlanController.php:171
 * @route '/budget-periods/{period}/fund'
 */
fund.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: fund.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::claim
 * @see app/Http/Controllers/BudgetPlanController.php:180
 * @route '/budget-periods/{period}/claim'
 */
export const claim = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: claim.url(args, options),
    method: 'post',
})

claim.definition = {
    methods: ["post"],
    url: '/budget-periods/{period}/claim',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::claim
 * @see app/Http/Controllers/BudgetPlanController.php:180
 * @route '/budget-periods/{period}/claim'
 */
claim.url = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { period: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    period: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        period: args.period,
                }

    return claim.definition.url
            .replace('{period}', parsedArgs.period.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::claim
 * @see app/Http/Controllers/BudgetPlanController.php:180
 * @route '/budget-periods/{period}/claim'
 */
claim.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: claim.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::release
 * @see app/Http/Controllers/BudgetPlanController.php:199
 * @route '/budget-periods/{period}/release'
 */
export const release = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release.url(args, options),
    method: 'post',
})

release.definition = {
    methods: ["post"],
    url: '/budget-periods/{period}/release',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::release
 * @see app/Http/Controllers/BudgetPlanController.php:199
 * @route '/budget-periods/{period}/release'
 */
release.url = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { period: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    period: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        period: args.period,
                }

    return release.definition.url
            .replace('{period}', parsedArgs.period.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::release
 * @see app/Http/Controllers/BudgetPlanController.php:199
 * @route '/budget-periods/{period}/release'
 */
release.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::close
 * @see app/Http/Controllers/BudgetPlanController.php:234
 * @route '/budget-periods/{period}/close'
 */
export const close = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

close.definition = {
    methods: ["post"],
    url: '/budget-periods/{period}/close',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::close
 * @see app/Http/Controllers/BudgetPlanController.php:234
 * @route '/budget-periods/{period}/close'
 */
close.url = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { period: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    period: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        period: args.period,
                }

    return close.definition.url
            .replace('{period}', parsedArgs.period.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::close
 * @see app/Http/Controllers/BudgetPlanController.php:234
 * @route '/budget-periods/{period}/close'
 */
close.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})
const budgetPeriods = {
    fund: Object.assign(fund, fund),
claim: Object.assign(claim, claim),
release: Object.assign(release, release),
close: Object.assign(close, close),
}

export default budgetPeriods