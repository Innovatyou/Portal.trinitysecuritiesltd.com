import 'package:realise/core/utils/images.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/controller/auth/registration_controller.dart';
import 'package:realise/view/components/buttons/rounded_button.dart';
import 'package:realise/view/components/buttons/rounded_loading_button.dart';
import 'package:realise/view/components/text-form-field/custom_text_field.dart';
import 'package:realise/view/screens/auth/registration/widget/select_account_type_widget.dart';

class AccountForm extends StatefulWidget {
  const AccountForm({super.key});

  @override
  State<AccountForm> createState() => _AccountFormState();
}

class _AccountFormState extends State<AccountForm> {
  final formKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return GetBuilder<RegistrationController>(
      builder: (controller) {
        return Form(
          key: formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  SelectAccountTypeWidget(
                    accountType: LocalStrings.companyAccount.tr,
                    imageSrc: MyImages.company,
                    isActive: controller.isActiveAccount,
                    press: () {
                      if (!controller.isActiveAccount) {
                        controller.changeState(true);
                        controller.accountTypeController.text = 'organization';
                      }
                    },
                  ),
                  const SizedBox(width: Dimensions.space10),
                  SelectAccountTypeWidget(
                    accountType: LocalStrings.personalAccount.tr,
                    imageSrc: MyImages.personal,
                    isActive: !controller.isActiveAccount,
                    press: () {
                      if (controller.isActiveAccount) {
                        controller.changeState(false);
                        controller.accountTypeController.text = 'individual';
                      }
                    },
                  ),
                ],
              ),
              const SizedBox(height: Dimensions.space15),
              CustomTextField(
                animatedLabel: true,
                needOutlineBorder: true,
                labelText: LocalStrings.companyName.tr,
                controller: controller.companyController,
                focusNode: controller.companyFocusNode,
                textInputType: TextInputType.text,
                nextFocus: controller.firstNameFocusNode,
                validator: (value) {
                  if (value!.isEmpty) {
                    return LocalStrings.enterCompanyName.tr;
                  } else {
                    return null;
                  }
                },
                onChanged: (value) {
                  return;
                },
              ),
              const SizedBox(height: Dimensions.space15),
              CustomTextField(
                animatedLabel: true,
                needOutlineBorder: true,
                labelText: LocalStrings.firstName.tr,
                hintText: LocalStrings.enterFirstName.tr,
                textInputType: TextInputType.text,
                inputAction: TextInputAction.next,
                focusNode: controller.firstNameFocusNode,
                controller: controller.firstNameController,
                nextFocus: controller.lastNameFocusNode,
                onChanged: (value) {
                  return;
                },
              ),
              const SizedBox(height: Dimensions.space15),
              CustomTextField(
                animatedLabel: true,
                needOutlineBorder: true,
                labelText: LocalStrings.lastName.tr,
                hintText: LocalStrings.enterLastName.tr,
                textInputType: TextInputType.text,
                inputAction: TextInputAction.next,
                focusNode: controller.lastNameFocusNode,
                controller: controller.lastNameController,
                nextFocus: controller.emailFocusNode,
                onChanged: (value) {
                  return;
                },
              ),
              const SizedBox(height: Dimensions.space15),
              CustomTextField(
                animatedLabel: true,
                needOutlineBorder: true,
                labelText: LocalStrings.email.tr,
                controller: controller.emailController,
                focusNode: controller.emailFocusNode,
                nextFocus: controller.mobileFocusNode,
                textInputType: TextInputType.emailAddress,
                inputAction: TextInputAction.next,
                validator: (value) {
                  if (value != null && value.isEmpty) {
                    return LocalStrings.enterEmail.tr;
                  } else if (!UrlContainer.emailValidatorRegExp
                      .hasMatch(value ?? '')) {
                    return LocalStrings.invalidEmailMsg.tr;
                  } else {
                    return null;
                  }
                },
                onChanged: (value) {
                  return;
                },
              ),
              const SizedBox(height: Dimensions.space15),
              CustomTextField(
                animatedLabel: true,
                needOutlineBorder: true,
                labelText: LocalStrings.mobileNumber.tr,
                controller: controller.mobileController,
                focusNode: controller.mobileFocusNode,
                nextFocus: controller.passwordFocusNode,
                textInputType: TextInputType.number,
                inputAction: TextInputAction.next,
                onChanged: (value) {
                  return;
                },
              ),
              const SizedBox(height: Dimensions.space15),
              Focus(
                  onFocusChange: (hasFocus) {
                    controller.changePasswordFocus(hasFocus);
                  },
                  child: CustomTextField(
                    animatedLabel: true,
                    needOutlineBorder: true,
                    isShowSuffixIcon: true,
                    isPassword: true,
                    labelText: LocalStrings.password.tr,
                    controller: controller.passwordController,
                    focusNode: controller.passwordFocusNode,
                    nextFocus: controller.confirmPasswordFocusNode,
                    textInputType: TextInputType.text,
                    onChanged: (value) {},
                    validator: (value) {
                      return controller.validatePassword(value ?? '');
                    },
                  )),
              const SizedBox(height: Dimensions.space15),
              CustomTextField(
                animatedLabel: true,
                needOutlineBorder: true,
                labelText: LocalStrings.confirmPassword.tr,
                controller: controller.cPasswordController,
                focusNode: controller.confirmPasswordFocusNode,
                inputAction: TextInputAction.done,
                isShowSuffixIcon: true,
                isPassword: true,
                onChanged: (value) {},
                validator: (value) {
                  if (controller.passwordController.text.toLowerCase() !=
                      controller.cPasswordController.text.toLowerCase()) {
                    return LocalStrings.passwordMatchError.tr;
                  } else {
                    return null;
                  }
                },
              ),
              const SizedBox(height: Dimensions.space20),
              Row(
                children: [
                  SizedBox(
                    width: 25,
                    height: 25,
                    child: Checkbox(
                      shape: RoundedRectangleBorder(
                          borderRadius:
                              BorderRadius.circular(Dimensions.defaultRadius)),
                      activeColor: ColorResources.primaryColor,
                      checkColor: ColorResources.colorWhite,
                      value: controller.agreeTC,
                      side: WidgetStateBorderSide.resolveWith(
                        (states) => BorderSide(
                            width: 1.0,
                            color: controller.agreeTC
                                ? ColorResources.getTextFieldEnableBorder()
                                : ColorResources.getTextFieldDisableBorder()),
                      ),
                      onChanged: (bool? value) {
                        controller.updateAgreeTC();
                      },
                    ),
                  ),
                  const SizedBox(width: Dimensions.space8),
                  Row(
                    children: [
                      Text(LocalStrings.iAgreeWith.tr,
                          style: regularDefault.copyWith(
                              color: Theme.of(context)
                                  .textTheme
                                  .bodyMedium!
                                  .color!
                                  .withOpacity(0.5))),
                      const SizedBox(width: Dimensions.space3),
                      GestureDetector(
                        onTap: () {
                          Get.toNamed(RouteHelper.privacyScreen);
                        },
                        child: Text(LocalStrings.policies.tr.toLowerCase(),
                            style: regularDefault.copyWith(
                                color: Theme.of(context).primaryColor,
                                decoration: TextDecoration.underline,
                                decorationColor:
                                    ColorResources.getPrimaryColor())),
                      ),
                      const SizedBox(width: Dimensions.space3),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: Dimensions.space20),
              controller.submitLoading
                  ? const RoundedLoadingBtn()
                  : RoundedButton(
                      text: LocalStrings.signUp.tr,
                      press: () {
                        if (formKey.currentState!.validate()) {
                          controller.signUpUser();
                        }
                      }),
            ],
          ),
        );
      },
    );
  }
}
