import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:43
 * @route '/savings/create'
 */
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/savings/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:43
 * @route '/savings/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:43
 * @route '/savings/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:43
 * @route '/savings/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:20
 * @route '/savings'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/savings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:20
 * @route '/savings'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:20
 * @route '/savings'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:20
 * @route '/savings'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:69
 * @route '/savings/{saving}/manage'
 */
export const manage = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage.url(args, options),
    method: 'get',
})

manage.definition = {
    methods: ["get","head"],
    url: '/savings/{saving}/manage',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:69
 * @route '/savings/{saving}/manage'
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
 * @see app/Http/Controllers/SavingController.php:69
 * @route '/savings/{saving}/manage'
 */
manage.get = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:69
 * @route '/savings/{saving}/manage'
 */
manage.head = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: manage.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:48
 * @route '/savings/{saving}'
 */
export const show = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/savings/{saving}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:48
 * @route '/savings/{saving}'
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
 * @see app/Http/Controllers/SavingController.php:48
 * @route '/savings/{saving}'
 */
show.get = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:48
 * @route '/savings/{saving}'
 */
show.head = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SavingController::store
 * @see app/Http/Controllers/SavingController.php:200
 * @route '/savings'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/savings',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SavingController::store
 * @see app/Http/Controllers/SavingController.php:200
 * @route '/savings'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::store
 * @see app/Http/Controllers/SavingController.php:200
 * @route '/savings'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SavingController::update
 * @see app/Http/Controllers/SavingController.php:212
 * @route '/savings/{saving}'
 */
export const update = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/savings/{saving}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\SavingController::update
 * @see app/Http/Controllers/SavingController.php:212
 * @route '/savings/{saving}'
 */
update.url = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{saving}', parsedArgs.saving.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::update
 * @see app/Http/Controllers/SavingController.php:212
 * @route '/savings/{saving}'
 */
update.patch = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\SavingController::allocate
 * @see app/Http/Controllers/SavingController.php:242
 * @route '/savings/{saving}/allocate'
 */
export const allocate = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: allocate.url(args, options),
    method: 'post',
})

allocate.definition = {
    methods: ["post"],
    url: '/savings/{saving}/allocate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SavingController::allocate
 * @see app/Http/Controllers/SavingController.php:242
 * @route '/savings/{saving}/allocate'
 */
allocate.url = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return allocate.definition.url
            .replace('{saving}', parsedArgs.saving.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::allocate
 * @see app/Http/Controllers/SavingController.php:242
 * @route '/savings/{saving}/allocate'
 */
allocate.post = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: allocate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SavingController::release
 * @see app/Http/Controllers/SavingController.php:275
 * @route '/savings/{saving}/release'
 */
export const release = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release.url(args, options),
    method: 'post',
})

release.definition = {
    methods: ["post"],
    url: '/savings/{saving}/release',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SavingController::release
 * @see app/Http/Controllers/SavingController.php:275
 * @route '/savings/{saving}/release'
 */
release.url = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return release.definition.url
            .replace('{saving}', parsedArgs.saving.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::release
 * @see app/Http/Controllers/SavingController.php:275
 * @route '/savings/{saving}/release'
 */
release.post = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: release.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SavingController::archive
 * @see app/Http/Controllers/SavingController.php:295
 * @route '/savings/{saving}/archive'
 */
export const archive = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: archive.url(args, options),
    method: 'post',
})

archive.definition = {
    methods: ["post"],
    url: '/savings/{saving}/archive',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SavingController::archive
 * @see app/Http/Controllers/SavingController.php:295
 * @route '/savings/{saving}/archive'
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
 * @see app/Http/Controllers/SavingController.php:295
 * @route '/savings/{saving}/archive'
 */
archive.post = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: archive.url(args, options),
    method: 'post',
})
const savings = {
    create: Object.assign(create, create),
index: Object.assign(index, index),
manage: Object.assign(manage, manage),
show: Object.assign(show, show),
store: Object.assign(store, store),
update: Object.assign(update, update),
allocate: Object.assign(allocate, allocate),
release: Object.assign(release, release),
archive: Object.assign(archive, archive),
}

export default savings