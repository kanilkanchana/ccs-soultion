import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\CcsBillingController::store
 * @see app/Http/Controllers/CcsBillingController.php:50
 * @route '/ccs/invoices/{id}/payments'
 */
export const store = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/ccs/invoices/{id}/payments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsBillingController::store
 * @see app/Http/Controllers/CcsBillingController.php:50
 * @route '/ccs/invoices/{id}/payments'
 */
store.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return store.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsBillingController::store
 * @see app/Http/Controllers/CcsBillingController.php:50
 * @route '/ccs/invoices/{id}/payments'
 */
store.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsBillingController::store
 * @see app/Http/Controllers/CcsBillingController.php:50
 * @route '/ccs/invoices/{id}/payments'
 */
    const storeForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsBillingController::store
 * @see app/Http/Controllers/CcsBillingController.php:50
 * @route '/ccs/invoices/{id}/payments'
 */
        storeForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(args, options),
            method: 'post',
        })
    
    store.form = storeForm
const payments = {
    store: Object.assign(store, store),
}

export default payments