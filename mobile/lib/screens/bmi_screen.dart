import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/page_header.dart';
import '../widgets/zine.dart';
import 'profile_screen.dart' show memberProfileProvider;
import 'progress_screen.dart' show measurementsProvider;

(String, StampVariant) _bmiCategory(double bmi) {
  if (bmi < 18.5) return ('Underweight', StampVariant.blue);
  if (bmi < 25) return ('Normal', StampVariant.good);
  if (bmi < 30) return ('Overweight', StampVariant.warn);
  return ('Obese', StampVariant.bad);
}

/// Dialog to enter or edit height, used both first time and later for edits.
Future<void> _showHeightDialog(BuildContext context, WidgetRef ref, {double? currentHeightCm}) async {
  final controller = TextEditingController(text: currentHeightCm?.toStringAsFixed(0) ?? '');
  String? error;
  var saving = false;

  await showDialog<void>(
    context: context,
    barrierDismissible: false,
    builder: (dialogContext) => StatefulBuilder(
      builder: (dialogContext, setState) {
        Future<void> save() async {
          final parsed = double.tryParse(controller.text.trim());
          if (parsed == null || parsed < 50 || parsed > 300) {
            setState(() => error = 'Enter a height between 50 and 300 cm.');
            return;
          }

          setState(() {
            saving = true;
            error = null;
          });

          try {
            final client = ref.read(apiClientProvider);
            await client.updateProfile(heightCm: parsed);
            ref.invalidate(memberProfileProvider);
            if (dialogContext.mounted) Navigator.of(dialogContext).pop();
          } catch (e) {
            if (dialogContext.mounted) {
              setState(() {
                saving = false;
                error = e.toString().replaceFirst('Exception: ', '');
              });
            }
          }
        }

        return AlertDialog(
          title: const Text('HEIGHT'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextField(
                controller: controller,
                autofocus: true,
                enabled: !saving,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(labelText: 'Height (cm)'),
              ),
              if (error != null) ...[
                const SizedBox(height: 8),
                Text(error!, style: AppTheme.body(color: AppColors.tape)),
              ],
            ],
          ),
          actions: [
            TextButton(
              onPressed: saving ? null : () => Navigator.of(dialogContext).pop(),
              child: const Text('CANCEL'),
            ),
            FilledButton(
              onPressed: saving ? null : save,
              child: saving
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.voidBg),
                    )
                  : const Text('SAVE'),
            ),
          ],
        );
      },
    ),
  );

  controller.dispose();
}

class BmiScreen extends ConsumerWidget {
  const BmiScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final profileAsync = ref.watch(memberProfileProvider);
    final client = ref.watch(apiClientProvider);
    final member = profileAsync.asData?.value;
    final fullName = (member?['name'] as String?)?.trim() ?? '';
    final firstName = fullName.isEmpty ? '' : fullName.split(RegExp(r'\s+')).first;
    final photoUrl = member?['photo_url'] as String?;

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
              child: PageHeader(
                title: 'BMI',
                showBackButton: false,
                fontSize: 32,
                leading: Avatar(
                  photoUrl: photoUrl,
                  name: firstName,
                  authToken: client.authToken,
                  size: 60,
                  borderWidth: 2.5,
                ),
              ),
            ),
            Expanded(
              child: profileAsync.when(
                data: (member) {
                  final heightCm = member['height_cm'] == null ? null : double.tryParse(member['height_cm'].toString());

                  if (heightCm == null) {
                    return _EmptyPrompt(
                      message: 'Enter your height to see your BMI.',
                      actionLabel: 'ENTER HEIGHT',
                      onAction: () => _showHeightDialog(context, ref),
                    );
                  }

                  final measurementsAsync = ref.watch(measurementsProvider);

                  return measurementsAsync.when(
                    data: (measurements) => _BmiBody(heightCm: heightCm, measurements: measurements.cast<Map<String, dynamic>>()),
                    loading: () => const Center(child: CircularProgressIndicator()),
                    error: (_, _) => _BmiBody(heightCm: heightCm, measurements: const []),
                  );
                },
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (err, _) => Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text('Could not load your profile: $err', style: AppTheme.body(color: AppColors.tape)),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _BmiBody extends ConsumerStatefulWidget {
  const _BmiBody({required this.heightCm, required this.measurements});

  final double heightCm;
  final List<Map<String, dynamic>> measurements;

  @override
  ConsumerState<_BmiBody> createState() => _BmiBodyState();
}

class _BmiBodyState extends ConsumerState<_BmiBody> {
  final _weightController = TextEditingController();
  bool _manualEntry = false;

  @override
  void dispose() {
    _weightController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final latest = widget.measurements.isEmpty ? null : widget.measurements.last;
    final hasLatest = latest != null && latest['weight_kg'] != null;
    final useManual = _manualEntry || !hasLatest;

    double? weightKg;
    if (useManual) {
      weightKg = double.tryParse(_weightController.text.trim());
    } else {
      weightKg = double.tryParse(latest['weight_kg'].toString());
    }

    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        InkWell(
          onTap: () => _showHeightDialog(context, ref, currentHeightCm: widget.heightCm),
          borderRadius: BorderRadius.circular(AppTheme.radiusSm),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: Row(
              children: [
                Text(
                  'Height: ${widget.heightCm.toStringAsFixed(0)} cm',
                  style: AppTheme.mono(fontSize: 13, color: AppColors.steel),
                ),
                const SizedBox(width: 6),
                const Icon(Icons.edit, size: 14, color: AppColors.steel),
              ],
            ),
          ),
        ),
        const SizedBox(height: 8),
        if (hasLatest) ...[
          Row(
            children: [
              Expanded(
                child: _SourceOption(
                  label: 'Latest logged',
                  subtitle: '${latest['weight_kg']} kg · ${latest['recorded_at'].toString().split('T').first}',
                  selected: !_manualEntry,
                  onTap: () => setState(() => _manualEntry = false),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _SourceOption(
                  label: 'Enter manually',
                  subtitle: null,
                  selected: _manualEntry,
                  onTap: () => setState(() => _manualEntry = true),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
        ],
        if (useManual)
          TextField(
            controller: _weightController,
            autofocus: !hasLatest,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: const InputDecoration(labelText: 'Weight (kg)'),
            onChanged: (_) => setState(() {}),
          ),
        if (useManual) const SizedBox(height: 20),
        if (weightKg == null || weightKg <= 0)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 24),
            child: Text(
              'Enter today\'s weight to see your BMI.',
              textAlign: TextAlign.center,
              style: AppTheme.mono(color: AppColors.steel),
            ),
          )
        else
          _BmiResultCard(weightKg: weightKg, heightCm: widget.heightCm),
      ],
    );
  }
}

class _SourceOption extends StatelessWidget {
  const _SourceOption({required this.label, required this.subtitle, required this.selected, required this.onTap});

  final String label;
  final String? subtitle;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppTheme.radiusSm),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: selected ? AppColors.gold.withValues(alpha: 0.12) : AppColors.paper2,
          borderRadius: BorderRadius.circular(AppTheme.radiusSm),
          border: Border.all(color: selected ? AppColors.gold : AppColors.ink2, width: selected ? 1.5 : 1),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              label,
              style: AppTheme.display(fontSize: 12, fontWeight: FontWeight.w600, color: selected ? AppColors.gold : AppColors.ink),
            ),
            if (subtitle != null) ...[
              const SizedBox(height: 3),
              Text(subtitle!, style: AppTheme.mono(fontSize: 11, color: AppColors.steel)),
            ],
          ],
        ),
      ),
    );
  }
}

class _BmiResultCard extends StatelessWidget {
  const _BmiResultCard({required this.weightKg, required this.heightCm});

  final double weightKg;
  final double heightCm;

  @override
  Widget build(BuildContext context) {
    final heightM = heightCm / 100;
    final bmi = weightKg / (heightM * heightM);
    final (category, variant) = _bmiCategory(bmi);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        MemCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('YOUR BMI', style: AppTheme.display(fontSize: 11, color: AppColors.steel, letterSpacing: 1.5)),
              const SizedBox(height: 12),
              Row(
                crossAxisAlignment: CrossAxisAlignment.baseline,
                textBaseline: TextBaseline.alphabetic,
                children: [
                  CountUpNumber(value: bmi, decimals: 1, style: AppTheme.display(fontSize: 56, fontWeight: FontWeight.w700)),
                  const SizedBox(width: 14),
                  StampBadge(label: category, variant: variant),
                ],
              ),
              const SizedBox(height: 18),
              _BmiGauge(bmi: bmi),
              const SizedBox(height: 10),
              Text(
                '${weightKg.toStringAsFixed(1)}kg · ${heightCm.toStringAsFixed(0)}cm',
                style: AppTheme.mono(fontSize: 12, color: AppColors.steel),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),
        Text(
          'BMI is a general screening tool and doesn\'t account for muscle mass, frame size, or body composition.',
          style: AppTheme.mono(fontSize: 11, color: AppColors.steel),
        ),
      ],
    );
  }
}

/// Shows BMI on a 4-part bar (Underweight/Normal/Overweight/Obese), 15-40 range.
class _BmiGauge extends StatelessWidget {
  const _BmiGauge({required this.bmi});

  final double bmi;

  static const _min = 15.0;
  static const _max = 40.0;
  static const _bandFlex = [4, 7, 5, 10]; // Underweight, Normal, Overweight, Obese
  static const _bandColors = [AppColors.blue, AppColors.turf, AppColors.warn, AppColors.tape];

  @override
  Widget build(BuildContext context) {
    final fraction = ((bmi - _min) / (_max - _min)).clamp(0.0, 1.0);

    return LayoutBuilder(
      builder: (context, constraints) {
        final width = constraints.maxWidth;
        const markerSize = 14.0;
        final markerLeft = (fraction * width - markerSize / 2).clamp(0.0, width - markerSize);

        return SizedBox(
          height: 20,
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              Positioned(
                left: 0,
                right: 0,
                top: 6,
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(AppTheme.radiusSm),
                  child: Row(
                    children: [
                      for (var i = 0; i < _bandFlex.length; i++)
                        Expanded(flex: _bandFlex[i], child: Container(height: 8, color: _bandColors[i])),
                    ],
                  ),
                ),
              ),
              Positioned(
                left: markerLeft,
                child: Container(
                  width: markerSize,
                  height: markerSize,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: AppColors.ink,
                    border: Border.all(color: AppColors.voidBg, width: 2.5),
                    boxShadow: const [BoxShadow(color: Colors.black45, blurRadius: 4)],
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _EmptyPrompt extends StatelessWidget {
  const _EmptyPrompt({required this.message, required this.actionLabel, required this.onAction});

  final String message;
  final String actionLabel;
  final VoidCallback onAction;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(message, textAlign: TextAlign.center, style: AppTheme.mono(color: AppColors.steel)),
            const SizedBox(height: 16),
            OutlinedButton(onPressed: onAction, child: Text(actionLabel)),
          ],
        ),
      ),
    );
  }
}
