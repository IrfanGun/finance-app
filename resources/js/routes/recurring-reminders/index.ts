import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\RecurringReminderController::index
 * @see app/Http/Controllers/RecurringReminderController.php:21
 * @route '/recurring-reminders'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/recurring-reminders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RecurringReminderController::index
 * @see app/Http/Controllers/RecurringReminderController.php:21
 * @route '/recurring-reminders'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringReminderController::index
 * @see app/Http/Controllers/RecurringReminderController.php:21
 * @route '/recurring-reminders'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\RecurringReminderController::index
 * @see app/Http/Controllers/RecurringReminderController.php:21
 * @route '/recurring-reminders'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RecurringReminderController::show
 * @see app/Http/Controllers/RecurringReminderController.php:54
 * @route '/recurring-reminders/{reminder}'
 */
export const show = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/recurring-reminders/{reminder}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RecurringReminderController::show
 * @see app/Http/Controllers/RecurringReminderController.php:54
 * @route '/recurring-reminders/{reminder}'
 */
show.url = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reminder: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    reminder: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        reminder: args.reminder,
                }

    return show.definition.url
            .replace('{reminder}', parsedArgs.reminder.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringReminderController::show
 * @see app/Http/Controllers/RecurringReminderController.php:54
 * @route '/recurring-reminders/{reminder}'
 */
show.get = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\RecurringReminderController::show
 * @see app/Http/Controllers/RecurringReminderController.php:54
 * @route '/recurring-reminders/{reminder}'
 */
show.head = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RecurringReminderController::store
 * @see app/Http/Controllers/RecurringReminderController.php:66
 * @route '/recurring-reminders'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/recurring-reminders',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RecurringReminderController::store
 * @see app/Http/Controllers/RecurringReminderController.php:66
 * @route '/recurring-reminders'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringReminderController::store
 * @see app/Http/Controllers/RecurringReminderController.php:66
 * @route '/recurring-reminders'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RecurringReminderController::update
 * @see app/Http/Controllers/RecurringReminderController.php:80
 * @route '/recurring-reminders/{reminder}'
 */
export const update = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/recurring-reminders/{reminder}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\RecurringReminderController::update
 * @see app/Http/Controllers/RecurringReminderController.php:80
 * @route '/recurring-reminders/{reminder}'
 */
update.url = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reminder: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    reminder: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        reminder: args.reminder,
                }

    return update.definition.url
            .replace('{reminder}', parsedArgs.reminder.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringReminderController::update
 * @see app/Http/Controllers/RecurringReminderController.php:80
 * @route '/recurring-reminders/{reminder}'
 */
update.patch = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\RecurringReminderController::pause
 * @see app/Http/Controllers/RecurringReminderController.php:96
 * @route '/recurring-reminders/{reminder}/pause'
 */
export const pause = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pause.url(args, options),
    method: 'post',
})

pause.definition = {
    methods: ["post"],
    url: '/recurring-reminders/{reminder}/pause',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RecurringReminderController::pause
 * @see app/Http/Controllers/RecurringReminderController.php:96
 * @route '/recurring-reminders/{reminder}/pause'
 */
pause.url = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reminder: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    reminder: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        reminder: args.reminder,
                }

    return pause.definition.url
            .replace('{reminder}', parsedArgs.reminder.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringReminderController::pause
 * @see app/Http/Controllers/RecurringReminderController.php:96
 * @route '/recurring-reminders/{reminder}/pause'
 */
pause.post = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pause.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RecurringReminderController::resume
 * @see app/Http/Controllers/RecurringReminderController.php:104
 * @route '/recurring-reminders/{reminder}/resume'
 */
export const resume = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resume.url(args, options),
    method: 'post',
})

resume.definition = {
    methods: ["post"],
    url: '/recurring-reminders/{reminder}/resume',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RecurringReminderController::resume
 * @see app/Http/Controllers/RecurringReminderController.php:104
 * @route '/recurring-reminders/{reminder}/resume'
 */
resume.url = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reminder: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    reminder: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        reminder: args.reminder,
                }

    return resume.definition.url
            .replace('{reminder}', parsedArgs.reminder.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringReminderController::resume
 * @see app/Http/Controllers/RecurringReminderController.php:104
 * @route '/recurring-reminders/{reminder}/resume'
 */
resume.post = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resume.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RecurringReminderController::end
 * @see app/Http/Controllers/RecurringReminderController.php:112
 * @route '/recurring-reminders/{reminder}/end'
 */
export const end = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: end.url(args, options),
    method: 'post',
})

end.definition = {
    methods: ["post"],
    url: '/recurring-reminders/{reminder}/end',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RecurringReminderController::end
 * @see app/Http/Controllers/RecurringReminderController.php:112
 * @route '/recurring-reminders/{reminder}/end'
 */
end.url = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reminder: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    reminder: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        reminder: args.reminder,
                }

    return end.definition.url
            .replace('{reminder}', parsedArgs.reminder.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringReminderController::end
 * @see app/Http/Controllers/RecurringReminderController.php:112
 * @route '/recurring-reminders/{reminder}/end'
 */
end.post = (args: { reminder: string | number } | [reminder: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: end.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RecurringReminderController::transaction
 * @see app/Http/Controllers/RecurringReminderController.php:120
 * @route '/recurring-reminders/{reminder}/logs/{log}/transaction'
 */
export const transaction = (args: { reminder: string | number, log: string | number } | [reminder: string | number, log: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transaction.url(args, options),
    method: 'get',
})

transaction.definition = {
    methods: ["get","head"],
    url: '/recurring-reminders/{reminder}/logs/{log}/transaction',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RecurringReminderController::transaction
 * @see app/Http/Controllers/RecurringReminderController.php:120
 * @route '/recurring-reminders/{reminder}/logs/{log}/transaction'
 */
transaction.url = (args: { reminder: string | number, log: string | number } | [reminder: string | number, log: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    reminder: args[0],
                    log: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        reminder: args.reminder,
                                log: args.log,
                }

    return transaction.definition.url
            .replace('{reminder}', parsedArgs.reminder.toString())
            .replace('{log}', parsedArgs.log.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringReminderController::transaction
 * @see app/Http/Controllers/RecurringReminderController.php:120
 * @route '/recurring-reminders/{reminder}/logs/{log}/transaction'
 */
transaction.get = (args: { reminder: string | number, log: string | number } | [reminder: string | number, log: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transaction.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\RecurringReminderController::transaction
 * @see app/Http/Controllers/RecurringReminderController.php:120
 * @route '/recurring-reminders/{reminder}/logs/{log}/transaction'
 */
transaction.head = (args: { reminder: string | number, log: string | number } | [reminder: string | number, log: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: transaction.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RecurringReminderController::complete
 * @see app/Http/Controllers/RecurringReminderController.php:165
 * @route '/recurring-reminders/{reminder}/logs/{log}/complete'
 */
export const complete = (args: { reminder: string | number, log: string | number } | [reminder: string | number, log: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: complete.url(args, options),
    method: 'post',
})

complete.definition = {
    methods: ["post"],
    url: '/recurring-reminders/{reminder}/logs/{log}/complete',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RecurringReminderController::complete
 * @see app/Http/Controllers/RecurringReminderController.php:165
 * @route '/recurring-reminders/{reminder}/logs/{log}/complete'
 */
complete.url = (args: { reminder: string | number, log: string | number } | [reminder: string | number, log: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    reminder: args[0],
                    log: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        reminder: args.reminder,
                                log: args.log,
                }

    return complete.definition.url
            .replace('{reminder}', parsedArgs.reminder.toString())
            .replace('{log}', parsedArgs.log.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringReminderController::complete
 * @see app/Http/Controllers/RecurringReminderController.php:165
 * @route '/recurring-reminders/{reminder}/logs/{log}/complete'
 */
complete.post = (args: { reminder: string | number, log: string | number } | [reminder: string | number, log: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: complete.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RecurringReminderController::snooze
 * @see app/Http/Controllers/RecurringReminderController.php:184
 * @route '/recurring-reminders/{reminder}/logs/{log}/snooze'
 */
export const snooze = (args: { reminder: string | number, log: string | number } | [reminder: string | number, log: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: snooze.url(args, options),
    method: 'post',
})

snooze.definition = {
    methods: ["post"],
    url: '/recurring-reminders/{reminder}/logs/{log}/snooze',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RecurringReminderController::snooze
 * @see app/Http/Controllers/RecurringReminderController.php:184
 * @route '/recurring-reminders/{reminder}/logs/{log}/snooze'
 */
snooze.url = (args: { reminder: string | number, log: string | number } | [reminder: string | number, log: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    reminder: args[0],
                    log: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        reminder: args.reminder,
                                log: args.log,
                }

    return snooze.definition.url
            .replace('{reminder}', parsedArgs.reminder.toString())
            .replace('{log}', parsedArgs.log.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringReminderController::snooze
 * @see app/Http/Controllers/RecurringReminderController.php:184
 * @route '/recurring-reminders/{reminder}/logs/{log}/snooze'
 */
snooze.post = (args: { reminder: string | number, log: string | number } | [reminder: string | number, log: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: snooze.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\RecurringReminderController::skip
 * @see app/Http/Controllers/RecurringReminderController.php:236
 * @route '/recurring-reminders/{reminder}/logs/{log}/skip'
 */
export const skip = (args: { reminder: string | number, log: string | number } | [reminder: string | number, log: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: skip.url(args, options),
    method: 'post',
})

skip.definition = {
    methods: ["post"],
    url: '/recurring-reminders/{reminder}/logs/{log}/skip',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\RecurringReminderController::skip
 * @see app/Http/Controllers/RecurringReminderController.php:236
 * @route '/recurring-reminders/{reminder}/logs/{log}/skip'
 */
skip.url = (args: { reminder: string | number, log: string | number } | [reminder: string | number, log: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    reminder: args[0],
                    log: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        reminder: args.reminder,
                                log: args.log,
                }

    return skip.definition.url
            .replace('{reminder}', parsedArgs.reminder.toString())
            .replace('{log}', parsedArgs.log.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\RecurringReminderController::skip
 * @see app/Http/Controllers/RecurringReminderController.php:236
 * @route '/recurring-reminders/{reminder}/logs/{log}/skip'
 */
skip.post = (args: { reminder: string | number, log: string | number } | [reminder: string | number, log: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: skip.url(args, options),
    method: 'post',
})
const recurringReminders = {
    index: Object.assign(index, index),
show: Object.assign(show, show),
store: Object.assign(store, store),
update: Object.assign(update, update),
pause: Object.assign(pause, pause),
resume: Object.assign(resume, resume),
end: Object.assign(end, end),
transaction: Object.assign(transaction, transaction),
complete: Object.assign(complete, complete),
snooze: Object.assign(snooze, snooze),
skip: Object.assign(skip, skip),
}

export default recurringReminders