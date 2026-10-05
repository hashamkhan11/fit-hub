import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show HapticFeedback;

import '../theme/app_theme.dart';

/// Small shared widgets used across cards: status badge, streak dot, and more.

enum StampVariant { neutral, gold, blue, good, bad, warn }

/// Small colored chip used to show a status or booking label.
class StampBadge extends StatelessWidget {
  const StampBadge({
    super.key,
    required this.label,
    this.variant = StampVariant.neutral,
    this.angle = 0,
  });

  final String label;
  final StampVariant variant;
  final double angle;

  ({Color fg, Color bg}) get _colors {
    switch (variant) {
      case StampVariant.gold:
        return (fg: AppColors.gold, bg: AppColors.gold.withValues(alpha: 0.14));
      case StampVariant.blue:
        return (fg: AppColors.blue, bg: AppColors.blue.withValues(alpha: 0.14));
      case StampVariant.good:
        return (fg: AppColors.turf, bg: AppColors.turf.withValues(alpha: 0.12));
      case StampVariant.bad:
        return (fg: AppColors.tape, bg: AppColors.tape.withValues(alpha: 0.12));
      case StampVariant.warn:
        return (fg: AppColors.warn, bg: AppColors.warn.withValues(alpha: 0.14));
      case StampVariant.neutral:
        return (fg: AppColors.steel, bg: AppColors.steel.withValues(alpha: 0.1));
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = _colors;
    final chip = Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: colors.bg,
        borderRadius: BorderRadius.circular(AppTheme.radiusSm),
      ),
      child: Text(
        label.toUpperCase(),
        style: AppTheme.mono(fontSize: 11, fontWeight: FontWeight.w600, color: colors.fg, letterSpacing: 0.6),
      ),
    );

    return angle == 0 ? chip : Transform.rotate(angle: angle, child: chip);
  }
}

/// One dot in the check-in streak: filled means present, empty means missed.
class PunchDot extends StatelessWidget {
  const PunchDot({super.key, required this.filled, this.size = 22});

  final bool filled;
  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: filled ? AppColors.gold : Colors.transparent,
        border: Border.all(color: filled ? AppColors.gold : AppColors.ink2, width: 1.5),
        boxShadow: filled
            ? [BoxShadow(color: AppColors.gold.withValues(alpha: 0.5), blurRadius: 6)]
            : null,
      ),
      child: filled
          ? const Icon(Icons.check, size: 13, color: AppColors.voidBg)
          : null,
    );
  }
}

/// Dark card style used on Home and Profile, like a membership ID card.
class MemCard extends StatelessWidget {
  const MemCard({super.key, required this.child, this.padding});

  final Widget child;
  final EdgeInsetsGeometry? padding;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: padding ?? const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppColors.paper2, AppColors.voidBg],
        ),
        borderRadius: BorderRadius.circular(AppTheme.radiusXl),
        border: Border.all(color: AppColors.ink2, width: 1),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.4), blurRadius: 24, offset: const Offset(0, 10)),
        ],
      ),
      child: Stack(
        children: [
          Positioned(
            right: -30,
            top: -30,
            child: Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [AppColors.gold.withValues(alpha: 0.25), Colors.transparent],
                ),
              ),
            ),
          ),
          child,
        ],
      ),
    );
  }
}

/// Fades and slides content in on load. Skipped if reduce-motion is on.
class Reveal extends StatelessWidget {
  const Reveal({super.key, this.index = 0, required this.child});

  final int index;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.of(context).disableAnimations) return child;

    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: 1),
      duration: Duration(milliseconds: 280 + index * 60),
      curve: Curves.easeOutQuart,
      builder: (context, value, child) {
        return Opacity(
          opacity: value,
          child: Transform.translate(offset: Offset(0, (1 - value) * 16), child: child),
        );
      },
      child: child,
    );
  }
}

/// Round profile photo with a gold ring. Shows first letter if no photo yet.
class Avatar extends StatelessWidget {
  const Avatar({
    super.key,
    required this.photoUrl,
    required this.name,
    required this.authToken,
    this.size = 44,
    this.borderWidth = 2,
    this.overlay = const [],
  });

  final String? photoUrl;
  final String name;
  final String authToken;
  final double size;
  final double borderWidth;

  /// Extra widgets placed on top of the circle, like a camera icon.
  final List<Widget> overlay;

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Container(
          width: size,
          height: size,
          padding: EdgeInsets.all(borderWidth),
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(color: AppColors.gold, width: borderWidth),
          ),
          child: ClipOval(
            child: SizedBox(
              width: size - borderWidth * 2,
              height: size - borderWidth * 2,
              child: photoUrl != null
                  ? CachedNetworkImage(
                      imageUrl: photoUrl!,
                      httpHeaders: {'Authorization': 'Bearer $authToken'},
                      fit: BoxFit.cover,
                      width: size - borderWidth * 2,
                      height: size - borderWidth * 2,
                      errorWidget: (context, url, error) => ColoredBox(
                        color: AppColors.ink2,
                        child: Center(
                          child: Text(
                            name.isNotEmpty ? name[0].toUpperCase() : '?',
                            style: AppTheme.display(fontSize: size * 0.37, color: AppColors.ink),
                          ),
                        ),
                      ),
                    )
                  : ColoredBox(
                      color: AppColors.ink2,
                      child: Center(
                        child: Text(
                          name.isNotEmpty ? name[0].toUpperCase() : '?',
                          style: AppTheme.display(fontSize: size * 0.37, color: AppColors.ink),
                        ),
                      ),
                    ),
            ),
          ),
        ),
        ...overlay,
      ],
    );
  }
}

/// Shrinks a button a bit when pressed and gives haptic feedback on tap.
class PressScale extends StatefulWidget {
  const PressScale({super.key, required this.onTap, required this.child, this.enabled = true});

  final VoidCallback? onTap;
  final Widget child;
  final bool enabled;

  @override
  State<PressScale> createState() => _PressScaleState();
}

class _PressScaleState extends State<PressScale> {
  bool _pressed = false;

  void _setPressed(bool value) {
    if (!widget.enabled || widget.onTap == null) return;
    setState(() => _pressed = value);
  }

  @override
  Widget build(BuildContext context) {
    final reduceMotion = MediaQuery.of(context).disableAnimations;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTapDown: (_) => _setPressed(true),
      onTapCancel: () => _setPressed(false),
      onTapUp: (_) => _setPressed(false),
      onTap: widget.enabled
          ? () {
              HapticFeedback.mediumImpact();
              widget.onTap?.call();
            }
          : null,
      child: AnimatedScale(
        scale: !reduceMotion && _pressed ? 0.96 : 1.0,
        duration: const Duration(milliseconds: 100),
        curve: Curves.easeOut,
        child: widget.child,
      ),
    );
  }
}

/// Animates a number counting up to its value, like streak or BMI stats.
class CountUpNumber extends StatelessWidget {
  const CountUpNumber({super.key, required this.value, required this.style, this.decimals = 0, this.suffix = ''});

  final num value;
  final TextStyle style;
  final int decimals;
  final String suffix;

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.of(context).disableAnimations) {
      return Text('${value.toStringAsFixed(decimals)}$suffix', style: style);
    }
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: value.toDouble()),
      duration: const Duration(milliseconds: 900),
      curve: Curves.easeOutCubic,
      builder: (context, animatedValue, _) => Text('${animatedValue.toStringAsFixed(decimals)}$suffix', style: style),
    );
  }
}
