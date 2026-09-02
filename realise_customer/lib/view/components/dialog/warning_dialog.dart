import 'package:realise/core/utils/local_strings.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/utils/dimensions.dart';
import '../../../core/utils/color_resources.dart';
import '../../../core/utils/images.dart';
import '../../../core/utils/style.dart';
import '../buttons/rounded_button.dart';

class WarningAlertDialog {
  const WarningAlertDialog();

  void warningAlertDialog(BuildContext context, VoidCallback press,
      {String title = LocalStrings.logoutSureWarningMSg,
      String subTitle = '',
      String image = MyImages.warningImage}) {
    showDialog(
      context: context,
      builder: (context) => Dialog(
        backgroundColor: ColorResources.getCardBgColor(),
        insetPadding:
            const EdgeInsets.symmetric(horizontal: Dimensions.space40),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(5)),
        child: SingleChildScrollView(
          physics: const ClampingScrollPhysics(),
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              Container(
                padding: const EdgeInsets.only(
                    top: Dimensions.space40,
                    bottom: Dimensions.space15,
                    left: Dimensions.space15,
                    right: Dimensions.space15),
                alignment: Alignment.center,
                width: MediaQuery.of(context).size.width,
                decoration: BoxDecoration(
                    color: Theme.of(context).cardColor,
                    borderRadius:
                        BorderRadius.circular(Dimensions.defaultRadius)),
                child: Column(
                  children: [
                    const SizedBox(height: Dimensions.space10),
                    Text(
                      title.tr,
                      style: mediumLarge,
                      textAlign: TextAlign.center,
                      overflow: TextOverflow.ellipsis,
                      maxLines: 4,
                    ),
                    if (subTitle != '')
                      const SizedBox(height: Dimensions.space12),
                    if (subTitle != '')
                      Text(
                        subTitle.tr,
                        style: lightSmall,
                        textAlign: TextAlign.center,
                        overflow: TextOverflow.ellipsis,
                        maxLines: 4,
                      ),
                    const SizedBox(height: Dimensions.space20),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: RoundedButton(
                            text: LocalStrings.no.tr,
                            press: () {
                              Navigator.pop(context);
                            },
                            horizontalPadding: 3,
                            verticalPadding: 3,
                            color: ColorResources.colorGrey,
                            textColor: ColorResources.colorWhite,
                          ),
                        ),
                        const SizedBox(width: Dimensions.space10),
                        Expanded(
                          child: RoundedButton(
                              text: LocalStrings.yes.tr,
                              press: press,
                              horizontalPadding: 3,
                              verticalPadding: 3,
                              color: ColorResources.getPrimaryColor(),
                              textColor: ColorResources.colorWhite),
                        )
                      ],
                    )
                  ],
                ),
              ),
              Positioned(
                top: -40,
                left: MediaQuery.of(context).padding.left,
                right: MediaQuery.of(context).padding.right,
                child: Image.asset(
                  image,
                  height: 80,
                  width: 80,
                ),
              )
            ],
          ),
        ),
      ),
    );
  }
}
