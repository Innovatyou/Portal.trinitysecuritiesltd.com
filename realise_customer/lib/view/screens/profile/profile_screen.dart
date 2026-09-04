import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/core/utils/images.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/data/controller/profile/profile_controller.dart';
import 'package:realise/data/repo/profile/profile_repo.dart';
import 'package:realise/view/components/circle_image_button.dart';
import 'package:realise/view/components/column_widget/card_column.dart';
import 'package:realise/view/components/divider/custom_divider.dart';
import 'package:realise/view/components/image/circle_shape_image.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/app-bar/custom_appbar.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  @override
  void initState() {
    Get.put(ApiClient(sharedPreferences: Get.find()));
    Get.put(ProfileRepo(apiClient: Get.find()));
    final controller = Get.put(ProfileController(profileRepo: Get.find()));
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((timeStamp) {
      controller.loadData();
    });
  }

  @override
  void dispose() {
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<ProfileController>(
      builder: (controller) {
        final prefs = controller.profileRepo.apiClient.sharedPreferences;

        // profileModel.data is null for a staff login (customersapi/profile
        // only supports client accounts - see Operations_api vs
        // RestApiController) - fall back to what login already gave us
        // instead of crashing on a null check, or showing nothing.
        final clientData = controller.profileModel.data;

        String name =
            '${clientData?.firstName?.capitalize ?? ''} ${clientData?.lastName?.capitalize ?? ''}'
                .trim();
        if (name.isEmpty) {
          name = prefs.getString(SharedPreferenceHelper.userNameKey) ?? '';
        }

        String subtitle =
            '${clientData?.companyName?.capitalize ?? ''} - ${clientData?.type?.capitalize ?? ''}';
        if (subtitle.replaceAll('-', '').trim().isEmpty) {
          subtitle =
              prefs.getString(SharedPreferenceHelper.userJobTitleKey) ?? '';
        }

        final email = clientData?.email ??
            prefs.getString(SharedPreferenceHelper.userEmailKey) ??
            '';
        final avatarFile = (clientData?.avatar?.isNotEmpty ?? false)
            ? clientData!.avatar!
            : prefs.getString(SharedPreferenceHelper.userAvatarKey) ?? '';

        return SafeArea(
          child: Scaffold(
            backgroundColor: Theme.of(context).scaffoldBackgroundColor,
            appBar: CustomAppBar(
              title: LocalStrings.profile.tr,
              bgColor: Theme.of(context).appBarTheme.backgroundColor!,
            ),
            body: controller.isLoading
                ? const CustomLoader()
                : Stack(
                    children: [
                      Positioned(
                        top: -10,
                        child: Container(
                          height: 100,
                          width: MediaQuery.of(context).size.width,
                          color:
                              Theme.of(context).appBarTheme.backgroundColor!,
                        ),
                      ),
                      Align(
                        alignment: Alignment.topCenter,
                        child: SingleChildScrollView(
                          padding: const EdgeInsets.only(
                              left: Dimensions.space15,
                              right: Dimensions.space15,
                              top: Dimensions.space20,
                              bottom: Dimensions.space20),
                          child: Container(
                            width: MediaQuery.of(context).size.width,
                            padding: const EdgeInsets.symmetric(
                                vertical: Dimensions.space15,
                                horizontal: Dimensions.space30),
                            decoration: BoxDecoration(
                                color: Theme.of(context).cardColor,
                                borderRadius: BorderRadius.circular(15)),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.start,
                                  children: [
                                    GestureDetector(
                                      onTap: controller.isUploadingAvatar
                                          ? null
                                          : controller.pickAndUploadAvatar,
                                      child: Stack(
                                        clipBehavior: Clip.none,
                                        children: [
                                          Container(
                                            height: 100,
                                            width: 100,
                                            padding: EdgeInsets.zero,
                                            alignment: Alignment.center,
                                            decoration: BoxDecoration(
                                                shape: BoxShape.circle,
                                                color: Colors.transparent,
                                                border: Border.all(
                                                    width: .3,
                                                    color: Theme.of(context)
                                                        .primaryColor)),
                                            child: controller.isUploadingAvatar
                                                ? const SizedBox(
                                                    height: 32,
                                                    width: 32,
                                                    child:
                                                        CircularProgressIndicator(
                                                            strokeWidth: 3),
                                                  )
                                                : CircleImageWidget(
                                                    isProfile: true,
                                                    imagePath:
                                                        '${UrlContainer.profileImgUrl}$avatarFile',
                                                    height: 80,
                                                    width: 80,
                                                    isAsset: false,
                                                  ),
                                          ),
                                          Positioned(
                                            bottom: 0,
                                            right: 0,
                                            child: Container(
                                              padding:
                                                  const EdgeInsets.all(6),
                                              decoration: BoxDecoration(
                                                shape: BoxShape.circle,
                                                color: ColorResources
                                                    .primaryColor,
                                                border: Border.all(
                                                    color: Theme.of(context)
                                                        .cardColor,
                                                    width: 2),
                                              ),
                                              child: const Icon(
                                                Icons.camera_alt,
                                                size: 14,
                                                color: ColorResources
                                                    .colorBlack,
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    const SizedBox(width: Dimensions.space15),
                                    SizedBox(
                                      width: MediaQuery.of(context).size.width /
                                          2,
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            name,
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            style: regularExtraLarge,
                                          ),
                                          if (subtitle
                                              .replaceAll('-', '')
                                              .trim()
                                              .isNotEmpty)
                                            Text(
                                              subtitle,
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                              style: lightSmall,
                                            ),
                                        ],
                                      ),
                                    )
                                  ],
                                ),
                                const SizedBox(height: Dimensions.space20),
                                Row(
                                  children: [
                                    CircleShapeImage(
                                        imageColor: Theme.of(context)
                                            .appBarTheme
                                            .backgroundColor,
                                        image: MyImages.email),
                                    const SizedBox(width: Dimensions.space15),
                                    CardColumn(
                                        header: LocalStrings.email.tr,
                                        body: email)
                                  ],
                                ),
                                const CustomDivider(space: Dimensions.space15),
                                Row(
                                  children: [
                                    CircleShapeImage(
                                        imageColor: Theme.of(context)
                                            .appBarTheme
                                            .backgroundColor,
                                        image: MyImages.phone),
                                    const SizedBox(width: Dimensions.space15),
                                    CardColumn(
                                        header: LocalStrings.phone.tr,
                                        body: controller
                                                .profileModel.data?.phone ??
                                            "-")
                                  ],
                                ),
                                const CustomDivider(space: Dimensions.space15),
                                Row(
                                  children: [
                                    CircleShapeImage(
                                        imageColor: Theme.of(context)
                                            .appBarTheme
                                            .backgroundColor,
                                        image: MyImages.address),
                                    const SizedBox(width: Dimensions.space15),
                                    CardColumn(
                                        header: LocalStrings.address.tr,
                                        body: controller
                                                .profileModel.data?.address ??
                                            "")
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ),
                      )
                    ],
                  ),
          ),
        );
      },
    );
  }
}
