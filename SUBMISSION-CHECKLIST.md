# WordPress Plugin Submission Checklist

## Pre-Submission Tasks

### Required Files
- [x] `markdown-fm.php` with proper plugin headers
- [x] `readme.txt` in WordPress format
- [x] `LICENSE.txt` (GPL v2 or later)
- [x] `/vendor/` directory with dependencies included
- [ ] Screenshots (5 recommended, in `/assets/` folder)
- [ ] Plugin icon (128x128 and 256x256, optional but recommended)
- [ ] Plugin banner (772x250 and 1544x500, optional but recommended)

### Plugin Information
- [x] Plugin Name: Markdown FM
- [x] Version: 1.0.0
- [x] Author: Silvestar Bistrović
- [x] Author Email: me@silvestar.codes
- [x] License: GPL v2 or later
- [x] Text Domain: markdown-fm
- [x] Requires at least: 5.0
- [x] Tested up to: 6.7
- [x] Requires PHP: 7.4

### Code Quality
- [x] All functions prefixed with `markdown_fm_` or `mdfm_`
- [x] Class name follows WordPress standards
- [x] No conflicts with WordPress core functions
- [x] Direct file access prevented (`ABSPATH` check)
- [x] Proper WordPress coding standards
- [x] Text domain matches plugin slug

### Security
- [x] All inputs sanitized (using sanitize_text_field, etc.)
- [x] All outputs escaped (using esc_html, esc_url, etc.)
- [x] AJAX requests use nonces
- [x] Capability checks (current_user_can)
- [x] SQL queries use $wpdb prepared statements (if any)
- [x] No eval() or base64_decode() usage
- [x] File upload validation (if applicable)

### Functionality
- [x] Plugin activation works
- [x] Plugin deactivation works
- [x] Uninstall hook cleans up database
- [x] No PHP errors or warnings
- [x] No JavaScript console errors
- [x] Works with WordPress debug mode enabled
- [ ] Tested on PHP 7.4, 8.0, 8.1, 8.2, 8.3
- [ ] Tested on WordPress 5.0 to latest version
- [ ] Tested with default WordPress themes (Twenty Twenty-Four, etc.)
- [ ] Tested on fresh WordPress installation

### WordPress Guidelines Compliance
- [x] GPL-compatible license
- [x] No phone-home or tracking (without disclosure)
- [x] No external dependencies loaded from CDNs
- [x] No obfuscated code
- [x] No trademark violations in plugin name
- [x] Scripts/styles properly enqueued
- [x] Uses WordPress APIs (not custom implementations)
- [x] No advertising in free version
- [x] No upsells in dashboard
- [ ] All third-party code is GPL-compatible

### Documentation
- [x] Clear installation instructions in readme.txt
- [x] FAQ section completed
- [x] Changelog documented
- [x] Screenshot descriptions in readme.txt
- [x] Developer documentation (usage examples)
- [x] Support/contact information provided

### Testing Checklist
- [ ] Test plugin installation from ZIP
- [ ] Test plugin activation
- [ ] Test creating/editing schemas
- [ ] Test all 12 field types work correctly
- [ ] Test saving data (posts and partials)
- [ ] Test template functions (mdfm_get_field, etc.)
- [ ] Test with different themes
- [ ] Test with Gutenberg editor
- [ ] Test with Classic editor
- [ ] Test admin permissions (only admins can access)
- [ ] Test plugin deactivation
- [ ] Test plugin deletion (data cleanup)
- [ ] Test on multisite (if applicable)
- [ ] Cross-browser testing (Chrome, Firefox, Safari, Edge)
- [ ] Mobile responsive testing

## Submission Process

### Step 1: Create WordPress.org Account
- [ ] Register at https://wordpress.org/support/register.php
- [ ] Verify email address
- [ ] Complete profile

### Step 2: Prepare Screenshots
- [ ] Screenshot 1: Admin interface (1200x900px)
- [ ] Screenshot 2: YAML editor (1200x900px)
- [ ] Screenshot 3: Post editor with fields (1200x900px)
- [ ] Screenshot 4: Partial management (1200x900px)
- [ ] Screenshot 5: Block field example (1200x900px)
- [ ] Add screenshots to `/assets/` folder
- [ ] Name them: screenshot-1.png through screenshot-5.png

### Step 3: Create Plugin ZIP
```bash
cd /path/to/wp-content/plugins
zip -r markdown-fm.zip markdown-fm/ \
  -x "*/node_modules/*" \
  -x "*/.git/*" \
  -x "*/.github/*" \
  -x "*/.claude/*" \
  -x "*/composer.json" \
  -x "*/composer.lock" \
  -x "*/.DS_Store" \
  -x "*/README.md" \
  -x "*/.editorconfig" \
  -x "*/PUBLISHING-GUIDE.md" \
  -x "*/SUBMISSION-CHECKLIST.md"
```

### Step 4: Validate Before Submission
- [ ] Validate readme.txt: https://wordpress.org/plugins/developers/readme-validator/
- [ ] Check PHP syntax: `php -l markdown-fm.php`
- [ ] Install Plugin Check plugin and run scan
- [ ] Review plugin guidelines: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/

### Step 5: Submit Plugin
- [ ] Go to: https://wordpress.org/plugins/developers/add/
- [ ] Upload markdown-fm.zip
- [ ] Wait for automated checks
- [ ] Wait for manual review (1-14 days)
- [ ] Monitor email for responses
- [ ] Respond to any feedback within 48 hours

### Step 6: SVN Setup (After Approval)
- [ ] Note SVN URL: `https://plugins.svn.wordpress.org/markdown-fm/`
- [ ] Checkout repository: `svn co https://plugins.svn.wordpress.org/markdown-fm`
- [ ] Create folder structure (trunk, tags, assets)
- [ ] Copy plugin files to trunk
- [ ] Copy screenshots to assets
- [ ] Commit trunk: `svn ci -m "Initial commit"`
- [ ] Tag version 1.0.0: `svn cp trunk tags/1.0.0`
- [ ] Commit tag: `svn ci -m "Tagging version 1.0.0"`

### Step 7: Post-Launch
- [ ] Verify plugin is live: https://wordpress.org/plugins/markdown-fm/
- [ ] Test installation from WordPress.org
- [ ] Upload plugin icon and banner to SVN assets folder
- [ ] Set up notifications for support forum
- [ ] Monitor initial reviews and feedback
- [ ] Create support documentation
- [ ] Announce plugin launch

## Common Issues to Avoid

### Security Issues
- ❌ Missing nonce verification on forms
- ❌ Missing capability checks
- ❌ Direct database queries without sanitization
- ❌ Unescaped output
- ❌ File uploads without validation

### Guideline Violations
- ❌ Loading external resources (jQuery from CDN)
- ❌ Phone-home without disclosure
- ❌ Including other plugins/themes
- ❌ Obfuscated code
- ❌ Trademark violations

### Code Quality Issues
- ❌ Using deprecated WordPress functions
- ❌ Not using WordPress APIs
- ❌ Poor PHP practices
- ❌ JavaScript errors
- ❌ CSS conflicts with WordPress admin

## Quick Reference

### Validate readme.txt
```bash
curl -F "readme=@readme.txt" https://wordpress.org/plugins/about/validator/
```

### Test PHP Syntax
```bash
find . -name "*.php" -exec php -l {} \;
```

### Check for Common Issues
```bash
# Check for eval (not allowed)
grep -r "eval(" .

# Check for base64_decode (suspicious)
grep -r "base64_decode" .

# Check for direct file access prevention
grep -r "ABSPATH" *.php
```

### SVN Quick Commands
```bash
# Checkout
svn co https://plugins.svn.wordpress.org/markdown-fm

# Status
svn status

# Add files
svn add trunk/* --force

# Commit
svn ci -m "Commit message"

# Create tag
svn cp trunk tags/1.0.0
svn ci -m "Tagging version 1.0.0"

# Update
svn up
```

## Support After Launch

### Monitor These Channels
- [ ] Support forum: https://wordpress.org/support/plugin/markdown-fm/
- [ ] Reviews: https://wordpress.org/support/plugin/markdown-fm/reviews/
- [ ] Email notifications from WordPress.org
- [ ] GitHub issues (if you have a repo)

### Response Times
- Support threads: Within 48 hours
- Security issues: Within 24 hours
- Feature requests: Acknowledge within 1 week
- Bug reports: Within 48 hours

### Best Practices
- Be professional and courteous
- Close resolved threads
- Mark solutions as resolved
- Thank users for feedback
- Document common issues in FAQ

---

**Current Status:** Ready for screenshot creation and final testing

**Next Steps:**
1. Create 5 screenshots
2. Run final tests on clean WP install
3. Create plugin ZIP
4. Submit to WordPress.org

**Estimated Timeline:**
- Screenshot creation: 1-2 hours
- Testing: 2-4 hours
- Submission: 30 minutes
- Review wait: 1-14 days
- SVN setup: 1-2 hours

Good luck! 🚀
