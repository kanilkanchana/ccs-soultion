import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\CcsAdminController::submit
 * @see app/Http/Controllers/CcsAdminController.php:16
 * @route '/ccs/login'
 */
export const submit = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/ccs/login',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsAdminController::submit
 * @see app/Http/Controllers/CcsAdminController.php:16
 * @route '/ccs/login'
 */
submit.url = (options?: RouteQueryOptions) => {
    return submit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::submit
 * @see app/Http/Controllers/CcsAdminController.php:16
 * @route '/ccs/login'
 */
submit.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::submit
 * @see app/Http/Controllers/CcsAdminController.php:16
 * @route '/ccs/login'
 */
    const submitForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: submit.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::submit
 * @see app/Http/Controllers/CcsAdminController.php:16
 * @route '/ccs/login'
 */
        submitForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: submit.url(options),
            method: 'post',
        })
    
    submit.form = submitForm
const login = {
    submit: Object.assign(submit, submit),
}

export default login