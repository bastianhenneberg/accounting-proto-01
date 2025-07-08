# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a comprehensive personal finance management application built with Laravel 12 and the TALL stack (Tailwind CSS, Alpine.js, Laravel, and Livewire). The application provides complete financial tracking capabilities including accounts, transactions, categories, budgets, goals, and recurring transactions. It follows PHP and Laravel best practices and uses Livewire Volt for reactive components.

## Key Technologies

- **Laravel 12** - Primary framework
- **Livewire** - For reactive components
- **Livewire Volt** - For single-file components
- **Livewire Flux** - UI component library
- **Tailwind CSS 4** - For styling
- **Vite** - Build tool
- **PestPHP** - Testing framework
- **SQLite** - Database (development)

## Development Commands

### Starting Development
```bash
# Start all services (server, queue, logs, vite)
composer dev

# Alternative individual commands
php artisan serve          # Start Laravel server
php artisan queue:listen   # Start queue worker
php artisan pail          # Start log viewer
npm run dev               # Start Vite dev server
```

### Building and Assets
```bash
npm run build             # Build for production
npm run dev               # Development build with watch
```

### Testing
```bash
composer test             # Run all tests
php artisan test          # Run tests directly
vendor/bin/pest           # Run Pest tests
```

### Code Quality
```bash
vendor/bin/pint           # Format code (Laravel Pint)
php artisan config:clear  # Clear config cache
```

### Database
```bash
php artisan migrate       # Run migrations
php artisan migrate:fresh # Fresh migration
php artisan migrate:refresh # Refresh migrations
```

## Architecture

### Livewire Components
- **Class-based Components**: Located in `app/Livewire/`
- **Volt Components**: Single-file components in `resources/views/livewire/`
- **Actions**: Reusable logic in `app/Livewire/Actions/`

### Volt Integration
- Volt components are mounted from `resources/views/livewire/` and `resources/views/pages/`
- Routes can be defined directly in Volt components using `Volt::route()`
- Example: `Volt::route('settings/profile', 'settings.profile')`

### Authentication
- Uses Laravel's built-in authentication
- Custom logout action in `app/Livewire/Actions/Logout.php`
- Email verification implemented
- Settings pages: profile, password, appearance

### Views and Components
- **Blade Components**: Reusable UI components in `resources/views/components/`
- **Flux Components**: UI library components (flux:input, flux:button, etc.)
- **Layouts**: Main layouts in `resources/views/components/layouts/`

### Testing Strategy
- **PestPHP** for testing framework
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`
- Uses SQLite in-memory database for testing
- Authentication tests included

## File Structure Patterns

### Livewire Volt Components
- Single-file components combining PHP logic and Blade templates
- PHP code at the top, Blade template at the bottom
- Example pattern in `resources/views/livewire/settings/profile.blade.php`

### Settings Architecture
- Centralized settings layout with nested components
- Settings routes grouped under `/settings/` prefix
- Each setting page is a separate Volt component

### Authentication Flow
- Auth routes in `routes/auth.php`
- Auth views in `resources/views/livewire/auth/`
- Middleware protection on authenticated routes

## Development Guidelines

### Code Style
- Follow PSR-12 coding standards
- Use PHP 8.2+ features
- Implement strict typing: `declare(strict_types=1);`
- Use Laravel's built-in features and helpers

### Livewire Best Practices
- Use Volt for single-file components
- Implement proper validation in component methods
- Use wire:model for form binding
- Dispatch events for component communication

### Testing
- Write feature tests for Livewire components
- Use Laravel's testing utilities
- Test authentication flows
- Test form validation

### UI/UX
- Use Flux components for consistency
- Implement proper error handling and user feedback
- Follow responsive design principles with Tailwind
- Use Alpine.js for client-side interactivity when needed

## Database Schema

The application includes the following core tables:
- **users** - User authentication and basic info
- **user_profiles** - Extended user profile information 
- **accounts** - Financial accounts (checking, savings, credit cards, etc.)
- **categories** - Income and expense categories with hierarchical support
- **transactions** - Individual financial transactions
- **transfers** - Money transfers between accounts
- **recurring_transactions** - Scheduled recurring transactions
- **budgets** - Budget tracking by category
- **goals** - Financial savings goals
- **tags** - Transaction tagging system
- **notifications** - User notifications
- **settings** - User preferences
- **activity_logs** - Audit trail
- **imports** - CSV/bank data import tracking

## Main Features Implemented

### Dashboard
- Financial overview with key metrics
- Recent transactions display
- Budget and goal progress tracking
- Monthly income/expense summaries

### Account Management
- Multiple account types (checking, savings, credit card, cash, investment)
- Multi-currency support
- Account balance tracking
- Account creation and management

### Transaction Management
- Income, expense, and transfer tracking
- Category assignment
- Tag support
- Advanced filtering and search
- Date range filtering
- Pagination support

### Category Management
- Hierarchical category structure
- Color-coded categories
- Icon support
- Separate income/expense categories
- Default category seeder included

### Navigation
- Organized sidebar with Finance and Planning sections
- Responsive design
- Dark/light mode support

## Seeders Available

- **DefaultCategoriesSeeder** - Creates comprehensive default income and expense categories for all users

## Important Notes

- Database uses SQLite by default
- Email verification is implemented
- Queue system is configured
- Logging uses Laravel Pail
- Asset compilation uses Vite with Tailwind CSS 4
- The project follows the cursor rules defined in `cursorrules` for TALL stack development
- All financial amounts use decimal(15,2) precision
- User data is properly scoped to authenticated users
- Soft deletes are not implemented but referential integrity is maintained