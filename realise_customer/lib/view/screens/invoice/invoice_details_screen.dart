import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/controller/invoice/invoice_controller.dart';
import 'package:realise/data/repo/invoice/invoice_repo.dart';
import 'package:realise/data/services/api_service.dart';
import 'package:realise/view/components/app-bar/custom_appbar.dart';
import 'package:realise/view/components/card/custom_card.dart';
import 'package:realise/view/components/custom_loader/custom_loader.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/view/components/divider/custom_divider.dart';
import 'package:realise/view/components/table_item.dart';
import 'package:realise/view/screens/invoice/widget/invoice_payment.dart';

class InvoiceDetailsScreen extends StatefulWidget {
  const InvoiceDetailsScreen({super.key, required this.id});
  final String id;

  @override
  State<InvoiceDetailsScreen> createState() => _InvoiceDetailsScreenState();
}

class _InvoiceDetailsScreenState extends State<InvoiceDetailsScreen> {
  @override
  void initState() {
    Get.put(ApiClient(sharedPreferences: Get.find()));
    Get.put(InvoiceRepo(apiClient: Get.find()));
    final controller = Get.put(InvoiceController(invoiceRepo: Get.find()));
    controller.isLoading = true;
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((timeStamp) {
      controller.loadInvoiceDetails(widget.id);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(
        title: LocalStrings.invoiceDetails.tr,
      ),
      body: GetBuilder<InvoiceController>(
        builder: (controller) {
          return controller.isLoading
              ? const CustomLoader()
              : RefreshIndicator(
                  color: Theme.of(context).primaryColor,
                  onRefresh: () async {
                    await controller.initialData(shouldLoad: false);
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
                                controller
                                        .invoiceDetailsModel.data!.displayId ??
                                    '',
                                style: mediumLarge,
                              ),
                              Text(
                                controller.invoiceDetailsModel.data!.status?.tr
                                        .capitalize
                                        ?.replaceAll("_", " ") ??
                                    '',
                                style: lightDefault.copyWith(
                                    color: ColorResources.invoiceStatusColor(
                                        controller.invoiceDetailsModel.data!
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
                                        controller.invoiceDetailsModel.data!
                                                .companyName ??
                                            '',
                                        style: regularDefault),
                                    Text(
                                        controller.invoiceDetailsModel.data!
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
                                    Text(LocalStrings.invoiceDate.tr,
                                        style: lightSmall),
                                    Text(LocalStrings.dueDate.tr,
                                        style: lightSmall),
                                  ],
                                ),
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                        controller.invoiceDetailsModel.data!
                                                .billDate ??
                                            '',
                                        style: regularDefault),
                                    Text(
                                        controller.invoiceDetailsModel.data!
                                                .dueDate ??
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
                                    title: controller.invoiceDetailsModel.data!
                                            .items![index].title ??
                                        '',
                                    qty: controller.invoiceDetailsModel.data!
                                            .items![index].quantity ??
                                        '',
                                    unit: controller.invoiceDetailsModel.data!
                                            .items![index].unitType ??
                                        '',
                                    rate: controller.invoiceDetailsModel.data!
                                            .items![index].rate ??
                                        '',
                                    total: controller.invoiceDetailsModel.data!
                                            .items![index].total ??
                                        '',
                                    currency: controller.currency,
                                  );
                                },
                                separatorBuilder: (context, index) =>
                                    const CustomDivider(
                                        space: Dimensions.space10),
                                itemCount: controller
                                    .invoiceDetailsModel.data!.items!.length),
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
                                      '${controller.currency ?? ''}${controller.invoiceDetailsModel.data!.discountAmount}',
                                      style: regularDefault,
                                    ),
                                  ],
                                ),
                                if (controller.invoiceDetailsModel.data!
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
                                          '${LocalStrings.tax.tr} (${controller.invoiceDetailsModel.data!.taxPercentage}%)',
                                          style: lightDefault,
                                        ),
                                        Text(
                                          '${controller.currency ?? ''}${controller.subtotal / int.parse(controller.invoiceDetailsModel.data!.taxPercentage!)}',
                                          style: regularDefault,
                                        ),
                                      ],
                                    ),
                                  ),
                                if (controller.invoiceDetailsModel.data!
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
                                          '${LocalStrings.tax.tr} (${controller.invoiceDetailsModel.data!.taxPercentage2}%)',
                                          style: lightDefault,
                                        ),
                                        Text(
                                          '${controller.currency ?? ''}${controller.subtotal / int.parse(controller.invoiceDetailsModel.data!.taxPercentage2!)}',
                                          style: regularDefault,
                                        ),
                                      ],
                                    ),
                                  ),
                                if (controller.invoiceDetailsModel.data!
                                        .paymentReceived !=
                                    null)
                                  Padding(
                                    padding: const EdgeInsets.only(
                                        top: Dimensions.space10),
                                    child: Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.spaceBetween,
                                      children: [
                                        Text(
                                          LocalStrings.totalPaid.tr,
                                          style: lightDefault,
                                        ),
                                        Text(
                                          '${controller.currency ?? ''}${controller.invoiceDetailsModel.data!.paymentReceived}',
                                          style: regularDefault,
                                        ),
                                      ],
                                    ),
                                  ),
                                const CustomDivider(space: Dimensions.space10),
                                controller.invoiceDetailsModel.data!
                                            .paymentReceived !=
                                        null
                                    ? Row(
                                        mainAxisAlignment:
                                            MainAxisAlignment.spaceBetween,
                                        children: [
                                          Text(
                                            LocalStrings.balanceDue.tr,
                                            style: regularLarge,
                                          ),
                                          Text(
                                            '${controller.currency ?? ''}${int.parse(controller.invoiceDetailsModel.data!.invoiceValue!) - int.parse(controller.invoiceDetailsModel.data!.paymentReceived!)}',
                                            style: mediumLarge,
                                          ),
                                        ],
                                      )
                                    : Row(
                                        mainAxisAlignment:
                                            MainAxisAlignment.spaceBetween,
                                        children: [
                                          Text(
                                            LocalStrings.total.tr,
                                            style: regularLarge,
                                          ),
                                          Text(
                                            '${controller.currency ?? ''}${controller.invoiceDetailsModel.data!.invoiceValue ?? ''}',
                                            style: mediumLarge,
                                          ),
                                        ],
                                      ),
                              ],
                            ),
                          ),
                          Padding(
                            padding: const EdgeInsets.symmetric(
                                vertical: Dimensions.space10),
                            child: Text(
                              LocalStrings.payments.tr,
                              style: mediumLarge.copyWith(
                                color: ColorResources.primaryColor,
                              ),
                            ),
                          ),
                          CustomCard(
                            child: ListView.separated(
                                shrinkWrap: true,
                                itemBuilder: (context, index) {
                                  return InvoicePayment(
                                      currency: controller.currency ?? '',
                                      payment: controller.invoiceDetailsModel
                                          .data!.payments![index]);
                                },
                                separatorBuilder: (context, index) =>
                                    const CustomDivider(
                                        space: Dimensions.space10),
                                itemCount: controller.invoiceDetailsModel.data!
                                    .payments!.length),
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
                                  controller.invoiceDetailsModel.data!.note ??
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
