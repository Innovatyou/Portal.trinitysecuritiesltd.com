import 'dart:convert';
import 'package:realise/core/helper/biometric_auth_helper.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:flutter/cupertino.dart';
import 'package:get/get.dart';
import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/core/route/route.dart';
import 'package:realise/data/model/auth/login/login_model.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:realise/data/repo/auth/login_repo.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class LoginController extends GetxController {
  LoginRepo loginRepo;

  final FocusNode emailFocusNode = FocusNode();
  final FocusNode passwordFocusNode = FocusNode();

  TextEditingController emailController = TextEditingController();
  TextEditingController passwordController = TextEditingController();

  List<String> errors = [];
  String? email;
  String? password;
  bool remember = false;
  bool isLoading = true;
  String? appLogo = '';

  LoginController({required this.loginRepo});

  Future<void> checkAndGotoNextStep(LoginModel responseModel) async {
    if (remember) {
      await loginRepo.apiClient.sharedPreferences
          .setBool(SharedPreferenceHelper.rememberMeKey, true);
    } else {
      await loginRepo.apiClient.sharedPreferences
          .setBool(SharedPreferenceHelper.rememberMeKey, false);
    }

    await loginRepo.apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.userIdKey,
        responseModel.data?.clientId.toString() ?? '-1');
    await loginRepo.apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.accessTokenKey, responseModel.data?.token ?? '');
    // Dashboard's client-account name/email come back blank for staff
    // logins (the customersapi/dashboard endpoint only knows about
    // client-type accounts) - persisted here so the header has something
    // real to fall back to instead of showing "Welcome -".
    await loginRepo.apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.userNameKey,
        '${responseModel.data?.firstName ?? ''} ${responseModel.data?.lastName ?? ''}'
            .trim());
    await loginRepo.apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.userEmailKey, responseModel.data?.email ?? '');
    await loginRepo.apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.userJobTitleKey,
        responseModel.data?.jobTitle ?? '');
    await loginRepo.apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.userAvatarKey, responseModel.data?.avatar ?? '');
    await loginRepo.apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.userTypeKey, responseModel.data?.userType ?? '');

    Get.offAndToNamed(RouteHelper.dashboardScreen);

    if (remember) {
      changeRememberMe();
    }
  }

  bool isSubmitLoading = false;
  void loginUser() async {
    isSubmitLoading = true;
    update();

    ResponseModel model = await loginRepo.loginUser(
        emailController.text.toString(), passwordController.text.toString());

    if (model.statusCode == 200) {
      LoginModel loginModel =
          LoginModel.fromJson(jsonDecode(model.responseJson));
      if (loginModel.success!) {
        checkAndGotoNextStep(loginModel);
      } else {
        CustomSnackBar.error(errorList: [LocalStrings.loginFailedTryAgain.tr]);
      }
    } else {
      CustomSnackBar.error(errorList: [model.message]);
    }
    isSubmitLoading = false;
    update();
  }

  changeRememberMe() {
    remember = !remember;
    update();
  }

  void initData() async {
    isLoading = true;
    update();
    appLogo = loginRepo.apiClient.sharedPreferences
        .getString(SharedPreferenceHelper.appLogo);

    isLoading = false;
    update();

    await checkBiometricAvailability();
  }

  // Splash only ever offers biometric once, right after app launch, for a
  // "remember me" session - there was previously no way back to it from
  // here (e.g. after cancelling that prompt, or landing back on this
  // screen from Profile's false-logout bug) short of restarting the app.
  bool showBiometricOption = false;

  Future<void> checkBiometricAvailability() async {
    final prefs = loginRepo.apiClient.sharedPreferences;
    final biometricEnabled =
        prefs.getBool(SharedPreferenceHelper.biometricEnabledKey) ?? false;
    final isRemember =
        prefs.getBool(SharedPreferenceHelper.rememberMeKey) ?? false;
    final hasSession =
        (prefs.getString(SharedPreferenceHelper.accessTokenKey) ?? '')
            .isNotEmpty;

    if (!biometricEnabled || !isRemember || !hasSession) {
      showBiometricOption = false;
      update();
      return;
    }

    showBiometricOption = await BiometricAuthHelper.isDeviceSupported();
    update();
  }

  Future<void> signInWithBiometrics() async {
    final ok = await BiometricAuthHelper.authenticate(
      reason: 'Verify it\'s you to continue',
    );
    if (ok) {
      Get.offAndToNamed(RouteHelper.dashboardScreen);
    }
  }

  void clearTextField() {
    passwordController.text = '';
    emailController.text = '';
    if (remember) {
      remember = false;
    }
    update();
  }
}
