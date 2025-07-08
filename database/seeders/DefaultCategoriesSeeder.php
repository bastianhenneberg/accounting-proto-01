<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();

        $defaultCategories = [
            // Income Categories
            [
                'name' => 'Salary',
                'type' => 'income',
                'color' => '#10B981',
                'icon' => 'briefcase',
                'children' => []
            ],
            [
                'name' => 'Freelancing',
                'type' => 'income',
                'color' => '#3B82F6',
                'icon' => 'computer-desktop',
                'children' => []
            ],
            [
                'name' => 'Investments',
                'type' => 'income',
                'color' => '#8B5CF6',
                'icon' => 'chart-bar',
                'children' => [
                    'Dividends',
                    'Interest',
                    'Capital Gains'
                ]
            ],
            [
                'name' => 'Other Income',
                'type' => 'income',
                'color' => '#06B6D4',
                'icon' => 'banknotes',
                'children' => []
            ],

            // Expense Categories
            [
                'name' => 'Housing',
                'type' => 'expense',
                'color' => '#EF4444',
                'icon' => 'home',
                'children' => [
                    'Rent/Mortgage',
                    'Utilities',
                    'Insurance',
                    'Maintenance'
                ]
            ],
            [
                'name' => 'Food & Dining',
                'type' => 'expense',
                'color' => '#F59E0B',
                'icon' => 'shopping-cart',
                'children' => [
                    'Groceries',
                    'Restaurants',
                    'Coffee & Snacks'
                ]
            ],
            [
                'name' => 'Transportation',
                'type' => 'expense',
                'color' => '#6366F1',
                'icon' => 'truck',
                'children' => [
                    'Public Transport',
                    'Gas',
                    'Car Maintenance',
                    'Parking'
                ]
            ],
            [
                'name' => 'Entertainment',
                'type' => 'expense',
                'color' => '#EC4899',
                'icon' => 'film',
                'children' => [
                    'Movies',
                    'Games',
                    'Subscriptions',
                    'Hobbies'
                ]
            ],
            [
                'name' => 'Healthcare',
                'type' => 'expense',
                'color' => '#F97316',
                'icon' => 'heart',
                'children' => [
                    'Doctor Visits',
                    'Medications',
                    'Insurance'
                ]
            ],
            [
                'name' => 'Shopping',
                'type' => 'expense',
                'color' => '#84CC16',
                'icon' => 'shopping-bag',
                'children' => [
                    'Clothing',
                    'Electronics',
                    'Books',
                    'Personal Care'
                ]
            ],
            [
                'name' => 'Education',
                'type' => 'expense',
                'color' => '#0EA5E9',
                'icon' => 'academic-cap',
                'children' => [
                    'Tuition',
                    'Books',
                    'Courses'
                ]
            ],
            [
                'name' => 'Other Expenses',
                'type' => 'expense',
                'color' => '#6B7280',
                'icon' => 'ellipsis-horizontal',
                'children' => []
            ],
        ];

        foreach ($users as $user) {
            foreach ($defaultCategories as $categoryData) {
                $category = $user->categories()->create([
                    'name' => $categoryData['name'],
                    'type' => $categoryData['type'],
                    'color' => $categoryData['color'],
                    'icon' => $categoryData['icon'],
                    'is_active' => true,
                    'sort_order' => 0,
                ]);

                // Create subcategories if they exist
                if (!empty($categoryData['children'])) {
                    foreach ($categoryData['children'] as $childName) {
                        $user->categories()->create([
                            'name' => $childName,
                            'type' => $categoryData['type'],
                            'color' => $categoryData['color'],
                            'icon' => null,
                            'parent_id' => $category->id,
                            'is_active' => true,
                            'sort_order' => 0,
                        ]);
                    }
                }
            }
        }
    }
}
