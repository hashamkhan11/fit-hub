import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/page_header.dart';
import '../widgets/zine.dart';

final paymentHistoryProvider = FutureProvider.autoDispose((ref) async {
  final client = ref.watch(apiClientProvider);
  return client.fetchPaymentHistory();
});

class PaymentHistoryScreen extends ConsumerWidget {
  const PaymentHistoryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final historyAsync = ref.watch(paymentHistoryProvider);

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            const Padding(
              padding: EdgeInsets.fromLTRB(20, 12, 20, 8),
              child: PageHeader(title: 'Payment History'),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () async => ref.invalidate(paymentHistoryProvider),
                color: AppColors.gold,
                backgroundColor: AppColors.paper2,
                child: historyAsync.when(
                  data: (data) {
                    final payments = (data['payments'] as List<dynamic>).cast<Map<String, dynamic>>();
                    final currencySymbol = data['currency_symbol'] as String? ?? '';

                    if (payments.isEmpty) {
                      return ListView(
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(24),
                            child: Text('No payments recorded yet.', style: AppTheme.mono(color: AppColors.steel)),
                          ),
                        ],
                      );
                    }

                    return ListView.separated(
                      padding: const EdgeInsets.all(16),
                      itemCount: payments.length,
                      separatorBuilder: (_, _) => const SizedBox(height: 12),
                      itemBuilder: (context, index) {
                        final payment = payments[index];
                        final amount = double.tryParse(payment['amount'].toString()) ?? 0;
                        final paidAt = (payment['paid_at'] as String?)?.split('T').first ?? '—';
                        final rawMethod = payment['method'] as String?;
                        final method = (rawMethod == null || rawMethod.isEmpty) ? '—' : rawMethod;
                        final membership = payment['membership'] as Map<String, dynamic>?;
                        final plan = membership?['plan'] as Map<String, dynamic>?;
                        final planName = plan?['name'] as String?;

                        return MemCard(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    '$currencySymbol${amount.toStringAsFixed(2)}',
                                    style: AppTheme.display(fontSize: 20, fontWeight: FontWeight.w700),
                                  ),
                                  Text(paidAt, style: AppTheme.mono(fontSize: 12, color: AppColors.steel)),
                                ],
                              ),
                              const SizedBox(height: 6),
                              if (planName != null)
                                Text(planName, style: AppTheme.mono(fontSize: 13, color: AppColors.steel)),
                              Text(
                                method[0].toUpperCase() + method.substring(1),
                                style: AppTheme.mono(fontSize: 12, color: AppColors.steel),
                              ),
                            ],
                          ),
                        );
                      },
                    );
                  },
                  loading: () => const Center(child: CircularProgressIndicator()),
                  error: (err, _) => Center(
                    child: Text('Could not load payment history: $err', style: AppTheme.body(color: AppColors.tape)),
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
