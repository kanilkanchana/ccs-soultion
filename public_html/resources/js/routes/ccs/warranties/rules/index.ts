import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\CcsWarrantyController::store
 * @see app/Http/Controllers/CcsWarrantyController.php:31
 * @route '/ccs/warranties/rules'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/ccs/warranties/rules',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsWarrantyController::store
 * @see app/Http/Controllers/CcsWarrantyController.php:31
 * @route '/ccs/warranties/rules'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsWarrantyController::store
 * @see app/Http/Controllers/CcsWarrantyController.php:31
 * @route '/ccs/warranties/rules'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsWarrantyController::store
 * @see app/Http/Controllers/CcsWarrantyController.php:31
 * @route '/ccs/warranties/rules'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsWarrantyController::store
 * @see app/Http/Controllers/CcsWarrantyController.php:31
 * @route '/ccs/warranties/rules'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
const rules = {
    store: Object.assign(store, store),
}

export default rules