import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:87
 * @route '/ccs/jobs'
 */
export const save = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

save.definition = {
    methods: ["post"],
    url: '/ccs/jobs',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:87
 * @route '/ccs/jobs'
 */
save.url = (options?: RouteQueryOptions) => {
    return save.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:87
 * @route '/ccs/jobs'
 */
save.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:87
 * @route '/ccs/jobs'
 */
    const saveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: save.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:87
 * @route '/ccs/jobs'
 */
        saveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: save.url(options),
            method: 'post',
        })
    
    save.form = saveForm
/**
* @see \App\Http\Controllers\CcsAdminController::status
 * @see app/Http/Controllers/CcsAdminController.php:105
 * @route '/ccs/jobs/{id}/status'
 */
export const status = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: status.url(args, options),
    method: 'post',
})

status.definition = {
    methods: ["post"],
    url: '/ccs/jobs/{id}/status',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsAdminController::status
 * @see app/Http/Controllers/CcsAdminController.php:105
 * @route '/ccs/jobs/{id}/status'
 */
status.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return status.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::status
 * @see app/Http/Controllers/CcsAdminController.php:105
 * @route '/ccs/jobs/{id}/status'
 */
status.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: status.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::status
 * @see app/Http/Controllers/CcsAdminController.php:105
 * @route '/ccs/jobs/{id}/status'
 */
    const statusForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: status.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::status
 * @see app/Http/Controllers/CcsAdminController.php:105
 * @route '/ccs/jobs/{id}/status'
 */
        statusForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: status.url(args, options),
            method: 'post',
        })
    
    status.form = statusForm
const jobs = {
    save: Object.assign(save, save),
status: Object.assign(status, status),
}

export default jobs