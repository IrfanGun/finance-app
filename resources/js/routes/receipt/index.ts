import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ReceiptController::scan
 * @see app/Http/Controllers/ReceiptController.php:13
 * @route '/receipt/scan'
 */
export const scan = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: scan.url(options),
    method: 'get',
})

scan.definition = {
    methods: ["get","head"],
    url: '/receipt/scan',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReceiptController::scan
 * @see app/Http/Controllers/ReceiptController.php:13
 * @route '/receipt/scan'
 */
scan.url = (options?: RouteQueryOptions) => {
    return scan.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReceiptController::scan
 * @see app/Http/Controllers/ReceiptController.php:13
 * @route '/receipt/scan'
 */
scan.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: scan.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ReceiptController::scan
 * @see app/Http/Controllers/ReceiptController.php:13
 * @route '/receipt/scan'
 */
scan.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: scan.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReceiptController::model
 * @see app/Http/Controllers/ReceiptController.php:29
 * @route '/receipt/model'
 */
export const model = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: model.url(options),
    method: 'get',
})

model.definition = {
    methods: ["get","head"],
    url: '/receipt/model',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReceiptController::model
 * @see app/Http/Controllers/ReceiptController.php:29
 * @route '/receipt/model'
 */
model.url = (options?: RouteQueryOptions) => {
    return model.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReceiptController::model
 * @see app/Http/Controllers/ReceiptController.php:29
 * @route '/receipt/model'
 */
model.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: model.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ReceiptController::model
 * @see app/Http/Controllers/ReceiptController.php:29
 * @route '/receipt/model'
 */
model.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: model.url(options),
    method: 'head',
})
const receipt = {
    scan: Object.assign(scan, scan),
model: Object.assign(model, model),
}

export default receipt