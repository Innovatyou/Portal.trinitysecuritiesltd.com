import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/controller/estimate/estimate_controller.dart';
import 'package:realise/data/repo/estimate/estimate_repo.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/app-bar/custom_appbar.dart';
import 'package:realise/view/components/card/custom_card.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';
import 'package:realise/view/components/divider/custom_divider.dart';
import 'package:realise/view/components/table_item.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

class EstimateDetailsScreen extends StatefulWidget {
  const EstimateDetailsScreen({super.key, required this.id});
  final String id;

  @override
  State<EstimateDetailsScreen> createState() => _EstimateDetailsScreenState();
}

class _EstimateDetailsScreenState extends State<EstimateDetailsScreen> {
  @override
  void initState() {
    Get.put(ApiClient(sharedPreferences: Get.find()));
    Get.put(EstimateRepo(apiClient: Get.find()));
    final controller = Get.put(EstimateController(estimateRepo: Get.find()));
    controller.isLoading = true;
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((timeStamp) {
      controller.loadEstimateDetails(widget.id);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(
        title: LocalStrings.estimateDetails.tr,
      ),
      body: GetBuilder<EstimateController>(
        builder: (controller) {
          return controller.isLoading
              ? const CustomLoader()
              : RefreshIndicator(
                  color: ColorResources.primaryColor,
                  onRefresh: () async {
                    await controller.loadEstimateDetails(widget.id);
                  },
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    scrollDirection: Axis.vertical,
                    child: Padding(
                      padding: const EdgeInsets.all(Dimensions.space15),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                '${LocalStrings.estimate.tr} #${controller.estimateDetailsModel.data!.id}',
                                style: mediumLarge,
                              ),
                              Text(
                                controller.estimateDetailsModel.data!.status?.tr
                                        .capitalize ??
                                    '',
                                style: TextStyle(
                                    color: ColorResources.estimateStatusColor(
                                        controller.estimateDetailsModel.data!
                                            .status!)),
                              )
                            ],
                          ),
                          const SizedBox(height: Dimensions.space10),
                          CustomCard(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(LocalStrings.companyName.tr,
                                        style: lightSmall),
                                    Text(LocalStrings.project.tr,
                                        style: lightSmall),
                                  ],
                                ),
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                        controller.estimateDetailsModel.data!
                                                .companyName ??
                                            '',
                                        style: regularDefault),
                                    Text(
                                        controller.estimateDetailsModel.data!
                                                .projectTitle ??
                                            '-',
                                        style: regularDefault),
                                  ],
                                ),
                                const CustomDivider(space: Dimensions.space10),
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(LocalStrings.estimateDate.tr,
                                        style: lightSmall),
                                    Text(LocalStrings.expiryDate.tr,
                                        style: lightSmall),
                                  ],
                                ),
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                        controller.estimateDetailsModel.data!
                                                .estimateDate ??
                                            '',
                                        style: regularDefault),
                                    Text(
                                        controller.estimateDetailsModel.data!
                                                .validUntil ??
                                            '-',
                                        style: regularDefault),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          Padding(
                            padding: const EdgeInsets.symmetric(
                                vertical: Dimensions.space10),
                            child: Text(
                              LocalStrings.items.tr,
                              style: mediumLarge,
                            ),
                          ),
                          CustomCard(
                            child: ListView.separated(
                                shrinkWrap: true,
                                itemBuilder: (context, index) {
                                  return TableItem(
                                    title: controller.estimateDetailsModel.data!
                                            .items![index].title ??
                                        '',
                                    qty: controller.estimateDetailsModel.data!
                                            .items![index].quantity ??
                                        '',
                                    unit: controller.estimateDetailsModel.data!
                                            .items![index].unitType ??
                                        '',
                                    rate: controller.estimateDetailsModel.data!
                                            .items![index].rate ??
                                        '',
                                    total: controller.estimateDetailsModel.data!
                                            .items![index].total ??
                                        '',
                                    currency: controller.currency ?? '',
                                  );
                                },
                                separatorBuilder: (context, index) =>
                                    const CustomDivider(
                                        space: Dimensions.space10),
                                itemCount: controller
                                    .estimateDetailsModel.data!.items!.length),
                          ),
                          Padding(
                            padding: const EdgeInsets.all(Dimensions.space10),
                            child: Column(
                              children: [
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                      LocalStrings.subtotal.tr,
                                      style: lightDefault,
                                    ),
                                    Text(
                                      '${controller.currency ?? ''}${controller.subtotal}',
                                      style: regularDefault,
                                    ),
                                  ],
                                ),
                                const SizedBox(height: Dimensions.space10),
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                      LocalStrings.discount.tr,
                                      style: lightDefault,
                                    ),
                                    Text(
                                      '${controller.currency ?? ''}${controller.estimateDetailsModel.data!.discountAmount}',
                                      style: regularDefault,
                                    ),
                                  ],
                                ),
                                if (controller.estimateDetailsModel.data!
                                        .taxPercentage !=
                                    null)
                                  Padding(
                                    padding: const EdgeInsets.only(
                                        top: Dimensions.space10),
                                    child: Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.spaceBetween,
                                      children: [
                                        Text(
                                          '${LocalStrings.tax.tr} (${controller.estimateDetailsModel.data!.taxPercentage}%)',
                                          style: lightDefault,
                                        ),
                                        Text(
                                          '${controller.currency ?? ''}${controller.subtotal / int.parse(controller.estimateDetailsModel.data!.taxPercentage!)}',
                                          style: regularDefault,
                                        ),
                                      ],
                                    ),
                                  ),
                                if (controller.estimateDetailsModel.data!
                                        .taxPercentage2 !=
                                    null)
                                  Padding(
                                    padding: const EdgeInsets.only(
                                        top: Dimensions.space10),
                                    child: Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.spaceBetween,
                                      children: [
                                        Text(
                                          '${LocalStrings.tax.tr} (${controller.estimateDetailsModel.data!.taxPercentage2}%)',
                                          style: lightDefault,
                                        ),
                                        Text(
                                          '${controller.currency ?? ''}${controller.subtotal / int.parse(controller.estimateDetailsModel.data!.taxPercentage2!)}',
                                          style: regularDefault,
                                        ),
                                      ],
                                    ),
                                  ),
                                const CustomDivider(space: Dimensions.space10),
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                      LocalStrings.total.tr,
                                      style: regularLarge,
                                    ),
                                    Text(
                                      '${controller.currency ?? ''}${controller.estimateDetailsModel.data!.estimateValue ?? ''}',
                                      style: mediumLarge,
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: Dimensions.space10),
                          CustomCard(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.start,
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  LocalStrings.note.tr,
                                  style: Theme.of(context).textTheme.bodyLarge,
                                ),
                                const Divider(
                                  color: ColorResources.blueGreyColor,
                                  thickness: 0.50,
                                ),
                                Text(
                                  controller.estimateDetailsModel.data!.note ??
                                      '-',
                                  style: lightSmall.copyWith(
                                      color: ColorResources.darkColor),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                );
        },
      ),
    );
  }
}
