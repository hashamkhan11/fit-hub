<div class="max-w-[1100px] mx-auto space-y-6">
    <div>
        <p class="pf-eyebrow">RankSol Platform</p>
        <h1 class="pf-heading text-2xl">Activity</h1>
    </div>

    <div class="pf-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="pf-th">When</th>
                    <th class="pf-th">Admin</th>
                    <th class="pf-th">Gym</th>
                    <th class="pf-th">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="pf-tr">
                        <td class="pf-td-mono">{{ $log->created_at->format('M j, Y g:ia') }}</td>
                        <td class="pf-td">{{ $log->platformAdmin?->name ?? 'System' }}</td>
                        <td class="pf-td">
                            @if ($log->gym)
                                <a href="/ranksol/gyms/{{ $log->gym->id }}" class="text-teal-2 hover:underline">{{ $log->gym->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="pf-td">{{ $log->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="pf-td text-mist" colspan="4">No activity yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{ $logs->links('platform.pagination') }}
</div>
