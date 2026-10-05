import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// Big title header used on every screen's body except Home.
class PageHeader extends StatelessWidget {
  const PageHeader({
    super.key,
    required this.title,
    this.trailing,
    this.showBackButton = true,
    this.leading,
    this.fontSize = 28,
  });

  final String title;
  final Widget? trailing;
  final bool showBackButton;

  /// Replaces the default back arrow, e.g. to show the avatar instead.
  final Widget? leading;

  final double fontSize;

  @override
  Widget build(BuildContext context) {
    final canPop = showBackButton && Navigator.of(context).canPop();
    final resolvedLeading = leading ??
        (canPop
            ? _HeaderIconButton(
                icon: Icons.arrow_back_ios_new_rounded,
                onTap: () => Navigator.of(context).pop(),
              )
            : null);

    return Row(
      children: [
        if (resolvedLeading != null) ...[
          resolvedLeading,
          const SizedBox(width: 14),
        ],
        Expanded(
          child: Text(
            title,
            style: AppTheme.display(fontSize: fontSize, fontWeight: FontWeight.w800),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ),
        if (trailing != null) ...[
          const SizedBox(width: 8),
          trailing!,
        ],
      ],
    );
  }
}

/// Round icon button with a thin ring, used for back button and other actions.
class HeaderIconButton extends StatelessWidget {
  const HeaderIconButton({super.key, required this.icon, required this.onTap, this.tooltip});

  final IconData icon;
  final VoidCallback onTap;
  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    return _HeaderIconButton(icon: icon, onTap: onTap, tooltip: tooltip);
  }
}

class _HeaderIconButton extends StatelessWidget {
  const _HeaderIconButton({required this.icon, required this.onTap, this.tooltip});

  final IconData icon;
  final VoidCallback onTap;
  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    final button = Material(
      color: AppColors.paper2,
      elevation: 2,
      shadowColor: Colors.black.withValues(alpha: 0.4),
      shape: const CircleBorder(side: BorderSide(color: AppColors.ink2, width: 1)),
      child: InkWell(
        onTap: onTap,
        customBorder: const CircleBorder(),
        child: SizedBox(
          width: 44,
          height: 44,
          child: Icon(icon, color: AppColors.ink, size: 20),
        ),
      ),
    );

    return tooltip == null ? button : Tooltip(message: tooltip!, child: button);
  }
}
