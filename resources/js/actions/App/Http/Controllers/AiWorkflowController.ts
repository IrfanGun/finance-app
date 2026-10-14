import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AiWorkflowController::cancel
 * @see app/Http/Controllers/AiWorkflowController.php:12
 * @route '/api/ai/workflows/{workflow}/cancel'
 */
export const cancel = (args: { workflow: number | { id: number } } | [workflow: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})

cancel.definition = {
    methods: ["post"],
    url: '/api/ai/workflows/{workflow}/cancel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AiWorkflowController::cancel
 * @see app/Http/Controllers/AiWorkflowController.php:12
 * @route '/api/ai/workflows/{workflow}/cancel'
 */
cancel.url = (args: { workflow: number | { id: number } } | [workflow: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { workflow: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { workflow: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    workflow: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        workflow: typeof args.workflow === 'object'
                ? args.workflow.id
                : args.workflow,
                }

    return cancel.definition.url
            .replace('{workflow}', parsedArgs.workflow.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AiWorkflowController::cancel
 * @see app/Http/Controllers/AiWorkflowController.php:12
 * @route '/api/ai/workflows/{workflow}/cancel'
 */
cancel.post = (args: { workflow: number | { id: number } } | [workflow: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})
const AiWorkflowController = { cancel }

export default AiWorkflowController