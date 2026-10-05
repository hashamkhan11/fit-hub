<?php

namespace App\Livewire;

use App\Models\Attendance as AttendanceModel;
use App\Models\Member;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Attendance extends Component
{
    public ?string $lastMessage = null;

    public bool $lastSuccess = false;

    public function mount(): void
    {
        Gate::authorize('view-attendance');
    }

    public function render()
    {
        return view('livewire.attendance', [
            'recent' => AttendanceModel::where('gym_id', auth()->user()->gym_id)
                ->with('member')
                ->latest('checked_in_at')
                ->limit(20)
                ->get(),
            'currentlyIn' => AttendanceModel::where('gym_id', auth()->user()->gym_id)
                ->whereNull('checked_out_at')
                ->count(),
        ]);
    }

    public function checkIn(string $code): void
    {
        Gate::authorize('checkin-attendance');

        $member = Member::where('gym_id', auth()->user()->gym_id)
            ->where('qr_code', $code)
            ->first();

        if (! $member) {
            $this->lastSuccess = false;
            $this->lastMessage = 'QR code not recognized.';

            return;
        }

        $result = AttendanceModel::recordScan($member);

        $this->lastSuccess = $result['success'];
        $this->lastMessage = $result['message'];
    }
}
