import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/page_header.dart';
import '../widgets/zine.dart';

final lockDevicesProvider = FutureProvider.autoDispose((ref) async {
  final client = ref.watch(apiClientProvider);
  return client.fetchLockDevices();
});

class LockScreen extends ConsumerStatefulWidget {
  const LockScreen({super.key});

  @override
  ConsumerState<LockScreen> createState() => _LockScreenState();
}

class _LockScreenState extends ConsumerState<LockScreen> {
  final _pendingDeviceIds = <int>{};

  @override
  Widget build(BuildContext context) {
    final devicesAsync = ref.watch(lockDevicesProvider);
    final client = ref.watch(apiClientProvider);

    Future<void> sendCommand(int deviceId, String name, bool unlock) async {
      final verb = unlock ? 'Unlock' : 'Lock';

      setState(() => _pendingDeviceIds.add(deviceId));
      try {
        final commandId = unlock ? await client.unlockDevice(deviceId) : await client.lockDevice(deviceId);

        // Device checks for new commands every second, so keep checking status.
        var status = 'pending';
        for (var attempt = 0; attempt < 30 && status == 'pending'; attempt++) {
          await Future.delayed(const Duration(milliseconds: 500));
          status = await client.fetchCommandStatus(commandId);
        }

        if (!context.mounted) return;

        final message = switch (status) {
          'completed' => '$name is now ${unlock ? 'unlocked' : 'locked'}.',
          'failed' => '$verb failed on $name.',
          'expired' => '$verb request to $name timed out — the device may be offline.',
          _ => '$verb request sent to $name — still waiting for it to respond.',
        };
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
        ref.invalidate(lockDevicesProvider);
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
          );
        }
      } finally {
        if (mounted) setState(() => _pendingDeviceIds.remove(deviceId));
      }
    }

    return Scaffold(
      backgroundColor: AppColors.voidBg,
      body: SafeArea(
        child: Column(
          children: [
            const Padding(
              padding: EdgeInsets.fromLTRB(20, 12, 20, 8),
              child: PageHeader(title: 'Lock'),
            ),
            Expanded(child: _buildBody(devicesAsync, sendCommand)),
          ],
        ),
      ),
    );
  }

  Widget _buildBody(
    AsyncValue<List<dynamic>> devicesAsync,
    Future<void> Function(int deviceId, String name, bool unlock) sendCommand,
  ) {
    return devicesAsync.when(
          data: (devices) {
            if (devices.isEmpty) {
              return Center(
                child: Text(
                  'No lock devices set up yet.',
                  style: AppTheme.display(color: AppColors.ink, fontSize: 16),
                ),
              );
            }

            return SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: devices.map((raw) {
                  final device = raw as Map<String, dynamic>;
                  final isOnline = device['is_online'] as bool;
                  final name = device['name'] as String;
                  final id = device['id'] as int;
                  final isPending = _pendingDeviceIds.contains(id);
                  Widget spinner(Color color) => SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2, color: color),
                      );

                  return Container(
                    margin: const EdgeInsets.only(bottom: 16),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppColors.paper2,
                      borderRadius: BorderRadius.circular(AppTheme.radiusSm),
                      border: Border.all(color: AppColors.ink2),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          name,
                          style: AppTheme.display(color: AppColors.ink, fontSize: 18, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          isOnline ? 'Online' : 'Offline',
                          style: AppTheme.mono(
                            color: isOnline ? AppColors.turf : AppColors.steel,
                            fontSize: 13,
                          ),
                        ),
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            Expanded(
                              child: SizedBox(
                                height: 48,
                                child: PressScale(
                                  enabled: !isPending,
                                  onTap: () => sendCommand(id, name, true),
                                  child: IgnorePointer(
                                    child: ElevatedButton(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: AppColors.gold,
                                        foregroundColor: AppColors.voidBg,
                                      ),
                                      onPressed: isPending ? null : () => sendCommand(id, name, true),
                                      child: isPending
                                          ? spinner(AppColors.voidBg)
                                          : Text('UNLOCK', style: AppTheme.mono(fontWeight: FontWeight.bold, color: AppColors.voidBg)),
                                    ),
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: SizedBox(
                                height: 48,
                                child: ElevatedButton(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: AppColors.paper3,
                                    foregroundColor: AppColors.ink,
                                    side: const BorderSide(color: AppColors.ink2),
                                  ),
                                  onPressed: isPending ? null : () => sendCommand(id, name, false),
                                  child: isPending
                                      ? spinner(AppColors.ink)
                                      : Text('LOCK', style: AppTheme.mono(fontWeight: FontWeight.bold, color: AppColors.ink)),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  );
                }).toList(),
              ),
            );
          },
          loading: () => const Center(
            child: CircularProgressIndicator(color: AppColors.gold),
          ),
          error: (err, _) => Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Text(
                'Could not load lock devices:\n$err',
                textAlign: TextAlign.center,
                style: AppTheme.body(color: AppColors.ink, fontSize: 15),
              ),
            ),
          ),
        );
  }
}
