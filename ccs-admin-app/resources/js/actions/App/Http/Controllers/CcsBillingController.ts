import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\CcsBillingController::issue
 * @see app/Http/Controllers/CcsBillingController.php:26
 * @route '/ccs/invoices/{id}/finalize'
 */
export const issue = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: issue.url(args, options),
    method: 'post',
})

issue.definition = {
    methods: ["post"],
    url: '/ccs/invoices/{id}/finalize',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsBillingController::issue
 * @see app/Http/Controllers/CcsBillingController.php:26
 * @route '/ccs/invoices/{id}/finalize'
 */
issue.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return issue.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsBillingController::issue
 * @see app/Http/Controllers/CcsBillingController.php:26
 * @route '/ccs/invoices/{id}/finalize'
 */
issue.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: issue.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsBillingController::issue
 * @see app/Http/Controllers/CcsBillingController.php:26
 * @route '/ccs/invoices/{id}/finalize'
 */
    const issueForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: issue.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsBillingController::issue
 * @see app/Http/Controllers/CcsBillingController.php:26
 * @route '/ccs/invoices/{id}/finalize'
 */
        issueForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: issue.url(args, options),
            method: 'post',
        })
    
    issue.form = issueForm
/**
* @see \App\Http\Controllers\CcsBillingController::payment
 * @see app/Http/Controllers/CcsBillingController.php:50
 * @route '/ccs/invoices/{id}/payments'
 */
export const payment = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: payment.url(args, options),
    method: 'post',
})

payment.definition = {
    methods: ["post"],
    url: '/ccs/invoices/{id}/payments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsBillingController::payment
 * @see app/Http/Controllers/CcsBillingController.php:50
 * @route '/ccs/invoices/{id}/payments'
 */
payment.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return payment.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsBillingController::payment
 * @see app/Http/Controllers/CcsBillingController.php:50
 * @route '/ccs/invoices/{id}/payments'
 */
payment.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: payment.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsBillingController::payment
 * @see app/Http/Controllers/CcsBillingController.php:50
 * @route '/ccs/invoices/{id}/payments'
 */
    const paymentForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: payment.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsBillingController::payment
 * @see app/Http/Controllers/CcsBillingController.php:50
 * @route '/ccs/invoices/{id}/payments'
 */
        paymentForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: payment.url(args, options),
            method: 'post',
        })
    
    payment.form = paymentForm
const CcsBillingController = { issue, payment }

export default CcsBillingController