import 'dart:convert';

import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/data/model/authorization/authorization_response_model.dart';
import 'package:realise/data/model/global/response_model/response_model.dart';
import 'package:flutter/cupertino.dart';
import 'package:get/get.dart';
import 'package:realise/data/repo/auth/forgot_password_repo.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class ForgetPasswordController extends GetxController {
  ForgotPasswordRepo forgotPasswordRepo;

  ForgetPasswordController({required this.forgotPasswordRepo});

  bool submitLoading = false;
  bool isLoading = true;
  String? appLogo = '';
  TextEditingController emailController = TextEditingController();

  void initData() async {
    isLoading = true;
    update();
    appLogo = forgotPasswordRepo.apiClient.sharedPreferences
        .getString(SharedPreferenceHelper.appLogo);

    isLoading = false;
    update();
  }

  void submitForgetPassword() async {
    String email = emailController.text;

    if (email.isEmpty) {
      CustomSnackBar.error(errorList: [LocalStrings.enterEmail.tr]);
      return;
    }

    submitLoading = true;
    update();
    ResponseModel response = await forgotPasswordRepo.forgetPassword(email);
    if (response.statusCode == 200) {
      emailController.text = '';
      AuthorizationResponseModel res = AuthorizationResponseModel.fromJson(
          jsonDecode(response.responseJson));
      if (res.success!) {
        CustomSnackBar.success(successList: [res.message!.tr]);
        Get.toNamed(RouteHelper.loginScreen);
      } else {
        CustomSnackBar.error(errorList: [res.message!.tr]);
      }
    } else {
      CustomSnackBar.error(errorList: [(response.message.tr)]);
    }

    submitLoading = false;
    update();
  }
}
