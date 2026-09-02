import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/model/invoice/invoice_model.dart';
import 'package:realise/view/components/divider/custom_divider.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:nb_utils/nb_utils.dart';

class InvoiceCard extends StatelessWidget {
  const InvoiceCard({
    super.key,
    required this.invoiceModel,
    required this.currency,
  });
  final Invoice invoiceModel;
  final String currency;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () {
        Get.toNamed(RouteHelper.invoiceDetailsScreen,
            arguments: invoiceModel.id!);
      },
      child: Card(
        margin: EdgeInsets.zero,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(5),
          child: Container(
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              border: Border(
                left: BorderSide(
                  width: 5.0,
                  color: ColorResources.invoiceStatusColor(
                      invoiceModel.status ?? ''),
                ),
              ),
            ),
            child: Padding(
                padding: const EdgeInsets.all(Dimensions.space15),
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          invoiceModel.displayId ?? '',
                          style: regularLarge,
                        ),
                        Text(
                          '$currency${invoiceModel.invoiceTotal}',
                          style: regularDefault,
                        ),
                      ],
                    ),
                    const SizedBox(height: Dimensions.space5),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          invoiceModel.projectTitle ?? invoiceModel.note ?? '',
                          overflow: TextOverflow.ellipsis,
                          style: regularSmall.copyWith(
                              color: ColorResources.blueGreyColor),
                        ),
                        Text(
                          invoiceModel.status?.tr.capitalize
                                  ?.replaceAll("_", " ") ??
                              '',
                          style: lightDefault.copyWith(
                              color: ColorResources.invoiceStatusColor(
                                  invoiceModel.status!)),
                        ),
                      ],
                    ),
                    const CustomDivider(space: Dimensions.space10),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        TextIcon(
                          text: invoiceModel.companyName,
                          prefix: const Icon(
                            Icons.account_box_rounded,
                            color: ColorResources.primaryColor,
                            size: 16,
                          ),
                          edgeInsets: EdgeInsets.zero,
                          textStyle: regularSmall.copyWith(
                              color: ColorResources.blueGreyColor),
                        ),
                        TextIcon(
                          text: invoiceModel.dueDate,
                          prefix: const Icon(
                            Icons.calendar_month,
                            color: ColorResources.primaryColor,
                            size: 16,
                          ),
                          edgeInsets: EdgeInsets.zero,
                          textStyle: regularSmall.copyWith(
                              color: ColorResources.blueGreyColor),
                        ),
                      ],
                    )
                  ],
                )),
          ),
        ),
      ),
    );
  }
}
