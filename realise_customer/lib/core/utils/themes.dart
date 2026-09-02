import 'package:realise/core/utils/color_resources.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

ThemeData light = ThemeData(
  useMaterial3: true,
  primaryColor: ColorResources.primaryColor,
  colorScheme: ColorScheme.fromSeed(
    seedColor: ColorResources.primaryColor,
    primary: ColorResources.primaryColor,
    secondary: ColorResources.secondaryColor,
    brightness: Brightness.light,
  ),
  secondaryHeaderColor: ColorResources.secondaryColor,
  appBarTheme: const AppBarTheme(
    backgroundColor: ColorResources.primaryColor,
    elevation: 0,
    actionsIconTheme: IconThemeData(color: ColorResources.colorBlack),
    foregroundColor: ColorResources.colorBlack,
    titleTextStyle: TextStyle(
      color: ColorResources.colorBlack,
      fontWeight: FontWeight.bold,
      fontSize: 23,
    ),
    iconTheme: IconThemeData(color: ColorResources.colorBlack),
    systemOverlayStyle: SystemUiOverlayStyle(
      statusBarBrightness: Brightness.light,
      statusBarColor: ColorResources.primaryColor,
      statusBarIconBrightness: Brightness.dark,
    ),
  ),
  fontFamily: 'Montserrat-Arabic',
  scaffoldBackgroundColor: ColorResources.lightBackgroundColor,
  floatingActionButtonTheme: const FloatingActionButtonThemeData(
      foregroundColor: ColorResources.colorBlack,
      backgroundColor: ColorResources.primaryColor),
  inputDecorationTheme: InputDecorationTheme(
    border: OutlineInputBorder(borderRadius: BorderRadius.circular(15)),
    contentPadding: const EdgeInsetsDirectional.only(top: 5, start: 20),
    hintStyle: const TextStyle(color: Colors.black),
    fillColor: ColorResources.inputColor,
  ),
  cardTheme: CardThemeData(
      color: Colors.white,
      elevation: 8,
      shadowColor: ColorResources.primaryColor.withOpacity(.12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22))),
  cardColor: Colors.white,
  dataTableTheme: DataTableThemeData(
      headingRowColor:
          WidgetStateProperty.all<Color>(ColorResources.lightBlueGreyColor),
      dataRowColor: WidgetStateProperty.all<Color>(Colors.white)),
  drawerTheme: const DrawerThemeData(
      backgroundColor: ColorResources.colorWhite,
      surfaceTintColor: ColorResources.primaryColor),
  textTheme: const TextTheme(
      displaySmall: TextStyle(
          color: ColorResources.colorGrey,
          fontWeight: FontWeight.w400,
          fontSize: 16),
      bodyMedium: TextStyle(
          color: Colors.black, fontWeight: FontWeight.w400, fontSize: 12),
      bodySmall: TextStyle(
          color: Colors.grey, fontWeight: FontWeight.w400, fontSize: 12),
      bodyLarge: TextStyle(
          color: ColorResources.primaryColor,
          fontWeight: FontWeight.w400,
          fontSize: 14)),
  bottomNavigationBarTheme: const BottomNavigationBarThemeData(
      backgroundColor: Colors.white,
      selectedItemColor: ColorResources.secondaryColor,
      unselectedItemColor: Colors.grey,
      showUnselectedLabels: true,
      elevation: 5,
      type: BottomNavigationBarType.fixed),
  hintColor: ColorResources.hintColor,
);

ThemeData dark = ThemeData(
  useMaterial3: true,
  primaryColor: ColorResources.primaryColor,
  colorScheme: ColorScheme.fromSeed(
    seedColor: ColorResources.primaryColor,
    primary: ColorResources.primaryColor,
    secondary: ColorResources.secondaryColor,
    brightness: Brightness.dark,
  ),
  secondaryHeaderColor: ColorResources.secondaryColor,
  appBarTheme: const AppBarTheme(
    backgroundColor: ColorResources.primaryColor,
    elevation: 0,
    actionsIconTheme: IconThemeData(color: Colors.white),
    titleTextStyle: TextStyle(
      color: Colors.white,
      fontWeight: FontWeight.bold,
      fontSize: 23,
    ),
    iconTheme: IconThemeData(color: Colors.white),
    systemOverlayStyle: SystemUiOverlayStyle(
      statusBarBrightness: Brightness.light,
      statusBarColor: ColorResources.primaryColor,
      statusBarIconBrightness: Brightness.light,
    ),
  ),
  scaffoldBackgroundColor: ColorResources.screenBgColorDark,
  inputDecorationTheme: InputDecorationTheme(
    border: OutlineInputBorder(borderRadius: BorderRadius.circular(15)),
    contentPadding: const EdgeInsetsDirectional.only(top: 5, start: 30),
    fillColor: ColorResources.inputColorDark,
    hintStyle: const TextStyle(color: Colors.white),
  ),
  cardTheme: const CardThemeData(color: Colors.black),
  cardColor: ColorResources.cardColorDark,
  drawerTheme: const DrawerThemeData(
      backgroundColor: ColorResources.screenBgColorDark,
      surfaceTintColor: ColorResources.screenBgColorDark),
  textTheme: const TextTheme(
      displaySmall: TextStyle(
          color: ColorResources.colorGrey,
          fontWeight: FontWeight.w400,
          fontSize: 16),
      bodyMedium: TextStyle(
          color: Colors.white, fontWeight: FontWeight.w400, fontSize: 12),
      bodySmall: TextStyle(
          color: Colors.grey, fontWeight: FontWeight.w400, fontSize: 12),
      bodyLarge: TextStyle(
          color: Colors.white, fontWeight: FontWeight.w400, fontSize: 14)),
  iconTheme: const IconThemeData(color: Colors.white),
  primaryIconTheme: const IconThemeData(color: Colors.white),
  hintColor: ColorResources.hintColorDark,
);
