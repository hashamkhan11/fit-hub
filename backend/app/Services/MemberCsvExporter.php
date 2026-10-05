<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds the CSV member export used by the Members admin screen. Pulled out
 * of the Livewire component because it's plain formatting/output logic that
 * doesn't touch any UI state.
 */
class MemberCsvExporter
{
    public function stream(Collection $members): StreamedResponse
    {
        return response()->streamDownload(function () use ($members) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Member ID', 'Name', 'Email', 'Phone', 'Plan', 'Trainer', 'Membership Ends', 'Payment Status', 'Status', 'Join Date']);

            foreach ($members as $member) {
                $membership = $member->memberships->first();

                fputcsv($handle, [
                    $member->display_code,
                    $member->name,
                    $member->email,
                    $member->phone,
                    $membership?->plan?->name,
                    $member->trainer?->name,
                    $membership?->end_date?->format('Y-m-d'),
                    $membership?->payment_status,
                    $membership?->isPaused() ? 'Paused' : (($membership?->isActive() ?? false) ? 'Active' : 'Inactive'),
                    $member->join_date?->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        }, 'members-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
