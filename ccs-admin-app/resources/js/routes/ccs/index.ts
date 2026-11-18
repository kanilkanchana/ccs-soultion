import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import loginDf2c2a from './login'
import customers1682b4 from './customers'
import vehicles from './vehicles'
import jobsF3446c from './jobs'
import pos28cf7d from './pos'
import invoices from './invoices'
import warranties6451cb from './warranties'
/**
* @see \App\Http\Controllers\CcsAdminController::login
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
export const login = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(options),
    method: 'get',
})

login.definition = {
    methods: ["get","head"],
    url: '/ccs/login',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CcsAdminController::login
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::login
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
login.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CcsAdminController::login
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
login.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: login.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::login
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
    const loginForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: login.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::login
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
        loginForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: login.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CcsAdminController::login
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
        loginForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: login.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    login.form = loginForm
/**
* @see \App\Http\Controllers\CcsAdminController::logout
 * @see app/Http/Controllers/CcsAdminController.php:23
 * @route '/ccs/logout'
 */
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/ccs/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsAdminController::logout
 * @see app/Http/Controllers/CcsAdminController.php:23
 * @route '/ccs/logout'
 */
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::logout
 * @see app/Http/Controllers/CcsAdminController.php:23
 * @route '/ccs/logout'
 */
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::logout
 * @see app/Http/Controllers/CcsAdminController.php:23
 * @route '/ccs/logout'
 */
    const logoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: logout.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::logout
 * @see app/Http/Controllers/CcsAdminController.php:23
 * @route '/ccs/logout'
 */
        logoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: logout.url(options),
            method: 'post',
        })
    
    logout.form = logoutForm
/**
* @see \App\Http\Controllers\CcsAdminController::dashboard
 * @see app/Http/Controllers/CcsAdminController.php:30
 * @route '/ccs'
 */
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/ccs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CcsAdminController::dashboard
 * @see app/Http/Controllers/CcsAdminController.php:30
 * @route '/ccs'
 */
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::dashboard
 * @see app/Http/Controllers/CcsAdminController.php:30
 * @route '/ccs'
 */
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CcsAdminController::dashboard
 * @see app/Http/Controllers/CcsAdminController.php:30
 * @route '/ccs'
 */
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::dashboard
 * @see app/Http/Controllers/CcsAdminController.php:30
 * @route '/ccs'
 */
    const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: dashboard.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::dashboard
 * @see app/Http/Controllers/CcsAdminController.php:30
 * @route '/ccs'
 */
        dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dashboard.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CcsAdminController::dashboard
 * @see app/Http/Controllers/CcsAdminController.php:30
 * @route '/ccs'
 */
        dashboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dashboard.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    dashboard.form = dashboardForm
/**
* @see \App\Http\Controllers\CcsAdminController::customers
 * @see app/Http/Controllers/CcsAdminController.php:41
 * @route '/ccs/customers'
 */
export const customers = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: customers.url(options),
    method: 'get',
})

customers.definition = {
    methods: ["get","head"],
    url: '/ccs/customers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CcsAdminController::customers
 * @see app/Http/Controllers/CcsAdminController.php:41
 * @route '/ccs/customers'
 */
customers.url = (options?: RouteQueryOptions) => {
    return customers.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::customers
 * @see app/Http/Controllers/CcsAdminController.php:41
 * @route '/ccs/customers'
 */
customers.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: customers.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CcsAdminController::customers
 * @see app/Http/Controllers/CcsAdminController.php:41
 * @route '/ccs/customers'
 */
customers.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: customers.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::customers
 * @see app/Http/Controllers/CcsAdminController.php:41
 * @route '/ccs/customers'
 */
    const customersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: customers.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::customers
 * @see app/Http/Controllers/CcsAdminController.php:41
 * @route '/ccs/customers'
 */
        customersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: customers.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CcsAdminController::customers
 * @see app/Http/Controllers/CcsAdminController.php:41
 * @route '/ccs/customers'
 */
        customersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: customers.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    customers.form = customersForm
/**
* @see \App\Http\Controllers\CcsAdminController::jobs
 * @see app/Http/Controllers/CcsAdminController.php:78
 * @route '/ccs/jobs'
 */
export const jobs = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: jobs.url(options),
    method: 'get',
})

jobs.definition = {
    methods: ["get","head"],
    url: '/ccs/jobs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CcsAdminController::jobs
 * @see app/Http/Controllers/CcsAdminController.php:78
 * @route '/ccs/jobs'
 */
jobs.url = (options?: RouteQueryOptions) => {
    return jobs.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::jobs
 * @see app/Http/Controllers/CcsAdminController.php:78
 * @route '/ccs/jobs'
 */
jobs.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: jobs.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CcsAdminController::jobs
 * @see app/Http/Controllers/CcsAdminController.php:78
 * @route '/ccs/jobs'
 */
jobs.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: jobs.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::jobs
 * @see app/Http/Controllers/CcsAdminController.php:78
 * @route '/ccs/jobs'
 */
    const jobsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: jobs.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::jobs
 * @see app/Http/Controllers/CcsAdminController.php:78
 * @route '/ccs/jobs'
 */
        jobsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: jobs.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CcsAdminController::jobs
 * @see app/Http/Controllers/CcsAdminController.php:78
 * @route '/ccs/jobs'
 */
        jobsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: jobs.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    jobs.form = jobsForm
/**
* @see \App\Http\Controllers\CcsAdminController::pos
 * @see app/Http/Controllers/CcsAdminController.php:112
 * @route '/ccs/pos'
 */
export const pos = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pos.url(options),
    method: 'get',
})

pos.definition = {
    methods: ["get","head"],
    url: '/ccs/pos',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CcsAdminController::pos
 * @see app/Http/Controllers/CcsAdminController.php:112
 * @route '/ccs/pos'
 */
pos.url = (options?: RouteQueryOptions) => {
    return pos.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::pos
 * @see app/Http/Controllers/CcsAdminController.php:112
 * @route '/ccs/pos'
 */
pos.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pos.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CcsAdminController::pos
 * @see app/Http/Controllers/CcsAdminController.php:112
 * @route '/ccs/pos'
 */
pos.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pos.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::pos
 * @see app/Http/Controllers/CcsAdminController.php:112
 * @route '/ccs/pos'
 */
    const posForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: pos.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::pos
 * @see app/Http/Controllers/CcsAdminController.php:112
 * @route '/ccs/pos'
 */
        posForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: pos.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CcsAdminController::pos
 * @see app/Http/Controllers/CcsAdminController.php:112
 * @route '/ccs/pos'
 */
        posForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: pos.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    pos.form = posForm
/**
* @see \App\Http\Controllers\CcsWarrantyController::warranties
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
export const warranties = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: warranties.url(options),
    method: 'get',
})

warranties.definition = {
    methods: ["get","head"],
    url: '/ccs/warranties',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CcsWarrantyController::warranties
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
warranties.url = (options?: RouteQueryOptions) => {
    return warranties.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsWarrantyController::warranties
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
warranties.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: warranties.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CcsWarrantyController::warranties
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
warranties.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: warranties.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CcsWarrantyController::warranties
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
    const warrantiesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: warranties.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CcsWarrantyController::warranties
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
        warrantiesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: warranties.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CcsWarrantyController::warranties
 * @see app/Http/Controllers/CcsWarrantyController.php:12
 * @route '/ccs/warranties'
 */
        warrantiesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: warranties.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    warranties.form = warrantiesForm
const ccs = {
    login: Object.assign(login, loginDf2c2a),
logout: Object.assign(logout, logout),
dashboard: Object.assign(dashboard, dashboard),
customers: Object.assign(customers, customers1682b4),
vehicles: Object.assign(vehicles, vehicles),
jobs: Object.assign(jobs, jobsF3446c),
pos: Object.assign(pos, pos28cf7d),
invoices: Object.assign(invoices, invoices),
warranties: Object.assign(warranties, warranties6451cb),
}

export default ccs