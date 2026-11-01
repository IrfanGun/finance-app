import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MealRecordController::index
 * @see app/Http/Controllers/MealRecordController.php:18
 * @route '/meals'
 */
const index57b3a6105afb17387b56dc33f17ab3eb = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index57b3a6105afb17387b56dc33f17ab3eb.url(options),
    method: 'get',
})

index57b3a6105afb17387b56dc33f17ab3eb.definition = {
    methods: ["get","head"],
    url: '/meals',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MealRecordController::index
 * @see app/Http/Controllers/MealRecordController.php:18
 * @route '/meals'
 */
index57b3a6105afb17387b56dc33f17ab3eb.url = (options?: RouteQueryOptions) => {
    return index57b3a6105afb17387b56dc33f17ab3eb.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::index
 * @see app/Http/Controllers/MealRecordController.php:18
 * @route '/meals'
 */
index57b3a6105afb17387b56dc33f17ab3eb.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index57b3a6105afb17387b56dc33f17ab3eb.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MealRecordController::index
 * @see app/Http/Controllers/MealRecordController.php:18
 * @route '/meals'
 */
index57b3a6105afb17387b56dc33f17ab3eb.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index57b3a6105afb17387b56dc33f17ab3eb.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\MealRecordController::index
 * @see app/Http/Controllers/MealRecordController.php:18
 * @route '/cms/meals'
 */
const index1f606ac9ceecafec2623bb42df195c37 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index1f606ac9ceecafec2623bb42df195c37.url(options),
    method: 'get',
})

index1f606ac9ceecafec2623bb42df195c37.definition = {
    methods: ["get","head"],
    url: '/cms/meals',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MealRecordController::index
 * @see app/Http/Controllers/MealRecordController.php:18
 * @route '/cms/meals'
 */
index1f606ac9ceecafec2623bb42df195c37.url = (options?: RouteQueryOptions) => {
    return index1f606ac9ceecafec2623bb42df195c37.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::index
 * @see app/Http/Controllers/MealRecordController.php:18
 * @route '/cms/meals'
 */
index1f606ac9ceecafec2623bb42df195c37.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index1f606ac9ceecafec2623bb42df195c37.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MealRecordController::index
 * @see app/Http/Controllers/MealRecordController.php:18
 * @route '/cms/meals'
 */
index1f606ac9ceecafec2623bb42df195c37.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index1f606ac9ceecafec2623bb42df195c37.url(options),
    method: 'head',
})

/**
* Multiple routes resolve to \App\Http\Controllers\MealRecordController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/meals': index57b3a6105afb17387b56dc33f17ab3eb,
    '/cms/meals': index1f606ac9ceecafec2623bb42df195c37,
}

/**
* @see \App\Http\Controllers\MealRecordController::store
 * @see app/Http/Controllers/MealRecordController.php:47
 * @route '/meals'
 */
const store57b3a6105afb17387b56dc33f17ab3eb = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store57b3a6105afb17387b56dc33f17ab3eb.url(options),
    method: 'post',
})

store57b3a6105afb17387b56dc33f17ab3eb.definition = {
    methods: ["post"],
    url: '/meals',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MealRecordController::store
 * @see app/Http/Controllers/MealRecordController.php:47
 * @route '/meals'
 */
store57b3a6105afb17387b56dc33f17ab3eb.url = (options?: RouteQueryOptions) => {
    return store57b3a6105afb17387b56dc33f17ab3eb.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::store
 * @see app/Http/Controllers/MealRecordController.php:47
 * @route '/meals'
 */
store57b3a6105afb17387b56dc33f17ab3eb.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store57b3a6105afb17387b56dc33f17ab3eb.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MealRecordController::store
 * @see app/Http/Controllers/MealRecordController.php:47
 * @route '/cms/meals'
 */
const store1f606ac9ceecafec2623bb42df195c37 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store1f606ac9ceecafec2623bb42df195c37.url(options),
    method: 'post',
})

store1f606ac9ceecafec2623bb42df195c37.definition = {
    methods: ["post"],
    url: '/cms/meals',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MealRecordController::store
 * @see app/Http/Controllers/MealRecordController.php:47
 * @route '/cms/meals'
 */
store1f606ac9ceecafec2623bb42df195c37.url = (options?: RouteQueryOptions) => {
    return store1f606ac9ceecafec2623bb42df195c37.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::store
 * @see app/Http/Controllers/MealRecordController.php:47
 * @route '/cms/meals'
 */
store1f606ac9ceecafec2623bb42df195c37.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store1f606ac9ceecafec2623bb42df195c37.url(options),
    method: 'post',
})

/**
* Multiple routes resolve to \App\Http\Controllers\MealRecordController::store, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `store['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const store = {
    '/meals': store57b3a6105afb17387b56dc33f17ab3eb,
    '/cms/meals': store1f606ac9ceecafec2623bb42df195c37,
}

/**
* @see \App\Http\Controllers\MealRecordController::show
 * @see app/Http/Controllers/MealRecordController.php:57
 * @route '/meals/{meal}'
 */
const showa387d77b0dd404ca1ab189685db434ab = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showa387d77b0dd404ca1ab189685db434ab.url(args, options),
    method: 'get',
})

showa387d77b0dd404ca1ab189685db434ab.definition = {
    methods: ["get","head"],
    url: '/meals/{meal}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MealRecordController::show
 * @see app/Http/Controllers/MealRecordController.php:57
 * @route '/meals/{meal}'
 */
showa387d77b0dd404ca1ab189685db434ab.url = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meal: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meal: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meal: args.meal,
                }

    return showa387d77b0dd404ca1ab189685db434ab.definition.url
            .replace('{meal}', parsedArgs.meal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::show
 * @see app/Http/Controllers/MealRecordController.php:57
 * @route '/meals/{meal}'
 */
showa387d77b0dd404ca1ab189685db434ab.get = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showa387d77b0dd404ca1ab189685db434ab.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MealRecordController::show
 * @see app/Http/Controllers/MealRecordController.php:57
 * @route '/meals/{meal}'
 */
showa387d77b0dd404ca1ab189685db434ab.head = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showa387d77b0dd404ca1ab189685db434ab.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\MealRecordController::show
 * @see app/Http/Controllers/MealRecordController.php:57
 * @route '/cms/meals/{meal}'
 */
const show0ca51121e0f8e82765ca5c3c75daa5da = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show0ca51121e0f8e82765ca5c3c75daa5da.url(args, options),
    method: 'get',
})

show0ca51121e0f8e82765ca5c3c75daa5da.definition = {
    methods: ["get","head"],
    url: '/cms/meals/{meal}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MealRecordController::show
 * @see app/Http/Controllers/MealRecordController.php:57
 * @route '/cms/meals/{meal}'
 */
show0ca51121e0f8e82765ca5c3c75daa5da.url = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meal: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meal: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meal: args.meal,
                }

    return show0ca51121e0f8e82765ca5c3c75daa5da.definition.url
            .replace('{meal}', parsedArgs.meal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::show
 * @see app/Http/Controllers/MealRecordController.php:57
 * @route '/cms/meals/{meal}'
 */
show0ca51121e0f8e82765ca5c3c75daa5da.get = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show0ca51121e0f8e82765ca5c3c75daa5da.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MealRecordController::show
 * @see app/Http/Controllers/MealRecordController.php:57
 * @route '/cms/meals/{meal}'
 */
show0ca51121e0f8e82765ca5c3c75daa5da.head = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show0ca51121e0f8e82765ca5c3c75daa5da.url(args, options),
    method: 'head',
})

/**
* Multiple routes resolve to \App\Http\Controllers\MealRecordController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/meals/{meal}': showa387d77b0dd404ca1ab189685db434ab,
    '/cms/meals/{meal}': show0ca51121e0f8e82765ca5c3c75daa5da,
}

/**
* @see \App\Http\Controllers\MealRecordController::update
 * @see app/Http/Controllers/MealRecordController.php:71
 * @route '/meals/{meal}'
 */
const updatea387d77b0dd404ca1ab189685db434ab = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatea387d77b0dd404ca1ab189685db434ab.url(args, options),
    method: 'put',
})

updatea387d77b0dd404ca1ab189685db434ab.definition = {
    methods: ["put","patch"],
    url: '/meals/{meal}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\MealRecordController::update
 * @see app/Http/Controllers/MealRecordController.php:71
 * @route '/meals/{meal}'
 */
updatea387d77b0dd404ca1ab189685db434ab.url = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meal: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meal: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meal: args.meal,
                }

    return updatea387d77b0dd404ca1ab189685db434ab.definition.url
            .replace('{meal}', parsedArgs.meal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::update
 * @see app/Http/Controllers/MealRecordController.php:71
 * @route '/meals/{meal}'
 */
updatea387d77b0dd404ca1ab189685db434ab.put = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatea387d77b0dd404ca1ab189685db434ab.url(args, options),
    method: 'put',
})
/**
* @see \App\Http\Controllers\MealRecordController::update
 * @see app/Http/Controllers/MealRecordController.php:71
 * @route '/meals/{meal}'
 */
updatea387d77b0dd404ca1ab189685db434ab.patch = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updatea387d77b0dd404ca1ab189685db434ab.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\MealRecordController::update
 * @see app/Http/Controllers/MealRecordController.php:71
 * @route '/cms/meals/{meal}'
 */
const update0ca51121e0f8e82765ca5c3c75daa5da = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update0ca51121e0f8e82765ca5c3c75daa5da.url(args, options),
    method: 'put',
})

update0ca51121e0f8e82765ca5c3c75daa5da.definition = {
    methods: ["put","patch"],
    url: '/cms/meals/{meal}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\MealRecordController::update
 * @see app/Http/Controllers/MealRecordController.php:71
 * @route '/cms/meals/{meal}'
 */
update0ca51121e0f8e82765ca5c3c75daa5da.url = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meal: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meal: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meal: args.meal,
                }

    return update0ca51121e0f8e82765ca5c3c75daa5da.definition.url
            .replace('{meal}', parsedArgs.meal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::update
 * @see app/Http/Controllers/MealRecordController.php:71
 * @route '/cms/meals/{meal}'
 */
update0ca51121e0f8e82765ca5c3c75daa5da.put = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update0ca51121e0f8e82765ca5c3c75daa5da.url(args, options),
    method: 'put',
})
/**
* @see \App\Http\Controllers\MealRecordController::update
 * @see app/Http/Controllers/MealRecordController.php:71
 * @route '/cms/meals/{meal}'
 */
update0ca51121e0f8e82765ca5c3c75daa5da.patch = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update0ca51121e0f8e82765ca5c3c75daa5da.url(args, options),
    method: 'patch',
})

/**
* Multiple routes resolve to \App\Http\Controllers\MealRecordController::update, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `update['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const update = {
    '/meals/{meal}': updatea387d77b0dd404ca1ab189685db434ab,
    '/cms/meals/{meal}': update0ca51121e0f8e82765ca5c3c75daa5da,
}

/**
* @see \App\Http\Controllers\MealRecordController::destroy
 * @see app/Http/Controllers/MealRecordController.php:83
 * @route '/meals/{meal}'
 */
const destroya387d77b0dd404ca1ab189685db434ab = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroya387d77b0dd404ca1ab189685db434ab.url(args, options),
    method: 'delete',
})

destroya387d77b0dd404ca1ab189685db434ab.definition = {
    methods: ["delete"],
    url: '/meals/{meal}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\MealRecordController::destroy
 * @see app/Http/Controllers/MealRecordController.php:83
 * @route '/meals/{meal}'
 */
destroya387d77b0dd404ca1ab189685db434ab.url = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meal: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meal: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meal: args.meal,
                }

    return destroya387d77b0dd404ca1ab189685db434ab.definition.url
            .replace('{meal}', parsedArgs.meal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::destroy
 * @see app/Http/Controllers/MealRecordController.php:83
 * @route '/meals/{meal}'
 */
destroya387d77b0dd404ca1ab189685db434ab.delete = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroya387d77b0dd404ca1ab189685db434ab.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\MealRecordController::destroy
 * @see app/Http/Controllers/MealRecordController.php:83
 * @route '/cms/meals/{meal}'
 */
const destroy0ca51121e0f8e82765ca5c3c75daa5da = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy0ca51121e0f8e82765ca5c3c75daa5da.url(args, options),
    method: 'delete',
})

destroy0ca51121e0f8e82765ca5c3c75daa5da.definition = {
    methods: ["delete"],
    url: '/cms/meals/{meal}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\MealRecordController::destroy
 * @see app/Http/Controllers/MealRecordController.php:83
 * @route '/cms/meals/{meal}'
 */
destroy0ca51121e0f8e82765ca5c3c75daa5da.url = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meal: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meal: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meal: args.meal,
                }

    return destroy0ca51121e0f8e82765ca5c3c75daa5da.definition.url
            .replace('{meal}', parsedArgs.meal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::destroy
 * @see app/Http/Controllers/MealRecordController.php:83
 * @route '/cms/meals/{meal}'
 */
destroy0ca51121e0f8e82765ca5c3c75daa5da.delete = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy0ca51121e0f8e82765ca5c3c75daa5da.url(args, options),
    method: 'delete',
})

/**
* Multiple routes resolve to \App\Http\Controllers\MealRecordController::destroy, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `destroy['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const destroy = {
    '/meals/{meal}': destroya387d77b0dd404ca1ab189685db434ab,
    '/cms/meals/{meal}': destroy0ca51121e0f8e82765ca5c3c75daa5da,
}

/**
* @see \App\Http\Controllers\MealRecordController::uploadPhoto
 * @see app/Http/Controllers/MealRecordController.php:92
 * @route '/cms/meals/{meal}/photo'
 */
export const uploadPhoto = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadPhoto.url(args, options),
    method: 'post',
})

uploadPhoto.definition = {
    methods: ["post"],
    url: '/cms/meals/{meal}/photo',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MealRecordController::uploadPhoto
 * @see app/Http/Controllers/MealRecordController.php:92
 * @route '/cms/meals/{meal}/photo'
 */
uploadPhoto.url = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meal: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meal: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meal: args.meal,
                }

    return uploadPhoto.definition.url
            .replace('{meal}', parsedArgs.meal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::uploadPhoto
 * @see app/Http/Controllers/MealRecordController.php:92
 * @route '/cms/meals/{meal}/photo'
 */
uploadPhoto.post = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadPhoto.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MealRecordController::photo
 * @see app/Http/Controllers/MealRecordController.php:103
 * @route '/cms/meals/{meal}/photo'
 */
export const photo = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: photo.url(args, options),
    method: 'get',
})

photo.definition = {
    methods: ["get","head"],
    url: '/cms/meals/{meal}/photo',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MealRecordController::photo
 * @see app/Http/Controllers/MealRecordController.php:103
 * @route '/cms/meals/{meal}/photo'
 */
photo.url = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meal: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meal: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meal: args.meal,
                }

    return photo.definition.url
            .replace('{meal}', parsedArgs.meal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealRecordController::photo
 * @see app/Http/Controllers/MealRecordController.php:103
 * @route '/cms/meals/{meal}/photo'
 */
photo.get = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: photo.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MealRecordController::photo
 * @see app/Http/Controllers/MealRecordController.php:103
 * @route '/cms/meals/{meal}/photo'
 */
photo.head = (args: { meal: string | number } | [meal: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: photo.url(args, options),
    method: 'head',
})
const MealRecordController = { index, store, show, update, destroy, uploadPhoto, photo }

export default MealRecordController