import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/images.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/controller/auth/login_controller.dart';
import 'package:realise/data/repo/auth/login_repo.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/buttons/rounded_button.dart';
import 'package:realise/view/components/buttons/rounded_loading_button.dart';
import 'package:realise/view/components/text-form-field/custom_text_field.dart';
import 'package:realise/view/components/text/default_text.dart';
import 'package:realise/view/components/will_pop_widget.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final formKey = GlobalKey<FormState>();

  @override
  void initState() {
    Get.put(ApiClient(sharedPreferences: Get.find()));
    Get.put(LoginRepo(apiClient: Get.find()));
    Get.put(LoginController(loginRepo: Get.find()));

    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<LoginController>().initData();
      Get.find<LoginController>().remember = false;
    });
  }

  @override
  void dispose() {
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return WillPopWidget(
      nextRoute: '',
      child: SafeArea(
        child: Scaffold(
          backgroundColor: Theme.of(context).scaffoldBackgroundColor,
          body: GetBuilder<LoginController>(
            builder: (controller) => SingleChildScrollView(
              child: Column(
                children: [
                  Container(
                    color: Theme.of(context).scaffoldBackgroundColor,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        SizedBox(
                          height: MediaQuery.sizeOf(context).height * 0.46,
                          width: double.infinity,
                          child: Stack(
                            fit: StackFit.expand,
                            children: [
                              Image.asset(
                                MyImages.loginLagosTeam,
                                fit: BoxFit.cover,
                                alignment: Alignment.center,
                              ),
                              const DecoratedBox(
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    begin: Alignment.topCenter,
                                    end: Alignment.bottomCenter,
                                    colors: [
                                      Color(0x12000F28),
                                      Color(0x08000F28),
                                      Color(0xD900132A),
                                    ],
                                    stops: [0.0, 0.55, 1.0],
                                  ),
                                ),
                              ),
                              Positioned(
                                top: 22,
                                left: Dimensions.space20,
                                right: Dimensions.space20,
                                child: Align(
                                  alignment: Alignment.centerLeft,
                                  child: Container(
                                    height: 64,
                                    constraints:
                                        const BoxConstraints(maxWidth: 250),
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 14, vertical: 8),
                                    decoration: BoxDecoration(
                                      color: const Color(0xEFFFFFFF),
                                      borderRadius: BorderRadius.circular(18),
                                      border: Border.all(
                                          color: Colors.white, width: 1.2),
                                      boxShadow: const [
                                        BoxShadow(
                                          color: Color(0x3800132A),
                                          blurRadius: 24,
                                          offset: Offset(0, 10),
                                        ),
                                      ],
                                    ),
                                    child: Image.asset(
                                      MyImages.workflowWordmark,
                                      fit: BoxFit.contain,
                                      alignment: Alignment.centerLeft,
                                    ),
                                  ),
                                ),
                              ),
                              Positioned(
                                left: Dimensions.space20,
                                right: Dimensions.space20,
                                bottom: 26,
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 11, vertical: 6),
                                      decoration: BoxDecoration(
                                        color: ColorResources.primaryColor,
                                        borderRadius: BorderRadius.circular(30),
                                      ),
                                      child: const Text(
                                        'OPERATIONS ON THE GO',
                                        style: TextStyle(
                                          color: Color(0xFF102000),
                                          fontSize: 10,
                                          fontWeight: FontWeight.w800,
                                          letterSpacing: 1.2,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(height: 12),
                                    Text(
                                      LocalStrings.login.tr,
                                      style: mediumOverLarge.copyWith(
                                        fontSize: Dimensions.fontMegaLarge,
                                        color: Colors.white,
                                      ),
                                    ),
                                    const SizedBox(height: 3),
                                    Text(
                                      'Post, review and approve your workflow securely.',
                                      style: regularDefault.copyWith(
                                        fontSize: Dimensions.fontDefault,
                                        color: const Color(0xE6FFFFFF),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                        Transform.translate(
                          offset: const Offset(0, -18),
                          child: Container(
                            width: MediaQuery.sizeOf(context).width,
                            decoration: BoxDecoration(
                              color: Theme.of(context).scaffoldBackgroundColor,
                              borderRadius: const BorderRadius.only(
                                topLeft: Radius.circular(30),
                                topRight: Radius.circular(30),
                              ),
                              boxShadow: const [
                                BoxShadow(
                                  color: Color(0x2200132A),
                                  blurRadius: 28,
                                  offset: Offset(0, -8),
                                ),
                              ],
                            ),
                            padding: const EdgeInsets.fromLTRB(
                              Dimensions.space20,
                              30,
                              Dimensions.space20,
                              Dimensions.space20,
                            ),
                            child: Form(
                              key: formKey,
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.center,
                                children: [
                                  CustomTextField(
                                    animatedLabel: true,
                                    needOutlineBorder: true,
                                    controller: controller.emailController,
                                    labelText: LocalStrings.email.tr,
                                    onChanged: (value) {},
                                    focusNode: controller.emailFocusNode,
                                    nextFocus: controller.passwordFocusNode,
                                    textInputType: TextInputType.emailAddress,
                                    inputAction: TextInputAction.next,
                                    validator: (value) {
                                      if (value!.isEmpty) {
                                        return 'fieldErrorMsg'.tr;
                                      } else {
                                        return null;
                                      }
                                    },
                                  ),
                                  const SizedBox(height: Dimensions.space20),
                                  CustomTextField(
                                    animatedLabel: true,
                                    needOutlineBorder: true,
                                    labelText: LocalStrings.password.tr,
                                    controller: controller.passwordController,
                                    focusNode: controller.passwordFocusNode,
                                    onChanged: (value) {},
                                    isShowSuffixIcon: true,
                                    isPassword: true,
                                    textInputType: TextInputType.text,
                                    inputAction: TextInputAction.done,
                                    validator: (value) {
                                      if (value!.isEmpty) {
                                        return LocalStrings.fieldErrorMsg.tr;
                                      } else {
                                        return null;
                                      }
                                    },
                                  ),
                                  const SizedBox(height: Dimensions.space20),
                                  Row(
                                    mainAxisAlignment:
                                        MainAxisAlignment.spaceBetween,
                                    children: [
                                      Row(
                                        children: [
                                          SizedBox(
                                            width: 25,
                                            height: 25,
                                            child: Checkbox(
                                              shape: RoundedRectangleBorder(
                                                borderRadius:
                                                    BorderRadius.circular(
                                                  Dimensions.defaultRadius,
                                                ),
                                              ),
                                              activeColor:
                                                  ColorResources.primaryColor,
                                              checkColor:
                                                  ColorResources.colorWhite,
                                              value: controller.remember,
                                              side: WidgetStateBorderSide
                                                  .resolveWith(
                                                (states) => BorderSide(
                                                  width: 1.0,
                                                  color: controller.remember
                                                      ? ColorResources
                                                          .getTextFieldEnableBorder()
                                                      : ColorResources
                                                          .getTextFieldDisableBorder(),
                                                ),
                                              ),
                                              onChanged: (value) {
                                                controller.changeRememberMe();
                                              },
                                            ),
                                          ),
                                          const SizedBox(width: 8),
                                          DefaultText(
                                            text: LocalStrings.rememberMe.tr,
                                            textColor: Theme.of(context)
                                                .textTheme
                                                .bodyMedium!
                                                .color!
                                                .withValues(alpha: 0.5),
                                          ),
                                        ],
                                      ),
                                      InkWell(
                                        onTap: () {
                                          controller.clearTextField();
                                          Get.toNamed(
                                            RouteHelper.forgotPasswordScreen,
                                          );
                                        },
                                        child: DefaultText(
                                          text: LocalStrings.forgotPassword.tr,
                                          textColor:
                                              ColorResources.secondaryColor,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: Dimensions.space20),
                                  controller.isSubmitLoading
                                      ? const RoundedLoadingBtn()
                                      : RoundedButton(
                                          text: LocalStrings.signIn.tr,
                                          press: () {
                                            if (formKey.currentState!
                                                .validate()) {
                                              controller.loginUser();
                                            }
                                          },
                                        ),
                                  const SizedBox(height: Dimensions.space20),
                                  if (controller
                                          .loginRepo.apiClient.sharedPreferences
                                          .getString(
                                        SharedPreferenceHelper
                                            .disableRegistration,
                                      ) !=
                                      '1')
                                    Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.center,
                                      children: [
                                        Text(
                                          LocalStrings.doNotHaveAccount.tr,
                                          overflow: TextOverflow.ellipsis,
                                          style: regularLarge.copyWith(
                                            color: Theme.of(context)
                                                .textTheme
                                                .bodyMedium!
                                                .color!
                                                .withValues(alpha: 0.5),
                                            fontWeight: FontWeight.w400,
                                          ),
                                        ),
                                        TextButton(
                                          onPressed: () {
                                            Get.offAndToNamed(
                                              RouteHelper.registrationScreen,
                                            );
                                          },
                                          child: Text(
                                            LocalStrings.createAnAccount.tr,
                                            maxLines: 2,
                                            overflow: TextOverflow.ellipsis,
                                            style: regularLarge.copyWith(
                                              color:
                                                  ColorResources.secondaryColor,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
