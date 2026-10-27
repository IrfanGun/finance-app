import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/budget-plans/create'
 */
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/budget-plans/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/budget-plans/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/budget-plans/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/budget-plans/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/budget-plans'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/budget-plans',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/budget-plans'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/budget-plans'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/budget-plans'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::store
 * @see app/Http/Controllers/BudgetPlanController.php:146
 * @route '/budget-plans'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/budget-plans',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::store
 * @see app/Http/Controllers/BudgetPlanController.php:146
 * @route '/budget-plans'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::store
 * @see app/Http/Controllers/BudgetPlanController.php:146
 * @route '/budget-plans'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::update
 * @see app/Http/Controllers/BudgetPlanController.php:153
 * @route '/budget-plans/{plan}'
 */
export const update = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/budget-plans/{plan}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::update
 * @see app/Http/Controllers/BudgetPlanController.php:153
 * @route '/budget-plans/{plan}'
 */
update.url = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { plan: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    plan: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        plan: args.plan,
                }

    return update.definition.url
            .replace('{plan}', parsedArgs.plan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::update
 * @see app/Http/Controllers/BudgetPlanController.php:153
 * @route '/budget-plans/{plan}'
 */
update.patch = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::status
 * @see app/Http/Controllers/BudgetPlanController.php:164
 * @route '/budget-plans/{plan}/status'
 */
export const status = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: status.url(args, options),
    method: 'post',
})

status.definition = {
    methods: ["post"],
    url: '/budget-plans/{plan}/status',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::status
 * @see app/Http/Controllers/BudgetPlanController.php:164
 * @route '/budget-plans/{plan}/status'
 */
status.url = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { plan: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    plan: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        plan: args.plan,
                }

    return status.definition.url
            .replace('{plan}', parsedArgs.plan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::status
 * @see app/Http/Controllers/BudgetPlanController.php:164
 * @route '/budget-plans/{plan}/status'
 */
status.post = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: status.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:138
 * @route '/budget-plans/{plan}'
 */
export const show = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/budget-plans/{plan}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:138
 * @route '/budget-plans/{plan}'
 */
show.url = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { plan: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    plan: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        plan: args.plan,
                }

    return show.definition.url
            .replace('{plan}', parsedArgs.plan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:138
 * @route '/budget-plans/{plan}'
 */
show.get = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:138
 * @route '/budget-plans/{plan}'
 */
show.head = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::fund
 * @see app/Http/Controllers/BudgetPlanController.php:175
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
 * @see app/Http/Controllers/BudgetPlanController.php:175
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
 * @see app/Http/Controllers/BudgetPlanController.php:175
 * @route '/budget-periods/{period}/fund'
 */
fund.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: fund.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::claim
 * @see app/Http/Controllers/BudgetPlanController.php:184
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
 * @see app/Http/Controllers/BudgetPlanController.php:184
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
 * @see app/Http/Controllers/BudgetPlanController.php:184
 * @route '/budget-periods/{period}/claim'
 */
claim.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: claim.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::reverseClaim
 * @see app/Http/Controllers/BudgetPlanController.php:220
 * @route '/budget-claims/{claim}/reverse'
 */
export const reverseClaim = (args: { claim: string | number } | [claim: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reverseClaim.url(args, options),
    method: 'post',
})

reverseClaim.definition = {
    methods: ["post"],
    url: '/budget-claims/{claim}/reverse',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::reverseClaim
 * @see app/Http/Controllers/BudgetPlanController.php:220
 * @route '/budget-claims/{claim}/reverse'
 */
reverseClaim.url = (args: { claim: string | number } | [claim: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { claim: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    claim: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        claim: args.claim,
                }

    return reverseClaim.definition.url
            .replace('{claim}', parsedArgs.claim.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::reverseClaim
 * @see app/Http/Controllers/BudgetPlanController.php:220
 * @route '/budget-claims/{claim}/reverse'
 */
reverseClaim.post = (args: { claim: string | number } | [claim: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reverseClaim.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::release
 * @see app/Http/Controllers/BudgetPlanController.php:203
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
 * @see app/Http/Controllers/BudgetPlanController.php:203
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
 * @see app/Http/Controllers/BudgetPlanController.php:203
 * @route '/budget-periods/{period}/release'
 */
release.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::close
 * @see app/Http/Controllers/BudgetPlanController.php:238
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
 * @see app/Http/Controllers/BudgetPlanController.php:238
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
 * @see app/Http/Controllers/BudgetPlanController.php:238
 * @route '/budget-periods/{period}/close'
 */
close.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})
const BudgetPlanController = { create, index, store, update, status, show, fund, claim, reverseClaim, release, close }

export default BudgetPlanController