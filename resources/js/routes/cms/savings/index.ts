import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:44
 * @route '/cms/savings/create'
 */
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/cms/savings/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:44
 * @route '/cms/savings/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:44
 * @route '/cms/savings/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:44
 * @route '/cms/savings/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:21
 * @route '/cms/savings'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cms/savings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:21
 * @route '/cms/savings'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:21
 * @route '/cms/savings'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:21
 * @route '/cms/savings'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SavingController::archive
 * @see app/Http/Controllers/SavingController.php:296
 * @route '/cms/savings/{saving}/archive'
 */
export const archive = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: archive.url(args, options),
    method: 'post',
})

archive.definition = {
    methods: ["post"],
    url: '/cms/savings/{saving}/archive',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SavingController::archive
 * @see app/Http/Controllers/SavingController.php:296
 * @route '/cms/savings/{saving}/archive'
 */
archive.url = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { saving: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    saving: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        saving: args.saving,
                }

    return archive.definition.url
            .replace('{saving}', parsedArgs.saving.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::archive
 * @see app/Http/Controllers/SavingController.php:296
 * @route '/cms/savings/{saving}/archive'
 */
archive.post = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: archive.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SavingController::unarchive
 * @see app/Http/Controllers/SavingController.php:313
 * @route '/cms/savings/{saving}/unarchive'
 */
export const unarchive = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unarchive.url(args, options),
    method: 'post',
})

unarchive.definition = {
    methods: ["post"],
    url: '/cms/savings/{saving}/unarchive',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SavingController::unarchive
 * @see app/Http/Controllers/SavingController.php:313
 * @route '/cms/savings/{saving}/unarchive'
 */
unarchive.url = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { saving: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    saving: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        saving: args.saving,
                }

    return unarchive.definition.url
            .replace('{saving}', parsedArgs.saving.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::unarchive
 * @see app/Http/Controllers/SavingController.php:313
 * @route '/cms/savings/{saving}/unarchive'
 */
unarchive.post = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unarchive.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SavingController::destroy
 * @see app/Http/Controllers/SavingController.php:328
 * @route '/cms/savings/{saving}'
 */
export const destroy = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/cms/savings/{saving}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\SavingController::destroy
 * @see app/Http/Controllers/SavingController.php:328
 * @route '/cms/savings/{saving}'
 */
destroy.url = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { saving: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    saving: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        saving: args.saving,
                }

    return destroy.definition.url
            .replace('{saving}', parsedArgs.saving.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::destroy
 * @see app/Http/Controllers/SavingController.php:328
 * @route '/cms/savings/{saving}'
 */
destroy.delete = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:70
 * @route '/cms/savings/{saving}/manage'
 */
export const manage = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage.url(args, options),
    method: 'get',
})

manage.definition = {
    methods: ["get","head"],
    url: '/cms/savings/{saving}/manage',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:70
 * @route '/cms/savings/{saving}/manage'
 */
manage.url = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { saving: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    saving: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        saving: args.saving,
                }

    return manage.definition.url
            .replace('{saving}', parsedArgs.saving.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:70
 * @route '/cms/savings/{saving}/manage'
 */
manage.get = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:70
 * @route '/cms/savings/{saving}/manage'
 */
manage.head = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: manage.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:49
 * @route '/cms/savings/{saving}'
 */
export const show = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/cms/savings/{saving}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:49
 * @route '/cms/savings/{saving}'
 */
show.url = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { saving: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    saving: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        saving: args.saving,
                }

    return show.definition.url
            .replace('{saving}', parsedArgs.saving.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:49
 * @route '/cms/savings/{saving}'
 */
show.get = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:49
 * @route '/cms/savings/{saving}'
 */
show.head = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})
const savings = {
    create: Object.assign(create, create),
index: Object.assign(index, index),
archive: Object.assign(archive, archive),
unarchive: Object.assign(unarchive, unarchive),
destroy: Object.assign(destroy, destroy),
manage: Object.assign(manage, manage),
show: Object.assign(show, show),
}

export default savings