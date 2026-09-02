import 'package:realise/core/helper/date_converter.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/model/invoice/invoice_details_model.dart';
import 'package:realise/view/components/divider/custom_divider.dart';
import 'package:flutter/material.dart';
import 'package:nb_utils/nb_utils.dart';

class InvoicePayment extends StatelessWidget {
  const InvoicePayment({
    super.key,
    required this.payment,
    required this.currency,
  });
  final Payment payment;
  final String currency;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              payment.note ?? '',
              style: regularLarge,
            ),
            Text(
              '$currency${payment.amount}',
              style: regularDefault,
            ),
          ],
        ),
        const CustomDivider(space: Dimensions.space10),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: [
            TextIcon(
              text: payment.paymentMethodTitle ?? '',
              prefix: const Icon(
                Icons.payments_outlined,
                color: ColorResources.primaryColor,
                size: 16,
              ),
              edgeInsets: EdgeInsets.zero,
              textStyle:
                  regularSmall.copyWith(color: ColorResources.blueGreyColor),
            ),
            const SizedBox(
              width: Dimensions.space15,
            ),
            TextIcon(
              text: DateConverter.formatValidityDate(payment.createdAt ?? ''),
              prefix: const Icon(
                Icons.calendar_month,
                color: ColorResources.primaryColor,
                size: 16,
              ),
              edgeInsets: EdgeInsets.zero,
              textStyle:
                  regularSmall.copyWith(color: ColorResources.blueGreyColor),
            ),
          ],
        )
      ],
    );
  }
}
