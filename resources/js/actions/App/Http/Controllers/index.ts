import AiTransactionController from './AiTransactionController'
import DashboardController from './DashboardController'
import AiChatController from './AiChatController'
import AiChatTransactionController from './AiChatTransactionController'
import ChatController from './ChatController'
import CategoryController from './CategoryController'
import FinancialAccountController from './FinancialAccountController'
import TransactionController from './TransactionController'
import ReceiptController from './ReceiptController'
import ProfileController from './ProfileController'
import Auth from './Auth'
const Controllers = {
    AiTransactionController: Object.assign(AiTransactionController, AiTransactionController),
DashboardController: Object.assign(DashboardController, DashboardController),
AiChatController: Object.assign(AiChatController, AiChatController),
AiChatTransactionController: Object.assign(AiChatTransactionController, AiChatTransactionController),
ChatController: Object.assign(ChatController, ChatController),
CategoryController: Object.assign(CategoryController, CategoryController),
FinancialAccountController: Object.assign(FinancialAccountController, FinancialAccountController),
TransactionController: Object.assign(TransactionController, TransactionController),
ReceiptController: Object.assign(ReceiptController, ReceiptController),
ProfileController: Object.assign(ProfileController, ProfileController),
Auth: Object.assign(Auth, Auth),
}

export default Controllers