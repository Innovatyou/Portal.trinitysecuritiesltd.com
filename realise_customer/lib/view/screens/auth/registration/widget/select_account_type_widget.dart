import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/view/components/image/circle_shape_image.dart';

class SelectAccountTypeWidget extends StatelessWidget {
  final String accountType;
  final bool isActive;
  final VoidCallback press;
  final String imageSrc;

  const SelectAccountTypeWidget(
      {super.key,
      required this.accountType,
      required this.isActive,
      required this.press,
      required this.imageSrc});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: press,
        child: Container(
          width: MediaQuery.of(context).size.width,
          margin: const EdgeInsets.only(
              left: Dimensions.space3, right: Dimensions.space3),
          padding: const EdgeInsets.symmetric(
              vertical: Dimensions.space10, horizontal: Dimensions.space3),
          decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
              border: Border.all(
                  color: isActive
                      ? ColorResources.primaryColor
                      : ColorResources.colorGrey.withOpacity(0.2))),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              CircleShapeImage(
                image: imageSrc,
                imageColor: isActive
                    ? ColorResources.primaryColor
                    : ColorResources.colorBlack.withOpacity(0.3),
              ),
              Text(
                accountType.tr,
                style: regularSmall,
              )
            ],
          ),
        ),
      ),
    );
  }
}
