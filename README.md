# Personal Finance Management Application

A comprehensive personal finance management application built with Laravel 12 and the TALL stack (Tailwind CSS, Alpine.js, Laravel, and Livewire). The application provides complete financial tracking capabilities including accounts, transactions, categories, budgets, goals, recurring transactions, planned transactions, and investment portfolio management.

## Features

### 📊 Core Financial Management
- **Dashboard** - Complete financial overview with key metrics
- **Accounts** - Multi-currency account management (checking, savings, credit card, cash, investment)
- **Transactions** - Income, expense, and transfer tracking with categories and tags
- **Categories** - Hierarchical category structure with color coding
- **Budgets** - Budget tracking with projected spending analysis
- **Goals** - Financial savings goals with progress tracking

### 📅 Financial Planning
- **Recurring Transactions** - Automated recurring income and expenses
- **Planned Transactions** - Future one-time transactions with auto-conversion
- **Financial Calendar** - Unified timeline view of all financial activities
- **Budget Projections** - Future budget impact analysis with confirmed planned expenses

### 💼 Investment Portfolio Management
- **Investment Accounts** - Multi-asset portfolio tracking (Trade Republic style)
- **Holdings Management** - Support for cryptocurrencies, stocks, ETFs, bonds
- **Real-time Pricing** - Live price updates via CoinGecko API
- **P&L Tracking** - Unrealized gains/losses with performance analytics
- **Asset Management** - Database-driven asset support system

### 🌍 Multilingual Support
- **German (Deutsch)** and **English** interface
- **Language Switcher** - Settings → Language for easy switching
- **Session-based** - Language preference persists across sessions

### 🎨 Modern UI/UX
- **Flux UI Components** - Modern, responsive design
- **Collapsible Sidebar** - Desktop/mobile optimized navigation
- **Dark Mode Support** - System preference integration
- **Interactive Elements** - Modals, dropdowns, confirmations

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd accounting-proto-01
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed --class=SupportedAssetsSeeder
   ```

5. **Start development**
   ```bash
   composer dev  # Starts all services (server, queue, logs, vite)
   ```

## Investment Portfolio System

### Asset Management

The application includes a comprehensive asset management system that supports multiple asset types through a database-driven approach.

#### Supported Asset Types
- **Cryptocurrency** - Bitcoin, XRP, Ethereum, Cardano, Polygon, etc.
- **Stocks** - Apple, Tesla, Microsoft, Amazon, Google, Meta, etc.
- **ETFs** - MSCI World, S&P 500, NASDAQ 100, European indices, etc.
- **Bonds** - Government and corporate bonds (extensible)

#### Adding New Assets

**Admin Interface:** `/admin/assets`

To add new assets to the system:

1. Navigate to `/admin/assets` (Asset Management)
2. Click **"Add Asset"**
3. Fill in the asset details:
   - **Asset Type**: cryptocurrency, stock, etf, or bond
   - **Symbol**: Unique identifier (e.g., `BTC`, `AAPL`, `MSCI_WORLD`)
   - **Name**: Full asset name (e.g., `Bitcoin`, `Apple Inc.`, `MSCI World ETF`)
   - **API ID**: CoinGecko ID or Yahoo Finance symbol for price fetching
   - **ISIN**: International Securities Identification Number (for European assets)

#### API Integration

The system uses **CoinGecko API** for cryptocurrency prices and supports extensible price sources for other asset types.

**Price Fetching Logic:**
```php
// 1. System looks up asset in database
$asset = SupportedAsset::where('symbol', 'BTC')->first();

// 2. Uses API ID for price fetching
$apiId = $asset->api_id; // 'bitcoin'

// 3. Makes API call
GET https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=eur

// 4. Updates holding with current price
```

**Key Mapping Examples:**
- `BTC` (symbol) → `bitcoin` (CoinGecko ID)
- `XRP` (symbol) → `ripple` (CoinGecko ID)
- `ETH` (symbol) → `ethereum` (CoinGecko ID)

### Managing Investment Holdings

#### Creating Investment Accounts
1. Go to **Accounts** → **Add Account**
2. Select **"Investment Account (Multi-Asset)"**
3. Name it (e.g., "Trade Republic", "Interactive Brokers")

#### Adding Holdings to Investment Accounts
1. Navigate to **Accounts** → [Your Investment Account] → **"Manage Holdings"**
2. Click **"Add Holding"**
3. Select:
   - **Asset Type**: Choose from crypto, stock, ETF
   - **Asset**: Select from available assets (populated from database)
   - **Quantity**: Amount held (supports up to 8 decimal places for crypto)
   - **Average Cost**: Your average purchase price in EUR

#### Example Trade Republic Portfolio Setup
```
Trade Republic Account
├─ Bitcoin      0.025 BTC      €1,247.50  📈 +2.3%
├─ XRP          1,500 XRP      €3,855.00  📉 -1.2%
├─ Apple Inc.   12 Shares      €2,145.60  📈 +1.8%
├─ MSCI World   50 Shares      €4,567.80  📈 +1.1%
└─ Total Portfolio Value:      €11,815.90
```

### Price Updates

**Automatic Updates:** Prices are cached for 15 minutes and updated automatically when accessed.

**Manual Updates:** Use the **"Update Prices"** button in the holdings management interface.

**API Rate Limits:** The system respects CoinGecko's free tier limits (50 calls/minute) with intelligent caching.

## Architecture

### Technology Stack
- **Laravel 12** - Primary framework with streamlined structure
- **Livewire 3** - Reactive components and real-time updates
- **Livewire Volt** - Single-file components for rapid development
- **Flux UI Pro** - Modern component library with collapsible sidebar
- **Tailwind CSS 4** - Utility-first styling with dark mode
- **SQLite** - Database for development (easily switchable to MySQL/PostgreSQL)

### Key Models
- **User** - Authentication and relationship management
- **Account** - Financial accounts with crypto/investment support
- **Transaction** - All financial movements with automatic balance updates
- **Holding** - Investment portfolio positions with P&L tracking
- **PlannedTransaction** - Future transaction planning with auto-conversion
- **RecurringTransaction** - Automated recurring payments/income
- **Budget** - Expense tracking with projected analysis
- **Goal** - Savings goals with progress monitoring
- **SupportedAsset** - Database-driven asset management

### Services
- **FinancialCalendarService** - Unified calendar data aggregation
- **CryptoPriceService** - Cryptocurrency price fetching via CoinGecko
- **AssetPriceService** - Universal asset price management

## Database Schema Highlights

### Holdings Table
```sql
holdings:
- id, account_id (FK)
- asset_type: ENUM('crypto', 'stock', 'etf', 'bond', 'commodity')
- symbol: VARCHAR(20) - e.g., 'BTC', 'AAPL', 'MSCI_WORLD'
- name: VARCHAR(100) - Full asset name
- quantity: DECIMAL(20,8) - Amount held
- average_cost: DECIMAL(15,8) - Average purchase price
- current_price: DECIMAL(15,8) - Live market price
- market_value: DECIMAL(15,2) - Current total value
- total_invested: DECIMAL(15,2) - Total EUR invested
- unrealized_pnl: DECIMAL(15,2) - Profit/Loss
```

### Supported Assets Table
```sql
supported_assets:
- id, asset_type, symbol, name
- api_id: VARCHAR(50) - CoinGecko ID, Yahoo symbol, etc.
- currency: VARCHAR(3) - Default EUR
- is_active: BOOLEAN - Enable/disable assets
- metadata: JSON - ISIN, WKN, additional info
```

## API Configuration

### CoinGecko Integration
- **Endpoint**: `https://api.coingecko.com/api/v3/simple/price`
- **Rate Limit**: 50 calls/minute (free tier)
- **Cache Duration**: 15 minutes per asset
- **Supported Currencies**: EUR primary, USD secondary

### Adding New Crypto Assets
1. Go to `/admin/assets`
2. Add asset with correct CoinGecko ID
3. Find CoinGecko IDs at: https://api.coingecko.com/api/v3/coins/list

## Development Commands

### Asset Management
```bash
# Seed initial assets
php artisan db:seed --class=SupportedAssetsSeeder

# Clear price cache
php artisan cache:clear

# Update all crypto prices manually
# (Use Update Prices button in holdings interface)
```

### Financial Calendar
```bash
# Process due planned transactions
php artisan planned:process-due

# Dry run (see what would be processed)
php artisan planned:process-due --dry-run
```

### Code Quality
```bash
# Format code
vendor/bin/pint

# Run tests
composer test

# Clear caches
php artisan config:clear
php artisan view:clear
```

## Navigation Structure

```
Finance
├── Dashboard
├── Accounts
│   ├── Traditional Accounts (checking, savings, etc.)
│   └── Investment Accounts
│       └── Holdings Management (/accounts/{id}/holdings)
├── Transactions
├── Categories
└── Budgets

Planning
├── Goals
├── Recurring
├── Planned
└── Calendar (Financial Calendar)

Settings
├── Profile
├── Password
├── Appearance
└── Language

Admin
└── Assets (/admin/assets) - Asset Management Interface
```

## Investment Account Workflow

### 1. Create Investment Account
- **Type**: Investment Account (Multi-Asset)
- **Name**: Trade Republic, Interactive Brokers, etc.

### 2. Add Holdings
- Navigate to account → "Manage Holdings"
- Add each cryptocurrency, stock, or ETF position
- System automatically fetches current prices

### 3. Portfolio Tracking
- Real-time portfolio valuation
- P&L tracking with percentage gains/losses
- Integration with financial planning features

## Contributing

### Adding New Asset Types
1. Extend `asset_type` enum in migrations
2. Add price fetching logic to `AssetPriceService`
3. Update UI components for new asset type display
4. Add translations for new asset types

### Adding New Price Sources
1. Extend `AssetPriceService` with new API integration
2. Add API configuration to environment variables
3. Implement fallback mechanisms for API failures
4. Update asset metadata structure as needed

## License

This project is for personal use and portfolio demonstration.