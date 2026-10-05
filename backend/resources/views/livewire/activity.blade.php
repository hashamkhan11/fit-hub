<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="fh-card-flush">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="fh-th">When</th>
                        <th class="fh-th">Staff</th>
                        <th class="fh-th">Action</th>
                        <th class="fh-th">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="fh-tr">
                            <td class="fh-td-mono whitespace-nowrap">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                            <td class="fh-td">{{ $log->user->name ?? 'Deleted user' }}</td>
                            <td class="fh-td font-mono text-xs text-steel">{{ $log->action }}</td>
                            <td class="fh-td">{{ $log->description }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="fh-td text-steel" colspan="4">No activity recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $logs->links() }}
</div>
