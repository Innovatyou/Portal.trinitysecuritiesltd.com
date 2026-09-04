import 'package:realise/core/utils/local_strings.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/helper/string_format_helper.dart';
import 'package:realise/core/utils/color_resources.dart';
import 'package:realise/core/utils/style.dart';
import 'package:realise/main.dart';

/// Was Get.rawSnackbar() - confirmed live (via emulator + real account) to
/// throw deterministically ("No Overlay widget found" / a null-check crash
/// inside GetX's own Overlay resolution), not just as a rare race, and
/// worse: a snackbar job that fails to initialize this way leaves GetX's
/// SnackbarController half-initialized, so a LATER Get.back() call - which
/// internally calls closeCurrentSnackbar() unconditionally - throws too and
/// never completes. That silently blocked navigation after every
/// success/error toast app-wide (e.g. "Submit request" appearing to do
/// nothing). ScaffoldMessenger is Flutter's own mechanism and doesn't go
/// anywhere near GetX's Overlay code.
class CustomSnackBar {
  static void error({required List<String> errorList, int duration = 5}) {
    _show(_joinMessages(errorList), ColorResources.colorRed, duration);
  }

  static void success({required List<String> successList, int duration = 5}) {
    _show(_joinMessages(successList), ColorResources.colorGreen, duration);
  }

  static String _joinMessages(List<String> list) {
    if (list.isEmpty) return LocalStrings.somethingWentWrong.tr;
    String message = '';
    for (var element in list) {
      message = message.isEmpty ? element : "$message\n$element";
    }
    return Converter.removeQuotationAndSpecialCharacterFromString(message);
  }

  static void _show(String message, Color color, int duration) {
    final messenger = rootScaffoldMessengerKey.currentState;
    if (messenger == null) return;
    messenger.hideCurrentSnackBar();
    messenger.showSnackBar(SnackBar(
      content: Text(message,
          style: regularLarge.copyWith(color: ColorResources.colorWhite)),
      backgroundColor: color,
      duration: Duration(seconds: duration),
      behavior: SnackBarBehavior.floating,
      dismissDirection: DismissDirection.horizontal,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
    ));
  }
}
