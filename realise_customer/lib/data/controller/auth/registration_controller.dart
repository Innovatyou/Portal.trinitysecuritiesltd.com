import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/route/route.dart';
import 'package:realise/data/model/auth/sign_up_model/sign_up_model.dart';
import 'package:realise/data/repo/auth/signup_repo.dart';
import 'package:realise/view/components/snack_bar/show_custom_snackbar.dart';

class RegistrationController extends GetxController {
  RegistrationRepo registrationRepo;

  RegistrationController({required this.registrationRepo});

  bool isLoading = true;
  bool agreeTC = false;
  bool needAgree = true;
  String? appLogo = '';

  final FocusNode emailFocusNode = FocusNode();
  final FocusNode mobileFocusNode = FocusNode();
  final FocusNode passwordFocusNode = FocusNode();
  final FocusNode confirmPasswordFocusNode = FocusNode();
  final FocusNode firstNameFocusNode = FocusNode();
  final FocusNode lastNameFocusNode = FocusNode();
  final FocusNode countryNameFocusNode = FocusNode();
  final FocusNode companyFocusNode = FocusNode();

  final TextEditingController emailController = TextEditingController();
  final TextEditingController mobileController = TextEditingController();
  final TextEditingController accountTypeController =
      TextEditingController(text: 'organization');
  final TextEditingController passwordController = TextEditingController();
  final TextEditingController cPasswordController = TextEditingController();
  final TextEditingController firstNameController = TextEditingController();
  final TextEditingController lastNameController = TextEditingController();
  final TextEditingController countryController = TextEditingController();
  final TextEditingController companyController = TextEditingController();

  String? email;
  String? mobile;
  String? password;
  String? confirmPassword;
  String? company;
  String? phoneNo;
  String? firstName;
  String? lastName;

  RegExp regex = RegExp(r'[!@#$%^&*(),.?":{}|<>]');

  bool submitLoading = false;

  signUpUser() async {
    if (!agreeTC) {
      CustomSnackBar.error(
        errorList: [LocalStrings.agreePolicyMessage.tr],
      );
      return;
    }

    submitLoading = true;
    update();

    SignUpModel model = getUserData();
    final responseModel = await registrationRepo.registerUser(model);
    if (responseModel.success!) {
      CustomSnackBar.success(successList: [responseModel.message!]);
      Get.offAndToNamed(RouteHelper.loginScreen);
    } else {
      CustomSnackBar.error(errorList: [responseModel.message!]);
      submitLoading = false;
      update();
    }
    submitLoading = false;
    update();
  }

  updateAgreeTC() {
    agreeTC = !agreeTC;
    update();
  }

  SignUpModel getUserData() {
    SignUpModel model = SignUpModel(
      firstName: firstNameController.text.toString(),
      lastName: lastNameController.text.toString(),
      email: emailController.text.toString(),
      mobile: mobileController.text.toString(),
      accountType: accountTypeController.text.toString(),
      company: companyController.text.toString(),
      password: passwordController.text.toString(),
    );

    return model;
  }

  void closeAllController() {
    isLoading = false;
    emailController.text = '';
    mobileController.text = '';
    passwordController.text = '';
    cPasswordController.text = '';
    firstNameController.text = '';
    lastNameController.text = '';
  }

  clearAllData() {
    closeAllController();
  }

  void initData() async {
    isLoading = true;
    update();
    appLogo = registrationRepo.apiClient.sharedPreferences
        .getString(SharedPreferenceHelper.appLogo);

    isLoading = false;
    update();
  }

  String? validatePassword(String value) {
    if (value.isEmpty) {
      return LocalStrings.enterYourPassword.tr;
    }
    return null;
  }

  bool hasPasswordFocus = false;
  void changePasswordFocus(bool hasFocus) {
    hasPasswordFocus = hasFocus;
    update();
  }

  bool isActiveAccount = true;
  void changeState(bool activeState) {
    isActiveAccount = activeState;
    update();
  }
}
