import 'package:realise/core/utils/local_strings.dart';
import 'package:flutter/material.dart';
import 'package:flutter_widget_from_html/flutter_widget_from_html.dart';
import 'package:get/get.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/controller/privacy/privacy_controller.dart';
import 'package:realise/data/repo/privacy/privacy_repo.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/app-bar/custom_appbar.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';

class PrivacyPolicyScreen extends StatefulWidget {
  const PrivacyPolicyScreen({super.key});

  @override
  State<PrivacyPolicyScreen> createState() => _PrivacyPolicyScreenState();
}

class _PrivacyPolicyScreenState extends State<PrivacyPolicyScreen> {
  @override
  void initState() {
    Get.put(ApiClient(sharedPreferences: Get.find()));
    Get.put(PrivacyRepo(apiClient: Get.find()));
    final controller = Get.put(PrivacyController(privacyRepo: Get.find()));

    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((timeStamp) {
      controller.loadData();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
        appBar: CustomAppBar(
          title: LocalStrings.privacyPolicy.tr,
          bgColor: Theme.of(context).appBarTheme.backgroundColor!,
        ),
        body: GetBuilder<PrivacyController>(
          builder: (controller) => SizedBox(
            width: MediaQuery.of(context).size.width,
            child: controller.isLoading
                ? const CustomLoader()
                : SingleChildScrollView(
                    child: Padding(
                        padding: const EdgeInsets.all(20),
                        child: HtmlWidget('',
                            textStyle: regularDefault,
                            onLoadingBuilder:
                                (context, element, loadingProgress) =>
                                    const Center(child: CustomLoader())))),
          ),
        ));
  }
}
