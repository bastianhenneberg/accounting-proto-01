<?php

use App\Services\FinancialCalendarService;
use Carbon\Carbon;
use Livewire\Volt\Component;

new class extends Component {
    public $currentMonth;
    public $calendarData = [];
    public $selectedDay = null;
    public $showDayDetail = false;

    public function mount(): void
    {
        $this->currentMonth = now();
        $this->loadCalendarData();
    }

    public function loadCalendarData(): void
    {
        $service = new FinancialCalendarService();
        $this->calendarData = $service->getMonthData($this->currentMonth, auth()->id());
    }

    public function previousMonth(): void
    {
        $this->currentMonth = $this->currentMonth->copy()->subMonth();
        $this->loadCalendarData();
    }

    public function nextMonth(): void
    {
        $this->currentMonth = $this->currentMonth->copy()->addMonth();
        $this->loadCalendarData();
    }

    public function goToToday(): void
    {
        $this->currentMonth = now();
        $this->loadCalendarData();
    }

    public function selectDay($dateString): void
    {
        $this->selectedDay = Carbon::parse($dateString);
        $this->showDayDetail = true;
    }

    public function closeDayDetail(): void
    {
        $this->showDayDetail = false;
        $this->selectedDay = null;
    }

    public function getDayDetailData(): array
    {
        if (!$this->selectedDay) {
            return [];
        }

        $service = new FinancialCalendarService();
        return $service->getDayDetail($this->selectedDay, auth()->id());
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Financial Calendar') }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ __('Overview of all your planned and recurring transactions') }}</p>
        </div>
        <div class="flex space-x-2">
            <flux:button wire:click="goToToday" variant="outline" size="sm">
                {{ __('Today') }}
            </flux:button>
            <flux:button href="/planned/create" variant="primary" icon="plus" wire:navigate>
                {{ __('Add Planned') }}
            </flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-4">
        {{-- Calendar Main View --}}
        <div class="lg:col-span-3">
            <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                {{-- Calendar Header --}}
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <flux:button wire:click="previousMonth" variant="ghost" size="sm" icon="chevron-left" />
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ $calendarData['month_name'] ?? $currentMonth->format('F Y') }}
                        </h2>
                        <flux:button wire:click="nextMonth" variant="ghost" size="sm" icon="chevron-right" />
                    </div>

                    <div class="flex items-center space-x-4 text-sm">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Income') }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Expense') }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Recurring') }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Planned') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Calendar Grid --}}
                <div class="grid grid-cols-7 gap-1">
                    {{-- Day Headers --}}
                    @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                        <div class="p-2 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ __($day) }}
                        </div>
                    @endforeach

                    {{-- Empty cells for month start --}}
                    @php
                        $startOfMonth = $currentMonth->copy()->startOfMonth();
                        $dayOfWeek = $startOfMonth->dayOfWeekIso; // 1 = Monday, 7 = Sunday
                    @endphp

                    @for($i = 1; $i < $dayOfWeek; $i++)
                        <div class="aspect-square p-1"></div>
                    @endfor

                    {{-- Calendar Days --}}
                    @for($day = 1; $day <= $currentMonth->daysInMonth; $day++)
                        @php
                            $date = $currentMonth->copy()->day($day);
                            $dateKey = $date->toDateString();
                            $dayData = $calendarData['days'][$dateKey] ?? ['has_transactions' => false, 'total_impact' => 0, 'transaction_count' => 0, 'recurring' => [], 'planned' => []];
                            $isToday = $date->isToday();
                            $isPast = $date->isPast();
                            $isFuture = $date->isFuture();
                        @endphp

                        <div class="aspect-square p-1">
                            <div
                                class="w-full h-full flex flex-col items-center justify-center rounded-lg cursor-pointer transition-all duration-200 {{ $isToday ? 'bg-blue-100 border-2 border-blue-500 dark:bg-blue-900/20 dark:border-blue-400' : ($dayData['has_transactions'] ? 'bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600' : 'hover:bg-gray-50 dark:hover:bg-gray-800') }}"
                                wire:click="selectDay('{{ $dateKey }}')"
                            >
                                <span class="text-sm font-medium {{ $isToday ? 'text-blue-600 dark:text-blue-400' : ($isPast ? 'text-gray-400 dark:text-gray-500' : 'text-gray-900 dark:text-white') }}">
                                    {{ $day }}
                                </span>

                                {{-- Transaction Indicators --}}
                                @if($dayData['has_transactions'])
                                    <div class="flex items-center justify-center space-x-1 mt-1">
                                        {{-- Actual transaction indicators --}}
                                        @if(count($dayData['actual']) > 0)
                                            @php
                                                $hasIncome = collect($dayData['actual'])->contains('type', 'income');
                                                $hasExpense = collect($dayData['actual'])->contains('type', 'expense');
                                            @endphp
                                            @if($hasIncome)
                                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                            @endif
                                            @if($hasExpense)
                                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                            @endif
                                        @endif

                                        {{-- Recurring indicators --}}
                                        @if(count($dayData['recurring']) > 0)
                                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                        @endif

                                        {{-- Planned indicators --}}
                                        @if(count($dayData['planned']) > 0)
                                            @php
                                                $hasConfirmed = collect($dayData['planned'])->contains('status', 'confirmed');
                                            @endphp
                                            <div class="w-2 h-2 {{ $hasConfirmed ? 'bg-purple-600' : 'bg-purple-300' }} rounded-full"></div>
                                        @endif

                                        {{-- Transaction count badge --}}
                                        @if($dayData['transaction_count'] > 3)
                                            <span class="text-xs bg-gray-600 text-white rounded-full w-4 h-4 flex items-center justify-center">
                                                {{ $dayData['transaction_count'] }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Net impact indicator --}}
                                    @if(abs($dayData['total_impact']) > 0)
                                        <div class="text-xs font-medium mt-1 {{ $dayData['total_impact'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $dayData['total_impact'] > 0 ? '+' : '' }}{{ number_format($dayData['total_impact'], 0) }}
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        {{-- Month Summary Sidebar --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Month Summary') }}</h3>

                @if(isset($calendarData['monthly_summary']))
                    <div class="space-y-4">
                        {{-- Actual Transactions Summary --}}
                        <div>
                            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">{{ __('Completed') }}</h4>
                            <div class="space-y-1">
                                <div class="flex justify-between text-sm">
                                    <span class="text-green-600 dark:text-green-400">{{ __('Income') }}</span>
                                    <span class="font-medium">+€{{ number_format($calendarData['monthly_summary']['actual_income'], 2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-red-600 dark:text-red-400">{{ __('Expenses') }}</span>
                                    <span class="font-medium">-€{{ number_format($calendarData['monthly_summary']['actual_expenses'], 2) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Recurring Summary --}}
                        <div>
                            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">{{ __('Recurring') }}</h4>
                            <div class="space-y-1">
                                <div class="flex justify-between text-sm">
                                    <span class="text-green-600 dark:text-green-400">{{ __('Income') }}</span>
                                    <span class="font-medium">+€{{ number_format($calendarData['monthly_summary']['recurring_income'], 2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-red-600 dark:text-red-400">{{ __('Expenses') }}</span>
                                    <span class="font-medium">-€{{ number_format($calendarData['monthly_summary']['recurring_expenses'], 2) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Planned Summary --}}
                        <div>
                            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">{{ __('Planned') }}</h4>
                            <div class="space-y-1">
                                <div class="flex justify-between text-sm">
                                    <span class="text-green-600 dark:text-green-400">{{ __('Income') }}</span>
                                    <span class="font-medium">+€{{ number_format($calendarData['monthly_summary']['planned_income'], 2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-red-600 dark:text-red-400">{{ __('Expenses') }}</span>
                                    <span class="font-medium">-€{{ number_format($calendarData['monthly_summary']['planned_expenses'], 2) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Net Projection --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-900 dark:text-white">{{ __('Net Projected') }}</span>
                                <span class="font-bold {{ $calendarData['monthly_summary']['net_projected'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $calendarData['monthly_summary']['net_projected'] >= 0 ? '+' : '' }}€{{ number_format($calendarData['monthly_summary']['net_projected'], 2) }}
                                </span>
                            </div>
                        </div>

                        {{-- Quick Stats --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <p>{{ __('Total Transactions') }}: {{ $calendarData['monthly_summary']['total_transactions'] }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Day Detail Modal --}}
    @if($showDayDetail && $selectedDay)
        <flux:modal name="day-detail" wire:model="showDayDetail">
            @php $dayDetail = $this->getDayDetailData(); @endphp

            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $selectedDay->format('l, F j, Y') }}</flux:heading>
                    <flux:subheading>{{ __('Financial transactions for this day') }}</flux:subheading>
                </div>

                @if($dayDetail['has_transactions'])
                    <div class="space-y-4">
                        {{-- Actual Transactions --}}
                        @if(count($dayDetail['actual']) > 0)
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                    <flux:icon.check-circle class="w-4 h-4 mr-2 text-green-600 dark:text-green-400" />
                                    {{ __('Completed Transactions') }}
                                </h4>
                                <div class="space-y-2">
                                    @foreach($dayDetail['actual'] as $actual)
                                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <div class="p-2 rounded-full {{ $actual['type'] === 'income' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20' }}">
                                                    @if($actual['type'] === 'income')
                                                        <flux:icon.arrow-up class="w-4 h-4 text-green-600 dark:text-green-400" />
                                                    @else
                                                        <flux:icon.arrow-down class="w-4 h-4 text-red-600 dark:text-red-400" />
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $actual['description'] }}</p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $actual['account']->name }} • {{ $actual['category']->name }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-semibold {{ $actual['type'] === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $actual['type'] === 'income' ? '+' : '-' }}€{{ number_format($actual['amount'], 2) }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Recurring Transactions --}}
                        @if(count($dayDetail['recurring']) > 0)
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                    <flux:icon.arrow-path class="w-4 h-4 mr-2 text-blue-600 dark:text-blue-400" />
                                    {{ __('Recurring Transactions') }}
                                </h4>
                                <div class="space-y-2">
                                    @foreach($dayDetail['recurring'] as $recurring)
                                        <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <div class="p-2 rounded-full {{ $recurring['type'] === 'income' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20' }}">
                                                    @if($recurring['type'] === 'income')
                                                        <flux:icon.arrow-up class="w-4 h-4 text-green-600 dark:text-green-400" />
                                                    @else
                                                        <flux:icon.arrow-down class="w-4 h-4 text-red-600 dark:text-red-400" />
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $recurring['description'] }}</p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $recurring['account']->name }} • {{ $recurring['category']->name }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-semibold {{ $recurring['type'] === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $recurring['type'] === 'income' ? '+' : '-' }}€{{ number_format($recurring['amount'], 2) }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Planned Transactions --}}
                        @if(count($dayDetail['planned']) > 0)
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                    <flux:icon.calendar class="w-4 h-4 mr-2 text-purple-600 dark:text-purple-400" />
                                    {{ __('Planned Transactions') }}
                                </h4>
                                <div class="space-y-2">
                                    @foreach($dayDetail['planned'] as $planned)
                                        <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <div class="p-2 rounded-full {{ $planned['type'] === 'income' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20' }}">
                                                    @if($planned['type'] === 'income')
                                                        <flux:icon.arrow-up class="w-4 h-4 text-green-600 dark:text-green-400" />
                                                    @else
                                                        <flux:icon.arrow-down class="w-4 h-4 text-red-600 dark:text-red-400" />
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="flex items-center space-x-2">
                                                        <p class="font-medium text-gray-900 dark:text-white">{{ $planned['description'] }}</p>
                                                        @if($planned['status'] === 'confirmed')
                                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                                                ✓ {{ __('Confirmed') }}
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                                {{ __('Pending') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $planned['account']->name }} • {{ $planned['category']->name }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-semibold {{ $planned['type'] === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $planned['type'] === 'income' ? '+' : '-' }}€{{ number_format($planned['amount'], 2) }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Day Total --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-900 dark:text-white">{{ __('Day Total') }}</span>
                                <span class="text-lg font-bold {{ $dayDetail['total_impact'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $dayDetail['total_impact'] >= 0 ? '+' : '' }}€{{ number_format($dayDetail['total_impact'], 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-8">
                        <flux:icon.calendar class="w-12 h-12 text-gray-400 dark:text-gray-600 mx-auto mb-3" />
                        <p class="text-gray-500 dark:text-gray-400">{{ __('No transactions planned for this day') }}</p>
                        <flux:button href="/planned/create" variant="primary" size="sm" class="mt-3" wire:navigate>
                            {{ __('Add Planned Transaction') }}
                        </flux:button>
                    </div>
                @endif
            </div>

            <div class="flex space-x-2 pt-4">
                <flux:button wire:click="closeDayDetail" variant="ghost">
                    {{ __('Close') }}
                </flux:button>
                <flux:button href="/planned" variant="outline" wire:navigate>
                    {{ __('Manage Planned') }}
                </flux:button>
            </div>
        </flux:modal>
    @endif
</div>