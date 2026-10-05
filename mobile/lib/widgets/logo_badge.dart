import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

/// Shows the real logo if it's added, else shows "FH" text as fallback.
class LogoBadge extends StatelessWidget {
  const LogoBadge({super.key, this.size = 56, this.fontSize = 20, this.circular = false});

  final double size;
  final double fontSize;

  /// Makes the logo round with a gold ring, to match the Avatar style.
  final bool circular;

  @override
  Widget build(BuildContext context) {
    final fallback = Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        borderRadius: circular ? null : BorderRadius.circular(AppTheme.radiusSm),
        shape: circular ? BoxShape.circle : BoxShape.rectangle,
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppColors.gold, AppColors.goldDeep],
        ),
      ),
      alignment: Alignment.center,
      child: Text(
        'FH',
        style: AppTheme.display(fontSize: fontSize, fontWeight: FontWeight.w800, color: AppColors.voidBg),
      ),
    );

    final mark = Image.asset(
      'assets/images/branding/logo.png',
      width: size,
      height: size,
      fit: BoxFit.contain,
      errorBuilder: (context, error, stackTrace) => fallback,
    );

    if (!circular) return mark;

    return Container(
      width: size,
      height: size,
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(color: AppColors.gold, width: 2),
        color: AppColors.ink2,
      ),
      child: mark,
    );
  }
}
