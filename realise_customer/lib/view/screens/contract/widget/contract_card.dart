import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/data/model/contract/contract_model.dart';
import 'package:realise/view/components/divider/custom_divider.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:nb_utils/nb_utils.dart';

class ContractCard extends StatelessWidget {
  const ContractCard({
    super.key,
    required this.contractModel,
    required this.currency,
  });
  final Contract contractModel;
  final String currency;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => Get.toNamed(RouteHelper.contractDetailsScreen,
          arguments: contractModel.id),
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
                  color: Colors.lightBlue.shade600,
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
                          contractModel.title ?? '',
                          style: regularLarge,
                        ),
                        Text(
                          "$currency${contractModel.contractValue ?? ''}",
                          style: regularDefault,
                        ),
                      ],
                    ),
                    const SizedBox(height: Dimensions.fontExtraSmall),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          contractModel.note ?? '',
                          overflow: TextOverflow.ellipsis,
                          style: regularSmall.copyWith(
                              color: ColorResources.blueGreyColor),
                        ),
                        Text(
                          contractModel.status?.tr.capitalize ?? '',
                          style: regularSmall.copyWith(
                              color: ColorResources.contractStatusColor(
                                  contractModel.status ?? '')),
                        ),
                      ],
                    ),
                    const CustomDivider(space: Dimensions.space10),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        TextIcon(
                          text: contractModel.companyName ?? '',
                          prefix: const Icon(
                            Icons.account_box_rounded,
                            color: ColorResources.primaryColor,
                            size: 16,
                          ),
                          edgeInsets: EdgeInsets.zero,
                          textStyle: regularSmall.copyWith(
                              color: ColorResources.blueGreyColor),
                        ),
                        const SizedBox(width: Dimensions.space10),
                        TextIcon(
                          text: contractModel.contractDate ?? '',
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
