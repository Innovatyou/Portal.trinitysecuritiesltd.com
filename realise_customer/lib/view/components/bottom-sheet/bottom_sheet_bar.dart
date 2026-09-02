import 'package:flutter/material.dart';
import 'package:realise/core/utils/color_resources.dart';

class BottomSheetBar extends StatelessWidget {
  const BottomSheetBar({super.key});

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.topCenter,
      child: Container(
        height: 5,
        width: 50,
        decoration: BoxDecoration(
            color: ColorResources.colorGrey.withOpacity(0.1),
            borderRadius: BorderRadius.circular(15)),
      ),
    );
  }
}
