import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:120
 * @route '/ccs/pos'
 */
export const save = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

save.definition = {
    methods: ["post"],
    url: '/ccs/pos',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:120
 * @route '/ccs/pos'
 */
save.url = (options?: RouteQueryOptions) => {
    return save.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:120
 * @route '/ccs/pos'
 */
save.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:120
 * @route '/ccs/pos'
 */
    const saveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: save.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:120
 * @route '/ccs/pos'
 */
        saveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: save.url(options),
            method: 'post',
        })
    
    save.form = saveForm
const pos = {
    save: Object.assign(save, save),
}

export default pos