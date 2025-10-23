# WordPress Plugin Directory Publishing Guide

## Complete Checklist for Publishing Markdown FM

### ✅ Pre-Submission Checklist

- [x] Plugin has unique name "Markdown FM"
- [x] GPL-compatible license (GPL v2)
- [x] LICENSE.txt file included
- [x] readme.txt in WordPress format created
- [x] README.md for developers included
- [x] Version number defined (1.0.0)
- [x] Plugin headers complete
- [x] Composer dependencies included in vendor/
- [ ] Screenshots added to assets/
- [ ] Icon/banner images created (optional but recommended)
- [ ] Test on clean WordPress installation
- [ ] Security review complete

### 📋 Step-by-Step Publishing Process

#### Step 1: Create WordPress.org Account

1. Go to https://wordpress.org/support/register.php
2. Create an account (if you don't have one)
3. Verify your email address

#### Step 2: Prepare Your Plugin Package

1. **Ensure all files are ready:**
   - ✅ `markdown-fm.php` (main plugin file)
   - ✅ `readme.txt` (WordPress format)
   - ✅ `LICENSE.txt`
   - ✅ `/vendor/` (with dependencies)
   - ✅ `/assets/` (CSS, JS)
   - ✅ `/templates/`
   - ⚠️ Add screenshots to `/assets/` folder named:
     - `screenshot-1.png` (Admin interface)
     - `screenshot-2.png` (YAML editor)
     - `screenshot-3.png` (Post editor with fields)
     - `screenshot-4.png` (Partial management)
     - `screenshot-5.png` (Block field example)

2. **Create screenshots** (1200x900px recommended):

   ```bash
   # Take screenshots of:
   # 1. Main admin page with template list
   # 2. YAML schema editor
   # 3. Post editor showing custom fields
   # 4. Partial data management modal
   # 5. Block field with multiple blocks
   ```

3. **Test the plugin thoroughly:**
   ```bash
   # Test on a fresh WordPress install
   # Test PHP 7.4, 8.0, 8.1, 8.2, 8.3
   # Test WordPress 5.0 up to latest version
   # Test with different themes
   # Test all field types
   # Check security (XSS, SQL injection, CSRF)
   ```

#### Step 3: Create Plugin ZIP

**DO NOT include development files:**

```bash
# From the plugin root directory
cd ..
zip -r markdown-fm.zip markdown-fm/ \
  -x "*/node_modules/*" \
  -x "*/.git/*" \
  -x "*/.github/*" \
  -x "*/.claude/*" \
  -x "*/composer.json" \
  -x "*/composer.lock" \
  -x "*/.DS_Store" \
  -x "*/Thumbs.db" \
  -x "*/README.md" \
  -x "*/.editorconfig" \
  -x "*/.distignore" \
  -x "*/PUBLISHING-GUIDE.md"

# OR use wp-cli
wp dist-archive . --plugin-dirname=markdown-fm

# Verify the zip contents
unzip -l markdown-fm.zip
```

**The ZIP should contain:**

```
markdown-fm/
├── markdown-fm.php
├── readme.txt
├── LICENSE.txt
├── assets/
│   ├── admin.css
│   ├── admin.js
│   ├── screenshot-1.png
│   ├── screenshot-2.png
│   ├── screenshot-3.png
│   ├── screenshot-4.png
│   └── screenshot-5.png
├── templates/
│   └── admin-page.php
└── vendor/
    └── (all composer dependencies)
```

#### Step 4: Submit to WordPress.org

1. **Go to the plugin submission page:**
   https://wordpress.org/plugins/developers/add/

2. **Upload your ZIP file**

3. **Wait for automated checks** (usually instant):
   - Will check for security issues
   - Will check for guideline violations
   - Will check for trademarked terms

4. **Wait for manual review** (can take 1-14 days):
   - A WordPress.org team member will review your plugin
   - They may request changes or clarifications
   - Check your email regularly for updates

5. **Respond promptly to any feedback**:
   - Address all concerns
   - Make required changes
   - Reply to the review ticket

#### Step 5: Approval and SVN Setup

Once approved, you'll receive:

- SVN repository URL: `https://plugins.svn.wordpress.org/markdown-fm/`
- Instructions for SVN access

**Initial SVN commit:**

```bash
# Checkout SVN repository
svn co https://plugins.svn.wordpress.org/markdown-fm markdown-fm-svn
cd markdown-fm-svn

# Create trunk directory if it doesn't exist
svn mkdir trunk
svn mkdir tags
svn mkdir assets

# Copy plugin files to trunk
cp -r /path/to/markdown-fm/* trunk/

# Do NOT commit development files
# Remove them if accidentally copied:
cd trunk
rm -f composer.json composer.lock README.md .editorconfig
rm -rf .git .github .claude node_modules

# Copy screenshots to assets folder (separate from plugin)
cd ..
cp /path/to/screenshots/* assets/

# Add all files
svn add trunk/* --force
svn add assets/* --force

# Commit to trunk
svn ci -m "Initial commit of Markdown FM version 1.0.0"

# Create first release tag
svn cp trunk tags/1.0.0
svn ci -m "Tagging version 1.0.0"
```

**Your plugin is now live!**

#### Step 6: Post-Launch

1. **Plugin page will be live at:**
   https://wordpress.org/plugins/markdown-fm/

2. **Add plugin assets** (optional but recommended):

   Create in `/assets/` folder in SVN (NOT in the plugin):
   - `banner-772x250.png` - Header banner
   - `banner-1544x500.png` - Retina header banner
   - `icon-128x128.png` - Plugin icon
   - `icon-256x256.png` - Retina plugin icon

   ```bash
   cd markdown-fm-svn/assets
   # Add your icon and banner files
   svn add icon-*.png banner-*.png
   svn ci -m "Add plugin icon and banner"
   ```

3. **Monitor and respond to:**
   - Support forum: https://wordpress.org/support/plugin/markdown-fm/
   - Reviews: https://wordpress.org/support/plugin/markdown-fm/reviews/
   - Bug reports

### 🔄 Updating Your Plugin

For each new version:

```bash
cd markdown-fm-svn

# Update trunk with new version
cd trunk
# Copy your updated files here
# Update version in markdown-fm.php and readme.txt

# Commit to trunk
svn ci -m "Update to version 1.0.1 - Bug fixes and improvements"

# Create new tag
cd ..
svn cp trunk tags/1.0.1
svn ci -m "Tagging version 1.0.1"
```

**The stable tag in readme.txt controls which version users download!**

### 📝 Important Guidelines

#### Must Follow:

1. **GPL License** - Your plugin must be GPL v2 or later
2. **No phone home** - Don't send data to external servers without disclosure
3. **Trademark compliance** - Don't use WordPress or WP in plugin name
4. **Security** - Sanitize inputs, escape outputs, use nonces
5. **Prefix functions** - Use unique prefixes (markdown*fm*)
6. **No advertising** - Don't include ads in free plugin
7. **Proper enqueuing** - Use wp_enqueue_script/style
8. **Database cleanup** - Remove data on uninstall

#### Best Practices:

1. **Semantic versioning** - Use MAJOR.MINOR.PATCH (1.0.0)
2. **Changelog** - Document all changes in readme.txt
3. **Testing** - Test on multiple PHP/WordPress versions
4. **Documentation** - Clear instructions in readme.txt
5. **Support** - Respond to support threads within 48 hours
6. **Accessibility** - Follow WordPress accessibility standards
7. **Internationalization** - Make strings translatable
8. **Performance** - Optimize queries and assets

### 🎨 Creating Plugin Assets

#### Screenshots (1200x900px or 1440x900px)

- PNG or JPG format
- High quality, not pixelated
- Show actual plugin functionality
- Clear, easy to understand

#### Icon (128x128px and 256x256px)

- Square format
- Simple, recognizable design
- PNG with transparent background
- Should work at small sizes

#### Banner (772x250px and 1544x500px)

- Professional design
- Plugin name and tagline
- Consistent with icon design
- PNG or JPG format

### 🐛 Common Rejection Reasons

1. **Security issues:**
   - Missing sanitization
   - Missing nonce checks
   - Direct file access not prevented
   - SQL injection vulnerabilities

2. **Guideline violations:**
   - Calling external files (CDNs)
   - Including other plugins/themes
   - Obfuscated or encrypted code
   - Trademark issues

3. **Code quality:**
   - Using deprecated WordPress functions
   - Not using WordPress APIs correctly
   - Poor coding standards

### 📚 Resources

- **Plugin Handbook:** https://developer.wordpress.org/plugins/
- **Plugin Guidelines:** https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- **SVN Guide:** https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/
- **Readme.txt Validator:** https://wordpress.org/plugins/developers/readme-validator/
- **Plugin Check Plugin:** https://wordpress.org/plugins/plugin-check/

### 🎯 Quick Commands Reference

```bash
# Test plugin syntax
php -l markdown-fm.php

# Validate readme.txt
curl -F "readme=@readme.txt" https://wordpress.org/plugins/about/validator/

# Create distribution ZIP
cd .. && zip -r markdown-fm.zip markdown-fm/ -x "*/.*" "*/node_modules/*" "*/README.md" "*/composer.json"

# SVN commit trunk
cd markdown-fm-svn
svn ci -m "Update description"

# SVN create tag
svn cp trunk tags/1.0.1
svn ci -m "Tagging version 1.0.1"

# SVN check status
svn status

# SVN update from repository
svn up
```

### ✉️ Communication Tips

When communicating with WordPress.org reviewers:

1. **Be professional and courteous**
2. **Respond promptly** (within 24-48 hours)
3. **Address ALL concerns** - even if you disagree, explain why
4. **Ask for clarification** if you don't understand feedback
5. **Update your ticket** when you've made changes
6. **Be patient** - reviewers are volunteers

### 🎉 After Approval

1. **Announce your plugin:**
   - Social media
   - WordPress forums
   - Developer communities
   - Your website/blog

2. **Engage with users:**
   - Answer support questions
   - Respond to reviews
   - Consider feature requests
   - Fix bugs promptly

3. **Keep improving:**
   - Regular updates
   - Security patches
   - New features
   - Better documentation

4. **Consider premium version:**
   - If appropriate for your plugin
   - Must be hosted elsewhere (not on WordPress.org)
   - Free version must be fully functional

---

## Your Plugin's WordPress.org Username

You'll need this for SVN commits. It's your wordpress.org username (not email).

**Good luck with your plugin submission!** 🚀

If you encounter issues, the WordPress.org plugin team is generally helpful and responsive. Don't be discouraged if changes are requested - it's part of ensuring quality for the WordPress ecosystem.
