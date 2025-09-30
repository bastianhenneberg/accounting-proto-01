# TODO - Pending Tasks & Future Enhancements

**Last Updated:** 2025-09-30

## 🚀 High Priority

### Localization & Formatting
- [ ] **Number Format Settings (Germany)**
  - Add number format preference to settings (German: 1.234,56 vs. US: 1,234.56)
  - Implement locale-aware number formatting throughout app
  - Update all `number_format()` calls to use user preference
  - Apply to: currency display, amounts, statistics, charts
  - **Complexity:** Medium-High
  - **Estimated Time:** 3-5 hours
  - **Notes:** Affects many views and components, needs comprehensive testing

- [ ] **Default Currency Integration**
  - Currently only stored in settings but not used
  - Use default_currency when creating new accounts
  - Apply to dashboard/reports (convert all to default currency?)
  - Consider: Multi-currency support vs. single default
  - Display currency symbol based on user preference (€ vs. $ etc.)
  - **Complexity:** Medium
  - **Estimated Time:** 2-3 hours
  - **Notes:** Related to number format task, should be done together

### Asset Management
- [ ] **Automatic Daily Price Updates**
  - Implement scheduled job to update all asset prices daily
  - Use Laravel scheduler (`schedule:work`)
  - Add configuration for update time (e.g., 8:00 AM)
  - Send notification on completion/errors
  - **Complexity:** Medium
  - **Estimated Time:** 2-3 hours

- [ ] **Asset Price History**
  - Create `asset_price_history` table
  - Store daily price snapshots
  - Add charts to visualize price trends
  - Enable historical performance analysis
  - **Complexity:** High
  - **Estimated Time:** 4-6 hours

### Holdings & Portfolio
- [ ] **Performance Dashboard Improvements**
  - Add portfolio performance charts (day, week, month, year)
  - Show total return (absolute and percentage)
  - Add benchmark comparison (e.g., vs S&P 500)
  - **Complexity:** Medium
  - **Estimated Time:** 3-4 hours

### Testing
- [ ] **Add Pest Tests for Asset Management**
  - Test manual price setting
  - Test AI price extraction (mocked)
  - Test service status checks
  - **Complexity:** Medium
  - **Estimated Time:** 2-3 hours

## 📊 Medium Priority

### Asset Management
- [ ] **Batch Asset Import**
  - CSV import for multiple assets at once
  - Excel file support
  - Template download
  - Validation and error reporting
  - **Complexity:** Medium
  - **Estimated Time:** 3-4 hours

- [ ] **Asset Search & Autocomplete**
  - Add asset search when creating holdings
  - Autocomplete from supported assets
  - Quick add button for popular assets
  - **Complexity:** Low
  - **Estimated Time:** 1-2 hours

### Notifications
- [ ] **Price Alert System**
  - Set price targets for assets
  - Email/notification when target reached
  - Support for both high and low targets
  - **Complexity:** Medium
  - **Estimated Time:** 3-4 hours

### Reports
- [ ] **Export Functionality**
  - Export portfolio to PDF
  - Export transactions to Excel
  - Tax report generation (Germany-specific)
  - **Complexity:** High
  - **Estimated Time:** 5-7 hours

## 🔮 Low Priority / Nice to Have

### UI/UX
- [ ] **Asset Logos/Icons**
  - Fetch company logos from API
  - Display in asset list
  - Cache locally
  - **Complexity:** Low
  - **Estimated Time:** 2 hours

- [ ] **Dark/Light Theme Toggle in Header**
  - Add theme switcher to navigation
  - Save preference
  - Smooth transitions
  - **Complexity:** Low
  - **Estimated Time:** 1 hour

### Integrations
- [ ] **Broker Integration**
  - Import transactions from broker APIs
  - Support for popular German brokers (Trade Republic, Scalable, etc.)
  - OAuth authentication
  - **Complexity:** Very High
  - **Estimated Time:** 15-20 hours

- [ ] **More Price Data Sources**
  - Morningstar API
  - Bloomberg Terminal (if available)
  - ECB currency rates
  - **Complexity:** Medium
  - **Estimated Time:** 3-4 hours per source

### Analytics
- [ ] **Advanced Portfolio Analytics**
  - Asset allocation visualization
  - Risk metrics (volatility, Sharpe ratio)
  - Correlation matrix
  - **Complexity:** High
  - **Estimated Time:** 8-10 hours

## 🐛 Known Issues to Fix

- [ ] **Yahoo Finance Extraction**
  - Currently not working reliably
  - Consider different approach or abandon
  - **Priority:** Low (workarounds exist)

- [ ] **Google Finance Cookie Consent**
  - Blocks screenshot
  - Need to handle cookie consent programmatically
  - **Priority:** Low (use alternatives)

## 🔧 Technical Debt

- [ ] **Extract Puppeteer Config**
  - Move delays/timeouts to config file
  - Site-specific settings in database
  - **Complexity:** Low
  - **Estimated Time:** 1 hour

- [ ] **Cache AI Responses**
  - Cache successful extractions for 15 minutes
  - Reduce API calls
  - **Complexity:** Low
  - **Estimated Time:** 1 hour

- [ ] **Queue Long-Running Tasks**
  - Move price updates to queue
  - Better progress feedback
  - Prevent timeouts
  - **Complexity:** Medium
  - **Estimated Time:** 2-3 hours

## 📚 Documentation Needed

- [ ] **User Guide for Asset Management**
  - How to add assets
  - Best URLs for different asset types
  - Troubleshooting guide
  - **Complexity:** Low
  - **Estimated Time:** 2 hours

- [ ] **API Documentation**
  - Document all endpoints
  - Add Postman collection
  - **Complexity:** Medium
  - **Estimated Time:** 3-4 hours

## 🎯 Feature Requests

*Add user-requested features here*

---

## Notes

- Tasks are categorized by priority
- Complexity ratings: Low (< 2h), Medium (2-5h), High (> 5h), Very High (> 10h)
- Estimated times are for experienced developers
- Always create a session document when working on these tasks
- Update this file when tasks are completed (move to appropriate session doc)

## How to Use This File

1. **Starting a new task:** Pick from High Priority first
2. **Document progress:** Create a session file in `docs/sessions/YYYY-MM-DD-feature-name.md`
3. **Mark complete:** Move task details to session file, check off here
4. **Add new tasks:** Add to appropriate priority section with complexity and time estimate
