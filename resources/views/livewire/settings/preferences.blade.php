<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $timezone = '';
    public string $defaultCurrency = '';

    public function mount(): void
    {
        $profile = Auth::user()->profile;
        $this->timezone = $profile->timezone ?? 'Europe/Berlin';
        $this->defaultCurrency = $profile->default_currency ?? 'EUR';
    }

    public function updatePreferences(): void
    {
        $validated = $this->validate([
            'timezone' => ['required', 'string', 'timezone:all'],
            'defaultCurrency' => ['required', 'string', 'max:3'],
        ]);

        $profile = Auth::user()->profile;
        $profile->timezone = $validated['timezone'];
        $profile->default_currency = $validated['defaultCurrency'];
        $profile->save();

        $this->dispatch('preferences-updated');

        Flux\Flux::toast(
            text: 'Einstellungen gespeichert',
            variant: 'success',
        );
    }

    public function with(): array
    {
        return [
            'timezones' => \DateTimeZone::listIdentifiers(),
            'currencies' => ['EUR', 'USD', 'GBP', 'CHF', 'JPY', 'AUD', 'CAD'],
        ];
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Preferences')" :subheading="__('Configure your timezone and currency preferences')">
        <form wire:submit="updatePreferences" class="my-6 w-full space-y-6">
            <flux:field>
                <flux:label>{{ __('Timezone') }}</flux:label>
                <flux:select wire:model="timezone" variant="listbox" searchable>
                    @foreach($timezones as $tz)
                        <flux:select.option value="{{ $tz }}">
                            {{ $tz }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:description>
                    {{ __('All dates and times will be displayed in this timezone') }}
                </flux:description>
                <flux:error name="timezone" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Default Currency') }}</flux:label>
                <flux:select wire:model="defaultCurrency" variant="listbox">
                    @foreach($currencies as $currency)
                        <flux:select.option value="{{ $currency }}">
                            {{ $currency }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:description>
                    {{ __('This will be the default currency for new accounts') }}
                </flux:description>
                <flux:error name="defaultCurrency" />
            </flux:field>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="preferences-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
