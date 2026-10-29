import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:43
 * @route '/savings/create'
 */
const create15e04b67b5a38ea222ff0a83ca19ad1b = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create15e04b67b5a38ea222ff0a83ca19ad1b.url(options),
    method: 'get',
})

create15e04b67b5a38ea222ff0a83ca19ad1b.definition = {
    methods: ["get","head"],
    url: '/savings/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:43
 * @route '/savings/create'
 */
create15e04b67b5a38ea222ff0a83ca19ad1b.url = (options?: RouteQueryOptions) => {
    return create15e04b67b5a38ea222ff0a83ca19ad1b.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:43
 * @route '/savings/create'
 */
create15e04b67b5a38ea222ff0a83ca19ad1b.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create15e04b67b5a38ea222ff0a83ca19ad1b.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:43
 * @route '/savings/create'
 */
create15e04b67b5a38ea222ff0a83ca19ad1b.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create15e04b67b5a38ea222ff0a83ca19ad1b.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:43
 * @route '/cms/savings/create'
 */
const create7022779e2e6c3dd081bc056a8b1d42d4 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create7022779e2e6c3dd081bc056a8b1d42d4.url(options),
    method: 'get',
})

create7022779e2e6c3dd081bc056a8b1d42d4.definition = {
    methods: ["get","head"],
    url: '/cms/savings/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:43
 * @route '/cms/savings/create'
 */
create7022779e2e6c3dd081bc056a8b1d42d4.url = (options?: RouteQueryOptions) => {
    return create7022779e2e6c3dd081bc056a8b1d42d4.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:43
 * @route '/cms/savings/create'
 */
create7022779e2e6c3dd081bc056a8b1d42d4.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create7022779e2e6c3dd081bc056a8b1d42d4.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::create
 * @see app/Http/Controllers/SavingController.php:43
 * @route '/cms/savings/create'
 */
create7022779e2e6c3dd081bc056a8b1d42d4.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create7022779e2e6c3dd081bc056a8b1d42d4.url(options),
    method: 'head',
})

/**
* Multiple routes resolve to \App\Http\Controllers\SavingController::create, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `create['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const create = {
    '/savings/create': create15e04b67b5a38ea222ff0a83ca19ad1b,
    '/cms/savings/create': create7022779e2e6c3dd081bc056a8b1d42d4,
}

/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:20
 * @route '/savings'
 */
const index27852c12aa3e367b02557fe64fb8f6b5 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index27852c12aa3e367b02557fe64fb8f6b5.url(options),
    method: 'get',
})

index27852c12aa3e367b02557fe64fb8f6b5.definition = {
    methods: ["get","head"],
    url: '/savings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:20
 * @route '/savings'
 */
index27852c12aa3e367b02557fe64fb8f6b5.url = (options?: RouteQueryOptions) => {
    return index27852c12aa3e367b02557fe64fb8f6b5.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:20
 * @route '/savings'
 */
index27852c12aa3e367b02557fe64fb8f6b5.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index27852c12aa3e367b02557fe64fb8f6b5.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:20
 * @route '/savings'
 */
index27852c12aa3e367b02557fe64fb8f6b5.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index27852c12aa3e367b02557fe64fb8f6b5.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:20
 * @route '/cms/savings'
 */
const index1ce3783c1f66c37e801385d88960f7ab = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index1ce3783c1f66c37e801385d88960f7ab.url(options),
    method: 'get',
})

index1ce3783c1f66c37e801385d88960f7ab.definition = {
    methods: ["get","head"],
    url: '/cms/savings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:20
 * @route '/cms/savings'
 */
index1ce3783c1f66c37e801385d88960f7ab.url = (options?: RouteQueryOptions) => {
    return index1ce3783c1f66c37e801385d88960f7ab.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:20
 * @route '/cms/savings'
 */
index1ce3783c1f66c37e801385d88960f7ab.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index1ce3783c1f66c37e801385d88960f7ab.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::index
 * @see app/Http/Controllers/SavingController.php:20
 * @route '/cms/savings'
 */
index1ce3783c1f66c37e801385d88960f7ab.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index1ce3783c1f66c37e801385d88960f7ab.url(options),
    method: 'head',
})

/**
* Multiple routes resolve to \App\Http\Controllers\SavingController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/savings': index27852c12aa3e367b02557fe64fb8f6b5,
    '/cms/savings': index1ce3783c1f66c37e801385d88960f7ab,
}

/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:69
 * @route '/savings/{saving}/manage'
 */
const manage2e05f256e306ca8f21b7c1d9bfd7ab06 = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage2e05f256e306ca8f21b7c1d9bfd7ab06.url(args, options),
    method: 'get',
})

manage2e05f256e306ca8f21b7c1d9bfd7ab06.definition = {
    methods: ["get","head"],
    url: '/savings/{saving}/manage',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:69
 * @route '/savings/{saving}/manage'
 */
manage2e05f256e306ca8f21b7c1d9bfd7ab06.url = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return manage2e05f256e306ca8f21b7c1d9bfd7ab06.definition.url
            .replace('{saving}', parsedArgs.saving.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:69
 * @route '/savings/{saving}/manage'
 */
manage2e05f256e306ca8f21b7c1d9bfd7ab06.get = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage2e05f256e306ca8f21b7c1d9bfd7ab06.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:69
 * @route '/savings/{saving}/manage'
 */
manage2e05f256e306ca8f21b7c1d9bfd7ab06.head = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: manage2e05f256e306ca8f21b7c1d9bfd7ab06.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:69
 * @route '/cms/savings/{saving}/manage'
 */
const managec7d4152a851f0ba4e7834e06e059cbb5 = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: managec7d4152a851f0ba4e7834e06e059cbb5.url(args, options),
    method: 'get',
})

managec7d4152a851f0ba4e7834e06e059cbb5.definition = {
    methods: ["get","head"],
    url: '/cms/savings/{saving}/manage',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:69
 * @route '/cms/savings/{saving}/manage'
 */
managec7d4152a851f0ba4e7834e06e059cbb5.url = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return managec7d4152a851f0ba4e7834e06e059cbb5.definition.url
            .replace('{saving}', parsedArgs.saving.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:69
 * @route '/cms/savings/{saving}/manage'
 */
managec7d4152a851f0ba4e7834e06e059cbb5.get = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: managec7d4152a851f0ba4e7834e06e059cbb5.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::manage
 * @see app/Http/Controllers/SavingController.php:69
 * @route '/cms/savings/{saving}/manage'
 */
managec7d4152a851f0ba4e7834e06e059cbb5.head = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: managec7d4152a851f0ba4e7834e06e059cbb5.url(args, options),
    method: 'head',
})

/**
* Multiple routes resolve to \App\Http\Controllers\SavingController::manage, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `manage['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const manage = {
    '/savings/{saving}/manage': manage2e05f256e306ca8f21b7c1d9bfd7ab06,
    '/cms/savings/{saving}/manage': managec7d4152a851f0ba4e7834e06e059cbb5,
}

/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:48
 * @route '/savings/{saving}'
 */
const showd2e0a0d8aa45b4b267294ba1057cde00 = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showd2e0a0d8aa45b4b267294ba1057cde00.url(args, options),
    method: 'get',
})

showd2e0a0d8aa45b4b267294ba1057cde00.definition = {
    methods: ["get","head"],
    url: '/savings/{saving}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:48
 * @route '/savings/{saving}'
 */
showd2e0a0d8aa45b4b267294ba1057cde00.url = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return showd2e0a0d8aa45b4b267294ba1057cde00.definition.url
            .replace('{saving}', parsedArgs.saving.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:48
 * @route '/savings/{saving}'
 */
showd2e0a0d8aa45b4b267294ba1057cde00.get = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showd2e0a0d8aa45b4b267294ba1057cde00.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:48
 * @route '/savings/{saving}'
 */
showd2e0a0d8aa45b4b267294ba1057cde00.head = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showd2e0a0d8aa45b4b267294ba1057cde00.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:48
 * @route '/cms/savings/{saving}'
 */
const show3e2aa1b4197e6f4e6c7da0de8f0e293a = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show3e2aa1b4197e6f4e6c7da0de8f0e293a.url(args, options),
    method: 'get',
})

show3e2aa1b4197e6f4e6c7da0de8f0e293a.definition = {
    methods: ["get","head"],
    url: '/cms/savings/{saving}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:48
 * @route '/cms/savings/{saving}'
 */
show3e2aa1b4197e6f4e6c7da0de8f0e293a.url = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show3e2aa1b4197e6f4e6c7da0de8f0e293a.definition.url
            .replace('{saving}', parsedArgs.saving.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:48
 * @route '/cms/savings/{saving}'
 */
show3e2aa1b4197e6f4e6c7da0de8f0e293a.get = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show3e2aa1b4197e6f4e6c7da0de8f0e293a.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SavingController::show
 * @see app/Http/Controllers/SavingController.php:48
 * @route '/cms/savings/{saving}'
 */
show3e2aa1b4197e6f4e6c7da0de8f0e293a.head = (args: { saving: string | number } | [saving: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show3e2aa1b4197e6f4e6c7da0de8f0e293a.url(args, options),
    method: 'head',
})

/**
* Multiple routes resolve to \App\Http\Controllers\SavingController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/savings/{saving}': showd2e0a0d8aa45b4b267294ba1057cde00,
    '/cms/savings/{saving}': show3e2aa1b4197e6f4e6c7da0de8f0e293a,
}

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

/**
* @see \App\Http\Controllers\SavingController::reverseAllocation
 * @see app/Http/Controllers/SavingController.php:312
 * @route '/saving-logs/{log}/reverse'
 */
export const reverseAllocation = (args: { log: string | number } | [log: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reverseAllocation.url(args, options),
    method: 'post',
})

reverseAllocation.definition = {
    methods: ["post"],
    url: '/saving-logs/{log}/reverse',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SavingController::reverseAllocation
 * @see app/Http/Controllers/SavingController.php:312
 * @route '/saving-logs/{log}/reverse'
 */
reverseAllocation.url = (args: { log: string | number } | [log: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { log: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    log: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        log: args.log,
                }

    return reverseAllocation.definition.url
            .replace('{log}', parsedArgs.log.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SavingController::reverseAllocation
 * @see app/Http/Controllers/SavingController.php:312
 * @route '/saving-logs/{log}/reverse'
 */
reverseAllocation.post = (args: { log: string | number } | [log: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reverseAllocation.url(args, options),
    method: 'post',
})
const SavingController = { create, index, manage, show, store, update, allocate, release, archive, reverseAllocation }

export default SavingController