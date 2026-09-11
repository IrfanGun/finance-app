import DashboardController from './DashboardController'
import CategoryController from './CategoryController'
import TransactionController from './TransactionController'
import ReceiptController from './ReceiptController'
import ProfileController from './ProfileController'
import Auth from './Auth'
const Controllers = {
    DashboardController: Object.assign(DashboardController, DashboardController),
CategoryController: Object.assign(CategoryController, CategoryController),
TransactionController: Object.assign(TransactionController, TransactionController),
ReceiptController: Object.assign(ReceiptController, ReceiptController),
ProfileController: Object.assign(ProfileController, ProfileController),
Auth: Object.assign(Auth, Auth),
}

export default Controllers