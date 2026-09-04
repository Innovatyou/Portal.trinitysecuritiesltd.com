import 'package:realise/core/utils/local_strings.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/helper/string_format_helper.dart';
import 'package:realise/core/utils/dimensions.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/style.dart';

class CustomSnackBar {
  static error({required List<String> errorList, int duration = 5}) {
    String message = '';
    if (errorList.isEmpty) {
      message = LocalStrings.somethingWentWrong.tr;
    } else {
      for (var element in errorList) {
        String tempMessage = element;
        message = message.isEmpty ? tempMessage : "$message\n$tempMessage";
      }
    }
    message = Converter.removeQuotationAndSpecialCharacterFromString(message);
    _showSafely(() => Get.rawSnackbar(
      progressIndicatorBackgroundColor: ColorResources.transparentColor,
      progressIndicatorValueColor:
          const AlwaysStoppedAnimation<Color>(Colors.transparent),
      messageText: Text(message,
          style: regularLarge.copyWith(color: ColorResources.colorWhite)),
      dismissDirection: DismissDirection.horizontal,
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: ColorResources.colorRed,
      borderRadius: 4,
      margin: const EdgeInsets.all(Dimensions.space8),
      padding: const EdgeInsets.all(Dimensions.space8),
      duration: Duration(seconds: duration),
      isDismissible: true,
      forwardAnimationCurve: Curves.easeIn,
      showProgressIndicator: true,
      leftBarIndicatorColor: ColorResources.transparentColor,
      animationDuration: const Duration(seconds: 1),
      borderColor: ColorResources.transparentColor,
      reverseAnimationCurve: Curves.easeOut,
      borderWidth: 2,
    ));
  }

  /// Get.rawSnackbar() only enqueues a job - GetX processes it later via its
  /// own internal queue, and that later step is where it looks up the
  /// current route's Overlay. If that runs while the navigator is
  /// mid-transition (e.g. right after Get.offAllNamed() replaces the whole
  /// stack post-login), the lookup throws "No Overlay widget found" inside
  /// GetX's own zone - invisible to any try/catch here, since the throw
  /// happens well after this function has already returned. Confirmed live:
  /// this is exactly why error/success messages have been silently not
  /// appearing with no crash and no visible error. A short defer lets the
  /// new route's Overlay finish attaching before the job is even queued.
  static void _showSafely(VoidCallback show) {
    Future.delayed(const Duration(milliseconds: 300), () {
      try {
        show();
      } catch (_) {}
    });
  }

  static success({required List<String> successList, int duration = 5}) {
    String message = '';
    if (successList.isEmpty) {
      message = LocalStrings.somethingWentWrong.tr;
    } else {
      for (var element in successList) {
        String tempMessage = element;
        message = message.isEmpty ? tempMessage : "$message\n$tempMessage";
      }
    }
    message = Converter.removeQuotationAndSpecialCharacterFromString(message);
    _showSafely(() => Get.rawSnackbar(
      progressIndicatorBackgroundColor: ColorResources.colorGreen,
      progressIndicatorValueColor:
          const AlwaysStoppedAnimation<Color>(ColorResources.transparentColor),
      messageText: Text(message,
          style: regularLarge.copyWith(color: ColorResources.colorWhite)),
      dismissDirection: DismissDirection.horizontal,
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: ColorResources.colorGreen,
      borderRadius: 4,
      margin: const EdgeInsets.all(Dimensions.space8),
      padding: const EdgeInsets.all(Dimensions.space8),
      duration: Duration(seconds: duration),
      isDismissible: true,
      forwardAnimationCurve: Curves.easeInOutCubicEmphasized,
      showProgressIndicator: true,
      leftBarIndicatorColor: ColorResources.transparentColor,
      animationDuration: const Duration(seconds: 2),
      borderColor: ColorResources.transparentColor,
      reverseAnimationCurve: Curves.easeOut,
      borderWidth: 2,
    ));
  }
}
