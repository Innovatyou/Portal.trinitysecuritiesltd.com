import 'package:flutter/material.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/color_resources.dart';

class CustomDivider extends StatelessWidget {
  final double space;
  final Color? color;

  const CustomDivider({super.key, this.space = Dimensions.space20, this.color});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        SizedBox(height: space),
        Divider(
            color: color?.withOpacity(0.3) ??
                ColorResources.getDividerColor().withOpacity(0.3),
            height: 0.5,
            thickness: 0.5),
        SizedBox(height: space),
      ],
    );
  }
}
