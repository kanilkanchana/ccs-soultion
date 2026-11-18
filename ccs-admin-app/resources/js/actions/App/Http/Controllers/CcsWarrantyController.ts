import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\CcsWarrantyController::index
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/ccs/warranties',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CcsWarrantyController::index
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsWarrantyController::index
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CcsWarrantyController::index
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CcsWarrantyController::index
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CcsWarrantyController::index
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CcsWarrantyController::index
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
        indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    index.form = indexForm
/**
* @see \App\Http\Controllers\CcsWarrantyController::createRule
 * @see app/Http/Controllers/CcsWarrantyController.php:31
 * @route '/ccs/warranties/rules'
 */
export const createRule = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createRule.url(options),
    method: 'post',
})

createRule.definition = {
    methods: ["post"],
    url: '/ccs/warranties/rules',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsWarrantyController::createRule
 * @see app/Http/Controllers/CcsWarrantyController.php:31
 * @route '/ccs/warranties/rules'
 */
createRule.url = (options?: RouteQueryOptions) => {
    return createRule.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsWarrantyController::createRule
 * @see app/Http/Controllers/CcsWarrantyController.php:31
 * @route '/ccs/warranties/rules'
 */
createRule.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createRule.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsWarrantyController::createRule
 * @see app/Http/Controllers/CcsWarrantyController.php:31
 * @route '/ccs/warranties/rules'
 */
    const createRuleForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: createRule.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsWarrantyController::createRule
 * @see app/Http/Controllers/CcsWarrantyController.php:31
 * @route '/ccs/warranties/rules'
 */
        createRuleForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: createRule.url(options),
            method: 'post',
        })
    
    createRule.form = createRuleForm
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
const CcsWarrantyController = { index, createRule, assign }

export default CcsWarrantyController