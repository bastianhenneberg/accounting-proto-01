# STYLESHEET.md - Design System & UI Guidelines

**Last Updated:** 2025-09-30
**Purpose:** Comprehensive design system documentation for consistent UI/UX across all features

---

## 📐 Table of Contents

1. [Color System](#color-system)
2. [Typography](#typography)
3. [Layout Patterns](#layout-patterns)
4. [Component Patterns](#component-patterns)
5. [Spacing System](#spacing-system)
6. [Dark Mode Guidelines](#dark-mode-guidelines)
7. [Responsive Design](#responsive-design)
8. [Flux Components Usage](#flux-components-usage)
9. [Common UI Patterns](#common-ui-patterns)

---

## 🎨 Color System

### Semantic Color Usage

#### Income/Positive States
- **Green Palette** - Use for income, gains, positive changes
  - Light background: `bg-green-50 dark:bg-green-900/20`
  - Icon background: `bg-green-100 dark:bg-green-900/20`
  - Text: `text-green-600 dark:text-green-400`
  - Border: `border-green-500`

#### Expense/Negative States
- **Red Palette** - Use for expenses, losses, negative changes
  - Light background: `bg-red-50 dark:bg-red-900/20`
  - Icon background: `bg-red-100 dark:bg-red-900/20`
  - Text: `text-red-600 dark:text-red-400`
  - Border: `border-red-500`

#### Informational/Neutral States
- **Blue Palette** - Use for informational elements, primary actions
  - Light background: `bg-blue-50 dark:bg-blue-900/20`
  - Icon background: `bg-blue-100 dark:bg-blue-900/20`
  - Text: `text-blue-600 dark:text-blue-400`
  - Border: `border-blue-500`
  - Gradient header: `bg-gradient-to-r from-blue-600 to-blue-700`

#### Account Type Colors
- **Checking**: Blue (`bg-blue-100 dark:bg-blue-900/20`, `text-blue-600 dark:text-blue-400`)
- **Savings**: Green (`bg-green-100 dark:bg-green-900/20`, `text-green-600 dark:text-green-400`)
- **Credit Card**: Red (`bg-red-100 dark:bg-red-900/20`, `text-red-600 dark:text-red-400`)
- **Cash**: Yellow (`bg-yellow-100 dark:bg-yellow-900/20`, `text-yellow-600 dark:text-yellow-400`)
- **Investment**: Purple (`bg-purple-100 dark:bg-purple-900/20`, `text-purple-600 dark:text-purple-400`)
- **Crypto**: Orange (`text-orange-600 dark:text-orange-400`)

#### Base Colors
- **Background (Primary)**: `bg-white dark:bg-neutral-800`
- **Background (Secondary)**: `bg-gray-50 dark:bg-gray-700`
- **Border**: `border-neutral-200 dark:border-neutral-700`
- **Border (Secondary)**: `border-gray-200 dark:border-gray-700`
- **Text (Primary)**: `text-gray-900 dark:text-white`
- **Text (Secondary)**: `text-gray-600 dark:text-gray-400`
- **Text (Muted)**: `text-gray-500 dark:text-gray-400`

### Tailwind Color Tokens (from app.css)

```css
--color-zinc-50: #fafafa
--color-zinc-100: #f5f5f5
--color-zinc-200: #e5e5e5
--color-zinc-300: #d4d4d4
--color-zinc-400: #a3a3a3
--color-zinc-500: #737373
--color-zinc-600: #525252
--color-zinc-700: #404040
--color-zinc-800: #262626
--color-zinc-900: #171717
--color-zinc-950: #0a0a0a

--color-accent: var(--color-neutral-800)
.dark --color-accent: var(--color-white)
```

---

## 📝 Typography

### Headings

#### Page Title (H1)
```blade
<h1 class="text-2xl font-bold text-gray-900 dark:text-white">Page Title</h1>
```

#### Section Title (H2)
```blade
<h2 class="text-lg font-semibold text-gray-900 dark:text-white">Section Title</h2>
```

#### Card/Item Title (H3)
```blade
<h3 class="text-lg font-semibold text-gray-900 dark:text-white">Item Title</h3>
<!-- OR smaller variant -->
<h3 class="font-medium text-gray-900 dark:text-white">Item Name</h3>
```

### Body Text

#### Description/Subtitle
```blade
<p class="text-gray-600 dark:text-gray-400">Description text goes here</p>
```

#### Metadata/Small Text
```blade
<span class="text-sm text-gray-500 dark:text-gray-400">Metadata</span>
<span class="text-xs text-gray-500 dark:text-gray-400">Very small text</span>
```

#### Labels
```blade
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Label Text</label>
```

### Value Display

#### Large Value (e.g., balance)
```blade
<p class="text-2xl font-bold text-gray-900 dark:text-white">€1,234.56</p>
```

#### Extra Large Value (e.g., total summary)
```blade
<p class="text-3xl font-bold">€10,000.00</p>
```

#### Colored Values (income/expense)
```blade
<!-- Income (positive) -->
<p class="text-2xl font-bold text-green-600 dark:text-green-400">+€500.00</p>

<!-- Expense (negative) -->
<p class="text-2xl font-bold text-red-600 dark:text-red-400">-€250.00</p>
```

---

## 📐 Layout Patterns

### Page Container
```blade
<div class="p-6">
    <!-- Page content -->
</div>
```

### Centered Content (Forms)
```blade
<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <!-- Form content -->
    </div>
</div>
```

### Page Header
```blade
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Page Title') }}</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ __('Page description') }}</p>
    </div>
    <flux:button href="/create" variant="primary" icon="plus" wire:navigate>
        {{ __('Add Item') }}
    </flux:button>
</div>
```

### Grid Layouts

#### 3-Column Grid (Desktop)
```blade
<div class="grid gap-6 lg:grid-cols-3">
    <div><!-- Column 1 --></div>
    <div><!-- Column 2 --></div>
    <div><!-- Column 3 --></div>
</div>
```

#### 2-Column Grid (Form Fields)
```blade
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div><!-- Field 1 --></div>
    <div><!-- Field 2 --></div>
</div>
```

#### Responsive Form Grid
```blade
<div class="grid gap-4 md:grid-cols-2">
    <!-- Fields -->
</div>
```

---

## 🧩 Component Patterns

### Stat Card Pattern
```blade
<div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Label') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">€1,234.56</p>
        </div>
        <div class="p-3 bg-blue-100 rounded-full dark:bg-blue-900/20">
            <flux:icon.banknotes class="w-6 h-6 text-blue-600 dark:text-blue-400" />
        </div>
    </div>
</div>
```

### Gradient Summary Card
```blade
<div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-6 text-white mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-medium opacity-90">{{ __('Total Balance') }}</h3>
            <p class="text-3xl font-bold">€10,000.00</p>
            <p class="text-sm opacity-75">Across 5 accounts</p>
        </div>
        <div class="p-4 bg-white/20 rounded-full">
            <flux:icon.banknotes class="w-8 h-8" />
        </div>
    </div>
</div>
```

### List Item Card
```blade
<div class="bg-white rounded-lg border border-neutral-200 dark:bg-neutral-800 dark:border-neutral-700">
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <!-- Icon -->
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/20">
                        <flux:icon.credit-card class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <!-- Content -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Item Name</h3>
                        <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                            <span>Metadata 1</span>
                            <span>•</span>
                            <span>Metadata 2</span>
                        </div>
                    </div>
                </div>

                <!-- Right Side (Value + Actions) -->
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">€1,234.56</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">EUR</p>
                    </div>

                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item href="/edit" icon="pencil" wire:navigate>
                                {{ __('Edit') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item
                                wire:click="delete({{ $item->id }})"
                                wire:confirm="Are you sure?"
                                icon="trash"
                                variant="danger">
                                {{ __('Delete') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>
    </div>
</div>
```

### Empty State
```blade
<div class="p-12 text-center">
    <flux:icon.credit-card class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No items yet</h3>
    <p class="text-gray-500 dark:text-gray-400 mb-6">Get started by adding your first item</p>
    <flux:button href="/create" variant="primary" wire:navigate>
        <flux:icon.plus class="w-4 h-4 mr-2" />
        Add Your First Item
    </flux:button>
</div>
```

### Info Banner/Alert
```blade
<div class="bg-blue-50 dark:bg-blue-900/10 rounded-lg border border-blue-200 dark:border-blue-800/50 p-4 mb-6">
    <div class="flex items-start">
        <flux:icon.information-circle class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3" />
        <div class="flex-1">
            <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                {{ __('Info Title') }}
            </h3>
            <p class="text-sm text-blue-800 dark:text-blue-200">
                Info message content goes here.
            </p>
        </div>
    </div>
</div>
```

### Transaction Type Radio Buttons
```blade
<div class="grid grid-cols-2 gap-3">
    <label class="flex items-center p-4 border rounded-lg cursor-pointer {{ $type === 'income' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-gray-600' }}">
        <input type="radio" wire:model.live="type" value="income" class="sr-only">
        <div class="flex items-center space-x-3">
            <div class="p-2 rounded-full {{ $type === 'income' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-gray-100 dark:bg-gray-700' }}">
                <flux:icon.arrow-up class="w-5 h-5 {{ $type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}" />
            </div>
            <div>
                <p class="font-medium {{ $type === 'income' ? 'text-green-900 dark:text-green-100' : 'text-gray-900 dark:text-white' }}">Income</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Money coming in</p>
            </div>
        </div>
    </label>

    <label class="flex items-center p-4 border rounded-lg cursor-pointer {{ $type === 'expense' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600' }}">
        <input type="radio" wire:model.live="type" value="expense" class="sr-only">
        <div class="flex items-center space-x-3">
            <div class="p-2 rounded-full {{ $type === 'expense' ? 'bg-red-100 dark:bg-red-900/20' : 'bg-gray-100 dark:bg-gray-700' }}">
                <flux:icon.arrow-down class="w-5 h-5 {{ $type === 'expense' ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}" />
            </div>
            <div>
                <p class="font-medium {{ $type === 'expense' ? 'text-red-900 dark:text-red-100' : 'text-gray-900 dark:text-white' }}">Expense</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Money going out</p>
            </div>
        </div>
    </label>
</div>
```

---

## 📏 Spacing System

### Padding
- **Page Container**: `p-6`
- **Card Padding**: `p-6` (standard), `p-4` (compact)
- **Small Elements**: `p-3`, `p-2`
- **Icon Containers**: `p-3` (standard), `p-2` (small), `p-4` (large)

### Margin
- **Section Spacing**: `mb-6` (between major sections)
- **Element Spacing**: `mb-4`, `mb-3`, `mb-2`
- **Top Spacing for Text**: `mt-2`, `mt-3`, `mt-4`

### Gap (Flexbox/Grid)
- **Large Gap**: `gap-6` (grid layouts)
- **Medium Gap**: `gap-4` (form fields)
- **Small Gap**: `gap-3`, `gap-2`, `gap-1`
- **Space Between**: `space-x-4`, `space-x-3`, `space-x-2` (horizontal)
- **Vertical Space**: `space-y-6`, `space-y-4`, `space-y-3` (vertical stacks)

### Border Radius
- **Large Cards**: `rounded-lg`
- **Standard Elements**: `rounded-md`
- **Full Rounded**: `rounded-full` (icons, badges, tags)

---

## 🌓 Dark Mode Guidelines

### Dark Mode Pattern
Every color class must have a dark mode variant:

```blade
<!-- CORRECT ✅ -->
<div class="bg-white dark:bg-neutral-800">
<p class="text-gray-900 dark:text-white">
<div class="border-gray-200 dark:border-gray-700">

<!-- INCORRECT ❌ -->
<div class="bg-white">
<p class="text-gray-900">
```

### Common Dark Mode Pairs
| Light | Dark |
|-------|------|
| `bg-white` | `dark:bg-neutral-800` |
| `bg-gray-50` | `dark:bg-gray-700` |
| `text-gray-900` | `dark:text-white` |
| `text-gray-600` | `dark:text-gray-400` |
| `text-gray-500` | `dark:text-gray-400` |
| `border-neutral-200` | `dark:border-neutral-700` |
| `border-gray-200` | `dark:border-gray-700` |
| `border-gray-300` | `dark:border-gray-600` |
| `hover:bg-gray-50` | `dark:hover:bg-gray-700/50` |
| `divide-gray-200` | `dark:divide-gray-700` |

### Color Icon Backgrounds (Dark Mode)
Use `/20` opacity for dark mode colored backgrounds:
```blade
bg-blue-100 dark:bg-blue-900/20
bg-green-100 dark:bg-green-900/20
bg-red-100 dark:bg-red-900/20
```

---

## 📱 Responsive Design

### Breakpoints (Tailwind Defaults)
- `sm:` - 640px and up
- `md:` - 768px and up
- `lg:` - 1024px and up
- `xl:` - 1280px and up
- `2xl:` - 1536px and up

### Common Responsive Patterns

#### Mobile-First Grid
```blade
<!-- Stack on mobile, 2 cols on tablet, 3 cols on desktop -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
```

#### Responsive Form Fields
```blade
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
```

#### Hide on Mobile, Show on Desktop
```blade
<span class="hidden sm:inline-block">Desktop only text</span>
```

---

## ⚡ Flux Components Usage

### Buttons

#### Primary Action
```blade
<flux:button variant="primary" icon="plus" wire:navigate>
    {{ __('Create') }}
</flux:button>
```

#### Secondary/Ghost Action
```blade
<flux:button variant="ghost">
    {{ __('Cancel') }}
</flux:button>
```

#### Danger Action
```blade
<flux:button variant="danger" icon="trash">
    {{ __('Delete') }}
</flux:button>
```

#### Button with Link
```blade
<flux:button href="/path" variant="primary" wire:navigate>
    {{ __('Go to Page') }}
</flux:button>
```

### Input Fields

#### Standard Text Input
```blade
<flux:input
    wire:model="field"
    label="Field Label"
    placeholder="Placeholder text"
    required
/>
```

#### Number Input
```blade
<flux:input
    wire:model="amount"
    label="Amount"
    type="number"
    step="0.01"
    min="0.01"
    required
/>
```

#### Date Input
```blade
<flux:input wire:model="date" label="Date" type="date" required />
```

### Select Dropdown
```blade
<flux:select wire:model="category_id" label="Category" required>
    <option value="">Select Category</option>
    @foreach($categories as $category)
        <option value="{{ $category->id }}">{{ $category->name }}</option>
    @endforeach
</flux:select>
```

### Textarea
```blade
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
<textarea
    wire:model="notes"
    rows="3"
    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
    placeholder="Additional notes..."></textarea>
```

### Modal (Flux Pro)

#### Standard Modal
```blade
<flux:modal wire:model.self="showModal" class="min-w-[22rem]">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Modal Title') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Modal description') }}</flux:text>
        </div>

        <!-- Form fields -->

        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </form>
</flux:modal>
```

#### Flyout Modal (Side Panel)
```blade
<flux:modal wire:model.self="showModal" variant="flyout" class="w-full max-w-2xl">
    <!-- Same content as standard modal -->
</flux:modal>
```

### Dropdown Menu
```blade
<flux:dropdown>
    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

    <flux:menu>
        <flux:menu.item href="/edit" icon="pencil" wire:navigate>
            {{ __('Edit') }}
        </flux:menu.item>
        <flux:menu.item href="/view" icon="eye" wire:navigate>
            {{ __('View') }}
        </flux:menu.item>
        <flux:menu.separator />
        <flux:menu.item
            wire:click="delete({{ $id }})"
            wire:confirm="Are you sure?"
            icon="trash"
            variant="danger">
            {{ __('Delete') }}
        </flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

### Toast Notifications
```blade
<!-- Success Toast -->
\Flux\Flux::toast(
    heading: '✅ Success',
    text: __('Action completed successfully'),
    variant: 'success'
);

<!-- Error Toast -->
\Flux\Flux::toast(
    heading: '❌ Error',
    text: __('Something went wrong'),
    variant: 'danger'
);

<!-- Info Toast -->
\Flux\Flux::toast(
    heading: 'ℹ️ Info',
    text: __('Information message'),
    variant: 'info'
);
```

### Icons
```blade
<!-- Standard Icon -->
<flux:icon.banknotes class="w-6 h-6 text-blue-600 dark:text-blue-400" />

<!-- Small Icon -->
<flux:icon.plus class="w-4 h-4" />

<!-- Large Icon -->
<flux:icon.credit-card class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto" />
```

---

## 🎨 Common UI Patterns

### Form Structure
```blade
<div class="bg-white rounded-lg border border-neutral-200 p-6 dark:bg-neutral-800 dark:border-neutral-700">
    <form wire:submit="save" class="space-y-6">
        <!-- Form fields with space-y-6 -->

        <!-- Actions at bottom with border-t -->
        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
            <flux:button variant="ghost" href="/back" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</div>
```

### Preview Section (Forms)
```blade
<div class="border-t border-gray-200 dark:border-gray-700 pt-6">
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Preview</h3>

    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
        <!-- Preview content -->
    </div>
</div>
```

### Tag/Badge Display
```blade
<!-- Tag with Custom Color -->
<span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full"
      style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
    {{ $tag->name }}
</span>

<!-- Removable Tag -->
<span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full"
      style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
    {{ $tag->name }}
    <button type="button" wire:click="removeTag({{ $tag->id }})" class="ml-2 text-sm hover:text-red-500">×</button>
</span>
```

### Color Picker
```blade
<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
    <div class="flex items-center space-x-2">
        <!-- Native color input -->
        <input type="color" wire:model="color" class="w-10 h-10 rounded border border-gray-300 dark:border-gray-600">

        <!-- Preset colors -->
        <div class="flex space-x-1">
            @foreach(['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6'] as $color)
                <button type="button"
                        wire:click="$set('color', '{{ $color }}')"
                        class="w-6 h-6 rounded border-2 border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform"
                        style="background-color: {{ $color }}">
                </button>
            @endforeach
        </div>
    </div>
</div>
```

### File Upload (Drag & Drop)
```blade
<div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
    <div class="text-center">
        <flux:icon.camera class="w-8 h-8 text-gray-400 mx-auto mb-2" />
        <input
            type="file"
            wire:model="file"
            accept=".jpg,.jpeg,.png,.pdf"
            class="hidden"
            id="file-upload"
        />
        <label for="file-upload" class="cursor-pointer">
            <span class="text-sm text-gray-600 dark:text-gray-400">
                Click to upload or drag and drop
            </span>
            <br>
            <span class="text-xs text-gray-500 dark:text-gray-500">
                PNG, JPG, PDF up to 5MB
            </span>
        </label>
    </div>

    @if($file)
        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
            <div class="flex items-center space-x-3">
                <flux:icon.document class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $file->getClientOriginalName() }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ round($file->getSize() / 1024, 2) }} KB
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="$set('file', null)"
                    class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                >
                    <flux:icon.trash class="w-4 h-4" />
                </button>
            </div>
        </div>
    @endif
</div>
```

### Metadata Separator Pattern
```blade
<div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
    <span>Metadata 1</span>
    <span>•</span>
    <span>Metadata 2</span>
    <span>•</span>
    <span>Metadata 3</span>
</div>
```

### Status Indicator Cards
```blade
<div class="flex items-center p-3 rounded-lg {{ $status === 'ok' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
    @if($status === 'ok')
        <flux:icon.check-circle class="w-6 h-6 text-green-600 dark:text-green-400 mr-3" />
    @else
        <flux:icon.x-circle class="w-6 h-6 text-red-600 dark:text-red-400 mr-3" />
    @endif
    <div class="flex-1">
        <p class="font-medium {{ $status === 'ok' ? 'text-green-900 dark:text-green-100' : 'text-red-900 dark:text-red-100' }}">
            Service Name
        </p>
        <p class="text-sm {{ $status === 'ok' ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
            {{ $message }}
        </p>
    </div>
</div>
```

---

## ✅ Checklist for New Features

When implementing new features, ensure:

- [ ] **All colors** have dark mode variants (`dark:`)
- [ ] **Spacing** follows standard patterns (p-6, gap-6, mb-6)
- [ ] **Cards** use standard structure (rounded-lg, border, p-6)
- [ ] **Typography** matches existing hierarchy
- [ ] **Buttons** use Flux components with appropriate variants
- [ ] **Forms** use Flux input components
- [ ] **Icons** use Flux icon components with proper sizing
- [ ] **Responsive design** uses standard breakpoints (md:, lg:)
- [ ] **Semantic colors** (green=positive, red=negative, blue=info)
- [ ] **Hover states** implemented where appropriate
- [ ] **Empty states** provided for lists
- [ ] **Loading states** implemented with wire:loading
- [ ] **Toast notifications** for user feedback
- [ ] **Accessibility** considered (labels, aria-labels, focus states)

---

## 📚 References

- **Flux UI Documentation**: Use `search-docs` tool for component-specific docs
- **Tailwind CSS v4**: All utility classes
- **CLAUDE.md**: Project-wide coding standards
- **Session Files**: Check `docs/sessions/` for implementation examples

---

**Maintained by:** Claude Code AI Assistant
**Version:** 1.0
**Last Review:** 2025-09-30
