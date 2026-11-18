import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:71
 * @route '/ccs/vehicles'
 */
export const save = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

save.definition = {
    methods: ["post"],
    url: '/ccs/vehicles',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:71
 * @route '/ccs/vehicles'
 */
save.url = (options?: RouteQueryOptions) => {
    return save.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:71
 * @route '/ccs/vehicles'
 */
save.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:71
 * @route '/ccs/vehicles'
 */
    const saveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: save.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:71
 * @route '/ccs/vehicles'
 */
        saveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: save.url(options),
            method: 'post',
        })
    
    save.form = saveForm
const vehicles = {
    save: Object.assign(save, save),
}

export default vehicles