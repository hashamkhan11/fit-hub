import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/page_header.dart';
import '../widgets/zine.dart';

final notificationsProvider = FutureProvider.autoDispose((ref) async {
  final client = ref.watch(apiClientProvider);
  return client.fetchNotifications();
});

class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});

  @override
  ConsumerState<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  bool _deletingAll = false;

  @override
  void initState() {
    super.initState();
    // Mark everything as read as soon as the inbox is opened, so the bell
    // badge clears — matches how a normal notification inbox behaves.
    Future.microtask(() async {
      try {
        await ref.read(apiClientProvider).markNotificationsRead();
      } catch (_) {
        // Non-critical — the badge will just stay lit until the next open.
      }
    });
  }

  IconData _iconFor(String type) {
    switch (type) {
      case 'class':
        return Icons.fitness_center_rounded;
      case 'renewal':
        return Icons.autorenew_rounded;
      case 'progress':
        return Icons.trending_up_rounded;
      default:
        return Icons.notifications_rounded;
    }
  }

  String _timeAgo(DateTime dt) {
    final diff = DateTime.now().difference(dt);
    if (diff.inMinutes < 1) return 'Just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    if (diff.inDays < 7) return '${diff.inDays}d ago';
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];
    return '${months[dt.month - 1]} ${dt.day}';
  }

  Future<void> _deleteOne(int id) async {
    try {
      await ref.read(apiClientProvider).deleteNotification(id);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
        );
      }
    } finally {
      ref.invalidate(notificationsProvider);
    }
  }

  Future<void> _confirmDeleteAll() async {
    if (_deletingAll) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('DELETE ALL'),
        content: const Text('This will permanently delete every notification. Are you sure?'),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialogContext).pop(false), child: const Text('CANCEL')),
          PressScale(
            onTap: () => Navigator.of(dialogContext).pop(true),
            child: IgnorePointer(
              child: TextButton(
                onPressed: () => Navigator.of(dialogContext).pop(true),
                child: Text('DELETE ALL', style: AppTheme.body(color: AppColors.tape, fontWeight: FontWeight.w700)),
              ),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    setState(() => _deletingAll = true);

    try {
      await ref.read(apiClientProvider).deleteAllNotifications();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
        );
      }
    } finally {
      ref.invalidate(notificationsProvider);
      if (mounted) setState(() => _deletingAll = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final notificationsAsync = ref.watch(notificationsProvider);
    final hasItems = (notificationsAsync.asData?.value['notifications'] as List?)?.isNotEmpty ?? false;

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
              child: PageHeader(
                title: 'Notifications',
                fontSize: 28,
                trailing: hasItems
                    ? PressScale(
                        onTap: _confirmDeleteAll,
                        enabled: !_deletingAll,
                        child: IgnorePointer(
                          child: TextButton(
                            onPressed: _deletingAll ? null : _confirmDeleteAll,
                            child: _deletingAll
                                ? const SizedBox(
                                    width: 14,
                                    height: 14,
                                    child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.tape),
                                  )
                                : Text(
                                    'DELETE ALL',
                                    style: AppTheme.mono(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.tape),
                                  ),
                          ),
                        ),
                      )
                    : null,
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () async => ref.invalidate(notificationsProvider),
                color: AppColors.gold,
                backgroundColor: AppColors.paper2,
                child: notificationsAsync.when(
                  data: (data) {
                    final items = (data['notifications'] as List).cast<Map<String, dynamic>>();

                    if (items.isEmpty) {
                      return ListView(
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(32),
                            child: Column(
                              children: [
                                const Icon(Icons.notifications_off_outlined, size: 40, color: AppColors.steel),
                                const SizedBox(height: 12),
                                Text(
                                  "You're all caught up — no notifications yet.",
                                  textAlign: TextAlign.center,
                                  style: AppTheme.mono(color: AppColors.steel),
                                ),
                              ],
                            ),
                          ),
                        ],
                      );
                    }

                    return ListView.separated(
                      padding: const EdgeInsets.all(16),
                      itemCount: items.length,
                      separatorBuilder: (_, _) => const SizedBox(height: 12),
                      itemBuilder: (context, index) {
                        final item = items[index];
                        final id = item['id'] as int;
                        final isUnread = item['read_at'] == null;
                        final createdAt = DateTime.parse(item['created_at'] as String).toLocal();

                        return Dismissible(
                          key: ValueKey(id),
                          direction: DismissDirection.endToStart,
                          onDismissed: (_) => _deleteOne(id),
                          background: Container(
                            alignment: Alignment.centerRight,
                            padding: const EdgeInsets.symmetric(horizontal: 20),
                            decoration: BoxDecoration(
                              color: AppColors.tape.withValues(alpha: 0.85),
                              borderRadius: BorderRadius.circular(AppTheme.radiusXl),
                            ),
                            child: const Icon(Icons.delete_outline_rounded, color: Colors.white),
                          ),
                          child: MemCard(
                            padding: const EdgeInsets.all(16),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(9),
                                  decoration: BoxDecoration(
                                    color: AppColors.gold.withValues(alpha: 0.14),
                                    shape: BoxShape.circle,
                                  ),
                                  child: Icon(_iconFor(item['type'] as String? ?? 'general'), size: 18, color: AppColors.gold),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Expanded(
                                            child: Text(
                                              item['title'] as String,
                                              style: AppTheme.display(fontSize: 15, fontWeight: FontWeight.w700),
                                            ),
                                          ),
                                          if (isUnread) ...[
                                            const SizedBox(width: 8),
                                            Container(
                                              width: 8,
                                              height: 8,
                                              decoration: const BoxDecoration(shape: BoxShape.circle, color: AppColors.gold),
                                            ),
                                          ],
                                        ],
                                      ),
                                      const SizedBox(height: 4),
                                      Text(item['body'] as String, style: AppTheme.body(fontSize: 13, color: AppColors.steel)),
                                      const SizedBox(height: 6),
                                      Text(_timeAgo(createdAt), style: AppTheme.mono(fontSize: 11, color: AppColors.steel)),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    );
                  },
                  loading: () => const Center(child: CircularProgressIndicator()),
                  error: (err, _) => Center(
                    child: Text('Could not load notifications: $err', style: AppTheme.body(color: AppColors.tape)),
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
