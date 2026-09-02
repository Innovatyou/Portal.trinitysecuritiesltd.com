import 'dart:async';
import 'dart:convert';
import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:get/get.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/data/model/dashboard/dashboard_model.dart';
import 'package:realise/data/repo/dashboard/dashboard_repo.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class DashboardController extends GetxController {
  DashboardRepo dashboardRepo;
  DashboardController({required this.dashboardRepo});

  bool isLoading = true;
  bool logoutLoading = false;
  String? appLogo;
  String? currency;

  DashboardModel dashboardModel = DashboardModel();

  Future<void> initialData({bool shouldLoad = true}) async {
    isLoading = shouldLoad ? true : false;
    update();

    await loadData();
    appLogo = dashboardRepo.apiClient.sharedPreferences.getString(
      SharedPreferenceHelper.appLogo,
    );
    currency = dashboardRepo.apiClient.sharedPreferences.getString(
      SharedPreferenceHelper.currencySymbol,
    );
    isLoading = false;
    update();
  }

  Future<void> loadData() async {
    ResponseModel responseModel = await dashboardRepo.getDashboardData();
    if (responseModel.statusCode == 200) {
      dashboardModel = DashboardModel.fromJson(
        jsonDecode(responseModel.responseJson),
      );

      if (dashboardModel.success!) {
        setClientMenu(dashboardModel.data?.permissions ?? []);
      } else {
        CustomSnackBar.error(errorList: [dashboardModel.message!]);
      }
    } else {
      CustomSnackBar.error(errorList: [responseModel.message]);
    }

    isLoading = false;
    update();
  }

  bool isProjectsEnable = false;
  bool isContractsEnable = false;
  bool isProposalsEnable = false;
  bool isEstimatesEnable = false;
  bool isInvoicesEnable = false;
  bool isPaymentsEnable = false;
  bool isTicketsEnable = false;
  bool isOperationsEnable = false;

  Future<void> setClientMenu(List<String> permissions) async {
    isProjectsEnable = false;
    isContractsEnable = false;
    isProposalsEnable = false;
    isEstimatesEnable = false;
    isInvoicesEnable = false;
    isPaymentsEnable = false;
    isTicketsEnable = false;
    isOperationsEnable = permissions.contains('operations');
    if (permissions.contains('all')) {
      isProjectsEnable = true;
      isContractsEnable = true;
      isProposalsEnable = true;
      isEstimatesEnable = true;
      isInvoicesEnable = true;
      isPaymentsEnable = true;
      isTicketsEnable = true;
      isOperationsEnable = true;
    } else {
      if (permissions.contains('projects')) {
        isProjectsEnable = true;
      }
      if (permissions.contains('contracts')) {
        isContractsEnable = true;
      }
      if (permissions.contains('proposals')) {
        isProposalsEnable = true;
      }
      if (permissions.contains('estimates')) {
        isEstimatesEnable = true;
      }
      if (permissions.contains('invoices')) {
        isInvoicesEnable = true;
      }
      if (permissions.contains('payments')) {
        isPaymentsEnable = true;
      }
      if (permissions.contains('tickets')) {
        isTicketsEnable = true;
      }
    }
    update();
  }

  Future<void> logout() async {
    logoutLoading = true;
    update();
    await dashboardRepo.clearSharedPrefData();
    CustomSnackBar.success(successList: [LocalStrings.logoutSuccessMsg.tr]);
    logoutLoading = false;
    update();
    Get.offAllNamed(RouteHelper.loginScreen);
  }
}
