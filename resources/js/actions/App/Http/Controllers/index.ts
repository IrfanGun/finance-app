import AiTransactionController from './AiTransactionController'
import AiFinanceController from './AiFinanceController'
import AiMemoryController from './AiMemoryController'
import AiWorkflowController from './AiWorkflowController'
import DashboardController from './DashboardController'
import AiChatController from './AiChatController'
import AiChatTransactionController from './AiChatTransactionController'
import ChatController from './ChatController'
import CategoryController from './CategoryController'
import FinancialAccountController from './FinancialAccountController'
import AssetController from './AssetController'
import AssetDepreciationController from './AssetDepreciationController'
import TransactionController from './TransactionController'
import RecurringReminderController from './RecurringReminderController'
import MealRecordController from './MealRecordController'
import ReceiptController from './ReceiptController'
import ProfileController from './ProfileController'
import Cms from './Cms'
import Auth from './Auth'
const Controllers = {
    AiTransactionController: Object.assign(AiTransactionController, AiTransactionController),
AiFinanceController: Object.assign(AiFinanceController, AiFinanceController),
AiMemoryController: Object.assign(AiMemoryController, AiMemoryController),
AiWorkflowController: Object.assign(AiWorkflowController, AiWorkflowController),
DashboardController: Object.assign(DashboardController, DashboardController),
AiChatController: Object.assign(AiChatController, AiChatController),
AiChatTransactionController: Object.assign(AiChatTransactionController, AiChatTransactionController),
ChatController: Object.assign(ChatController, ChatController),
CategoryController: Object.assign(CategoryController, CategoryController),
FinancialAccountController: Object.assign(FinancialAccountController, FinancialAccountController),
AssetController: Object.assign(AssetController, AssetController),
AssetDepreciationController: Object.assign(AssetDepreciationController, AssetDepreciationController),
TransactionController: Object.assign(TransactionController, TransactionController),
RecurringReminderController: Object.assign(RecurringReminderController, RecurringReminderController),
MealRecordController: Object.assign(MealRecordController, MealRecordController),
ReceiptController: Object.assign(ReceiptController, ReceiptController),
ProfileController: Object.assign(ProfileController, ProfileController),
Cms: Object.assign(Cms, Cms),
Auth: Object.assign(Auth, Auth),
}

export default Controllers