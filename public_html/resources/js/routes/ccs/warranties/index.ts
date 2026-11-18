import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
import rules from './rules'
/**
* @see \App\Http\Controllers\CcsWarrantyController::assign
 * @see app/Http/Controllers/CcsWarrantyController.php:53
 * @route '/ccs/warranties/assign'
 */
export const assign = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: assign.url(options),
    method: 'post',
})

assign.definition = {
    methods: ["post"],
    url: '/ccs/warranties/assign',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsWarrantyController::assign
 * @see app/Http/Controllers/CcsWarrantyController.php:53
 * @route '/ccs/warranties/assign'
 */
assign.url = (options?: RouteQueryOptions) => {
    return assign.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsWarrantyController::assign
 * @see app/Http/Controllers/CcsWarrantyController.php:53
 * @route '/ccs/warranties/assign'
 */
assign.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: assign.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsWarrantyController::assign
 * @see app/Http/Controllers/CcsWarrantyController.php:53
 * @route '/ccs/warranties/assign'
 */
    const assignForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: assign.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsWarrantyController::assign
 * @see app/Http/Controllers/CcsWarrantyController.php:53
 * @route '/ccs/warranties/assign'
 */
        assignForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: assign.url(options),
            method: 'post',
        })
    
    assign.form = assignForm
const warranties = {
    rules: Object.assign(rules, rules),
assign: Object.assign(assign, assign),
}

export default warranties