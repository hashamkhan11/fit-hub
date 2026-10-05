import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/page_header.dart';
import '../widgets/zine.dart';
import 'profile_screen.dart' show memberProfileProvider;

final classesProvider = FutureProvider.autoDispose((ref) async {
  final client = ref.watch(apiClientProvider);
  return client.fetchClasses();
});

class ClassesScreen extends ConsumerStatefulWidget {
  const ClassesScreen({super.key});

  @override
  ConsumerState<ClassesScreen> createState() => _ClassesScreenState();
}

class _ClassesScreenState extends ConsumerState<ClassesScreen> {
  final _pendingClassIds = <int>{};
  bool _showBookedOnly = false;

  String _formatDateTime(DateTime dt) {
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];
    final hour = dt.hour % 12 == 0 ? 12 : dt.hour % 12;
    final period = dt.hour >= 12 ? 'PM' : 'AM';
    final minute = dt.minute.toString().padLeft(2, '0');
    return '${months[dt.month - 1]} ${dt.day}, $hour:$minute $period';
  }

  @override
  Widget build(BuildContext context) {
    final classesAsync = ref.watch(classesProvider);
    final client = ref.watch(apiClientProvider);
    final member = ref.watch(memberProfileProvider).asData?.value;
    final fullName = (member?['name'] as String?)?.trim() ?? '';
    final firstName = fullName.isEmpty ? '' : fullName.split(RegExp(r'\s+')).first;
    final photoUrl = member?['photo_url'] as String?;

    Future<void> handleAction(int classId, Future<void> Function() action) async {
      setState(() => _pendingClassIds.add(classId));
      try {
        await action();
        ref.invalidate(classesProvider);
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
          );
        }
      } finally {
        if (mounted) setState(() => _pendingClassIds.remove(classId));
      }
    }

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
              child: PageHeader(
                title: 'Classes',
                showBackButton: false,
                fontSize: 32,
                leading: Avatar(
                  photoUrl: photoUrl,
                  name: firstName,
                  authToken: client.authToken,
                  size: 60,
                  borderWidth: 2.5,
                ),
                trailing: HeaderIconButton(
                  icon: _showBookedOnly ? Icons.filter_list_off : Icons.filter_list,
                  tooltip: _showBookedOnly ? 'Show all classes' : 'Show booked only',
                  onTap: () => setState(() => _showBookedOnly = !_showBookedOnly),
                ),
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () async => ref.invalidate(classesProvider),
                color: AppColors.gold,
                backgroundColor: AppColors.paper2,
                child: classesAsync.when(
                  data: (allClasses) {
                    final classes = _showBookedOnly
                        ? allClasses.where((c) => (c as Map<String, dynamic>)['my_status'] == 'booked').toList()
                        : allClasses;

                    if (classes.isEmpty) {
                      return ListView(
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(24),
                            child: Text(
                              _showBookedOnly ? 'No booked classes yet.' : 'No upcoming classes.',
                              style: AppTheme.mono(color: AppColors.steel),
                            ),
                          ),
                        ],
                      );
                    }

                    return Reveal(
                      child: ListView.separated(
                        padding: const EdgeInsets.all(16),
                        itemCount: classes.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 12),
                        itemBuilder: (context, index) {
                          final gymClass = classes[index] as Map<String, dynamic>;
                          final classId = gymClass['id'] as int;
                          final startTime = DateTime.parse(gymClass['start_time'] as String).toLocal();
                          final myStatus = gymClass['my_status'] as String?;
                          final myBookingId = gymClass['my_booking_id'] as int?;
                          final bookedCount = gymClass['booked_count'] as int;
                          final capacity = gymClass['capacity'] as int;
                          final isPending = _pendingClassIds.contains(classId);
                          final spinner = const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          );

                          return MemCard(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Expanded(
                                      child: Text(
                                        gymClass['name'] as String,
                                        style: AppTheme.display(fontSize: 17, fontWeight: FontWeight.w600),
                                      ),
                                    ),
                                    _statusChip(myStatus),
                                  ],
                                ),
                                const SizedBox(height: 6),
                                if (gymClass['instructor_name'] != null)
                                  Text(
                                    gymClass['instructor_name'] as String,
                                    style: AppTheme.body(color: AppColors.steel),
                                  ),
                                const SizedBox(height: 4),
                                Text(_formatDateTime(startTime), style: AppTheme.mono(fontSize: 13, color: AppColors.steel)),
                                Text('$bookedCount/$capacity booked', style: AppTheme.mono(fontSize: 13, color: AppColors.steel)),
                                const SizedBox(height: 14),
                                if (myStatus == 'booked')
                                  OutlinedButton(
                                    onPressed: isPending ? null : () => handleAction(classId, () => client.cancelBooking(myBookingId!)),
                                    child: isPending ? spinner : const Text('CANCEL BOOKING'),
                                  )
                                else if (myStatus == 'waitlisted')
                                  OutlinedButton(
                                    onPressed: isPending ? null : () => handleAction(classId, () => client.cancelBooking(myBookingId!)),
                                    child: isPending ? spinner : const Text('LEAVE WAITLIST'),
                                  )
                                else
                                  PressScale(
                                    enabled: !isPending,
                                    onTap: () => handleAction(classId, () => client.bookClass(classId)),
                                    child: IgnorePointer(
                                      child: FilledButton(
                                        onPressed: isPending ? null : () => handleAction(classId, () => client.bookClass(classId)),
                                        child: isPending ? spinner : const Text('BOOK'),
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                          );
                        },
                      ),
                    );
                  },
                  loading: () => const Center(child: CircularProgressIndicator()),
                  error: (err, _) => Center(child: Text('Could not load classes: $err', style: AppTheme.body(color: AppColors.tape))),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _statusChip(String? status) {
    if (status == 'booked') {
      return const StampBadge(label: 'BOOKED', variant: StampVariant.good);
    }
    if (status == 'waitlisted') {
      return const StampBadge(label: 'WAITLISTED', variant: StampVariant.blue);
    }
    return const SizedBox.shrink();
  }
}
