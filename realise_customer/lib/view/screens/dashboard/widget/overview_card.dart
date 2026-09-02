import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/style.dart';
import 'package:flutter/material.dart';

class OverviewCard extends StatelessWidget {
  const OverviewCard(
      {super.key,
      this.icon,
      this.iconColor,
      this.onPress,
      required this.name,
      required this.number,
      required this.color});
  final String name;
  final IconData? icon;
  final Color? iconColor;
  final String number;
  final Color color;
  final VoidCallback? onPress;
  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onPress,
        child: Container(
          height: Dimensions.space80,
          padding: const EdgeInsets.all(Dimensions.space10),
          margin: const EdgeInsets.symmetric(horizontal: Dimensions.space5),
          decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            borderRadius: BorderRadius.circular(Dimensions.cardRadius),
            boxShadow: [
              BoxShadow(
                color: Theme.of(context).shadowColor.withOpacity(0.05),
                spreadRadius: 1,
                blurRadius: 4,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Row(
            children: [
              if (icon != null) ...[
                Container(
                  height: Dimensions.space50,
                  width: Dimensions.space50,
                  padding: const EdgeInsets.all(10),
                  margin:
                      const EdgeInsetsDirectional.only(end: Dimensions.space10),
                  decoration: BoxDecoration(
                    color: iconColor,
                    borderRadius: BorderRadius.circular(Dimensions.cardRadius),
                  ),
                  child: Icon(
                    icon,
                    color: Theme.of(context).cardColor,
                  ),
                )
              ],
              Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    number,
                    style: mediumLarge.copyWith(color: color),
                  ),
                  Text(
                    name,
                    style: regularSmall.copyWith(
                        color: Theme.of(context).textTheme.bodyMedium!.color),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
