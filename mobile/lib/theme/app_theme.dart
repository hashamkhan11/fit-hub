import 'package:flutter/material.dart';

/// FitHub's dark theme: navy background, one red accent color. Same colors
/// as the web admin panel. App is dark-only, there is no light mode.
class AppColors {
  AppColors._();

  /// Darkest background color, used for page background and gradients.
  static const voidBg = Color(0xFF0B0F1A);

  static const paper = Color(0xFF1A2333);
  static const paper2 = Color(0xFF131A28);
  static const paper3 = Color(0xFF0F1420);

  static const ink = Color(0xFFEDF0F5);
  static const ink2 = Color(0xFF1E2738);

  static const steel = Color(0xFF8A93A6);
  static const steel2 = Color(0xFF4B5568);

  static const gold = Color(0xFFF0562B);
  static const goldDeep = Color(0xFFC7401D);
  static const goldLight = Color(0xFFFF8B63);
  static const blue = Color(0xFF4C8DFF);
  static const blueDeep = Color(0xFF3568C9);

  static const turf = Color(0xFF39D97A);
  static const tape = Color(0xFFFF4D5E);
  static const warn = Color(0xFFFFB020);
}

class AppTheme {
  AppTheme._();

  static const radiusSm = 8.0;
  static const radiusMd = 12.0;
  static const radiusLg = 20.0;
  static const radiusXl = 28.0;

  /// Font used for captions, badges, and numbers.
  static TextStyle mono({
    double fontSize = 14,
    FontWeight fontWeight = FontWeight.w500,
    Color color = AppColors.ink,
    double letterSpacing = 0,
  }) {
    return TextStyle(
      fontFamily: 'JetBrains Mono',
      fontSize: fontSize,
      fontWeight: fontWeight,
      color: color,
      letterSpacing: letterSpacing,
      fontFeatures: const [FontFeature.tabularFigures()],
    );
  }

  /// Font used for headings and big stat numbers.
  static TextStyle display({
    double fontSize = 16,
    FontWeight fontWeight = FontWeight.w700,
    Color color = AppColors.ink,
    double letterSpacing = 0.2,
  }) {
    return TextStyle(
      fontFamily: 'Bricolage Grotesque',
      fontSize: fontSize,
      fontWeight: fontWeight,
      color: color,
      letterSpacing: letterSpacing,
      height: 1.15,
    );
  }

  /// Font used for normal text, labels, and buttons.
  static TextStyle body({
    double fontSize = 14,
    FontWeight fontWeight = FontWeight.w500,
    Color color = AppColors.ink,
    double letterSpacing = 0.2,
  }) {
    return TextStyle(
      fontFamily: 'Plus Jakarta Sans',
      fontSize: fontSize,
      fontWeight: fontWeight,
      color: color,
      letterSpacing: letterSpacing,
      height: 1.3,
    );
  }

  /// Thin light border used on cards, inputs, and buttons.
  static Border get inkBorder => Border.all(color: AppColors.ink2, width: 1);

  static ThemeData get theme {
    final base = ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      scaffoldBackgroundColor: AppColors.voidBg,
      fontFamily: 'Plus Jakarta Sans',
      colorScheme: const ColorScheme.dark(
        primary: AppColors.gold,
        onPrimary: AppColors.voidBg,
        secondary: AppColors.blue,
        onSecondary: AppColors.voidBg,
        surface: AppColors.paper2,
        onSurface: AppColors.ink,
        error: AppColors.tape,
        onError: AppColors.voidBg,
      ),
    );

    return base.copyWith(
      textTheme: base.textTheme.apply(bodyColor: AppColors.ink, displayColor: AppColors.ink),
      appBarTheme: AppBarTheme(
        backgroundColor: AppColors.voidBg,
        foregroundColor: AppColors.ink,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        centerTitle: false,
        titleTextStyle: display(fontSize: 20, fontWeight: FontWeight.w700),
        shape: const Border(bottom: BorderSide(color: AppColors.ink2, width: 1)),
      ),
      cardTheme: CardThemeData(
        color: AppColors.paper2,
        elevation: 4,
        shadowColor: Colors.black.withValues(alpha: 0.4),
        surfaceTintColor: Colors.transparent,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusLg),
          side: const BorderSide(color: AppColors.ink2, width: 1),
        ),
      ),
      dividerTheme: const DividerThemeData(color: AppColors.ink2, thickness: 1, space: 1),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.paper3,
        labelStyle: const TextStyle(color: AppColors.steel),
        floatingLabelStyle: const TextStyle(color: AppColors.gold),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: AppColors.ink2, width: 1),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: AppColors.ink2, width: 1),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: AppColors.gold, width: 1.5),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.gold,
          foregroundColor: AppColors.voidBg,
          minimumSize: const Size.fromHeight(48),
          elevation: 6,
          shadowColor: AppColors.gold.withValues(alpha: 0.45),
          surfaceTintColor: Colors.transparent,
          textStyle: body(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.voidBg, letterSpacing: 0.4),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusMd)),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.blue,
          side: const BorderSide(color: AppColors.blue, width: 1),
          minimumSize: const Size.fromHeight(48),
          textStyle: body(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.blue, letterSpacing: 0.4),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusMd)),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.gold,
          textStyle: body(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.gold, letterSpacing: 0.4),
        ),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: AppColors.gold,
        foregroundColor: AppColors.voidBg,
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: AppColors.paper3,
        contentTextStyle: const TextStyle(color: AppColors.ink),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusMd)),
      ),
      iconTheme: const IconThemeData(color: AppColors.steel),
      progressIndicatorTheme: const ProgressIndicatorThemeData(color: AppColors.gold),
    );
  }
}
