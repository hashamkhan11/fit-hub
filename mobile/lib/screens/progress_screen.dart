import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';
import '../providers/navigation_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/page_header.dart';
import '../widgets/zine.dart';
import 'profile_screen.dart' show memberProfileProvider;

final measurementsProvider = FutureProvider.autoDispose((ref) async {
  final client = ref.watch(apiClientProvider);
  return client.fetchMeasurements();
});

class ProgressScreen extends ConsumerWidget {
  const ProgressScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final measurementsAsync = ref.watch(measurementsProvider);
    final client = ref.watch(apiClientProvider);
    final member = ref.watch(memberProfileProvider).asData?.value;
    final fullName = (member?['name'] as String?)?.trim() ?? '';
    final firstName = fullName.isEmpty ? '' : fullName.split(RegExp(r'\s+')).first;
    final photoUrl = member?['photo_url'] as String?;

    return Scaffold(
      floatingActionButton: FloatingActionButton(
        onPressed: () => _openLogSheet(context, ref),
        child: const Icon(Icons.add),
      ),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
              child: PageHeader(
                title: 'Progress',
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
              child: RefreshIndicator(
                onRefresh: () async => ref.invalidate(measurementsProvider),
                color: AppColors.gold,
                backgroundColor: AppColors.paper2,
                child: measurementsAsync.when(
                  data: (measurements) {
                    if (measurements.isEmpty) {
                      return ListView(
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(24),
                            child: Text(
                              'No measurements logged yet. Tap + to add one.',
                              style: AppTheme.mono(color: AppColors.steel),
                            ),
                          ),
                        ],
                      );
                    }

                    final entries = measurements.cast<Map<String, dynamic>>();

                    return Reveal(
                      child: ListView(
                        padding: const EdgeInsets.all(16),
                        children: [
                          Container(
                            height: 220,
                            padding: const EdgeInsets.fromLTRB(8, 16, 16, 8),
                            decoration: BoxDecoration(
                              color: AppColors.paper2,
                              borderRadius: BorderRadius.circular(AppTheme.radiusSm),
                              border: Border.all(color: AppColors.ink2),
                            ),
                            child: _WeightChart(entries: entries),
                          ),
                          const SizedBox(height: 16),
                          _BmiTeaser(onTap: () => ref.read(selectedTabProvider.notifier).select(4)),
                          const SizedBox(height: 24),
                          for (final entry in entries.reversed) _MeasurementCard(entry: entry),
                        ],
                      ),
                    );
                  },
                  loading: () => const Center(child: CircularProgressIndicator()),
                  error: (err, _) => Center(child: Text('Could not load progress: $err', style: AppTheme.body(color: AppColors.tape))),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _openLogSheet(BuildContext context, WidgetRef ref) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.paper2,
      builder: (_) => _LogMeasurementSheet(ref: ref),
    );
  }
}

class _WeightChart extends StatelessWidget {
  const _WeightChart({required this.entries});

  final List<Map<String, dynamic>> entries;

  @override
  Widget build(BuildContext context) {
    final spots = <FlSpot>[];
    for (var i = 0; i < entries.length; i++) {
      final weight = entries[i]['weight_kg'];
      if (weight != null) {
        spots.add(FlSpot(i.toDouble(), double.parse(weight.toString())));
      }
    }

    if (spots.isEmpty) {
      return Center(child: Text('No weight data to chart yet.', style: AppTheme.mono(color: AppColors.steel)));
    }

    final labelStyle = AppTheme.mono(fontSize: 10, color: AppColors.steel);

    return LineChart(
      LineChartData(
        gridData: FlGridData(
          drawVerticalLine: false,
          getDrawingHorizontalLine: (_) => FlLine(
            color: AppColors.ink.withValues(alpha: 0.15),
            strokeWidth: 1,
            dashArray: const [3, 4],
          ),
        ),
        borderData: FlBorderData(show: false),
        titlesData: FlTitlesData(
          topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          leftTitles: AxisTitles(sideTitles: SideTitles(showTitles: true, reservedSize: 36, getTitlesWidget: (value, meta) {
            return Text(value.toStringAsFixed(0), style: labelStyle);
          })),
          bottomTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              reservedSize: 32,
              getTitlesWidget: (value, meta) {
                final index = value.round();
                if (index < 0 || index >= entries.length) {
                  return const SizedBox.shrink();
                }
                final date = entries[index]['recorded_at'].toString().split('T').first;
                return Padding(
                  padding: const EdgeInsets.only(top: 8),
                  child: Text(date.substring(5), style: labelStyle),
                );
              },
            ),
          ),
        ),
        lineBarsData: [
          LineChartBarData(
            spots: spots,
            isCurved: true,
            color: AppColors.blue,
            barWidth: 2.5,
            dotData: FlDotData(
              show: true,
              getDotPainter: (spot, percent, barData, index) {
                final isLast = index == spots.length - 1;
                return FlDotCirclePainter(
                  radius: isLast ? 5 : 2.5,
                  color: isLast ? AppColors.gold : AppColors.blue,
                  strokeWidth: 1.5,
                  strokeColor: AppColors.paper2,
                );
              },
            ),
            belowBarData: BarAreaData(show: true, color: AppColors.blue.withValues(alpha: 0.12)),
          ),
        ],
      ),
    );
  }
}

class _BmiTeaser extends StatelessWidget {
  const _BmiTeaser({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppTheme.radiusMd),
      child: MemCard(
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
        child: Row(
          children: [
            Expanded(
              child: Text(
                'Check your BMI',
                style: AppTheme.display(fontSize: 14, fontWeight: FontWeight.w600),
              ),
            ),
            const Icon(Icons.arrow_forward, size: 18, color: AppColors.gold),
          ],
        ),
      ),
    );
  }
}

class _MeasurementCard extends StatelessWidget {
  const _MeasurementCard({required this.entry});

  final Map<String, dynamic> entry;

  @override
  Widget build(BuildContext context) {
    final date = entry['recorded_at'].toString().split('T').first;

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: MemCard(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(date, style: AppTheme.mono(fontWeight: FontWeight.w600, color: AppColors.goldDeep)),
            const SizedBox(height: 8),
            if (entry['weight_kg'] != null) _row('Weight', '${entry['weight_kg']} kg'),
            if (entry['body_fat_percentage'] != null) _row('Body fat', '${entry['body_fat_percentage']}%'),
            if (entry['chest_cm'] != null) _row('Chest', '${entry['chest_cm']} cm'),
            if (entry['waist_cm'] != null) _row('Waist', '${entry['waist_cm']} cm'),
            if (entry['hips_cm'] != null) _row('Hips', '${entry['hips_cm']} cm'),
            if (entry['arms_cm'] != null) _row('Arms', '${entry['arms_cm']} cm'),
            if (entry['notes'] != null && (entry['notes'] as String).isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(entry['notes'] as String, style: AppTheme.body(color: AppColors.steel)),
            ],
          ],
        ),
      ),
    );
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 2),
      child: Row(
        children: [
          SizedBox(width: 90, child: Text(label, style: AppTheme.mono(color: AppColors.steel, fontSize: 13))),
          Text(value, style: AppTheme.mono(fontSize: 13)),
        ],
      ),
    );
  }
}

class _LogMeasurementSheet extends StatefulWidget {
  const _LogMeasurementSheet({required this.ref});

  final WidgetRef ref;

  @override
  State<_LogMeasurementSheet> createState() => _LogMeasurementSheetState();
}

class _LogMeasurementSheetState extends State<_LogMeasurementSheet> {
  final _weightController = TextEditingController();
  final _bodyFatController = TextEditingController();
  final _chestController = TextEditingController();
  final _waistController = TextEditingController();
  final _hipsController = TextEditingController();
  final _armsController = TextEditingController();
  final _notesController = TextEditingController();
  bool _saving = false;
  String? _error;

  double? _parse(String text) => text.trim().isEmpty ? null : double.tryParse(text.trim());

  Future<void> _save() async {
    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      final client = widget.ref.read(apiClientProvider);
      await client.logMeasurement(
        recordedAt: DateTime.now().toIso8601String().split('T').first,
        weightKg: _parse(_weightController.text),
        bodyFatPercentage: _parse(_bodyFatController.text),
        chestCm: _parse(_chestController.text),
        waistCm: _parse(_waistController.text),
        hipsCm: _parse(_hipsController.text),
        armsCm: _parse(_armsController.text),
        notes: _notesController.text,
      );
      widget.ref.invalidate(measurementsProvider);
      if (mounted) Navigator.of(context).pop();
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  void dispose() {
    _weightController.dispose();
    _bodyFatController.dispose();
    _chestController.dispose();
    _waistController.dispose();
    _hipsController.dispose();
    _armsController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 24,
        right: 24,
        top: 24,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('LOG MEASUREMENT', style: AppTheme.display(fontSize: 16, fontWeight: FontWeight.w600)),
            const SizedBox(height: 16),
            TextField(
              controller: _weightController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Weight (kg)'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _bodyFatController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Body fat (%)'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _chestController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Chest (cm)'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _waistController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Waist (cm)'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _hipsController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Hips (cm)'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _armsController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Arms (cm)'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _notesController,
              decoration: const InputDecoration(labelText: 'Notes'),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: AppTheme.body(color: AppColors.tape)),
            ],
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _saving ? null : _save,
              child: _saving
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.voidBg),
                    )
                  : const Text('SAVE'),
            ),
          ],
        ),
      ),
    );
  }
}
