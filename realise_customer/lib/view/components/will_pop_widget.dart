import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/view/components/dialog/exit_dialog.dart';

class WillPopWidget extends StatelessWidget {
  final Widget child;
  final String nextRoute;

  const WillPopWidget({super.key, required this.child, this.nextRoute = ''});

  @override
  Widget build(BuildContext context) {
    // WillPopScope is deprecated and unreliable on current Flutter with
    // Android's predictive-back gesture and GetX's own navigation stack -
    // PopScope is the current, correctly-integrated replacement.
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        if (nextRoute.isEmpty) {
          showExitDialog(context);
        } else {
          Get.offAndToNamed(nextRoute);
        }
      },
      child: child,
    );
  }
}
