import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AiMemoryController::index
 * @see app/Http/Controllers/AiMemoryController.php:10
 * @route '/api/ai/memories'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/ai/memories',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AiMemoryController::index
 * @see app/Http/Controllers/AiMemoryController.php:10
 * @route '/api/ai/memories'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AiMemoryController::index
 * @see app/Http/Controllers/AiMemoryController.php:10
 * @route '/api/ai/memories'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AiMemoryController::index
 * @see app/Http/Controllers/AiMemoryController.php:10
 * @route '/api/ai/memories'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AiMemoryController::destroy
 * @see app/Http/Controllers/AiMemoryController.php:24
 * @route '/api/ai/memories/{memory}'
 */
export const destroy = (args: { memory: string | number } | [memory: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/api/ai/memories/{memory}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\AiMemoryController::destroy
 * @see app/Http/Controllers/AiMemoryController.php:24
 * @route '/api/ai/memories/{memory}'
 */
destroy.url = (args: { memory: string | number } | [memory: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { memory: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    memory: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        memory: args.memory,
                }

    return destroy.definition.url
            .replace('{memory}', parsedArgs.memory.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AiMemoryController::destroy
 * @see app/Http/Controllers/AiMemoryController.php:24
 * @route '/api/ai/memories/{memory}'
 */
destroy.delete = (args: { memory: string | number } | [memory: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const AiMemoryController = { index, destroy }

export default AiMemoryController