import 'package:cached_network_image/cached_network_image.dart';
import 'package:realise/core/helper/shared_preference_helper.dart';
import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/images.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/data/controller/messages/messages_controller.dart';
import 'package:realise/data/controller/operations/operations_controller.dart';
import 'package:realise/data/repo/messages/messages_repo.dart';
import 'package:realise/data/repo/operations/operations_repo.dart';
import 'package:realise/view/components/app-bar/action_button_icon_widget.dart';
import 'package:realise/view/components/circle_image_button.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';
import 'package:realise/view/components/no_data.dart';
import 'package:realise/view/screens/dashboard/widget/modern_stat_card.dart';
import 'package:realise/view/screens/dashboard/widget/overview_card.dart';
import 'package:realise/view/screens/dashboard/widget/drawer.dart';
import 'package:realise/view/screens/project/widget/project_card.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/data/controller/dashboard/dashboard_controller.dart';
import 'package:realise/data/repo/dashboard/dashboard_repo.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/will_pop_widget.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  @override
  void initState() {
    Get.put(ApiClient(sharedPreferences: Get.find()));
    Get.put(DashboardRepo(apiClient: Get.find()));
    final controller = Get.put(DashboardController(dashboardRepo: Get.find()));
    controller.isLoading = true;

    // Operations stats/inbox/mine-count for the quick-access cards below -
    // separate from DashboardController, which only ever carries
    // client-account data (projects/invoices/payments) that's blank for a
    // staff account like this one. This is the same Operations feature
    // already used by OperationsScreen, just surfaced here too.
    Get.put(OperationsRepo(api: Get.find()));
    final operationsController =
        Get.put(OperationsController(repo: Get.find()));

    Get.put(MessagesRepo(api: Get.find()));
    final messagesController = Get.put(MessagesController(repo: Get.find()));

    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((timeStamp) {
      controller.initialData();
      operationsController.load();
      // Kept running for the app session (not stopped when leaving this
      // screen) so the unread badge in the app bar stays live everywhere,
      // not just while the Messages screen itself is open.
      messagesController.loadConversations();
      messagesController.startListPolling();
    });
  }

  @override
  Widget build(BuildContext context) {
    return WillPopWidget(
      nextRoute: "",
      child: GetBuilder<DashboardController>(
        builder: (controller) => Scaffold(
          backgroundColor: Theme.of(context).scaffoldBackgroundColor,
          appBar: AppBar(
            toolbarHeight: 50,
            leading: Builder(builder: (context) {
              return IconButton(
                icon: const Icon(
                  Icons.menu_rounded,
                  size: 30,
                  color: ColorResources.colorBlack,
                ),
                onPressed: () {
                  Scaffold.of(context).openDrawer();
                },
                tooltip: MaterialLocalizations.of(context).openAppDrawerTooltip,
              );
            }),
            centerTitle: true,
            title: CachedNetworkImage(
                imageUrl: '${UrlContainer.systemImgUrl}${controller.appLogo}',
                height: 30,
                errorWidget: (ctx, object, trx) {
                  return Image.asset(
                    MyImages.appLogoFlat,
                    height: 30,
                  );
                },
                placeholder: (ctx, trx) {
                  return Image.asset(
                    MyImages.appLogo,
                    height: 30,
                  );
                }),
            actions: [
              GetBuilder<MessagesController>(builder: (messagesController) {
                final unread = messagesController.totalUnread;
                return Stack(
                  clipBehavior: Clip.none,
                  children: [
                    ActionButtonIconWidget(
                      pressed: () => Get.toNamed(RouteHelper.messagesScreen),
                      icon: Icons.chat_bubble_outline_rounded,
                      size: 35,
                      iconColor: Colors.white,
                    ),
                    if (unread > 0)
                      Positioned(
                        right: 6,
                        top: 2,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                          decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                          constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                          child: Text(
                            unread > 9 ? '9+' : '$unread',
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                          ),
                        ),
                      ),
                  ],
                );
              }),
              ActionButtonIconWidget(
                pressed: () => Get.toNamed(RouteHelper.settingsScreen),
                icon: Icons.settings,
                size: 35,
                iconColor: Colors.white,
              ),
            ],
          ),
          drawer: HomeDrawer(dashboardModel: controller.dashboardModel),
          body: controller.isLoading
              ? const CustomLoader()
              : RefreshIndicator(
                  onRefresh: () async {
                    await controller.initialData(shouldLoad: false);
                  },
                  color: Theme.of(context).primaryColor,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(Dimensions.space10),
                    child: Column(
                      children: [
                        Padding(
                          padding: const EdgeInsets.all(15),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.center,
                            children: [
                              CircleAvatar(
                                backgroundColor: ColorResources.blueGreyColor,
                                radius: 31,
                                child: CircleImageWidget(
                                  imagePath: '${UrlContainer.profileImgUrl}${(controller.dashboardModel.data?.clientData?.avatar?.isNotEmpty ?? false) ? controller.dashboardModel.data!.clientData!.avatar : controller.dashboardRepo.apiClient.sharedPreferences.getString(SharedPreferenceHelper.userAvatarKey) ?? ''}',
                                  isAsset: false,
                                  isProfile: true,
                                  width: 60,
                                  height: 60,
                                ),
                              ),
                              const SizedBox(width: Dimensions.space20),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  RichText(
                                      text: TextSpan(children: [
                                    TextSpan(
                                      text: '${LocalStrings.welcome.tr} ',
                                      style: regularLarge.copyWith(
                                          color: Theme.of(context)
                                              .textTheme
                                              .bodyMedium!
                                              .color),
                                    ),
                                    TextSpan(
                                      text: () {
                                        final clientName =
                                            '${controller.dashboardModel.data?.clientData?.firstName ?? ''} ${controller.dashboardModel.data?.clientData?.lastName ?? ''}'
                                                .trim();
                                        if (clientName.isNotEmpty) {
                                          return clientName;
                                        }
                                        // Staff accounts have no client
                                        // record - fall back to the name
                                        // saved at login time instead.
                                        return controller
                                                .dashboardRepo
                                                .apiClient
                                                .sharedPreferences
                                                .getString(
                                                    SharedPreferenceHelper
                                                        .userNameKey) ??
                                            '';
                                      }(),
                                      style: regularLarge.copyWith(
                                          color: Theme.of(context)
                                              .textTheme
                                              .bodyMedium!
                                              .color),
                                    ),
                                  ])),
                                  const SizedBox(height: Dimensions.space5),
                                  if ('${controller.dashboardModel.data?.clientData?.jobTitle ?? ''}${controller.dashboardModel.data?.clientData?.companyName ?? ''}'
                                      .isNotEmpty)
                                    Text(
                                      '${controller.dashboardModel.data?.clientData?.jobTitle ?? ''} - ${controller.dashboardModel.data?.clientData?.companyName ?? ''}',
                                      style: regularSmall.copyWith(
                                          color: ColorResources.blueGreyColor),
                                    )
                                ],
                              )
                            ],
                          ),
                        ),
                        if (controller.isOperationsEnable)
                          GetBuilder<OperationsController>(
                            builder: (ops) => Padding(
                              padding: const EdgeInsets.only(
                                  bottom: Dimensions.space15),
                              child: GridView.count(
                                crossAxisCount: 2,
                                shrinkWrap: true,
                                physics: const NeverScrollableScrollPhysics(),
                                mainAxisSpacing: Dimensions.space10,
                                crossAxisSpacing: Dimensions.space10,
                                childAspectRatio: 1.4,
                                children: [
                                  ModernStatCard(
                                    icon: Icons.assignment_outlined,
                                    label: 'My Requests',
                                    value: '${ops.stats['total'] ?? 0}',
                                    colors: const [
                                      Color(0xFF6D8CFF),
                                      Color(0xFF4A63D6),
                                    ],
                                    onTap: () =>
                                        Get.toNamed(RouteHelper.operationsScreen),
                                  ),
                                  ModernStatCard(
                                    icon: Icons.pending_actions_outlined,
                                    label: 'Pending Approvals',
                                    value:
                                        '${ops.stats['pending_approval'] ?? 0}',
                                    colors: const [
                                      Color(0xFFFFA85C),
                                      Color(0xFFE87A2E),
                                    ],
                                    onTap: () =>
                                        Get.toNamed(RouteHelper.operationsScreen),
                                  ),
                                  ModernStatCard(
                                    icon: Icons.check_circle_outline,
                                    label: 'Completed',
                                    value: '${ops.stats['completed'] ?? 0}',
                                    colors: const [
                                      Color(0xFF5CD98A),
                                      Color(0xFF2FAE63),
                                    ],
                                    onTap: () =>
                                        Get.toNamed(RouteHelper.operationsScreen),
                                  ),
                                  ModernStatCard(
                                    icon: Icons.add_circle_outline,
                                    label: 'New Request',
                                    value: '',
                                    colors: const [
                                      Color(0xFFB07CFF),
                                      Color(0xFF8B4FE0),
                                    ],
                                    onTap: () => Get.toNamed(
                                        RouteHelper.operationsCreateScreen),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        Row(
                          children: [
                            if (controller.isProjectsEnable)
                              OverviewCard(
                                  icon: Icons.grid_view,
                                  iconColor: ColorResources.blueColor,
                                  name: LocalStrings.projects.tr,
                                  onPress: () =>
                                      Get.toNamed(RouteHelper.projectScreen),
                                  number: controller.dashboardModel.data
                                          ?.widgetsData?.projects
                                          .toString() ??
                                      '0',
                                  color: ColorResources.blueColor),
                            if (controller.isInvoicesEnable)
                              OverviewCard(
                                  icon: Icons.article_outlined,
                                  iconColor: ColorResources.redColor,
                                  name: LocalStrings.totalInvoiced.tr,
                                  onPress: () =>
                                      Get.toNamed(RouteHelper.invoiceScreen),
                                  number:
                                      '${controller.currency}${controller.dashboardModel.data?.widgetsData?.totalInvoiced.toString()}',
                                  color: ColorResources.redColor),
                          ],
                        ),
                        const SizedBox(height: Dimensions.space10),
                        Row(
                          children: [
                            if (controller.isPaymentsEnable)
                              OverviewCard(
                                  icon: Icons.check_box_outlined,
                                  iconColor: ColorResources.yellowColor,
                                  name: LocalStrings.payments.tr,
                                  onPress: () =>
                                      Get.toNamed(RouteHelper.paymentScreen),
                                  number:
                                      '${controller.currency}${controller.dashboardModel.data?.widgetsData?.payments.toString()}',
                                  color: ColorResources.yellowColor),
                            if (controller.isInvoicesEnable)
                              OverviewCard(
                                  icon: Icons.auto_mode_outlined,
                                  iconColor: ColorResources.greenColor,
                                  name: LocalStrings.amountDue.tr,
                                  onPress: () =>
                                      Get.toNamed(RouteHelper.invoiceScreen),
                                  number:
                                      '${controller.currency}${controller.dashboardModel.data?.widgetsData?.due.toString()}',
                                  color: ColorResources.greenColor),
                          ],
                        ),
                        const SizedBox(height: Dimensions.space10),
                        if (controller.isProjectsEnable)
                          Row(
                            children: [
                              Container(
                                width: 3,
                                height: 15,
                                color: ColorResources.secondaryColor,
                              ),
                              const SizedBox(width: Dimensions.space5),
                              Text(
                                LocalStrings.projects.tr,
                                style: regularLarge,
                              ),
                              const Spacer(),
                              TextButton(
                                onPressed: () {
                                  Get.toNamed(RouteHelper.projectScreen);
                                },
                                child: Text(
                                  LocalStrings.viewAll.tr,
                                  style: lightSmall.copyWith(
                                      color: ColorResources.blueGreyColor),
                                ),
                              )
                            ],
                          ),
                        if (controller.isProjectsEnable)
                          controller.dashboardModel.data!.projects!.isNotEmpty
                              ? ListView.separated(
                                  shrinkWrap: true,
                                  itemBuilder: (context, index) {
                                    return ProjectCard(
                                      projectModel: controller.dashboardModel
                                          .data!.projects![index],
                                    );
                                  },
                                  separatorBuilder: (context, index) =>
                                      const SizedBox(
                                          height: Dimensions.space10),
                                  itemCount: controller
                                      .dashboardModel.data!.projects!.length)
                              : const NoDataWidget(),
                      ],
                    ),
                  ),
                ),
        ),
      ),
    );
  }
}
