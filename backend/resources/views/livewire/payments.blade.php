<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="fh-card">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <label class="fh-label">Search</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by member name or member ID"
                    class="fh-input"
                >
            </div>
            <div>
                <label class="fh-label">From</label>
                <input type="date" wire:model.live="from" class="fh-input">
            </div>
            <div>
                <label class="fh-label">To</label>
                <input type="date" wire:model.live="to" class="fh-input">
            </div>
        </div>
    </div>

    <div class="fh-card-flush">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="fh-th">Date</th>
                        <th class="fh-th">Member</th>
                        <th class="fh-th">Plan</th>
                        <th class="fh-th">Method</th>
                        <th class="fh-th font-mono normal-case tracking-normal">Total</th>
                        <th class="fh-th font-mono normal-case tracking-normal">Paid</th>
                        <th class="fh-th"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr class="fh-tr">
                            <td class="fh-td-mono">{{ $payment->paid_at->format('M j, Y') }}</td>
                            <td class="fh-td">
                                {{ $payment->membership->member->name }}
                                <span class="text-steel text-xs font-mono">{{ $payment->membership->member->display_code }}</span>
                            </td>
                            <td class="fh-td">{{ $payment->membership->plan->name }}</td>
                            <td class="fh-td capitalize">{{ str_replace('_', ' ', $payment->method) }}</td>
                            <td class="fh-td-mono text-steel">{{ $gym->currency_symbol }}{{ number_format($payment->membership->price_paid ?? 0, 2) }}</td>
                            <td class="fh-td-mono">{{ $gym->currency_symbol }}{{ number_format($payment->amount, 2) }}</td>
                            <td class="fh-td text-right whitespace-nowrap">
                                <a href="{{ route('payments.receipt', $payment) }}" target="_blank" class="fh-link-action text-gold-3">Receipt</a>
                                <a href="{{ route('payments.receipt.pdf', $payment) }}" class="fh-link-action text-steel ml-3">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="fh-td text-steel" colspan="7">No payments recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $payments->links() }}
</div>
