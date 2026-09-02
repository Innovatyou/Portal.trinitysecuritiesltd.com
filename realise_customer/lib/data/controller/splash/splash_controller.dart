import 'dart:convert';

import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/messages.dart';
import 'package:realise/data/controller/localization/localization_controller.dart';
import 'package:realise/data/model/global/overview_model.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/core/route/route.dart';
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
        Future.delayed(const Duration(seconds: 1), () {
          Get.offAndToNamed(RouteHelper.dashboardScreen);
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
