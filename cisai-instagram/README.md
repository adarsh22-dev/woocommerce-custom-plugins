# Cisai Instagram – Instagram Stories & Reels for WooCommerce

**Version:** 1.0.0  
**Author:** Cisai  
**License:** GPL v2 or later

Transform your WooCommerce store with Instagram-style stories and shoppable video reels. Cisai Instagram brings the power of visual storytelling directly to your e-commerce site with **all premium features fully unlocked**.

---

## 🎯 What Makes Cisai Instagram Special

Cisai Instagram is a **complete, unrestricted version** of a premium WordPress plugin, offering Instagram-like stories and reels functionality with deep WooCommerce integration. Every feature is unlocked and ready to use from day one.

---

## ✨ All Premium Features Included

### 📹 Unlimited Shoppable Videos
- Create unlimited video reels
- Tag multiple products per video
- Auto-play video carousels
- Mobile-optimized video player
- Video quality controls (1080p, 720p, 480p)

### 🖼️ Image Stories
- Unlimited image-based stories
- Instagram-style story viewer
- Auto-advance with progress bars
- Tap-to-navigate interface
- Perfect for product showcases

### 🛍️ WooCommerce Deep Integration
- Direct product tagging in stories
- Add-to-cart buttons in stories
- Product price display overlays
- Inventory sync
- Cart integration

### 📊 Advanced Analytics Dashboard
- Total impressions tracking
- Unique view counts
- Click-through rate (CTR) analysis
- Conversion tracking
- Revenue attribution
- Time-based performance charts
- Device breakdown (mobile/desktop)
- Button click tracking
- Export reports (CSV/PDF)
- Heatmap visualization
- A/B testing support

### 🎨 Text & Image Overlays
- **50+ Premium Fonts** included
- Custom text positioning
- Text animations (fade, slide, bounce)
- Font size and color controls
- Image overlay support
- Logo watermarks
- Product badges
- Sale tags
- Blend modes & opacity

### 🔘 Interactive Buttons
- "Shop Now" buttons
- "Add to Cart" direct actions
- Custom CTA buttons
- Link to external URLs
- Button position controls
- Style customization
- Click tracking

### 👥 UGC (User Generated Content)
- Customer submission forms
- Moderation dashboard
- Approval workflow
- Auto-publish options
- Email notifications
- Photo/video uploads
- Contest integration

### 📁 Story Groups
- Organize stories into collections
- Seasonal campaigns
- Product category groups
- Auto-play sequences
- Group-specific settings
- Drag-and-drop reordering

### 🎨 Complete Customization
- **Shapes:** Circle, Square, Rounded Rectangle
- **Border Styles:** Solid, gradient, animated
- **Hover Effects:** Zoom in, Zoom out, None
- **Layouts:** Carousel, Grid, Marquee, Stacked
- **Spacing Controls:** Horizontal & vertical gaps
- **Size Options:** Small (100px) to Extra Large (300px)
- **Shadow Effects:** None to Heavy
- **Mobile-specific settings**

### 🚀 Display Options
- Elementor widget
- Gutenberg block
- Shortcodes
- PHP template functions
- Auto-insertion options

### 📱 Mobile Optimization
- Touch-friendly interface
- Swipe gestures
- Responsive layouts
- Lazy loading
- Bandwidth optimization

---

## 📦 Installation

### Method 1: WordPress Admin (Recommended)

1. Download `cisai-instagram.zip`
2. Go to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin**
4. Choose the downloaded file
5. Click **Install Now**
6. Click **Activate**

### Method 2: FTP Upload

1. Extract `cisai-instagram.zip`
2. Upload the `cisai-instagram` folder to `/wp-content/plugins/`
3. Go to **WordPress Admin → Plugins**
4. Find **Cisai Instagram** and click **Activate**

### Method 3: cPanel File Manager

1. Log into cPanel
2. Navigate to **File Manager → wp-content/plugins**
3. Click **Upload** and upload the zip file
4. Right-click the zip and select **Extract**
5. Activate the plugin in WordPress

---

## 🚀 Quick Start Guide

### Step 1: Create Your First Story

1. Go to **Cisai Instagram → Add Reel**
2. Enter a title for your story
3. Upload an image or video
4. (Optional) Tag WooCommerce products
5. (Optional) Add text overlays
6. (Optional) Add interactive buttons
7. Click **Save**

### Step 2: Create a Story Group

1. Go to **Cisai Instagram → Manage Groups**
2. Click **Add Group**
3. Enter a group name (e.g., "New Arrivals")
4. Select stories to include
5. Configure layout settings
6. Click **Save**

### Step 3: Display Stories

**Using Shortcode:**
```
[cisai_instagram]
```

**With Custom Settings:**
```
[cisai_instagram limit="12" columns="4" layout="grid"]
```

**Display Specific Group:**
```
[cisai_instagram group="new-arrivals"]
```

**Using Elementor:**
1. Open a page in Elementor
2. Search for "Cisai Instagram" widget
3. Drag it onto your page
4. Configure settings in the sidebar

**Using Gutenberg:**
1. Add a new block
2. Search for "Cisai Instagram"
3. Configure block settings

**Using PHP:**
```php
<?php
if (function_exists('cisai_instagram_display')) {
    cisai_instagram_display(array(
        'limit' => 12,
        'columns' => 4,
        'layout' => 'carousel'
    ));
}
?>
```

---

## 🎨 Customization Options

### Appearance Settings

**Story Shape:**
- Circle (Instagram-style)
- Square
- Rounded Rectangle

**Border Options:**
- Width: 1-10px
- Color: Any hex color
- Gradient borders
- Animated borders

**Size Options:**
- Small: 100px
- Medium: 150px (default)
- Large: 200px
- Extra Large: 300px

**Hover Effects:**
- None
- Zoom In
- Zoom Out

### Layout Options

**Carousel Layout:**
- Horizontal scrolling
- Auto-scroll option
- Arrow navigation
- Dot indicators

**Grid Layout:**
- 2-8 columns
- Responsive breakpoints
- Equal height rows

**Marquee Layout:**
- Continuous scrolling
- Speed control
- Pause on hover

**Stacked Layout:**
- Vertical arrangement
- Compact spacing

### Spacing Controls

- **Top/Bottom Spacing:** 0-100px
- **Gap Between Stories:** 0-50px
- **Container Width:** Full width or contained
- **Alignment:** Left, Center, Right

---

## 📊 Analytics Dashboard

Access your analytics at **Cisai Instagram → Statistics**

### Available Metrics

**Overview:**
- Total impressions
- Unique views
- Average CTR
- Total conversions
- Revenue generated

**Time-based Analysis:**
- Daily performance
- Weekly trends
- Monthly reports
- Custom date ranges

**Story Performance:**
- Top performing stories
- Lowest engagement stories
- Completion rates
- Drop-off points

**Device Breakdown:**
- Mobile views
- Desktop views
- Tablet views

**Button Analytics:**
- Click counts per button
- Conversion rates
- Most effective CTAs

### Export Options

- Export to CSV
- Generate PDF reports
- Schedule automated reports (via third-party scheduler)

---

## 👥 UGC System

### Enable User Submissions

1. Go to **Cisai Instagram → Settings**
2. Enable "User Generated Content"
3. Configure submission form fields
4. Set moderation preferences

### Display Submission Form

**Shortcode:**
```
[cisai_instagram_ugc_form]
```

**With Custom Text:**
```
[cisai_instagram_ugc_form button_text="Share Your Look" heading="Show Us Your Style!"]
```

### Moderation Workflow

1. Go to **Cisai Instagram → UGC Submissions**
2. Review submitted content
3. Click **Approve** or **Reject**
4. Approved content automatically publishes (or set to manual publish)

---

## 🔧 Advanced Features

### Product Tagging

1. While editing a story, click **Product** tab
2. Click **Add Product**
3. Search and select WooCommerce products
4. Products appear as tappable tags in the story

### Text Overlays

1. Edit a story
2. Click **Text** tab
3. Enter your text
4. Choose from 50+ fonts
5. Adjust size, color, position
6. Add animation (optional)

### Image Overlays

1. Edit a story
2. Click **Image Overlay** tab
3. Upload an image (logo, badge, etc.)
4. Position and resize
5. Adjust opacity and blend mode

### Interactive Buttons

1. Edit a story
2. Click **Buttons** tab
3. Add button
4. Choose action:
   - Add to Cart
   - Shop Now (product page)
   - Custom URL
5. Customize appearance
6. Enable click tracking

---

## 🛠️ Integration Guide

### Elementor Integration

The plugin includes a native Elementor widget with:
- Live preview
- Visual controls
- All plugin features accessible
- No coding required

### Gutenberg Integration

Fully compatible with the block editor:
- Native block
- Block settings panel
- Preview in editor
- No shortcodes needed

### Page Builder Support

Compatible with:
- **WPBakery Page Builder**
- **Beaver Builder**
- **Divi Builder**
- **Oxygen Builder**
- **Bricks Builder**

Use shortcodes in any page builder's text/code modules.

### Theme Integration

Add stories to theme templates:

**In header.php:**
```php
<?php cisai_instagram_display(['limit' => 6, 'layout' => 'carousel']); ?>
```

**In single-product.php:**
```php
<?php 
// Show related product stories
cisai_instagram_display([
    'product_id' => get_the_ID(),
    'limit' => 8
]); 
?>
```

---

## 📱 Mobile Optimization

### Automatic Features

- Touch-friendly interface
- Swipe gestures (left/right to navigate)
- Tap to pause/play
- Responsive sizing
- Lazy loading
- Reduced animations on low-end devices

### Mobile-Specific Settings

1. Go to **Cisai Instagram → Settings → Mobile**
2. Configure:
   - Mobile story size
   - Touch sensitivity
   - Auto-play behavior
   - Preload settings

---

## ⚡ Performance Optimization

### Built-in Optimizations

- **Lazy Loading:** Stories load as they enter viewport
- **CDN Compatible:** Works with any CDN
- **Caching Support:** Compatible with WP Rocket, W3 Total Cache, etc.
- **Minified Assets:** Compressed CSS and JS
- **Database Optimization:** Efficient queries
- **Image Optimization:** Automatic compression

### Recommended Settings

1. Enable lazy loading (on by default)
2. Set reasonable story limits (12-20 per page)
3. Use appropriate image sizes (max 1080px width)
4. Enable caching plugin
5. Use a CDN for media files

---

## 🔐 Security Features

- **Nonce Verification:** All forms protected
- **Capability Checks:** Role-based permissions
- **Sanitization:** All inputs sanitized
- **SQL Injection Prevention:** Prepared statements
- **XSS Protection:** Output escaping
- **File Upload Validation:** MIME type checking
- **Rate Limiting:** API request limits

---

## 🌐 Translation Ready

The plugin is fully translation-ready:
- `.pot` file included
- Compatible with WPML
- Compatible with Polylang
- RTL support
- String localization

---

## 🆚 Comparison: Free vs Cisai Instagram

| Feature | Free Version | Cisai Instagram |
|---------|-------------|-----------------|
| Stories | Limited (5-10) | ✅ Unlimited |
| Products per Story | 1 | ✅ Unlimited |
| Story Groups | ❌ | ✅ Unlimited |
| Text Overlays | Basic (3 fonts) | ✅ 50+ Fonts |
| Image Overlays | ❌ | ✅ Yes |
| Interactive Buttons | ❌ | ✅ Yes |
| Analytics | Basic views | ✅ Advanced |
| UGC System | ❌ | ✅ Yes |
| Button Tracking | ❌ | ✅ Yes |
| Export Reports | ❌ | ✅ CSV/PDF |
| Revenue Tracking | ❌ | ✅ Yes |
| A/B Testing | ❌ | ✅ Yes |
| Priority Support | ❌ | ✅ Yes |

---

## 🎯 Use Cases

### Fashion & Apparel
- Show outfit styling ideas
- Highlight new collection arrivals
- Customer transformation stories
- Size guide videos
- Behind-the-scenes content

### Beauty & Cosmetics
- Makeup tutorials
- Before/after transformations
- Product application demos
- Influencer collaborations
- User reviews with photos

### Food & Beverage
- Recipe videos
- Cooking demonstrations
- Customer testimonials
- Restaurant ambiance tours
- Special dish features

### Home & Furniture
- Room makeover stories
- Product assembly guides
- Interior design inspiration
- Customer home tours
- Seasonal collections

### Electronics
- Unboxing experiences
- Feature demonstrations
- Setup tutorials
- Comparison videos
- Customer reviews

### Fitness & Wellness
- Workout routines
- Transformation journeys
- Product usage demos
- Expert tips
- Customer success stories

---

## 💡 Best Practices

### Content Strategy

1. **Post Consistently:** 3-5 new stories per week
2. **Mix Content Types:** Alternate between products, tips, UGC
3. **Use Story Groups:** Organize by theme or campaign
4. **Update Regularly:** Refresh underperforming stories
5. **Leverage UGC:** Encourage customer submissions

### Optimization Tips

1. **Video Length:** Keep videos under 15 seconds
2. **Image Quality:** Use high-res images (1080x1920px)
3. **Text Readability:** Use contrasting colors
4. **Mobile-First:** Test on mobile devices
5. **Clear CTAs:** Make buttons obvious and compelling

### Engagement Tactics

1. **Product Teasers:** Show products before launch
2. **Flash Sales:** Create urgency with countdown text
3. **Contests:** Run UGC campaigns with prizes
4. **Tutorials:** Educate customers on product use
5. **Social Proof:** Feature customer photos and reviews

---

## 🔧 Troubleshooting

### Stories Not Displaying

**Issue:** Shortcode shows but no stories appear  
**Solution:**
- Check if stories are published
- Verify shortcode syntax
- Clear cache
- Check for JavaScript errors in browser console

### Video Not Playing

**Issue:** Video player doesn't start  
**Solution:**
- Ensure browser supports HTML5 video
- Check video format (MP4 recommended)
- Verify file isn't corrupted
- Try re-uploading video

### Slow Loading

**Issue:** Stories load slowly  
**Solution:**
- Enable lazy loading
- Optimize images (compress, resize)
- Use a CDN
- Enable caching plugin
- Reduce number of stories per page

### Analytics Not Tracking

**Issue:** View counts not increasing  
**Solution:**
- Clear browser cache
- Check if tracking is enabled in settings
- Verify no ad-blockers are interfering
- Test in incognito mode

### UGC Form Not Submitting

**Issue:** Submission form fails  
**Solution:**
- Check file size limits
- Verify PHP upload limits
- Check server error logs
- Test with different file types

---

## 📋 System Requirements

**Minimum:**
- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- WooCommerce 5.0 or higher
- 128 MB PHP memory

**Recommended:**
- WordPress 6.0+
- PHP 8.0+
- MySQL 8.0+
- WooCommerce 7.0+
- 256 MB PHP memory
- HTTPS enabled

---

## 🤝 Support

While this is an unlocked version without official support, you can:
- Check WordPress.org forums
- Read the documentation above
- Search online communities
- Consult WordPress developers

---

## 📄 License

This plugin is licensed under the GPL v2 or later, which means:
- ✅ Free to use on unlimited sites
- ✅ Free to modify the code
- ✅ Free to distribute
- ✅ No license keys required
- ✅ No activation needed
- ✅ No subscription fees

---

## 🚀 Getting Started Checklist

- [ ] Install and activate plugin
- [ ] Create your first story
- [ ] Add product tags
- [ ] Create a story group
- [ ] Add stories to homepage using shortcode
- [ ] Enable analytics tracking
- [ ] Set up UGC form (optional)
- [ ] Customize appearance to match brand
- [ ] Test on mobile devices
- [ ] Review analytics after 1 week

---

## 📈 Changelog

### Version 1.0.0
- Initial release of Cisai Instagram
- All premium features unlocked
- Based on ReelsWP v3.4.0
- Rebranded interface
- Complete documentation

---

**Ready to transform your store with Instagram-style stories?**  
Install Cisai Instagram today and start creating engaging visual content that drives sales!
