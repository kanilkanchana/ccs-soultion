import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:54
 * @route '/ccs/customers'
 */
export const save = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

save.definition = {
    methods: ["post"],
    url: '/ccs/customers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:54
 * @route '/ccs/customers'
 */
save.url = (options?: RouteQueryOptions) => {
    return save.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:54
 * @route '/ccs/customers'
 */
save.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: save.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:54
 * @route '/ccs/customers'
 */
    const saveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: save.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::save
 * @see app/Http/Controllers/CcsAdminController.php:54
 * @route '/ccs/customers'
 */
        saveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: save.url(options),
            method: 'post',
        })
    
    save.form = saveForm
/**
* @see \App\Http\Controllers\CcsAdminController::show
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
 */
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/ccs/customers/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CcsAdminController::show
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
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
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
 */
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CcsAdminController::show
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
 */
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::show
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
 */
    const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::show
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
 */
        showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CcsAdminController::show
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
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
const customers = {
    save: Object.assign(save, save),
show: Object.assign(show, show),
}

export default customers