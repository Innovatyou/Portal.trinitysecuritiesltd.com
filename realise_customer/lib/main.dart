import 'dart:io';

import 'package:realise/core/utils/local_strings.dart';
import 'package:realise/core/utils/messages.dart';
import 'package:realise/core/utils/themes.dart';
import 'package:realise/data/controller/common/theme_controller.dart';
import 'package:realise/data/controller/localization/localization_controller.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:realise/core/route/route.dart';
import 'package:realise/core/utils/url_container.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'core/di_service/di_services.dart' as services;

/// Used by CustomSnackBar instead of Get.rawSnackbar(), which depends on
/// GetX's own Overlay resolution - confirmed broken in this app: it throws
/// "No Overlay widget found" / a null-check crash on Overlay.of every time,
/// not just as a rare race. Worse, a snackbar job that fails to initialize
/// this way leaves GetX's SnackbarController in a half-initialized state,
/// so a LATER Get.back() call - which internally calls
/// closeCurrentSnackbar() unconditionally - throws too and never completes,
/// silently blocking navigation after every success/error toast. Flutter's
/// own ScaffoldMessenger doesn't go anywhere near that code path.
final GlobalKey<ScaffoldMessengerState> rootScaffoldMessengerKey =
    GlobalKey<ScaffoldMessengerState>();

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final sharedPreferences = await SharedPreferences.getInstance();
  Get.lazyPut(() => sharedPreferences);
  UrlContainer.init(sharedPreferences);
  Map<String, Map<String, String>> languages = await services.init();

  HttpOverrides.global = MyHttpOverrides();
  runApp(MyApp(languages: languages));
}

class MyHttpOverrides extends HttpOverrides {
  @override
  HttpClient createHttpClient(SecurityContext? context) {
    return super.createHttpClient(context)
      ..badCertificateCallback =
          (X509Certificate cert, String host, int port) => true;
  }
}

class MyApp extends StatelessWidget {
  final Map<String, Map<String, String>> languages;
  const MyApp({super.key, required this.languages});

  @override
  Widget build(BuildContext context) {
    return GetBuilder<ThemeController>(builder: (theme) {
      return GetBuilder<LocalizationController>(builder: (localizeController) {
        return GetMaterialApp(
          title: LocalStrings.appName,
          debugShowCheckedModeBanner: false,
          defaultTransition: Transition.noTransition,
          transitionDuration: const Duration(milliseconds: 200),
          initialRoute: RouteHelper.splashScreen,
          navigatorKey: Get.key,
          scaffoldMessengerKey: rootScaffoldMessengerKey,
          theme: theme.darkTheme ? dark : light,
          getPages: RouteHelper().routes,
          locale: localizeController.locale,
          translations: Messages(languages: languages),
          fallbackLocale: Locale(localizeController.locale.languageCode,
              localizeController.locale.countryCode),
        );
      });
    });
  }
}
