import 'package:realise/view/screens/auth/company_domain/company_domain_screen.dart';
import 'package:realise/view/screens/auth/forgot_password/forget_password.dart';
import 'package:realise/view/screens/auth/login/login_screen.dart';
import 'package:realise/view/screens/auth/registration/registration_screen.dart';
import 'package:realise/view/screens/contract/contract_details_screen.dart';
import 'package:realise/view/screens/contract/contracts_screen.dart';
import 'package:realise/view/screens/estimate/estimate_details_screen.dart';
import 'package:realise/view/screens/estimate/estimate_screen.dart';
import 'package:realise/view/screens/dashboard/dashboard_screen.dart';
import 'package:realise/view/screens/intro_section/onboard_intro_screen.dart';
import 'package:realise/view/screens/invoice/invoice_details_screen.dart';
import 'package:realise/view/screens/invoice/invoice_screen.dart';
import 'package:realise/view/screens/menu/menu_screen.dart';
import 'package:realise/view/screens/messages/chat_screen.dart';
import 'package:realise/view/screens/messages/conversations_screen.dart';
import 'package:realise/view/screens/operations/operations_create_screen.dart';
import 'package:realise/view/screens/operations/operations_detail_screen.dart';
import 'package:realise/view/screens/operations/operations_screen.dart';
import 'package:realise/view/screens/payment/payment_details_screen.dart';
import 'package:realise/view/screens/payment/payment_screen.dart';
import 'package:realise/view/screens/privacy_policy/privacy_policy_screen.dart';
import 'package:realise/view/screens/profile/profile_screen.dart';
import 'package:realise/view/screens/project/project_details_screen.dart';
import 'package:realise/view/screens/project/projects_screen.dart';
import 'package:realise/view/screens/proposal/proposal_details_screen.dart';
import 'package:realise/view/screens/proposal/proposal_screen.dart';
import 'package:realise/view/screens/splash/splash_screen.dart';
import 'package:realise/view/screens/ticket/ticket_details_screen.dart';
import 'package:realise/view/screens/ticket/ticket_screen.dart';
import 'package:get/get.dart';

class RouteHelper {
  static const String splashScreen = "/splash_screen";
  static const String onboardScreen = '/onboard_screen';
  static const String companyDomainScreen = "/company_domain_screen";
  static const String loginScreen = "/login_screen";
  static const String registrationScreen = "/registration_screen";
  static const String forgotPasswordScreen = "/forgot_password_screen";

  static const String dashboardScreen = "/dashboard_screen";
  static const String projectScreen = "/project_screen";
  static const String projectDetailsScreen = "/project_details_screen";
  static const String invoiceScreen = "/invoice_screen";
  static const String invoiceDetailsScreen = "/invoice_details_screen";
  static const String contractScreen = "/contract_screen";
  static const String contractDetailsScreen = "/contract_details_screen";
  static const String contractCommentsScreen = "/contract_comments_screen";
  static const String ticketScreen = "/ticket_screen";
  static const String ticketDetailsScreen = "/ticket_details_screen";
  static const String estimateScreen = "/estimate_screen";
  static const String estimateDetailsScreen = "/estimate_details_screen";
  static const String paymentScreen = "/payment_screen";
  static const String paymentDetailsScreen = "/payment_details_screen";
  static const String proposalScreen = "/proposal_screen";
  static const String proposalDetailsScreen = "/proposal_details_screen";
  static const String settingsScreen = "/settings_screen";
  static const String profileScreen = "/profile_screen";
  static const String privacyScreen = "/privacy_screen";
  static const String operationsScreen = "/operations_screen";
  static const String operationsDetailScreen = "/operations_detail_screen";
  static const String operationsCreateScreen = "/operations_create_screen";
  static const String messagesScreen = "/messages_screen";
  static const String chatScreen = "/chat_screen";

  List<GetPage> routes = [
    GetPage(name: splashScreen, page: () => const SplashScreen()),
    GetPage(name: onboardScreen, page: () => const OnBoardIntroScreen()),
    GetPage(
        name: companyDomainScreen, page: () => const CompanyDomainScreen()),
    GetPage(name: loginScreen, page: () => const LoginScreen()),
    GetPage(name: registrationScreen, page: () => const RegistrationScreen()),
    GetPage(
        name: forgotPasswordScreen, page: () => const ForgetPasswordScreen()),
    GetPage(name: dashboardScreen, page: () => const HomeScreen()),
    GetPage(name: projectScreen, page: () => const ProjectsScreen()),
    GetPage(
        name: projectDetailsScreen,
        page: () => ProjectDetailsScreen(id: Get.arguments)),
    GetPage(name: invoiceScreen, page: () => const InvoicesScreen()),
    GetPage(
        name: invoiceDetailsScreen,
        page: () => InvoiceDetailsScreen(id: Get.arguments)),
    GetPage(name: contractScreen, page: () => const ContractsScreen()),
    GetPage(
        name: contractDetailsScreen,
        page: () => ContractDetailsScreen(id: Get.arguments)),
    GetPage(name: ticketScreen, page: () => const TicketsScreen()),
    GetPage(
        name: ticketDetailsScreen,
        page: () => TicketDetailsScreen(id: Get.arguments)),
    GetPage(name: estimateScreen, page: () => const EstimateScreen()),
    GetPage(
        name: estimateDetailsScreen,
        page: () => EstimateDetailsScreen(id: Get.arguments)),
    GetPage(name: paymentScreen, page: () => const PaymentScreen()),
    GetPage(
        name: paymentDetailsScreen,
        page: () => PaymentDetailsScreen(id: Get.arguments)),
    GetPage(name: proposalScreen, page: () => const ProposalScreen()),
    GetPage(
        name: proposalDetailsScreen,
        page: () => ProposalDetailsScreen(id: Get.arguments)),
    GetPage(name: profileScreen, page: () => const ProfileScreen()),
    GetPage(name: settingsScreen, page: () => const MenuScreen()),
    GetPage(name: privacyScreen, page: () => const PrivacyPolicyScreen()),
    GetPage(name: operationsScreen, page: () => const OperationsScreen()),
    GetPage(
        name: operationsDetailScreen,
        page: () => OperationsDetailScreen(id: Get.arguments)),
    GetPage(
        name: operationsCreateScreen,
        page: () => const OperationsCreateScreen()),
    GetPage(name: messagesScreen, page: () => const ConversationsScreen()),
    GetPage(
        name: chatScreen,
        page: () => ChatScreen(otherUserId: Get.arguments)),
  ];
}
