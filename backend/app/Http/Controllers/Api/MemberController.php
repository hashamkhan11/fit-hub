<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MemberController extends Controller
{
    public function qr(Request $request): Response
    {
        return response(
            QrCode::size(300)->generate($request->user()->qr_code),
            200,
            ['Content-Type' => 'image/svg+xml']
        );
    }

    public function membership(Request $request)
    {
        $member = $request->user();

        // Prefer the membership that's actually active now over just the latest
        // end_date, so an old paused one doesn't win. Fall back to latest end_date
        // only if nothing is active, so expired members still see something.
        $membership = $member->activeMembership()
            ?? $member->memberships()->latest('end_date')->first();

        $membership?->loadMissing('plan');

        $trainer = $member->trainer;

        return response()->json([
            'membership' => $membership,
            'trainer' => $trainer ? ['name' => $trainer->name, 'email' => $trainer->email] : null,
        ]);
    }

    public function updateFcmToken(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        $request->user()->update(['fcm_token' => $validated['fcm_token']]);

        return response()->json(['message' => 'Token saved.']);
    }

    public function attendance(Request $request)
    {
        $attendance = $request->user()
            ->attendances()
            ->orderByDesc('checked_in_at')
            ->limit(60)
            ->get();

        return response()->json(['attendance' => $attendance]);
    }

    public function measurements(Request $request)
    {
        $measurements = $request->user()
            ->measurements()
            ->orderBy('recorded_at')
            ->get();

        return response()->json(['measurements' => $measurements]);
    }

    public function storeMeasurement(Request $request)
    {
        $validated = $request->validate([
            'recorded_at' => ['required', 'date'],
            'weight_kg' => ['nullable', 'numeric'],
            'body_fat_percentage' => ['nullable', 'numeric'],
            'chest_cm' => ['nullable', 'numeric'],
            'waist_cm' => ['nullable', 'numeric'],
            'hips_cm' => ['nullable', 'numeric'],
            'arms_cm' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $measurement = $request->user()->measurements()->create([
            'gym_id' => $request->user()->gym_id,
            ...$validated,
        ]);

        return response()->json(['measurement' => $measurement], 201);
    }

    public function updateProfile(Request $request)
    {
        if ($request->has('height_cm') && $request->input('height_cm') === '') {
            $request->merge(['height_cm' => null]);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'height_cm' => ['sometimes', 'nullable', 'numeric', 'min:50', 'max:300'],
        ]);

        $request->user()->update($validated);

        return response()->json(['member' => $request->user()->fresh()]);
    }

    public function payments(Request $request)
    {
        $member = $request->user();

        $payments = Payment::whereHas('membership', fn ($query) => $query->where('member_id', $member->id))
            ->with('membership.plan')
            ->orderByDesc('paid_at')
            ->get();

        return response()->json([
            'payments' => $payments,
            'currency_symbol' => $member->gym->currency_symbol,
        ]);
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $member = $request->user();

        if ($member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
        }

        $member->update([
            'photo_path' => $request->file('photo')->store('member-photos', 'public'),
        ]);

        return response()->json(['member' => $member->fresh()]);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $request->user()->update(['password' => $validated['new_password']]);

        return response()->json(['message' => 'Password updated.']);
    }
}
