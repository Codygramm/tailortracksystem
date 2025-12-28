# TailorTrack Reports Module - Complete Documentation

## Overview
The Reports Module provides comprehensive analytics and reporting with **three report types**: Orders Summary, Staff Performance, and Financial Report. Clean, simple, and fully functional.

---

## 📊 Three Report Types

### 1. **Orders Summary Report**
Complete overview of orders with pie charts and detailed tables.

**Features:**
- 4 Summary Cards (Total Orders, Revenue, Completed, Avg Order Value)
- Orders by Type Pie Chart (Baju Melayu, Baju Kurung, etc.)
- Orders by Status Pie Chart (Pending, Assigned, In Progress, Completed, Paid, Cancel)
- Detailed data tables with totals

**Use Case:** Track order distribution and completion rates

---

### 2. **Staff Performance Report**
Analyze cashier and tailor productivity.

**Features:**
- Staff Performance Table showing:
  - Orders Created (by cashiers)
  - Orders Assigned (to tailors)
  - Orders Completed (by tailors)
  - Revenue Generated
  - Performance Percentage (completion rate)
- Bar Chart: Orders by Staff (Created vs Assigned)
- Bar Chart: Revenue by Staff

**Performance Metrics:**
- **Cashiers**: 100% for orders created
- **Tailors**: (Completed / Assigned) × 100%
- **Color Coding**:
  - Green (≥80%): Excellent
  - Yellow (60-79%): Good
  - Red (<60%): Needs improvement

**Use Case:** Monitor staff productivity and identify top performers

---

### 3. **Financial Report**
Track revenue, payments, and outstanding amounts over time.

**Features:**
- 4 Financial Cards (Total Revenue, Paid Amount, Pending Amount, Total Orders)
- Line Chart: Revenue Trend showing:
  - Daily Revenue (blue)
  - Paid Amount (green)
  - Pending Amount (yellow)
- Daily Financial Table with:
  - Date
  - Order count
  - Daily revenue
  - Paid amount
  - Pending amount
  - Total row

**Use Case:** Monitor cash flow and identify payment collection issues

---

## File Structure

```
testfyp/
├── admin/
│   ├── reports.php                ← Main reports page (1050 lines)
│   └── export_report_excel.php    ← Excel export handler (NEW!)
├── css/
│   └── admin.css                  ← Styling (print-optimized)
└── js/
    └── admin.js                   ← Utilities
```

---

## How to Use

### 1. **Access Reports**
- Login as **Admin**
- Click **Reports** in sidebar
- Default: Orders Summary for current month

### 2. **Generate Report**
1. Select **Report Type** dropdown:
   - Orders Summary
   - Staff Performance
   - Financial Report
2. Select **From Date**
3. Select **To Date**
4. Click **Generate Report**

### 3. **Export to Excel**
- Click **Export Excel** button (top-right, green button)
- Downloads CSV file (opens in Excel/Google Sheets)
- Includes all report data with proper formatting
- Filename format: `TailorTrack_Report_[type]_[date-time].csv`
- **Excel Format Includes**:
  - Report header with period and generated info
  - Summary statistics
  - All data tables with totals
  - Properly formatted currency (RM)

### 4. **Print Report**
- Click **Print Report** button (top-right, blue button)
- Professional layout with company logo
- Filters hidden in print view

---

## Code Structure

### PHP Backend (Lines 1-139)

```php
// Report Type Selection
$report_type = $_GET['report_type'] ?? 'orders_summary';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// 1. ORDERS SUMMARY
if ($report_type === 'orders_summary') {
    // Summary stats, orders by type, orders by status
}

// 2. STAFF PERFORMANCE
elseif ($report_type === 'staff_performance') {
    // Staff data with orders created/assigned/completed
}

// 3. FINANCIAL REPORT
elseif ($report_type === 'financial') {
    // Daily revenue, paid, and pending amounts
}
```

### Helper Functions

```php
// Format order types (set_baju_melayu → Set Baju Melayu)
function formatOrderType($type)

// Get status badge colors (pending=gray, completed=green, etc.)
function getStatusColor($status)

// Get role badge colors (cashier=info, tailor=warning)
function getRoleColor($role)
```

### JavaScript Charts (Lines 787-1030)

**Three Chart Initialization Functions:**

```javascript
// 1. Orders Summary Charts (Pie)
function initOrdersCharts() {
    // orderTypeChart - pie chart
    // orderStatusChart - pie chart
}

// 2. Staff Performance Charts (Bar)
function initStaffCharts() {
    // staffOrdersChart - bar chart (Created vs Assigned)
    // staffRevenueChart - bar chart (Revenue by staff)
}

// 3. Financial Chart (Line)
function initFinancialChart() {
    // financialChart - line chart (Revenue, Paid, Pending)
}
```

---

## Database Queries

### Orders Summary Queries

```sql
-- Summary statistics
SELECT COUNT(*) as total_orders,
       SUM(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
       SUM(total_amount) as total_revenue,
       AVG(total_amount) as avg_order_value
FROM orders
WHERE DATE(created_at) BETWEEN ? AND ?

-- Orders by type
SELECT order_type, COUNT(*) as count, SUM(total_amount) as revenue
FROM orders
WHERE DATE(created_at) BETWEEN ? AND ?
GROUP BY order_type

-- Orders by status
SELECT status, COUNT(*) as count
FROM orders
WHERE DATE(created_at) BETWEEN ? AND ?
GROUP BY status
```

### Staff Performance Query

```sql
SELECT u.user_id, u.full_name, u.role,
       COUNT(CASE WHEN u.role = 'cashier' THEN o.order_id END) as orders_created,
       COUNT(CASE WHEN u.role = 'tailor' AND o.assigned_tailor = u.user_id THEN o.order_id END) as orders_assigned,
       COUNT(CASE WHEN u.role = 'tailor' AND o.assigned_tailor = u.user_id AND o.status = 'completed' THEN 1 END) as orders_completed,
       SUM(CASE WHEN u.role = 'cashier' THEN o.total_amount ELSE 0 END) as revenue_generated
FROM users u
LEFT JOIN orders o ON (
    (u.role = 'cashier' AND o.created_by = u.user_id) OR
    (u.role = 'tailor' AND o.assigned_tailor = u.user_id)
) AND DATE(o.created_at) BETWEEN ? AND ?
WHERE u.role IN ('cashier', 'tailor')
GROUP BY u.user_id
```

### Financial Report Query

```sql
SELECT DATE(created_at) as order_date,
       COUNT(*) as order_count,
       SUM(total_amount) as daily_revenue,
       SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
       SUM(CASE WHEN status != 'paid' AND status != 'cancel' THEN total_amount ELSE 0 END) as pending_amount
FROM orders
WHERE DATE(created_at) BETWEEN ? AND ?
GROUP BY DATE(created_at)
ORDER BY order_date
```

---

## Chart Colors

### Orders Summary
- **Order Types**: Rainbow palette (Pink, Blue, Yellow, Teal, Purple, Orange)
- **Statuses**:
  - Pending: #6c757d (Gray)
  - Assigned: #0dcaf0 (Cyan)
  - In Progress: #ffc107 (Yellow)
  - Completed: #198754 (Green)
  - Paid: #0d6efd (Blue)
  - Cancel: #dc3545 (Red)

### Staff Performance
- **Orders Created**: #0d6efd (Blue)
- **Orders Assigned**: #ffc107 (Yellow)
- **Revenue**: #198754 (Green)

### Financial Report
- **Daily Revenue**: #0d6efd (Blue)
- **Paid Amount**: #198754 (Green)
- **Pending Amount**: #ffc107 (Yellow)

---

## Print Layout

### What Prints:
✅ Company logo and header
✅ Report title and period
✅ All charts and tables
✅ Report information footer
✅ Color-preserved badges

### What's Hidden:
❌ Navigation bar
❌ Sidebar
❌ Report type selector
❌ Date filters
❌ Generate/Print buttons

### CSS Print Media Query (admin.css)

```css
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    .card { page-break-inside: avoid; }
    * { print-color-adjust: exact !important; }
}
```

---

## Features Summary

### ✅ Orders Summary
- Pie charts for Type & Status
- Summary statistics cards
- Detailed data tables

### ✅ Staff Performance
- Performance metrics table
- Bar charts for orders and revenue
- Color-coded performance badges

### ✅ Financial Report
- Revenue trend line chart
- Daily financial breakdown
- Total revenue, paid, and pending

### ✅ General Features
- Date range filtering
- **Excel Export (CSV format)** - Download reports for offline analysis
- Professional print layout
- Responsive design
- Empty state handling
- Real-time chart generation

---

## Excel Export Feature

### What Gets Exported:

#### Orders Summary Excel:
```
TAILORTRACK - ORDERS SUMMARY REPORT
Period: 01 Dec 2024 to 31 Dec 2024
Generated: 23 Dec 2024, 3:45 PM
Generated By: Admin Name

SUMMARY STATISTICS
Total Orders, 150
Total Revenue, RM 45,000.00
Average Order Value, RM 300.00
Completed Orders, 120
Paid Orders, 100
Cancelled Orders, 5

ORDERS BY TYPE
Order Type, Count, Revenue
Set Baju Melayu, 50, RM 15,000.00
Set Baju Kurung, 40, RM 12,000.00
...
TOTAL, 150, RM 45,000.00

ORDERS BY STATUS
Status, Count, Percentage
Completed, 120, 80.0%
Paid, 100, 66.7%
...
TOTAL, 150, 100.0%
```

#### Staff Performance Excel:
```
TAILORTRACK - STAFF PERFORMANCE REPORT
Period: 01 Dec 2024 to 31 Dec 2024
...

STAFF PERFORMANCE OVERVIEW
Staff Name, Role, Orders Created, Orders Assigned, Orders Completed, Revenue Generated, Performance %
Ali Bin Hassan, Tailor, -, 25, 20, RM 7,500.00, 80.0%
Mariam Abdullah, Tailor, -, 30, 28, RM 8,400.00, 93.3%
Ahmad Ibrahim, Cashier, 150, -, -, RM 45,000.00, 100.0%
```

#### Financial Report Excel:
```
TAILORTRACK - FINANCIAL REPORT
Period: 01 Dec 2024 to 31 Dec 2024
...

FINANCIAL SUMMARY
Total Revenue, RM 45,000.00
Paid Amount, RM 30,000.00
Pending Amount, RM 12,000.00
Total Orders, 150

DAILY FINANCIAL DETAILS
Date, Orders, Daily Revenue, Paid Amount, Pending Amount
01 Dec 2024, 5, RM 1,500.00, RM 1,000.00, RM 400.00
02 Dec 2024, 8, RM 2,400.00, RM 1,600.00, RM 700.00
...
TOTAL, 150, RM 45,000.00, RM 30,000.00, RM 12,000.00
```

### File Format:
- **Format**: CSV (Comma-Separated Values)
- **Encoding**: UTF-8 with BOM (for Excel compatibility)
- **Opens In**: Microsoft Excel, Google Sheets, LibreOffice Calc
- **Advantages**:
  - ✅ No library dependencies
  - ✅ Works on all PHP versions
  - ✅ Small file size
  - ✅ Easy to manipulate in Excel
  - ✅ Can import into databases

### How It Works:

```javascript
// JavaScript (reports.php)
function exportToExcel() {
    const reportType = 'orders_summary';
    const dateFrom = '2024-12-01';
    const dateTo = '2024-12-31';

    window.location.href = `export_report_excel.php?report_type=${reportType}&date_from=${dateFrom}&date_to=${dateTo}`;
}
```

```php
// PHP (export_report_excel.php)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="TailorTrack_Report_orders_summary_2024-12-23_154530.csv"');

// Add UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV data
fputcsv($output, ['TAILORTRACK - ORDERS SUMMARY REPORT']);
fputcsv($output, ['Total Orders', 150]);
...
```

---

## Usage Examples

### Example 1: Monthly Orders Review
```
Report Type: Orders Summary
From: 2024-12-01
To: 2024-12-31
Result: See total orders, revenue, and breakdown by type/status
```

### Example 2: Staff Evaluation
```
Report Type: Staff Performance
From: 2024-12-01
To: 2024-12-31
Result: Compare cashier productivity and tailor completion rates
```

### Example 3: Cash Flow Analysis
```
Report Type: Financial Report
From: 2024-12-01
To: 2024-12-31
Result: Track daily revenue, identify payment collection trends
```

---

## Troubleshooting

### No Data Showing
**Problem**: All reports show "No data available"
**Solution**:
1. Check date range has orders
2. Verify database connection
3. Check orders table has data for that period

### Charts Not Rendering
**Problem**: Canvas appears but no chart
**Solution**:
1. Check browser console for errors
2. Verify Chart.js CDN is accessible
3. Ensure data arrays are not empty
4. Clear browser cache

### Staff Report Empty
**Problem**: No staff shown in performance report
**Solution**:
1. Verify users table has cashier/tailor roles
2. Check staff have created/been assigned orders in period
3. Review SQL query in browser console

### Financial Report Wrong Totals
**Problem**: Paid + Pending doesn't match Revenue
**Solution**:
- This is correct! Cancelled orders are excluded from both
- Revenue = All orders (including cancelled)
- Paid = Status 'paid' only
- Pending = Not 'paid' and not 'cancel'

### Excel Export Not Downloading
**Problem**: Clicking "Export Excel" does nothing
**Solution**:
1. Check browser's download settings
2. Look in Downloads folder (file may have downloaded silently)
3. Check browser console for JavaScript errors
4. Verify export_report_excel.php exists in admin folder

### Excel File Shows Garbled Text
**Problem**: Special characters appear as weird symbols
**Solution**:
1. Open CSV in Excel using "Data > From Text/CSV" instead of double-clicking
2. Select UTF-8 encoding when importing
3. OR: Open file in Google Sheets (handles UTF-8 better)

### Excel File Won't Open
**Problem**: "File format not recognized" error
**Solution**:
1. File is CSV format (not true .xlsx)
2. Right-click file → Open With → Excel
3. OR: In Excel, File → Open → Select "All Files (*.*)" → Choose the CSV file

---

## Browser Support

✅ Chrome 90+
✅ Firefox 88+
✅ Edge 90+
✅ Safari 14+

---

## Mobile Responsive

- Desktop: Full layout with sidebar
- Tablet: Collapsible sidebar, stacked charts
- Mobile: Vertical layout, tables scroll horizontally

---

## Security

✅ Session validation (admin only)
✅ SQL injection prevention (prepared statements)
✅ XSS protection (htmlspecialchars)
✅ Error logging (doesn't expose details to users)
✅ Role-based access control

---

## Performance

- **Fast Queries**: Optimized GROUP BY with date filters
- **Efficient Charts**: Only loads Chart.js library
- **Minimal JavaScript**: Conditional chart initialization
- **Print Optimized**: Hidden elements reduce print time

---

## Quick Reference

| Report Type | Best For | Key Metrics | Chart Types | Excel Export |
|-------------|----------|-------------|-------------|--------------|
| **Orders Summary** | Order analysis | Total, Revenue, Completed | 2× Pie Charts | ✅ Full data |
| **Staff Performance** | HR evaluation | Orders, Revenue, Performance % | 2× Bar Charts | ✅ Staff details |
| **Financial** | Cash flow | Revenue, Paid, Pending | 1× Line Chart | ✅ Daily breakdown |

---

## Support

**Common Issues:**
1. **Blank charts** → Check browser console, verify data exists
2. **Wrong totals** → Review date range, check order statuses
3. **Print issues** → Use Chrome/Edge, check print preview
4. **Empty reports** → Verify orders exist in selected period
5. **Excel not downloading** → Check Downloads folder, verify export_report_excel.php exists

---

**Created**: December 2024
**Version**: 3.0 (Complete with Excel Export!)
**Status**: Production Ready ✅
**Total Lines**:
- reports.php: 1050 lines
- export_report_excel.php: 250 lines (NEW!)

**Dependencies**: Bootstrap 5.3, Font Awesome 6.0, Chart.js 4.4
**New Feature**: ⭐ **Excel/CSV Export** - Download all reports for offline analysis!
