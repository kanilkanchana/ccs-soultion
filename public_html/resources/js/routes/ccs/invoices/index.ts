import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
import payments from './payments'
/**
* @see \App\Http\Controllers\CcsAdminController::show
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/ccs/invoices/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CcsAdminController::show
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::show
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CcsAdminController::show
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::show
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
    const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::show
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
        showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CcsAdminController::show
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
        showForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    show.form = showForm
/**
* @see \App\Http\Controllers\CcsBillingController::finalize
 * @see app/Http/Controllers/CcsBillingController.php:26
 * @route '/ccs/invoices/{id}/finalize'
 */
export const finalize = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finalize.url(args, options),
    method: 'post',
})

finalize.definition = {
    methods: ["post"],
    url: '/ccs/invoices/{id}/finalize',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsBillingController::finalize
 * @see app/Http/Controllers/CcsBillingController.php:26
 * @route '/ccs/invoices/{id}/finalize'
 */
finalize.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return finalize.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsBillingController::finalize
 * @see app/Http/Controllers/CcsBillingController.php:26
 * @route '/ccs/invoices/{id}/finalize'
 */
finalize.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finalize.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsBillingController::finalize
 * @see app/Http/Controllers/CcsBillingController.php:26
 * @route '/ccs/invoices/{id}/finalize'
 */
    const finalizeForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: finalize.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsBillingController::finalize
 * @see app/Http/Controllers/CcsBillingController.php:26
 * @route '/ccs/invoices/{id}/finalize'
 */
        finalizeForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: finalize.url(args, options),
            method: 'post',
        })
    
    finalize.form = finalizeForm
const invoices = {
    show: Object.assign(show, show),
finalize: Object.assign(finalize, finalize),
payments: Object.assign(payments, payments),
}

export default invoices