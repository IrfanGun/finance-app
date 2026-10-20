import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import loginDf2c2a from './login'
import transactions from './transactions'
import recurring from './recurring'
import categories from './categories'
import accounts from './accounts'
import assets from './assets'
import receipt from './receipt'
import profile from './profile'
/**
 * @see routes/web.php:106
 * @route '/cms'
 */
export const home = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

home.definition = {
    methods: ["get","head"],
    url: '/cms',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see routes/web.php:106
 * @route '/cms'
 */
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
 * @see routes/web.php:106
 * @route '/cms'
 */
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})
/**
 * @see routes/web.php:106
 * @route '/cms'
 */
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::logout
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:32
 * @route '/cms/logout'
 */
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/cms/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::logout
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:32
 * @route '/cms/logout'
 */
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::logout
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:32
 * @route '/cms/logout'
 */
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::login
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:16
 * @route '/cms/login'
 */
export const login = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(options),
    method: 'get',
})

login.definition = {
    methods: ["get","head"],
    url: '/cms/login',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::login
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:16
 * @route '/cms/login'
 */
login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::login
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:16
 * @route '/cms/login'
 */
login.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Cms\AuthenticatedSessionController::login
 * @see app/Http/Controllers/Cms/AuthenticatedSessionController.php:16
 * @route '/cms/login'
 */
login.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: login.url(options),
    method: 'head',
})

/**
 * @see routes/web.php:115
 * @route '/cms/dashboard'
 */
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/cms/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see routes/web.php:115
 * @route '/cms/dashboard'
 */
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
 * @see routes/web.php:115
 * @route '/cms/dashboard'
 */
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})
/**
 * @see routes/web.php:115
 * @route '/cms/dashboard'
 */
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
 * @see routes/web.php:180
 * @route '/cms/chat'
 */
export const chat = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: chat.url(options),
    method: 'get',
})

chat.definition = {
    methods: ["get","head"],
    url: '/cms/chat',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see routes/web.php:180
 * @route '/cms/chat'
 */
chat.url = (options?: RouteQueryOptions) => {
    return chat.definition.url + queryParams(options)
}

/**
 * @see routes/web.php:180
 * @route '/cms/chat'
 */
chat.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: chat.url(options),
    method: 'get',
})
/**
 * @see routes/web.php:180
 * @route '/cms/chat'
 */
chat.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: chat.url(options),
    method: 'head',
})
const cms = {
    home: Object.assign(home, home),
logout: Object.assign(logout, logout),
login: Object.assign(login, loginDf2c2a),
dashboard: Object.assign(dashboard, dashboard),
transactions: Object.assign(transactions, transactions),
recurring: Object.assign(recurring, recurring),
categories: Object.assign(categories, categories),
accounts: Object.assign(accounts, accounts),
assets: Object.assign(assets, assets),
chat: Object.assign(chat, chat),
receipt: Object.assign(receipt, receipt),
profile: Object.assign(profile, profile),
}

export default cms