<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Finance routes
    Volt::route('accounts', 'accounts.index')->name('accounts.index');
    Volt::route('accounts/create', 'accounts.create')->name('accounts.create');
    Volt::route('accounts/{account}', 'accounts.show')->name('accounts.show');
    Volt::route('accounts/{account}/edit', 'accounts.edit')->name('accounts.edit');
    
    Volt::route('transactions', 'transactions.index')->name('transactions.index');
    Volt::route('transactions/create', 'transactions.create')->name('transactions.create');
    Volt::route('transactions/{transaction}', 'transactions.show')->name('transactions.show');
    Volt::route('transactions/{transaction}/edit', 'transactions.edit')->name('transactions.edit');
    
    Volt::route('categories', 'categories.index')->name('categories.index');
    Volt::route('categories/create', 'categories.create')->name('categories.create');
    
    Volt::route('budgets', 'budgets.index')->name('budgets.index');
    Volt::route('budgets/create', 'budgets.create')->name('budgets.create');
    Volt::route('budgets/{budget}/edit', 'budgets.edit')->name('budgets.edit');
    
    Volt::route('goals', 'goals.index')->name('goals.index');
    Volt::route('goals/create', 'goals.create')->name('goals.create');
    Volt::route('goals/{goal}/edit', 'goals.edit')->name('goals.edit');
    
    Volt::route('recurring', 'recurring.index')->name('recurring.index');
    Volt::route('recurring/create', 'recurring.create')->name('recurring.create');

    Volt::route('planned', 'planned.index')->name('planned.index');
    Volt::route('planned/create', 'planned.create')->name('planned.create');

    // Settings routes
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    Volt::route('settings/language', 'settings.language')->name('settings.language');

    // Locale switching route
    Route::get('/locale/{locale}', function (string $locale) {
        if (in_array($locale, ['en', 'de'])) {
            session(['locale' => $locale]);
        }

        return redirect()->back();
    })->name('locale.set');
});

require __DIR__.'/auth.php';
