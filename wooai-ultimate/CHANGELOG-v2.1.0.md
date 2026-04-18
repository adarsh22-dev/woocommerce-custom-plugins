# WooAI Assistant - Version 2.1.0 Changelog

## 🎉 Version 2.1.0 - Dashboard Graphs Fixed & New Features (December 18, 2024)

### 🔧 Major Fixes

#### Dashboard Graphs (FIXED! ✅)
- **Fixed Intent Distribution Chart** - Now renders correctly with proper dimensions
- **Fixed Hourly Activity Chart** - Displays properly without blank spaces
- **Improved Chart.js Initialization** - Better context handling and error management
- **Added Chart Destruction** - Prevents memory leaks when reloading charts
- **Enhanced Canvas Dimensions** - Proper width/height calculations
- **Better Error Handling** - Detailed error messages in console for debugging
- **Improved Tooltips** - Added percentage display in intent chart
- **Enhanced Styling** - Better legend formatting and point hover effects

#### Quick Actions (IMPROVED! ✅)
- **Fixed SVG Icon Support** - Icons now display correctly in admin and frontend
- **Added Custom Actions** - Create unlimited custom quick actions
- **Improved Icon Picker** - Emoji picker modal with 16 pre-selected icons
- **Better Sorting** - Drag-and-drop functionality (ready for implementation)
- **Enhanced Status Indicators** - Clear active/inactive display
- **Fixed Action Types** - Added callback action type to defaults

### 🆕 New Features

#### 1. WordPress Page Selector for Policies
- **Page Dropdown** - Select existing WordPress pages for policies
- **Auto-Fill URL** - Automatic URL population when page is selected
- **Custom URL Option** - Still supports custom URLs
- **Better Organization** - Link policies directly to your WordPress pages
- **AJAX Integration** - Smooth page URL fetching

**How to Use:**
1. Go to WooAI Admin → Policies
2. Click "Add Policy" or edit existing
3. Select a page from "Link to Page" dropdown
4. URL automatically fills from WordPress page
5. Or enter custom URL manually

#### 2. Complete Callback Request System
- **New Callbacks Menu** - Dedicated admin page for callback management
- **Callback Form in Chat** - Users can request callbacks directly from chat widget
- **Status Management** - Track callbacks through: Pending → In Progress → Completed
- **Statistics Dashboard** - View total, pending, and completed callbacks
- **Filter Options** - Filter by status (All, Pending, In Progress, Completed)
- **User Tracking** - Links callbacks to registered users
- **Email Integration** - Stores user email for follow-up
- **Message Support** - Optional message from customer
- **Status Update Modal** - Easy status updates from admin

**Callback Workflow:**
1. User clicks "Callback" quick action in chat
2. Fills in name, phone, email (optional), message (optional)
3. Request saved to database with "pending" status
4. Admin sees request in WooAI Admin → Callbacks
5. Admin updates status: Pending → In Progress → Completed
6. Click phone number to call directly
7. Email link for quick communication

#### 3. Database Enhancements
- **Added `page_id` column** to `wooai_policies` table
- **Added `user_id` column** to `wooai_callbacks` table
- **Added `user_agent` column** to `wooai_conversations` table
- **Added `is_custom` column** to `wooai_actions` table
- **Changed `icon` column** type from VARCHAR(50) to TEXT for SVG support
- **Added indexes** for better query performance

### 📊 Callbacks Statistics Features

```
┌─────────────────────────────────────────┐
│  Total Requests    Pending    Completed │
│      127             23          98     │
└─────────────────────────────────────────┘
```

- Real-time callback count tracking
- Color-coded status badges
- Quick filter buttons
- Direct phone/email links
- Session tracking
- Created date/time display

### 🎨 UI Improvements

#### Charts
- Better responsive behavior
- Improved legend positioning
- Enhanced color schemes
- Smoother animations
- Proper aspect ratios
- Fixed canvas dimensions

#### Callbacks Page
- Clean, modern design
- Easy-to-scan table layout
- Color-coded status indicators:
  - 🟡 Pending (Yellow)
  - 🔵 In Progress (Blue)
  - 🟢 Completed (Green)
  - 🔴 Cancelled (Red)
- Responsive grid layout for stats
- Modal for status updates

#### Policies Page
- Added page selector dropdown
- Better form organization
- Clear field descriptions
- Improved user guidance

### 🛠️ Technical Improvements

#### Chart.js Integration
```javascript
// Old Method (Buggy)
canvas.height = 250;
new Chart(canvas, {...});

// New Method (Fixed)
const ctx = canvas.getContext('2d');
canvas.width = canvas.offsetWidth;
canvas.height = 250;
window.intentChart = new Chart(ctx, {...});
```

#### AJAX Endpoints
- **NEW:** `wooai_get_page_url` - Get WordPress page permalink
- **IMPROVED:** Better error handling in all endpoints
- **IMPROVED:** Consistent response formats

### 📝 Database Schema Updates

#### Policies Table (`wp_wooai_policies`)
```sql
ALTER TABLE wp_wooai_policies 
ADD COLUMN page_id BIGINT(20) DEFAULT NULL AFTER url,
ADD INDEX page_id (page_id);
```

#### Callbacks Table (`wp_wooai_callbacks`)
```sql
ALTER TABLE wp_wooai_callbacks 
ADD COLUMN user_id BIGINT(20) DEFAULT NULL AFTER message,
ADD INDEX user_id (user_id);
```

#### Actions Table (`wp_wooai_actions`)
```sql
ALTER TABLE wp_wooai_actions 
MODIFY COLUMN icon TEXT NOT NULL,
ADD COLUMN is_custom TINYINT(1) DEFAULT 0 AFTER is_active;
```

#### Conversations Table (`wp_wooai_conversations`)
```sql
ALTER TABLE wp_wooai_conversations 
ADD COLUMN user_agent TEXT DEFAULT NULL AFTER intent,
ADD INDEX intent (intent);
```

### 🔄 Migration Notes

**For existing installations:**
- Charts will automatically work with improved code
- Database tables will be updated on plugin activation
- Existing data is preserved
- No manual migration needed

**For fresh installations:**
- All new features work out of the box
- Default callback action is included
- Sample policies include page linking

### 🐛 Bug Fixes

1. **Chart Rendering** - Fixed blank charts issue
2. **SVG Icons** - Fixed display in quick actions
3. **Page Linking** - Proper URL handling for policies
4. **Geolocation** - Better storage in conversations table
5. **Custom Actions** - Fixed delete functionality
6. **Status Indicators** - Consistent styling across admin

### 📦 Files Modified

```
admin/
  └── views/
      ├── logs.php (Chart fixes)
      ├── policies.php (Page selector)
      └── callbacks.php (NEW FILE)
  └── class-admin.php (Callbacks menu)

includes/
  ├── class-installer.php (Database updates)
  └── class-ajax-handler.php (New endpoints)

wooai-assistant.php (Version update)
```

### 🔮 What's Next (Coming Soon)

- [ ] Drag-and-drop quick actions sorting
- [ ] Email notifications for callbacks
- [ ] Advanced callback scheduling
- [ ] Callback analytics dashboard
- [ ] Export callbacks to CSV
- [ ] Automated callback status updates
- [ ] Integration with phone systems
- [ ] SMS notifications

### 📚 Documentation Updates

- Updated installation guide
- Added callback system documentation
- Improved policy management guide
- Chart troubleshooting section

### ✅ Testing Checklist

Before using, please verify:
- [ ] Charts display correctly on Logs page
- [ ] Can create custom quick actions
- [ ] SVG icons work in actions
- [ ] Can select WordPress pages in policies
- [ ] Page URL auto-fills when page selected
- [ ] Callbacks menu appears in admin
- [ ] Can submit callback requests from chat
- [ ] Callbacks appear in admin panel
- [ ] Can update callback status
- [ ] Statistics show correct counts
- [ ] Filters work properly

### 🚀 Quick Start

**To use new features:**

1. **Fixed Charts:**
   - Just go to WooAI Admin → Chat Logs
   - Charts now render perfectly! ✨

2. **Callbacks:**
   - Ensure "Callback" quick action is active
   - Users can click it in chat widget
   - Manage requests in WooAI Admin → Callbacks

3. **Page Selector:**
   - Go to WooAI Admin → Policies
   - Edit or create policy
   - Select page from dropdown
   - URL auto-fills

### 🙏 Credits

Special thanks to user feedback for reporting:
- Dashboard graph rendering issues
- Quick actions SVG icon problems
- Request for callback system
- Need for WordPress page integration

### 📞 Support

- **Issues:** Report bugs via support
- **Docs:** Check updated documentation
- **Updates:** Auto-update available

---

## Previous Versions

### Version 2.0.0 (December 17, 2024)
- Complete plugin architecture
- Multi-AI support
- Admin dashboard
- Geolocation tracking
- Product assignments
- Custom quick actions

### Version 1.0.0 (Initial Release)
- Basic chat widget
- Simple product search
- Basic admin panel

---

**Enjoy the improved WooAI Assistant!** 🎉

If you encounter any issues, please report them immediately so we can help. The callback system is a major feature - let us know how you use it! 📞✨
