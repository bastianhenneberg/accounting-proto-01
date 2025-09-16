<?php

use Livewire\Volt\Component;

new class extends Component {
    public string $currentLocale;

    public function mount(): void
    {
        $this->currentLocale = app()->getLocale();
    }

    public function setLocale(string $locale): void
    {
        if (in_array($locale, ['en', 'de'])) {
            session(['locale' => $locale]);
            $this->currentLocale = $locale;

            session()->flash('success', __('Language updated successfully.'));
            $this->redirect(request()->header('Referer') ?: route('settings.language'), navigate: true);
        }
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Language')" :subheading="__('Choose your preferred language for the application')">
        <div class="space-y-4">
            <div class="space-y-3">
                <label class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors {{ $currentLocale === 'en' ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20' : '' }}" wire:click="setLocale('en')">
                    <span class="text-xl">🇺🇸</span>
                    <div class="flex-1">
                        <div class="font-medium text-gray-900 dark:text-white">{{ __('English') }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">English (United States)</div>
                    </div>
                    <div class="flex items-center">
                        @if($currentLocale === 'en')
                            <flux:icon.check-circle class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        @else
                            <div class="w-5 h-5 border-2 border-gray-300 rounded-full dark:border-gray-600"></div>
                        @endif
                    </div>
                </label>

                <label class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors {{ $currentLocale === 'de' ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20' : '' }}" wire:click="setLocale('de')">
                    <span class="text-xl">🇩🇪</span>
                    <div class="flex-1">
                        <div class="font-medium text-gray-900 dark:text-white">{{ __('German') }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Deutsch (Deutschland)</div>
                    </div>
                    <div class="flex items-center">
                        @if($currentLocale === 'de')
                            <flux:icon.check-circle class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        @else
                            <div class="w-5 h-5 border-2 border-gray-300 rounded-full dark:border-gray-600"></div>
                        @endif
                    </div>
                </label>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-start space-x-3">
                    <flux:icon.information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                    <div>
                        <h4 class="font-medium text-blue-900 dark:text-blue-100">{{ __('Language Information') }}</h4>
                        <p class="text-sm text-blue-800 dark:text-blue-200 mt-1">{{ __('The language change will take effect immediately. The interface will be updated in your selected language.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </x-settings.layout>

    @if(session('success'))
        <flux:toast variant="success">{{ session('success') }}</flux:toast>
    @endif
</section>