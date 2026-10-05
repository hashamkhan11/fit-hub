<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        // Honeypot: hidden field real users never fill in, only bots do.
        if ($request->filled('website')) {
            return redirect('/#contact')->with('status', 'Thanks — we\'ll get back to you shortly.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'gym_name' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        Mail::to(config('app.support_email'))->send(new ContactMessageMail(
            senderName: $validated['name'],
            senderEmail: $validated['email'],
            gymName: $validated['gym_name'] ?? '',
            messageBody: $validated['message'],
        ));

        return redirect('/#contact')->with('status', 'Thanks — we\'ll get back to you within one business day.');
    }
}
