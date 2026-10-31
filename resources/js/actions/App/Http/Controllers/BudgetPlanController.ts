import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/budget-plans/create'
 */
const create9f812e099206bcfce28397298b9db8c8 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create9f812e099206bcfce28397298b9db8c8.url(options),
    method: 'get',
})

create9f812e099206bcfce28397298b9db8c8.definition = {
    methods: ["get","head"],
    url: '/budget-plans/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/budget-plans/create'
 */
create9f812e099206bcfce28397298b9db8c8.url = (options?: RouteQueryOptions) => {
    return create9f812e099206bcfce28397298b9db8c8.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/budget-plans/create'
 */
create9f812e099206bcfce28397298b9db8c8.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create9f812e099206bcfce28397298b9db8c8.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/budget-plans/create'
 */
create9f812e099206bcfce28397298b9db8c8.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create9f812e099206bcfce28397298b9db8c8.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/cms/budget-plans/create'
 */
const createa2712ce8f1ade9f6601c40f25cb3096b = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createa2712ce8f1ade9f6601c40f25cb3096b.url(options),
    method: 'get',
})

createa2712ce8f1ade9f6601c40f25cb3096b.definition = {
    methods: ["get","head"],
    url: '/cms/budget-plans/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/cms/budget-plans/create'
 */
createa2712ce8f1ade9f6601c40f25cb3096b.url = (options?: RouteQueryOptions) => {
    return createa2712ce8f1ade9f6601c40f25cb3096b.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/cms/budget-plans/create'
 */
createa2712ce8f1ade9f6601c40f25cb3096b.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createa2712ce8f1ade9f6601c40f25cb3096b.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/cms/budget-plans/create'
 */
createa2712ce8f1ade9f6601c40f25cb3096b.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: createa2712ce8f1ade9f6601c40f25cb3096b.url(options),
    method: 'head',
})

/**
* Multiple routes resolve to \App\Http\Controllers\BudgetPlanController::create, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `create['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const create = {
    '/budget-plans/create': create9f812e099206bcfce28397298b9db8c8,
    '/cms/budget-plans/create': createa2712ce8f1ade9f6601c40f25cb3096b,
}

/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/budget-plans'
 */
const index91ad3cd02975d1afbfe48bd2302e3d01 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index91ad3cd02975d1afbfe48bd2302e3d01.url(options),
    method: 'get',
})

index91ad3cd02975d1afbfe48bd2302e3d01.definition = {
    methods: ["get","head"],
    url: '/budget-plans',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/budget-plans'
 */
index91ad3cd02975d1afbfe48bd2302e3d01.url = (options?: RouteQueryOptions) => {
    return index91ad3cd02975d1afbfe48bd2302e3d01.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/budget-plans'
 */
index91ad3cd02975d1afbfe48bd2302e3d01.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index91ad3cd02975d1afbfe48bd2302e3d01.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/budget-plans'
 */
index91ad3cd02975d1afbfe48bd2302e3d01.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index91ad3cd02975d1afbfe48bd2302e3d01.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/cms/budget-plans'
 */
const index429faaf47b74a60f647d972d5710bac9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index429faaf47b74a60f647d972d5710bac9.url(options),
    method: 'get',
})

index429faaf47b74a60f647d972d5710bac9.definition = {
    methods: ["get","head"],
    url: '/cms/budget-plans',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/cms/budget-plans'
 */
index429faaf47b74a60f647d972d5710bac9.url = (options?: RouteQueryOptions) => {
    return index429faaf47b74a60f647d972d5710bac9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/cms/budget-plans'
 */
index429faaf47b74a60f647d972d5710bac9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index429faaf47b74a60f647d972d5710bac9.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/cms/budget-plans'
 */
index429faaf47b74a60f647d972d5710bac9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index429faaf47b74a60f647d972d5710bac9.url(options),
    method: 'head',
})

/**
* Multiple routes resolve to \App\Http\Controllers\BudgetPlanController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/budget-plans': index91ad3cd02975d1afbfe48bd2302e3d01,
    '/cms/budget-plans': index429faaf47b74a60f647d972d5710bac9,
}

/**
* @see \App\Http\Controllers\BudgetPlanController::store
 * @see app/Http/Controllers/BudgetPlanController.php:170
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
 * @see app/Http/Controllers/BudgetPlanController.php:170
 * @route '/budget-plans'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::store
 * @see app/Http/Controllers/BudgetPlanController.php:170
 * @route '/budget-plans'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::update
 * @see app/Http/Controllers/BudgetPlanController.php:182
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
 * @see app/Http/Controllers/BudgetPlanController.php:182
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
 * @see app/Http/Controllers/BudgetPlanController.php:182
 * @route '/budget-plans/{plan}'
 */
update.patch = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::status
 * @see app/Http/Controllers/BudgetPlanController.php:200
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
 * @see app/Http/Controllers/BudgetPlanController.php:200
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
 * @see app/Http/Controllers/BudgetPlanController.php:200
 * @route '/budget-plans/{plan}/status'
 */
status.post = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: status.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:162
 * @route '/budget-plans/{plan}'
 */
const show279acf29e76bc124ed6a09be439f7910 = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show279acf29e76bc124ed6a09be439f7910.url(args, options),
    method: 'get',
})

show279acf29e76bc124ed6a09be439f7910.definition = {
    methods: ["get","head"],
    url: '/budget-plans/{plan}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:162
 * @route '/budget-plans/{plan}'
 */
show279acf29e76bc124ed6a09be439f7910.url = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show279acf29e76bc124ed6a09be439f7910.definition.url
            .replace('{plan}', parsedArgs.plan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:162
 * @route '/budget-plans/{plan}'
 */
show279acf29e76bc124ed6a09be439f7910.get = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show279acf29e76bc124ed6a09be439f7910.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:162
 * @route '/budget-plans/{plan}'
 */
show279acf29e76bc124ed6a09be439f7910.head = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show279acf29e76bc124ed6a09be439f7910.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:162
 * @route '/cms/budget-plans/{plan}'
 */
const showdbcc2d6ab61e2367983bd87b9a9a9906 = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showdbcc2d6ab61e2367983bd87b9a9a9906.url(args, options),
    method: 'get',
})

showdbcc2d6ab61e2367983bd87b9a9a9906.definition = {
    methods: ["get","head"],
    url: '/cms/budget-plans/{plan}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:162
 * @route '/cms/budget-plans/{plan}'
 */
showdbcc2d6ab61e2367983bd87b9a9a9906.url = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return showdbcc2d6ab61e2367983bd87b9a9a9906.definition.url
            .replace('{plan}', parsedArgs.plan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:162
 * @route '/cms/budget-plans/{plan}'
 */
showdbcc2d6ab61e2367983bd87b9a9a9906.get = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showdbcc2d6ab61e2367983bd87b9a9a9906.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:162
 * @route '/cms/budget-plans/{plan}'
 */
showdbcc2d6ab61e2367983bd87b9a9a9906.head = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showdbcc2d6ab61e2367983bd87b9a9a9906.url(args, options),
    method: 'head',
})

/**
* Multiple routes resolve to \App\Http\Controllers\BudgetPlanController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/budget-plans/{plan}': show279acf29e76bc124ed6a09be439f7910,
    '/cms/budget-plans/{plan}': showdbcc2d6ab61e2367983bd87b9a9a9906,
}

/**
* @see \App\Http\Controllers\BudgetPlanController::fund
 * @see app/Http/Controllers/BudgetPlanController.php:211
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
 * @see app/Http/Controllers/BudgetPlanController.php:211
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
 * @see app/Http/Controllers/BudgetPlanController.php:211
 * @route '/budget-periods/{period}/fund'
 */
fund.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: fund.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::claim
 * @see app/Http/Controllers/BudgetPlanController.php:220
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
 * @see app/Http/Controllers/BudgetPlanController.php:220
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
 * @see app/Http/Controllers/BudgetPlanController.php:220
 * @route '/budget-periods/{period}/claim'
 */
claim.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: claim.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::reverseClaim
 * @see app/Http/Controllers/BudgetPlanController.php:256
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
 * @see app/Http/Controllers/BudgetPlanController.php:256
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
 * @see app/Http/Controllers/BudgetPlanController.php:256
 * @route '/budget-claims/{claim}/reverse'
 */
reverseClaim.post = (args: { claim: string | number } | [claim: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reverseClaim.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::release
 * @see app/Http/Controllers/BudgetPlanController.php:239
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
 * @see app/Http/Controllers/BudgetPlanController.php:239
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
 * @see app/Http/Controllers/BudgetPlanController.php:239
 * @route '/budget-periods/{period}/release'
 */
release.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::close
 * @see app/Http/Controllers/BudgetPlanController.php:274
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
 * @see app/Http/Controllers/BudgetPlanController.php:274
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
 * @see app/Http/Controllers/BudgetPlanController.php:274
 * @route '/budget-periods/{period}/close'
 */
close.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::destroy
 * @see app/Http/Controllers/BudgetPlanController.php:193
 * @route '/cms/budget-plans/{plan}'
 */
export const destroy = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/cms/budget-plans/{plan}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::destroy
 * @see app/Http/Controllers/BudgetPlanController.php:193
 * @route '/cms/budget-plans/{plan}'
 */
destroy.url = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return destroy.definition.url
            .replace('{plan}', parsedArgs.plan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::destroy
 * @see app/Http/Controllers/BudgetPlanController.php:193
 * @route '/cms/budget-plans/{plan}'
 */
destroy.delete = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const BudgetPlanController = { create, index, store, update, status, show, fund, claim, reverseClaim, release, close, destroy }

export default BudgetPlanController