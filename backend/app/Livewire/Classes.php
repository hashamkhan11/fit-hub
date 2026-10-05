<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\GymClass;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Classes extends Component
{
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $instructor_name = '';

    #[Validate('required|date')]
    public string $start_time = '';

    #[Validate('required|integer|min:1')]
    public int $duration_minutes = 60;

    #[Validate('required|integer|min:1')]
    public int $capacity = 10;

    public bool $is_active = true;

    public bool $showForm = false;

    public function mount(): void
    {
        Gate::authorize('view-classes');
    }

    public function render()
    {
        $gymId = auth()->user()->gym_id;

        $classes = GymClass::where('gym_id', $gymId)
            ->withCount([
                'bookings as booked_count' => fn ($query) => $query->where('status', 'booked'),
                'bookings as waitlisted_count' => fn ($query) => $query->where('status', 'waitlisted'),
            ])
            ->with(['bookings' => fn ($query) => $query->where('status', 'booked')])
            ->orderBy('start_time')
            ->get();

        $this->attachNoShowCounts($classes, $gymId);

        return view('livewire.classes', [
            'classes' => $classes,
        ]);
    }

    /**
     * A booked member counts as attended if they checked in that day (no per-class scan).
     */
    private function attachNoShowCounts($classes, int $gymId): void
    {
        $pastClasses = $classes->filter(fn ($class) => $class->start_time->isPast());
        $memberIds = $pastClasses->flatMap->bookings->pluck('member_id')->unique();

        $attendedDatesByMember = Attendance::where('gym_id', $gymId)
            ->whereIn('member_id', $memberIds)
            ->get()
            ->groupBy('member_id')
            ->map(fn ($rows) => $rows->pluck('checked_in_at')->map->toDateString()->unique());

        foreach ($pastClasses as $class) {
            $classDate = $class->start_time->toDateString();

            $attended = $class->bookings->filter(
                fn ($booking) => $attendedDatesByMember->get($booking->member_id, collect())->contains($classDate)
            )->count();

            $class->attended_count = $attended;
            $class->no_show_count = $class->booked_count - $attended;
        }
    }

    public function save(): void
    {
        Gate::authorize('manage-classes');

        $this->validate();

        $data = [
            'name' => $this->name,
            'instructor_name' => $this->instructor_name ?: null,
            'start_time' => $this->start_time,
            'duration_minutes' => $this->duration_minutes,
            'capacity' => $this->capacity,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            $class = GymClass::where('gym_id', auth()->user()->gym_id)->findOrFail($this->editingId);

            $bookedCount = $class->bookings()->where('status', 'booked')->count();

            if ($this->capacity < $bookedCount) {
                $this->addError('capacity', "Capacity can't be reduced below the {$bookedCount} members already booked into this class.");

                return;
            }

            $class->update($data);
        } else {
            GymClass::create([...$data, 'gym_id' => auth()->user()->gym_id]);
        }

        $this->resetForm();
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $classId): void
    {
        Gate::authorize('manage-classes');

        $class = GymClass::where('gym_id', auth()->user()->gym_id)->findOrFail($classId);

        $this->editingId = $class->id;
        $this->name = $class->name;
        $this->instructor_name = $class->instructor_name ?? '';
        $this->start_time = $class->start_time->format('Y-m-d\TH:i');
        $this->duration_minutes = $class->duration_minutes;
        $this->capacity = $class->capacity;
        $this->is_active = $class->is_active;
        $this->showForm = true;
    }

    public function delete(int $classId): void
    {
        Gate::authorize('manage-classes');

        $class = GymClass::where('gym_id', auth()->user()->gym_id)->findOrFail($classId);
        $name = $class->name;
        $class->delete();

        ActivityLog::record('class.deleted', "Deleted class {$name}.");
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'instructor_name', 'start_time', 'duration_minutes', 'capacity', 'is_active', 'showForm']);
        $this->is_active = true;
        $this->duration_minutes = 60;
        $this->capacity = 10;
    }
}
