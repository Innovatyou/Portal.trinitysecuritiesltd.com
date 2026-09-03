import 'dart:convert';

import 'package:realise/core/helper/biometric_auth_helper.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/messages.dart';
import 'package:realise/data/controller/localization/localization_controller.dart';
import 'package:realise/data/model/global/overview_model.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/data/repo/splash/splash_repo.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class SplashController extends GetxController {
  SplashRepo splashRepo;
  LocalizationController localizationController;
  bool isLoading = true;

  SplashController(
      {required this.splashRepo, required this.localizationController});

  gotoNextPage() async {
    await loadLanguage();

    // Multi-tenant: every company is on its own domain, so there's nothing
    // to call yet if none has been chosen - would otherwise hit whatever
    // domain got compiled in by default. Onboarding still shows first (it's
    // generic marketing, not company-specific), then the company picker,
    // then normal login.
    if (!UrlContainer.hasChosenCompany(splashRepo.apiClient.sharedPreferences)) {
      bool isOnBoard = splashRepo.apiClient.sharedPreferences
              .getBool(SharedPreferenceHelper.onboardKey) ??
          false;
      isLoading = false;
      update();
      Future.delayed(const Duration(milliseconds: 600), () {
        Get.offAndToNamed(
            isOnBoard ? RouteHelper.companyDomainScreen : RouteHelper.onboardScreen);
      });
      return;
    }

    bool isRemember = splashRepo.apiClient.sharedPreferences
            .getBool(SharedPreferenceHelper.rememberMeKey) ??
        false;
    bool isOnBoard = splashRepo.apiClient.sharedPreferences
            .getBool(SharedPreferenceHelper.onboardKey) ??
        false;
    noInternet = false;
    update();

    getData(isRemember, isOnBoard);
  }

  bool noInternet = false;
  void getData(bool isRemember, bool isOnBoard) async {
    ResponseModel response = await splashRepo.getOverviewData();
    if (response.statusCode == 200) {
      OverviewModel model =
          OverviewModel.fromJson(jsonDecode(response.responseJson));
      if (model.success!) {
        await splashRepo.apiClient.sharedPreferences.setString(
            SharedPreferenceHelper.appTitle, model.data?.appTitle ?? '');

        await splashRepo.apiClient.sharedPreferences.setString(
            SharedPreferenceHelper.appLogo, model.data?.appLogo ?? '');

        await splashRepo.apiClient.sharedPreferences.setString(
            SharedPreferenceHelper.appLanguage, model.data?.language ?? '');

        await splashRepo.apiClient.sharedPreferences.setString(
            SharedPreferenceHelper.currencySymbol,
            model.data?.currencySymbol ?? '');

        await splashRepo.apiClient.sharedPreferences.setString(
            SharedPreferenceHelper.currencyPosition,
            model.data?.currencyPosition ?? '');

        await splashRepo.apiClient.sharedPreferences.setString(
            SharedPreferenceHelper.defaultCurrency,
            model.data?.defaultCurrency ?? '');

        await splashRepo.apiClient.sharedPreferences.setString(
            SharedPreferenceHelper.disableLogin,
            model.data?.disableLogin ?? '');

        await splashRepo.apiClient.sharedPreferences.setString(
            SharedPreferenceHelper.disableRegistration,
            model.data?.disableRegistration ?? '');

        await splashRepo.apiClient.sharedPreferences.setString(
            SharedPreferenceHelper.viewTasks, model.data?.viewTasks ?? '');

        await splashRepo.apiClient.sharedPreferences.setString(
            SharedPreferenceHelper.viewOverview,
            model.data?.viewOverview ?? '');
      } else {
        CustomSnackBar.error(errorList: [model.message!]);
      }
    } else {
      if (response.statusCode == 503) {
        noInternet = true;
        update();
      }
      CustomSnackBar.error(errorList: [response.message]);
    }

    isLoading = false;
    update();

    if (isOnBoard == false) {
      Future.delayed(const Duration(seconds: 1), () {
        Get.offAndToNamed(RouteHelper.onboardScreen);
      });
    } else {
      if (isRemember) {
        Future.delayed(const Duration(seconds: 1), () async {
          final biometricEnabled = splashRepo.apiClient.sharedPreferences
                  .getBool(SharedPreferenceHelper.biometricEnabledKey) ??
              false;
          // Biometric here only gates re-entry into an existing "remember
          // me" session - it never replaces the actual login call, so a
          // failed/cancelled scan just falls back to the normal login
          // screen (the stored token/session is untouched either way).
          if (biometricEnabled) {
            final ok = await BiometricAuthHelper.authenticate(
              reason: 'Verify it\'s you to continue',
            );
            if (ok) {
              Get.offAndToNamed(RouteHelper.dashboardScreen);
            } else {
              Get.offAndToNamed(RouteHelper.loginScreen);
            }
          } else {
            Get.offAndToNamed(RouteHelper.dashboardScreen);
          }
        });
      } else {
        Future.delayed(const Duration(seconds: 1), () {
          Get.offAndToNamed(RouteHelper.loginScreen);
        });
      }
    }
  }

  Future<bool> initSharedData() {
    if (!splashRepo.apiClient.sharedPreferences
        .containsKey(SharedPreferenceHelper.countryCode)) {
      return splashRepo.apiClient.sharedPreferences.setString(
          SharedPreferenceHelper.countryCode,
          LocalStrings.appLanguages[0].countryCode);
    }
    if (!splashRepo.apiClient.sharedPreferences
        .containsKey(SharedPreferenceHelper.languageCode)) {
      return splashRepo.apiClient.sharedPreferences.setString(
          SharedPreferenceHelper.languageCode,
          LocalStrings.appLanguages[0].languageCode);
    }
    return Future.value(true);
  }

  Future<void> loadLanguage() async {
    localizationController.loadCurrentLanguage();
    String languageCode = localizationController.locale.languageCode;
    Map<String, Map<String, String>> language = {};
    final String response =
        await rootBundle.loadString('assets/lang/$languageCode.json');
    var resJson = jsonDecode(response);
    saveLanguageList(response);
    var value = resJson as Map<String, dynamic>;
    Map<String, String> json = {};
    value.forEach((key, value) {
      json[key] = value.toString();
    });
    language[
            '${localizationController.locale.languageCode}_${localizationController.locale.countryCode}'] =
        json;
    Get.addTranslations(Messages(languages: language).keys);
  }

  void saveLanguageList(String languageJson) async {
    await splashRepo.apiClient.sharedPreferences
        .setString(SharedPreferenceHelper.languageListKey, languageJson);
    return;
  }
}
