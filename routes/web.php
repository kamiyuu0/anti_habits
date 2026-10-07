<?php

use App\Http\Controllers\AntiHabitController;
use App\Http\Controllers\AntiHabitRecordController;
use App\Http\Controllers\Auth\LineLoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
| URL は Rails (Devise) 版と同じパスを維持している。
*/

Route::pattern('anti_habit', '[0-9]+');
Route::pattern('anti_habit_record', '[0-9]+');

Route::get('/', [StaticPageController::class, 'top'])->name('root');
Route::get('/terms', [StaticPageController::class, 'terms'])->name('terms');
Route::get('/privacy', [StaticPageController::class, 'privacy'])->name('privacy');

// ---- 認証 (Devise 互換パス) ----
Route::prefix('users')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/sign_in', [SessionController::class, 'create'])->name('login');
        Route::post('/sign_in', [SessionController::class, 'store'])->name('login.store');

        Route::get('/sign_up', [RegistrationController::class, 'create'])->name('register');
        Route::post('/', [RegistrationController::class, 'store'])->name('register.store');

        Route::get('/password/new', [PasswordResetController::class, 'create'])->name('password.request');
        Route::post('/password', [PasswordResetController::class, 'store'])->name('password.email');
        Route::get('/password/edit', [PasswordResetController::class, 'edit'])->name('password.reset');
        Route::match(['put', 'patch'], '/password', [PasswordResetController::class, 'update'])->name('password.update');
    });

    Route::middleware('auth')->group(function () {
        Route::delete('/sign_out', [SessionController::class, 'destroy'])->name('logout');
        Route::get('/edit', [RegistrationController::class, 'edit'])->name('registration.edit');
        Route::match(['put', 'patch'], '/', [RegistrationController::class, 'update'])->name('registration.update');
    });

    // LINE ログイン / LINE 連携
    Route::post('/auth/line', [LineLoginController::class, 'redirect'])->name('line.redirect');
    Route::match(['get', 'post'], '/auth/line/callback', [LineLoginController::class, 'callback'])->name('line.callback');

    Route::get('/{user}', [UserController::class, 'show'])->whereNumber('user')->name('users.show');
});

// ---- 悪習慣 ----
Route::get('/anti_habits/autocomplete', [AntiHabitController::class, 'autocomplete'])->name('anti_habits.autocomplete');
Route::resource('anti_habits', AntiHabitController::class)->only(['index', 'show']);
Route::middleware('auth')->group(function () {
    Route::resource('anti_habits', AntiHabitController::class)->except(['index', 'show']);

    Route::post('/anti_habits/{anti_habit}/comments', [CommentController::class, 'store'])->name('anti_habits.comments.store');

    Route::get('/anti_habits/{anti_habit}/notification_setting/new', [NotificationSettingController::class, 'create'])->name('anti_habits.notification_setting.create');
    Route::post('/anti_habits/{anti_habit}/notification_setting', [NotificationSettingController::class, 'store'])->name('anti_habits.notification_setting.store');
    Route::get('/anti_habits/{anti_habit}/notification_setting/edit', [NotificationSettingController::class, 'edit'])->name('anti_habits.notification_setting.edit');
    Route::match(['put', 'patch'], '/anti_habits/{anti_habit}/notification_setting', [NotificationSettingController::class, 'update'])->name('anti_habits.notification_setting.update');

    Route::post('/anti_habits/{anti_habit}/reactions', [ReactionController::class, 'store'])->name('anti_habits.reactions.store');
    Route::delete('/anti_habits/{anti_habit}/reactions', [ReactionController::class, 'destroy'])->name('anti_habits.reactions.destroy');

    Route::post('/anti_habits/{anti_habit}/bookmarks', [BookmarkController::class, 'store'])->name('anti_habits.bookmarks.store');
    Route::delete('/anti_habits/{anti_habit}/bookmarks', [BookmarkController::class, 'destroy'])->name('anti_habits.bookmarks.destroy');

    Route::post('/anti_habit_records', [AntiHabitRecordController::class, 'store'])->name('anti_habit_records.store');
    Route::delete('/anti_habit_records/{anti_habit_record}', [AntiHabitRecordController::class, 'destroy'])->name('anti_habit_records.destroy');

    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');
});

Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
