import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\CcsAdminController::loginForm
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
export const loginForm = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loginForm.url(options),
    method: 'get',
})

loginForm.definition = {
    methods: ["get","head"],
    url: '/ccs/login',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CcsAdminController::loginForm
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
loginForm.url = (options?: RouteQueryOptions) => {
    return loginForm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::loginForm
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
loginForm.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loginForm.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CcsAdminController::loginForm
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
loginForm.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: loginForm.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::loginForm
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
    const loginFormForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: loginForm.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::loginForm
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
        loginFormForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: loginForm.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CcsAdminController::loginForm
 * @see app/Http/Controllers/CcsAdminController.php:15
 * @route '/ccs/login'
 */
        loginFormForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: loginForm.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    loginForm.form = loginFormForm
/**
* @see \App\Http\Controllers\CcsAdminController::login
 * @see app/Http/Controllers/CcsAdminController.php:16
 * @route '/ccs/login'
 */
export const login = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

login.definition = {
    methods: ["post"],
    url: '/ccs/login',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsAdminController::login
 * @see app/Http/Controllers/CcsAdminController.php:16
 * @route '/ccs/login'
 */
login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::login
 * @see app/Http/Controllers/CcsAdminController.php:16
 * @route '/ccs/login'
 */
login.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::login
 * @see app/Http/Controllers/CcsAdminController.php:16
 * @route '/ccs/login'
 */
    const loginForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: login.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::login
 * @see app/Http/Controllers/CcsAdminController.php:16
 * @route '/ccs/login'
 */
        loginForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: login.url(options),
            method: 'post',
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
* @see \App\Http\Controllers\CcsAdminController::saveCustomer
 * @see app/Http/Controllers/CcsAdminController.php:54
 * @route '/ccs/customers'
 */
export const saveCustomer = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveCustomer.url(options),
    method: 'post',
})

saveCustomer.definition = {
    methods: ["post"],
    url: '/ccs/customers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsAdminController::saveCustomer
 * @see app/Http/Controllers/CcsAdminController.php:54
 * @route '/ccs/customers'
 */
saveCustomer.url = (options?: RouteQueryOptions) => {
    return saveCustomer.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::saveCustomer
 * @see app/Http/Controllers/CcsAdminController.php:54
 * @route '/ccs/customers'
 */
saveCustomer.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveCustomer.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::saveCustomer
 * @see app/Http/Controllers/CcsAdminController.php:54
 * @route '/ccs/customers'
 */
    const saveCustomerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: saveCustomer.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::saveCustomer
 * @see app/Http/Controllers/CcsAdminController.php:54
 * @route '/ccs/customers'
 */
        saveCustomerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: saveCustomer.url(options),
            method: 'post',
        })
    
    saveCustomer.form = saveCustomerForm
/**
* @see \App\Http\Controllers\CcsAdminController::customerShow
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
 */
export const customerShow = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: customerShow.url(args, options),
    method: 'get',
})

customerShow.definition = {
    methods: ["get","head"],
    url: '/ccs/customers/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CcsAdminController::customerShow
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
 */
customerShow.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return customerShow.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::customerShow
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
 */
customerShow.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: customerShow.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CcsAdminController::customerShow
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
 */
customerShow.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: customerShow.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::customerShow
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
 */
    const customerShowForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: customerShow.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::customerShow
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
 */
        customerShowForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: customerShow.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CcsAdminController::customerShow
 * @see app/Http/Controllers/CcsAdminController.php:61
 * @route '/ccs/customers/{id}'
 */
        customerShowForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: customerShow.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    customerShow.form = customerShowForm
/**
* @see \App\Http\Controllers\CcsAdminController::saveVehicle
 * @see app/Http/Controllers/CcsAdminController.php:71
 * @route '/ccs/vehicles'
 */
export const saveVehicle = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveVehicle.url(options),
    method: 'post',
})

saveVehicle.definition = {
    methods: ["post"],
    url: '/ccs/vehicles',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsAdminController::saveVehicle
 * @see app/Http/Controllers/CcsAdminController.php:71
 * @route '/ccs/vehicles'
 */
saveVehicle.url = (options?: RouteQueryOptions) => {
    return saveVehicle.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::saveVehicle
 * @see app/Http/Controllers/CcsAdminController.php:71
 * @route '/ccs/vehicles'
 */
saveVehicle.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveVehicle.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::saveVehicle
 * @see app/Http/Controllers/CcsAdminController.php:71
 * @route '/ccs/vehicles'
 */
    const saveVehicleForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: saveVehicle.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::saveVehicle
 * @see app/Http/Controllers/CcsAdminController.php:71
 * @route '/ccs/vehicles'
 */
        saveVehicleForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: saveVehicle.url(options),
            method: 'post',
        })
    
    saveVehicle.form = saveVehicleForm
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
* @see \App\Http\Controllers\CcsAdminController::saveJob
 * @see app/Http/Controllers/CcsAdminController.php:87
 * @route '/ccs/jobs'
 */
export const saveJob = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveJob.url(options),
    method: 'post',
})

saveJob.definition = {
    methods: ["post"],
    url: '/ccs/jobs',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsAdminController::saveJob
 * @see app/Http/Controllers/CcsAdminController.php:87
 * @route '/ccs/jobs'
 */
saveJob.url = (options?: RouteQueryOptions) => {
    return saveJob.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::saveJob
 * @see app/Http/Controllers/CcsAdminController.php:87
 * @route '/ccs/jobs'
 */
saveJob.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveJob.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::saveJob
 * @see app/Http/Controllers/CcsAdminController.php:87
 * @route '/ccs/jobs'
 */
    const saveJobForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: saveJob.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::saveJob
 * @see app/Http/Controllers/CcsAdminController.php:87
 * @route '/ccs/jobs'
 */
        saveJobForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: saveJob.url(options),
            method: 'post',
        })
    
    saveJob.form = saveJobForm
/**
* @see \App\Http\Controllers\CcsAdminController::updateJob
 * @see app/Http/Controllers/CcsAdminController.php:105
 * @route '/ccs/jobs/{id}/status'
 */
export const updateJob = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateJob.url(args, options),
    method: 'post',
})

updateJob.definition = {
    methods: ["post"],
    url: '/ccs/jobs/{id}/status',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsAdminController::updateJob
 * @see app/Http/Controllers/CcsAdminController.php:105
 * @route '/ccs/jobs/{id}/status'
 */
updateJob.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return updateJob.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::updateJob
 * @see app/Http/Controllers/CcsAdminController.php:105
 * @route '/ccs/jobs/{id}/status'
 */
updateJob.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateJob.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::updateJob
 * @see app/Http/Controllers/CcsAdminController.php:105
 * @route '/ccs/jobs/{id}/status'
 */
    const updateJobForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateJob.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::updateJob
 * @see app/Http/Controllers/CcsAdminController.php:105
 * @route '/ccs/jobs/{id}/status'
 */
        updateJobForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateJob.url(args, options),
            method: 'post',
        })
    
    updateJob.form = updateJobForm
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
* @see \App\Http\Controllers\CcsAdminController::createInvoice
 * @see app/Http/Controllers/CcsAdminController.php:120
 * @route '/ccs/pos'
 */
export const createInvoice = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createInvoice.url(options),
    method: 'post',
})

createInvoice.definition = {
    methods: ["post"],
    url: '/ccs/pos',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CcsAdminController::createInvoice
 * @see app/Http/Controllers/CcsAdminController.php:120
 * @route '/ccs/pos'
 */
createInvoice.url = (options?: RouteQueryOptions) => {
    return createInvoice.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::createInvoice
 * @see app/Http/Controllers/CcsAdminController.php:120
 * @route '/ccs/pos'
 */
createInvoice.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createInvoice.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::createInvoice
 * @see app/Http/Controllers/CcsAdminController.php:120
 * @route '/ccs/pos'
 */
    const createInvoiceForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: createInvoice.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::createInvoice
 * @see app/Http/Controllers/CcsAdminController.php:120
 * @route '/ccs/pos'
 */
        createInvoiceForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: createInvoice.url(options),
            method: 'post',
        })
    
    createInvoice.form = createInvoiceForm
/**
* @see \App\Http\Controllers\CcsAdminController::invoice
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
export const invoice = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: invoice.url(args, options),
    method: 'get',
})

invoice.definition = {
    methods: ["get","head"],
    url: '/ccs/invoices/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CcsAdminController::invoice
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
invoice.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return invoice.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CcsAdminController::invoice
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
invoice.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: invoice.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CcsAdminController::invoice
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
invoice.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: invoice.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\CcsAdminController::invoice
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
    const invoiceForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: invoice.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\CcsAdminController::invoice
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
        invoiceForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: invoice.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\CcsAdminController::invoice
 * @see app/Http/Controllers/CcsAdminController.php:149
 * @route '/ccs/invoices/{id}'
 */
        invoiceForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: invoice.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    invoice.form = invoiceForm
const CcsAdminController = { loginForm, login, logout, dashboard, customers, saveCustomer, customerShow, saveVehicle, jobs, saveJob, updateJob, pos, createInvoice, invoice }

export default CcsAdminController