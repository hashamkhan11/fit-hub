<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SignupController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MemberQrController;
use App\Http\Controllers\Platform\PlatformForgotPasswordController;
use App\Http\Controllers\Platform\PlatformLoginController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\Account;
use App\Livewire\Activity;
use App\Livewire\Attendance;
use App\Livewire\Billing;
use App\Livewire\Bookings;
use App\Livewire\Classes;
use App\Livewire\Dashboard;
use App\Livewire\Fingerprints;
use App\Livewire\GymProfile;
use App\Livewire\Insight;
use App\Livewire\LockDevices;
use App\Livewire\Members;
use App\Livewire\Payments;
use App\Livewire\Plans;
use App\Livewire\Platform\Activity as PlatformActivity;
use App\Livewire\Platform\Admins as PlatformAdmins;
use App\Livewire\Platform\Business as PlatformBusiness;
use App\Livewire\Platform\GymCreate;
use App\Livewire\Platform\Gyms as PlatformGyms;
use App\Livewire\Platform\GymShow;
use App\Livewire\Platform\Overview as PlatformOverview;
use App\Livewire\Platform\PlatformAccount;
use App\Livewire\Platform\SubscriptionPlans;
use App\Livewire\Staff;
use App\Livewire\Trainers;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('marketing.home', [
        'plans' => SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        'faqs' => [
            ['q' => 'Do I need to sign a contract?', 'a' => 'No. FitHub is billed month-to-month (or annually if you prefer), and you can cancel anytime from your dashboard.'],
            ['q' => 'Can my staff have their own logins?', 'a' => 'Yes. Staff and trainers get scoped logins so they only see what their role needs — you don\'t have to hand over full owner access.'],
            ['q' => 'Does FitHub handle payments and billing?', 'a' => 'Yes, billing runs on Stripe. Members can see their own invoices and payment history from their account.'],
            ['q' => 'Can members book classes from their phone?', 'a' => 'Yes. Members use the FitHub app to check their plan, book or cancel a class spot, and see their attendance history.'],
            ['q' => 'How does check-in work?', 'a' => 'Members check in with a QR code at the front desk — attendance is logged automatically, no paper sign-in sheets.'],
            ['q' => 'Is there a setup fee or minimum commitment?', 'a' => 'No setup fee. Start with a 14-day free trial, no card required, and only pay if you decide to keep using it.'],
        ],
    ]);
});

Route::view('/legal/terms', 'legal.terms')->name('legal.terms');
Route::view('/legal/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/legal/data-deletion', 'legal.data-deletion')->name('legal.data-deletion');

Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => url('/'), 'priority' => '1.0'],
        ['loc' => url('/#features'), 'priority' => '0.8'],
        ['loc' => url('/#pricing'), 'priority' => '0.8'],
        ['loc' => url('/#faq'), 'priority' => '0.6'],
        ['loc' => url('/#contact'), 'priority' => '0.6'],
        ['loc' => route('login'), 'priority' => '0.3'],
        ['loc' => route('signup'), 'priority' => '0.7'],
        ['loc' => route('legal.terms'), 'priority' => '0.2'],
        ['loc' => route('legal.privacy'), 'priority' => '0.2'],
    ];

    return response()
        ->view('sitemap', ['urls' => $urls])
        ->header('Content-Type', 'application/xml');
});

Route::get('/login', [LoginController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware(['guest', 'throttle:5,1']);
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth:web')->name('logout');

Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->middleware('guest')->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware(['guest', 'throttle:3,1'])->name('password.email');
Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'edit'])->middleware('guest')->name('password.reset');
Route::post('/reset-password', [ForgotPasswordController::class, 'update'])->middleware(['guest', 'throttle:6,1'])->name('password.update');

Route::get('/start-trial', [SignupController::class, 'create'])->middleware('guest')->name('signup');
Route::post('/start-trial', [SignupController::class, 'store'])->middleware(['guest', 'throttle:5,1']);

Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact');

Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->middleware(['auth:web', 'signed'])
    ->name('verification.verify');
Route::post('/email/verification-notification', [VerifyEmailController::class, 'resend'])
    ->middleware(['auth:web', 'throttle:6,1'])
    ->name('verification.send');

Route::middleware(['auth:web', 'gym.active'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/dashboard/plans', Plans::class)->name('plans');
    Route::get('/dashboard/members', Members::class)->name('members');
    Route::get('/dashboard/members/{member}/qr', [MemberQrController::class, 'show'])->name('members.qr');
    Route::get('/dashboard/trainers', Trainers::class)->name('trainers');
    Route::get('/dashboard/attendance', Attendance::class)->name('attendance');
    Route::get('/dashboard/classes', Classes::class)->name('classes');
    Route::get('/dashboard/bookings', Bookings::class)->name('bookings');
    Route::get('/dashboard/insight', Insight::class)->name('insight');
    Route::get('/dashboard/staff', Staff::class)->name('staff');
    Route::get('/dashboard/activity', Activity::class)->name('activity');
    Route::get('/dashboard/lock-devices', LockDevices::class)->middleware('gym.hardware')->name('lock-devices');
    Route::get('/dashboard/fingerprints', Fingerprints::class)->middleware('gym.hardware')->name('fingerprints');
    Route::get('/dashboard/settings', GymProfile::class)->name('settings');
    Route::get('/dashboard/payments', Payments::class)->name('payments');
    Route::get('/dashboard/payments/{payment}/receipt', [ReceiptController::class, 'show'])->name('payments.receipt');
    Route::get('/dashboard/payments/{payment}/receipt.pdf', [ReceiptController::class, 'pdf'])->name('payments.receipt.pdf');
    Route::get('/dashboard/billing', Billing::class)->name('billing');
    Route::get('/account', Account::class)->name('account');
});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// RankSol platform admin panel — uses its own login, separate from gym owner/staff.
Route::get('/ranksol/login', [PlatformLoginController::class, 'create'])->middleware('guest:platform')->name('platform.login');
Route::post('/ranksol/login', [PlatformLoginController::class, 'store'])->middleware(['guest:platform', 'throttle:5,1']);
Route::post('/ranksol/logout', [PlatformLoginController::class, 'destroy'])->middleware('auth:platform')->name('platform.logout');

Route::get('/ranksol/forgot-password', [PlatformForgotPasswordController::class, 'create'])->middleware('guest:platform')->name('platform.password.request');
Route::post('/ranksol/forgot-password', [PlatformForgotPasswordController::class, 'store'])->middleware(['guest:platform', 'throttle:3,1'])->name('platform.password.email');
Route::get('/ranksol/reset-password/{token}', [PlatformForgotPasswordController::class, 'edit'])->middleware('guest:platform')->name('platform.password.reset');
Route::post('/ranksol/reset-password', [PlatformForgotPasswordController::class, 'update'])->middleware(['guest:platform', 'throttle:6,1'])->name('platform.password.update');

Route::middleware('auth:platform')->group(function () {
    Route::get('/ranksol', PlatformOverview::class)->name('platform.overview');
    Route::get('/ranksol/business', PlatformBusiness::class)->name('platform.business');
    Route::get('/ranksol/gyms/new', GymCreate::class)->name('platform.gyms.new');
    Route::get('/ranksol/gyms/{gym}', GymShow::class)->name('platform.gyms.show');
    Route::get('/ranksol/gyms', PlatformGyms::class)->name('platform.gyms');
    Route::get('/ranksol/plans', SubscriptionPlans::class)->name('platform.plans');
    Route::get('/ranksol/admins', PlatformAdmins::class)->name('platform.admins');
    Route::get('/ranksol/account', PlatformAccount::class)->name('platform.account');
    Route::get('/ranksol/activity', PlatformActivity::class)->name('platform.activity');
});
