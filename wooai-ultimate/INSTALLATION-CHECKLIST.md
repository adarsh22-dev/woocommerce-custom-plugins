# WooAI Assistant Pro - Installation Checklist

## ✅ Step-by-Step Installation

### 1. UPLOAD & ACTIVATE (2 minutes)
- [ ] Go to WordPress Admin → Plugins → Add New
- [ ] Click "Upload Plugin"
- [ ] Choose `wooai-assistant-complete.zip`
- [ ] Click "Install Now"
- [ ] Click "Activate Plugin"

### 2. GET API KEY (2 minutes)
**Option A: Google Gemini (FREE - Recommended)**
- [ ] Visit: https://makersuite.google.com/app/apikey
- [ ] Sign in with Google
- [ ] Click "Create API Key"
- [ ] Copy the key

**Option B: OpenAI (Paid)**
- [ ] Visit: https://platform.openai.com/api-keys
- [ ] Create API key
- [ ] Copy the key

### 3. CONFIGURE PLUGIN (2 minutes)
- [ ] Go to WooAI Admin → Settings
- [ ] Paste your API key in the appropriate field
- [ ] Keep default greeting or customize it
- [ ] Choose your brand color (default purple is good)
- [ ] Click "Save Changes"

### 4. ASSIGN PRODUCTS (5 minutes)
- [ ] Go to WooAI Admin → Assignments
- [ ] Click "Bestselling" tab
- [ ] Click on 4-8 products to select them (they'll get a purple border)
- [ ] Click "Save Assignments"
- [ ] Repeat for "Recommended", "New Arrivals", and "Offers" tabs

### 5. TEST THE WIDGET (2 minutes)
- [ ] Open your store homepage in a new tab
- [ ] Look for purple chat button in bottom-right corner
- [ ] Click the button to open chat
- [ ] Type "hi" and press Enter
- [ ] AI should respond
- [ ] Try clicking "Bestselling" quick action button
- [ ] Products should appear
- [ ] Try clicking "Callback" button
- [ ] Form should appear

## ✅ VERIFICATION CHECKLIST

### Frontend Tests
- [ ] Chat widget visible on homepage
- [ ] Chat widget visible on product pages
- [ ] Chat widget visible on shop page
- [ ] Chat button clickable
- [ ] Chat window opens smoothly
- [ ] Quick action buttons display
- [ ] Quick actions are clickable
- [ ] Products load when clicking actions
- [ ] Product images show correctly
- [ ] "View Product" links work
- [ ] Callback form appears
- [ ] Callback form can be submitted
- [ ] AI responds to messages
- [ ] Chat scrolls smoothly
- [ ] Widget works on mobile

### Backend Tests
- [ ] Dashboard loads without errors
- [ ] Statistics show numbers
- [ ] Chart displays (may need data)
- [ ] Assignments page loads
- [ ] Can select products
- [ ] Can save assignments
- [ ] Settings page loads
- [ ] Can save settings
- [ ] Policies page shows policies
- [ ] Actions page shows actions
- [ ] Logs page shows conversations

## 🐛 COMMON ISSUES & FIXES

### Widget Not Showing
**Fix 1:** Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)
**Fix 2:** Check if another plugin conflicts (disable others temporarily)
**Fix 3:** Make sure WooCommerce is active

### AI Not Responding
**Fix 1:** Verify API key has no extra spaces
**Fix 2:** Check API provider has credits (OpenAI needs payment)
**Fix 3:** Try switching to Gemini (free tier)

### Products Not Showing
**Fix 1:** Make sure you assigned products in Assignments page
**Fix 2:** Verify products are published (not draft)
**Fix 3:** Check products have images

### Styles Look Broken
**Fix 1:** Clear WordPress cache if using cache plugin
**Fix 2:** Regenerate CSS in customizer
**Fix 3:** Check for theme conflicts

## 🎯 POST-INSTALLATION TIPS

### Optimize Performance
1. Monitor Dashboard stats daily first week
2. Review Chat Logs to see common questions
3. Adjust Quick Actions based on usage
4. Update product assignments weekly

### Customize Further
1. Change widget color to match brand
2. Update greeting message seasonally
3. Add/update store policies
4. Toggle off unused quick actions

### Advanced Configuration
1. Set up WooCommerce REST API if needed
2. Configure vendor mode (if multi-vendor)
3. Create custom quick actions
4. Integrate with email marketing

## ✅ SUCCESS CRITERIA

You'll know it's working when:
- ✅ Chat widget appears on your site
- ✅ Clicking widget opens chat window
- ✅ Quick actions show and work
- ✅ Products display with images
- ✅ AI responds to messages
- ✅ Admin dashboard shows data
- ✅ No JavaScript errors in console

## 📞 NEED HELP?

If you encounter issues:
1. Check WordPress error log (enable WP_DEBUG)
2. Check browser console for JavaScript errors (F12)
3. Review the README.md file
4. Verify all prerequisites are met

## 🎉 YOU'RE DONE!

If all checkboxes are checked, congratulations! 
Your WooAI Assistant is fully operational.

---

**Estimated Total Time:** 10-15 minutes
**Difficulty Level:** Easy (no coding required)
**Support:** Check README.md for troubleshooting
