import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/cms/budget-plans/create'
 */
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/cms/budget-plans/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/cms/budget-plans/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/cms/budget-plans/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BudgetPlanController::create
 * @see app/Http/Controllers/BudgetPlanController.php:25
 * @route '/cms/budget-plans/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/cms/budget-plans'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cms/budget-plans',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/cms/budget-plans'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/cms/budget-plans'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BudgetPlanController::index
 * @see app/Http/Controllers/BudgetPlanController.php:20
 * @route '/cms/budget-plans'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:139
 * @route '/cms/budget-plans/{plan}'
 */
export const show = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/cms/budget-plans/{plan}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:139
 * @route '/cms/budget-plans/{plan}'
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
 * @see app/Http/Controllers/BudgetPlanController.php:139
 * @route '/cms/budget-plans/{plan}'
 */
show.get = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BudgetPlanController::show
 * @see app/Http/Controllers/BudgetPlanController.php:139
 * @route '/cms/budget-plans/{plan}'
 */
show.head = (args: { plan: string | number } | [plan: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})
const budgetPlans = {
    create: Object.assign(create, create),
index: Object.assign(index, index),
show: Object.assign(show, show),
}

export default budgetPlans