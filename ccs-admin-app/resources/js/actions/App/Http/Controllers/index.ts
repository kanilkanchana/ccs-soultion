import CcsAdminController from './CcsAdminController'
import CcsBillingController from './CcsBillingController'
import CcsWarrantyController from './CcsWarrantyController'
const Controllers = {
    CcsAdminController: Object.assign(CcsAdminController, CcsAdminController),
CcsBillingController: Object.assign(CcsBillingController, CcsBillingController),
CcsWarrantyController: Object.assign(CcsWarrantyController, CcsWarrantyController),
}

export default Controllers