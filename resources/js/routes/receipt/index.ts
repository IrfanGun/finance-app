import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ReceiptController::scan
 * @see app/Http/Controllers/ReceiptController.php:15
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
 * @see app/Http/Controllers/ReceiptController.php:15
 * @route '/receipt/scan'
 */
scan.url = (options?: RouteQueryOptions) => {
    return scan.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReceiptController::scan
 * @see app/Http/Controllers/ReceiptController.php:15
 * @route '/receipt/scan'
 */
scan.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: scan.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ReceiptController::scan
 * @see app/Http/Controllers/ReceiptController.php:15
 * @route '/receipt/scan'
 */
scan.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: scan.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReceiptController::ocr
 * @see app/Http/Controllers/ReceiptController.php:34
 * @route '/receipt/ocr'
 */
export const ocr = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ocr.url(options),
    method: 'post',
})

ocr.definition = {
    methods: ["post"],
    url: '/receipt/ocr',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ReceiptController::ocr
 * @see app/Http/Controllers/ReceiptController.php:34
 * @route '/receipt/ocr'
 */
ocr.url = (options?: RouteQueryOptions) => {
    return ocr.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReceiptController::ocr
 * @see app/Http/Controllers/ReceiptController.php:34
 * @route '/receipt/ocr'
 */
ocr.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ocr.url(options),
    method: 'post',
})
const receipt = {
    scan: Object.assign(scan, scan),
ocr: Object.assign(ocr, ocr),
}

export default receipt