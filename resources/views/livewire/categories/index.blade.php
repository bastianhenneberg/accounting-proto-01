<?php

use App\Models\Category;
use Livewire\Volt\Component;

new class extends Component {
    public $categories = [];
    public $name = '';
    public $type = 'expense';
    public $color = '#3B82F6';
    public $icon = '';
    public $parent_id = null;
    public $showCreateForm = false;

    public function mount(): void
    {
        $this->loadCategories();
    }

    public function loadCategories(): void
    {
        $this->categories = auth()->user()->categories()
            ->with(['parent', 'children'])
            ->whereNull('parent_id')
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function createCategory(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:income,expense'],
            'color' => ['required', 'string', 'size:7'],
            'icon' => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]);

        auth()->user()->categories()->create([
            ...$validated,
            'parent_id' => $validated['parent_id'] ?: null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->reset(['name', 'icon']);
        $this->parent_id = null;
        $this->showCreateForm = false;
        $this->loadCategories();
        
        session()->flash('success', 'Category created successfully.');
    }

    public function deleteCategory($categoryId): void
    {
        $category = auth()->user()->categories()->findOrFail($categoryId);
        
        if ($category->transactions()->count() > 0) {
            session()->flash('error', 'Cannot delete category with existing transactions.');
            return;
        }
        
        if ($category->children()->count() > 0) {
            session()->flash('error', 'Cannot delete category with subcategories.');
            return;
        }
        
        $category->delete();
        $this->loadCategories();
        session()->flash('success', 'Category deleted successfully.');
    }

    public function getParentCategories(): array
    {
        return auth()->user()->categories()
            ->whereNull('parent_id')
            ->where('type', $this->type)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function getDefaultColors(): array
    {
        return [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
            '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1'
        ];
    }
}; ?>

<div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Categories</h1>
                <p class="text-gray-600 dark:text-gray-400">Organize your income and expenses</p>
            </div>
            <flux:button wire:click="$toggle('showCreateForm')" variant="primary">
                <flux:icon.plus class="w-4 h-4 mr-2" />
                Add Category
            </flux:button>
        </div>

        {{-- Create Form --}}
        @if($showCreateForm)
            <div class="bg-white rounded-lg border border-neutral-200 p-6 mb-6 dark:bg-neutral-800 dark:border-neutral-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Create New Category</h3>
                
                <form wire:submit="createCategory" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <flux:input wire:model="name" label="Category Name" placeholder="e.g., Groceries, Salary" required />
                        </div>
                        <div>
                            <flux:select wire:model="type" label="Type" required>
                                <option value="expense">Expense</option>
                                <option value="income">Income</option>
                            </flux:select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <flux:input wire:model="icon" label="Icon (optional)" placeholder="e.g., shopping-cart, briefcase" />
                        </div>
                        <div>
                            <flux:select wire:model="parent_id" label="Parent Category (optional)">
                                <option value="">None - Top Level Category</option>
                                @foreach($this->getParentCategories() as $category)
                                    <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
                        <div class="flex items-center space-x-2">
                            <input type="color" wire:model="color" class="w-10 h-10 rounded border border-gray-300 dark:border-gray-600">
                            <div class="flex space-x-1">
                                @foreach($this->getDefaultColors() as $defaultColor)
                                    <button type="button" 
                                            wire:click="$set('color', '{{ $defaultColor }}')"
                                            class="w-6 h-6 rounded border-2 border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform"
                                            style="background-color: {{ $defaultColor }}">
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <flux:button wire:click="$set('showCreateForm', false)" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            Create Category
                        </flux:button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Categories List --}}
        <div class="space-y-6">
            {{-- Income Categories --}}
            <div class="bg-white rounded-lg border border-neutral-200 dark:bg-neutral-800 dark:border-neutral-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-green-600 dark:text-green-400 flex items-center">
                        <flux:icon.arrow-up class="w-5 h-5 mr-2" />
                        Income Categories
                    </h2>
                </div>
                
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($categories->where('type', 'income') as $category)
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-4 h-4 rounded-full" style="background-color: {{ $category->color }}"></div>
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-white">{{ $category->name }}</h3>
                                        @if($category->children->count() > 0)
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $category->children->count() }} subcategories
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $category->transactions()->count() }} transactions
                                    </span>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                        
                                        <flux:menu>
                                            <flux:menu.item 
                                                wire:click="deleteCategory({{ $category->id }})"
                                                wire:confirm="Are you sure you want to delete this category?"
                                                icon="trash" 
                                                variant="danger">
                                                Delete
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>
                            
                            {{-- Subcategories --}}
                            @if($category->children->count() > 0)
                                <div class="mt-3 ml-7 space-y-2">
                                    @foreach($category->children as $child)
                                        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $child->color }}"></div>
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $child->name }}</span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $child->transactions()->count() }} transactions
                                                </span>
                                                <flux:button 
                                                    wire:click="deleteCategory({{ $child->id }})"
                                                    wire:confirm="Are you sure you want to delete this subcategory?"
                                                    variant="ghost" 
                                                    size="sm"
                                                    icon="trash"
                                                    class="text-red-600 hover:text-red-700">
                                                </flux:button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <p class="text-gray-500 dark:text-gray-400">No income categories yet</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Expense Categories --}}
            <div class="bg-white rounded-lg border border-neutral-200 dark:bg-neutral-800 dark:border-neutral-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 flex items-center">
                        <flux:icon.arrow-down class="w-5 h-5 mr-2" />
                        Expense Categories
                    </h2>
                </div>
                
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($categories->where('type', 'expense') as $category)
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-4 h-4 rounded-full" style="background-color: {{ $category->color }}"></div>
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-white">{{ $category->name }}</h3>
                                        @if($category->children->count() > 0)
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $category->children->count() }} subcategories
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $category->transactions()->count() }} transactions
                                    </span>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                        
                                        <flux:menu>
                                            <flux:menu.item 
                                                wire:click="deleteCategory({{ $category->id }})"
                                                wire:confirm="Are you sure you want to delete this category?"
                                                icon="trash" 
                                                variant="danger">
                                                Delete
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>
                            
                            {{-- Subcategories --}}
                            @if($category->children->count() > 0)
                                <div class="mt-3 ml-7 space-y-2">
                                    @foreach($category->children as $child)
                                        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $child->color }}"></div>
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $child->name }}</span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $child->transactions()->count() }} transactions
                                                </span>
                                                <flux:button 
                                                    wire:click="deleteCategory({{ $child->id }})"
                                                    wire:confirm="Are you sure you want to delete this subcategory?"
                                                    variant="ghost" 
                                                    size="sm"
                                                    icon="trash"
                                                    class="text-red-600 hover:text-red-700">
                                                </flux:button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <p class="text-gray-500 dark:text-gray-400">No expense categories yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    @if(session('success'))
        <flux:toast variant="success">{{ session('success') }}</flux:toast>
    @endif

    @if(session('error'))
        <flux:toast variant="danger">{{ session('error') }}</flux:toast>
    @endif
</div>