<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
            <flux:sidebar.header>
                <flux:sidebar.brand
                    :href="route('dashboard')"
                    logo="https://fluxui.dev/img/demo/logo.png"
                    logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png"
                    name="Finance App"
                />

                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:sidebar.item>
                
                <flux:sidebar.group expandable icon="credit-card" heading="{{ __('Finance') }}" class="grid">
                    <flux:sidebar.item icon="credit-card" href="/accounts" :current="request()->is('accounts*')" wire:navigate>{{ __('Accounts') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-path" href="/transactions" :current="request()->is('transactions*')" wire:navigate>{{ __('Transactions') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="folder" href="/categories" :current="request()->is('categories*')" wire:navigate>{{ __('Categories') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" href="/budgets" :current="request()->is('budgets*')" wire:navigate>{{ __('Budgets') }}</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group expandable icon="flag" heading="{{ __('Planning') }}" class="grid">
                    <flux:sidebar.item icon="flag" href="/goals" :current="request()->is('goals*')" wire:navigate>{{ __('Goals') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-path" href="/recurring" :current="request()->is('recurring*')" wire:navigate>{{ __('Recurring') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="calendar" href="/planned" :current="request()->is('planned*')" wire:navigate>{{ __('Planned') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="calendar-days" href="/financial-calendar" :current="request()->is('financial-calendar*')" wire:navigate>{{ __('Calendar') }}</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">{{ __('Repository') }}</flux:sidebar.item>
                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">{{ __('Documentation') }}</flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:sidebar.profile :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        {{-- Flux Toast Component --}}
        @persist('toast')
            <flux:toast />
        @endpersist

        @fluxScripts
    </body>
</html>
