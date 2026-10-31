import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\BudgetPlanController::reverse
 * @see app/Http/Controllers/BudgetPlanController.php:256
 * @route '/budget-claims/{claim}/reverse'
 */
export const reverse = (args: { claim: string | number } | [claim: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reverse.url(args, options),
    method: 'post',
})

reverse.definition = {
    methods: ["post"],
    url: '/budget-claims/{claim}/reverse',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BudgetPlanController::reverse
 * @see app/Http/Controllers/BudgetPlanController.php:256
 * @route '/budget-claims/{claim}/reverse'
 */
reverse.url = (args: { claim: string | number } | [claim: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return reverse.definition.url
            .replace('{claim}', parsedArgs.claim.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetPlanController::reverse
 * @see app/Http/Controllers/BudgetPlanController.php:256
 * @route '/budget-claims/{claim}/reverse'
 */
reverse.post = (args: { claim: string | number } | [claim: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reverse.url(args, options),
    method: 'post',
})
const budgetClaims = {
    reverse: Object.assign(reverse, reverse),
}

export default budgetClaims