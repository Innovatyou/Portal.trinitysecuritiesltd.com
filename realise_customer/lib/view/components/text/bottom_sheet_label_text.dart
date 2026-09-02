import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/style.dart';

class BottomSheetLabelText extends StatelessWidget {
  final String text;
  final TextAlign? textAlign;
  const BottomSheetLabelText({super.key, required this.text, this.textAlign});

  @override
  Widget build(BuildContext context) {
    return Text(text.tr,
        textAlign: textAlign,
        style: regularSmall.copyWith(
            color: ColorResources.contentTextColor,
            fontWeight: FontWeight.w500));
  }
}
