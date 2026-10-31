import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
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
 * @see app/Http/Controllers/BudgetPlanController.php:162
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
 * @see app/Http/Controllers/BudgetPlanController.php:162
 * @route '/budget-plans/{plan}'
 */
show.get = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:162
 * @route '/budget-plans/{plan}'
 */
show.head = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})
const budgetPlans = {
    create: Object.assign(create, create),
index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
status: Object.assign(status, status),
show: Object.assign(show, show),
}

export default budgetPlans