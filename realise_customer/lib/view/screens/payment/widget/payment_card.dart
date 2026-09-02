import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/model/payment/payment_model.dart';
import 'package:realise/view/components/divider/custom_divider.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:nb_utils/nb_utils.dart';

class PaymentCard extends StatelessWidget {
  const PaymentCard({
    super.key,
    required this.paymentModel,
    required this.currency,
  });
  final Payment paymentModel;
  final String currency;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        Get.toNamed(RouteHelper.paymentDetailsScreen,
            arguments: paymentModel.id!);
      },
      child: Card(
        margin: EdgeInsets.zero,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(5),
          child: Container(
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              border: const Border(
                left: BorderSide(
                  width: 5.0,
                  color: ColorResources.primaryColor,
                ),
              ),
            ),
            child: Padding(
                padding: const EdgeInsets.all(15),
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          '${LocalStrings.payment.tr} #${paymentModel.id}',
                          style: regularLarge,
                        ),
                        Text(
                          '$currency${paymentModel.amount}',
                          style: regularLarge,
                        ),
                      ],
                    ),
                    const SizedBox(height: Dimensions.space5),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          '${paymentModel.note}',
                          overflow: TextOverflow.ellipsis,
                          style: regularSmall.copyWith(
                              color: ColorResources.blueGreyColor),
                        ),
                        Text(
                          paymentModel.paymentMethodTitle ?? '',
                          style: regularSmall.copyWith(
                              color: ColorResources.primaryColor),
                        ),
                      ],
                    ),
                    const CustomDivider(space: Dimensions.space10),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        TextIcon(
                          text: paymentModel.displayId ?? '-',
                          prefix: const Icon(
                            Icons.numbers_outlined,
                            color: ColorResources.primaryColor,
                            size: 16,
                          ),
                          edgeInsets: EdgeInsets.zero,
                          textStyle: regularSmall.copyWith(
                              color: ColorResources.blueGreyColor),
                        ),
                        TextIcon(
                          text: paymentModel.paymentDate ?? '',
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
