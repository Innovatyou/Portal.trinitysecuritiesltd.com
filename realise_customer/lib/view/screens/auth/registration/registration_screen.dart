import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/images.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/controller/auth/registration_controller.dart';
import 'package:realise/data/repo/auth/signup_repo.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';
import 'package:realise/view/components/will_pop_widget.dart';
import 'package:realise/view/screens/auth/registration/widget/account_form.dart';

class RegistrationScreen extends StatefulWidget {
  const RegistrationScreen({super.key});
  @override
  State<RegistrationScreen> createState() => _RegistrationScreenState();
}

class _RegistrationScreenState extends State<RegistrationScreen> {
  @override
  void initState() {
    Get.put(ApiClient(sharedPreferences: Get.find()));
    Get.put(RegistrationRepo(apiClient: Get.find()));
    Get.put(RegistrationController(registrationRepo: Get.find()));
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) =>
        Get.find<RegistrationController>().initData());
  }

  void _returnToLogin(RegistrationController controller) {
    controller.clearAllData();
    Get.offAndToNamed(RouteHelper.loginScreen);
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<RegistrationController>(
      builder: (controller) => WillPopWidget(
        nextRoute: RouteHelper.loginScreen,
        child: SafeArea(
          child: Scaffold(
            backgroundColor: Theme.of(context).scaffoldBackgroundColor,
            body: controller.isLoading
                ? const CustomLoader()
                : SingleChildScrollView(
                    child: Column(children: [
                      SizedBox(
                        height: MediaQuery.sizeOf(context).height * 0.40,
                        width: double.infinity,
                        child: Stack(fit: StackFit.expand, children: [
                          Image.asset(MyImages.loginLagosTeam,
                              fit: BoxFit.cover,
                              alignment: Alignment.center),
                          const DecoratedBox(
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                begin: Alignment.topCenter,
                                end: Alignment.bottomCenter,
                                colors: [
                                  Color(0x12000F28),
                                  Color(0x08000F28),
                                  Color(0xD900132A)
                                ],
                                stops: [0.0, 0.55, 1.0],
                              ),
                            ),
                          ),
                          Positioned(
                            top: 18,
                            left: Dimensions.space20,
                            child: Row(children: [
                              Material(
                                color: const Color(0xEFFFFFFF),
                                borderRadius: BorderRadius.circular(16),
                                child: IconButton(
                                  tooltip: LocalStrings.signIn.tr,
                                  icon: const Icon(Icons.arrow_back_ios_new,
                                      size: 20, color: Color(0xFF00132A)),
                                  onPressed: () => _returnToLogin(controller),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Container(
                                height: 58,
                                constraints:
                                    const BoxConstraints(maxWidth: 220),
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 13, vertical: 8),
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
                                    )
                                  ],
                                ),
                                child: Image.asset(MyImages.workflowWordmark,
                                    fit: BoxFit.contain,
                                    alignment: Alignment.centerLeft),
                              ),
                            ]),
                          ),
                          Positioned(
                            left: Dimensions.space20,
                            right: Dimensions.space20,
                            bottom: 24,
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
                                  child: const Text('JOIN THE WORKFLOW',
                                      style: TextStyle(
                                          color: Color(0xFF102000),
                                          fontSize: 10,
                                          fontWeight: FontWeight.w800,
                                          letterSpacing: 1.2)),
                                ),
                                const SizedBox(height: 10),
                                Text(LocalStrings.signUp.tr,
                                    style: mediumOverLarge.copyWith(
                                        fontSize: Dimensions.fontMegaLarge,
                                        color: Colors.white)),
                                const SizedBox(height: 3),
                                Text(
                                  'Create your client account and manage requests on the go.',
                                  style: regularDefault.copyWith(
                                      fontSize: Dimensions.fontDefault,
                                      color: const Color(0xE6FFFFFF)),
                                ),
                              ],
                            ),
                          ),
                        ]),
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
                              )
                            ],
                          ),
                          padding: const EdgeInsets.fromLTRB(
                              Dimensions.space20,
                              30,
                              Dimensions.space20,
                              Dimensions.space20),
                          child: Column(children: [
                            const AccountForm(),
                            const SizedBox(height: Dimensions.space10),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Flexible(
                                  child: Text(LocalStrings.alreadyAccount.tr,
                                      style: regularLarge.copyWith(
                                          color: Theme.of(context)
                                              .textTheme
                                              .bodyMedium!
                                              .color!
                                              .withValues(alpha: 0.5),
                                          fontWeight: FontWeight.w500)),
                                ),
                                TextButton(
                                  onPressed: () => _returnToLogin(controller),
                                  child: Text(LocalStrings.signIn.tr,
                                      style: regularLarge.copyWith(
                                          color:
                                              ColorResources.secondaryColor)),
                                ),
                              ],
                            ),
                          ]),
                        ),
                      ),
                    ]),
                  ),
          ),
        ),
      ),
    );
  }
}
