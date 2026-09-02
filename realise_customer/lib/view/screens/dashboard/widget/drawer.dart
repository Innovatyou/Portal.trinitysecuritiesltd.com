import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:realise/data/controller/dashboard/dashboard_controller.dart';
import 'package:realise/data/model/dashboard/dashboard_model.dart';
import 'package:realise/view/components/circle_image_button.dart';
import 'package:realise/view/components/dialog/warning_dialog.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/style.dart';

class HomeDrawer extends StatelessWidget {
  const HomeDrawer({super.key, required this.dashboardModel});
  final DashboardModel dashboardModel;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Drawer(
        child: Column(
          children: [
            DrawerHeader(
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  CircleAvatar(
                    backgroundColor: ColorResources.blueGreyColor,
                    radius: 42,
                    child: CircleImageWidget(
                      imagePath:
                          '${UrlContainer.profileImgUrl}${dashboardModel.data!.clientData?.avatar ?? ''}',
                      isAsset: false,
                      isProfile: true,
                      width: 80,
                      height: 80,
                    ),
                  ),
                  const SizedBox(width: Dimensions.space15),
                  Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${dashboardModel.data?.clientData?.firstName ?? ''} ${dashboardModel.data?.clientData?.lastName ?? ''}',
                        style: semiBoldLarge.copyWith(
                          color: Theme.of(context).textTheme.bodyLarge!.color,
                        ),
                      ),
                      TextButton(
                        onPressed: () {
                          Navigator.pop(context);
                          Get.toNamed(RouteHelper.profileScreen);
                        },
                        child: Text(
                          LocalStrings.viewProfile.tr,
                          style: mediumDefault.copyWith(
                            color: Theme.of(context).secondaryHeaderColor,
                            decoration: TextDecoration.underline,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            GetBuilder<DashboardController>(
              builder: (controller) {
                return Column(
                  children: [
                    if (controller.isOperationsEnable)
                      ListTile(
                        leading: const Icon(
                          Icons.hub_rounded,
                          color: ColorResources.primaryColor,
                        ),
                        title: Text(
                          'Operations & Approvals',
                          style: regularDefault.copyWith(
                            color: Theme.of(
                              context,
                            ).textTheme.bodyMedium!.color,
                          ),
                        ),
                        trailing: const Icon(
                          Icons.arrow_forward_ios_rounded,
                          size: Dimensions.space12,
                          color: ColorResources.contentTextColor,
                        ),
                        onTap: () {
                          Navigator.pop(context);
                          Get.toNamed(RouteHelper.operationsScreen);
                        },
                      ),
                    if (controller.isProjectsEnable)
                      ListTile(
                        leading: const Icon(
                          Icons.task_outlined,
                          color: ColorResources.primaryColor,
                        ),
                        title: Text(
                          LocalStrings.projects.tr,
                          style: regularDefault.copyWith(
                            color: Theme.of(
                              context,
                            ).textTheme.bodyMedium!.color,
                          ),
                        ),
                        trailing: const Icon(
                          Icons.arrow_forward_ios_rounded,
                          size: Dimensions.space12,
                          color: ColorResources.contentTextColor,
                        ),
                        onTap: () {
                          Navigator.pop(context);
                          Get.toNamed(RouteHelper.projectScreen);
                        },
                      ),
                    if (controller.isContractsEnable)
                      ListTile(
                        leading: const Icon(
                          Icons.article_outlined,
                          color: ColorResources.primaryColor,
                        ),
                        title: Text(
                          LocalStrings.contracts.tr,
                          style: regularDefault.copyWith(
                            color: Theme.of(
                              context,
                            ).textTheme.bodyMedium!.color,
                          ),
                        ),
                        trailing: const Icon(
                          Icons.arrow_forward_ios_rounded,
                          size: Dimensions.space12,
                          color: ColorResources.contentTextColor,
                        ),
                        onTap: () {
                          Navigator.pop(context);
                          Get.toNamed(RouteHelper.contractScreen);
                        },
                      ),
                    if (controller.isProposalsEnable)
                      ListTile(
                        leading: const Icon(
                          Icons.document_scanner_outlined,
                          color: ColorResources.primaryColor,
                        ),
                        title: Text(
                          LocalStrings.proposals.tr,
                          style: regularDefault.copyWith(
                            color: Theme.of(
                              context,
                            ).textTheme.bodyMedium!.color,
                          ),
                        ),
                        trailing: const Icon(
                          Icons.arrow_forward_ios_rounded,
                          size: Dimensions.space12,
                          color: ColorResources.contentTextColor,
                        ),
                        onTap: () {
                          Navigator.pop(context);
                          Get.toNamed(RouteHelper.proposalScreen);
                        },
                      ),
                    if (controller.isEstimatesEnable)
                      ListTile(
                        leading: const Icon(
                          Icons.add_chart_outlined,
                          color: ColorResources.primaryColor,
                        ),
                        title: Text(
                          LocalStrings.estimates.tr,
                          style: regularDefault.copyWith(
                            color: Theme.of(
                              context,
                            ).textTheme.bodyMedium!.color,
                          ),
                        ),
                        trailing: const Icon(
                          Icons.arrow_forward_ios_rounded,
                          size: Dimensions.space12,
                          color: ColorResources.contentTextColor,
                        ),
                        onTap: () {
                          Navigator.pop(context);
                          Get.toNamed(RouteHelper.estimateScreen);
                        },
                      ),
                    if (controller.isInvoicesEnable)
                      ListTile(
                        leading: const Icon(
                          Icons.assignment_outlined,
                          color: ColorResources.primaryColor,
                        ),
                        title: Text(
                          LocalStrings.invoices.tr,
                          style: regularDefault.copyWith(
                            color: Theme.of(
                              context,
                            ).textTheme.bodyMedium!.color,
                          ),
                        ),
                        trailing: const Icon(
                          Icons.arrow_forward_ios_rounded,
                          size: Dimensions.space12,
                          color: ColorResources.contentTextColor,
                        ),
                        onTap: () {
                          Navigator.pop(context);
                          Get.toNamed(RouteHelper.invoiceScreen);
                        },
                      ),
                    if (controller.isPaymentsEnable)
                      ListTile(
                        leading: const Icon(
                          Icons.money,
                          color: ColorResources.primaryColor,
                        ),
                        title: Text(
                          LocalStrings.payments.tr,
                          style: regularDefault.copyWith(
                            color: Theme.of(
                              context,
                            ).textTheme.bodyMedium!.color,
                          ),
                        ),
                        trailing: const Icon(
                          Icons.arrow_forward_ios_rounded,
                          size: Dimensions.space12,
                          color: ColorResources.contentTextColor,
                        ),
                        onTap: () {
                          Navigator.pop(context);
                          Get.toNamed(RouteHelper.paymentScreen);
                        },
                      ),
                    if (controller.isTicketsEnable)
                      ListTile(
                        leading: const Icon(
                          Icons.confirmation_number_outlined,
                          color: ColorResources.primaryColor,
                        ),
                        title: Text(
                          LocalStrings.tickets.tr,
                          style: regularDefault.copyWith(
                            color: Theme.of(
                              context,
                            ).textTheme.bodyMedium!.color,
                          ),
                        ),
                        trailing: const Icon(
                          Icons.arrow_forward_ios_rounded,
                          size: Dimensions.space12,
                          color: ColorResources.contentTextColor,
                        ),
                        onTap: () {
                          Navigator.pop(context);
                          Get.toNamed(RouteHelper.ticketScreen);
                        },
                      ),
                    ListTile(
                      leading: const Icon(
                        Icons.account_circle_outlined,
                        color: ColorResources.primaryColor,
                      ),
                      title: Text(
                        LocalStrings.settings.tr,
                        style: regularDefault.copyWith(
                          color: Theme.of(context).textTheme.bodyMedium!.color,
                        ),
                      ),
                      trailing: const Icon(
                        Icons.arrow_forward_ios_rounded,
                        size: Dimensions.space12,
                        color: ColorResources.contentTextColor,
                      ),
                      onTap: () {
                        Navigator.pop(context);
                        Get.toNamed(RouteHelper.settingsScreen);
                      },
                    ),
                  ],
                );
              },
            ),
            Expanded(
              child: Align(
                alignment: Alignment.bottomCenter,
                child: ListTile(
                  leading: const Icon(
                    Icons.logout,
                    size: Dimensions.space20,
                    color: Colors.red,
                  ),
                  title: Text(
                    LocalStrings.logout.tr,
                    style: regularDefault.copyWith(
                      color: Theme.of(context).textTheme.bodyMedium!.color,
                    ),
                  ),
                  onTap: () {
                    const WarningAlertDialog().warningAlertDialog(
                      context,
                      () {
                        Get.back();
                        Get.find<DashboardController>().logout();
                      },
                      title: LocalStrings.logoutTitle.tr,
                      subTitle: LocalStrings.logoutSureWarningMSg.tr,
                    );
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
