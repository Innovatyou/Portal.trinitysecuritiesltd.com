import 'dart:convert';
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

  TextEditingController emailController =
      TextEditingController(text: 'customer@demo.com');
  TextEditingController passwordController =
      TextEditingController(text: '123456');

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
