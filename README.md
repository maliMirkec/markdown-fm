# Markdown FM

A WordPress plugin for managing YAML frontmatter schemas in theme templates and partials. Inspired by [PagesCMS](https://pagescms.org/docs/), Markdown FM allows you to define structured content schemas with an intuitive interface and ACF-like template functions.

> Vibe-coded with Claude.

## Features

- 🎨 **Define YAML schemas** for page templates and template partials
- 📝 **12+ field types** including string, rich-text, images, blocks, and more
- 🔧 **Beautiful admin interface** with branded header and intuitive controls
- 🎯 **Per-page data** for templates (stored in post meta)
- 🌐 **Global data** for partials like headers and footers (stored in options)
- 🚀 **Simple template functions** with ACF-like syntax
- 🗑️ **Clear buttons** for image and file fields
- 🔄 **Reset all data** button with confirmation
- 🔒 **Administrator-only access** for security
- 🧹 **Clean uninstall** removes all database records

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Template Functions](#template-functions)
- [Working with Partials](#working-with-partials)
- [Field Types](#field-types)
- [Examples](#examples)
- [Requirements](#requirements)

## Installation

### From WordPress Plugin Directory (Recommended)

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins → Add New**
3. Search for "Markdown FM"
4. Click **Install Now** next to the Markdown FM plugin
5. Click **Activate** after installation completes
6. Go to **Markdown FM** in the admin menu to configure your schemas

### Manual Installation

If you're installing from source or a ZIP file:

1. Upload the `markdown-fm` folder to `/wp-content/plugins/`
2. If installing from source, navigate to the plugin directory and install dependencies:
   ```bash
   cd wp-content/plugins/markdown-fm
   composer install
   ```
   **Note:** If you downloaded from the WordPress plugin directory, dependencies are already included.
3. Activate the plugin through the **Plugins** menu in WordPress
4. Go to **Markdown FM** in the admin menu to configure your schemas

## Quick Start

### 1. Enable YAML for a Template

1. Go to **Markdown FM** in your WordPress admin
2. Find your template in the "Page Templates" section
3. Toggle the "Enable YAML" switch
4. Click "Add Schema" or "Edit Schema"

### 2. Define a Schema

Here's an example schema for a landing page template:

```yaml
fields:
  - name: hero_title
  label: Hero Title
  type: string
  required: true
  options:
    maxlength: 100
  - name: hero_image
  label: Hero Image
  type: image
  - name: description
  label: Description
  type: text
  options:
    maxlength: 500
  - name: cta_button
  label: Call to Action Button
  type: object
  fields:
    - name: text
    label: Button Text
    type: string
    - name: url
    label: Button URL
    type: string
  - name: features
  label: Feature Sections
  type: block
  list: true
  blockKey: type
  blocks:
    - name: feature
    label: Feature Block
    fields:
      - name: title
      label: Feature Title
      type: string
      - name: description
      label: Feature Description
      type: text
      - name: icon
      label: Icon
      type: image
```

### 3. Edit Page Data

1. Create or edit a page/post
2. Select your template from the **Template** dropdown
3. The **Markdown FM Schema** meta box appears below the editor
4. Fill in your custom fields
5. Publish!

### 4. Use Fields in Your Template

In your theme template file (e.g., `page-landing.php`):

```php
<?php
// Get individual fields using short alias
$hero_title = mdfm_get_field('hero_title');
$hero_image = mdfm_get_field('hero_image');
$description = mdfm_get_field('description');
$cta = mdfm_get_field('cta_button');
$features = mdfm_get_field('features');
?>

<div class="hero">
  <?php if ($hero_image): ?>
  <img src="<?php echo esc_url($hero_image); ?>" alt="Hero">
  <?php endif; ?>

  <h1><?php echo esc_html($hero_title); ?></h1>
  <p><?php echo esc_html($description); ?></p>

  <?php if ($cta): ?>
  <a href="<?php echo esc_url($cta['url']); ?>" class="button">
    <?php echo esc_html($cta['text']); ?>
  </a>
  <?php endif; ?>
</div>

<?php if ($features): ?>
  <div class="features">
  <?php foreach ($features as $feature): ?>
    <div class="feature">
    <?php if (!empty($feature['icon'])): ?>
      <img src="<?php echo esc_url($feature['icon']); ?>" alt="">
    <?php endif; ?>
    <h3><?php echo esc_html($feature['title']); ?></h3>
    <p><?php echo esc_html($feature['description']); ?></p>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
```

## Template Functions

Markdown FM provides ACF-like template functions for retrieving your data:

### `mdfm_get_field($field_name, $post_id = null)`

Get a single field value.

```php
// For current page/post
$title = mdfm_get_field('hero_title');

// For specific post ID
$title = mdfm_get_field('hero_title', 123);

// For partials
$logo = mdfm_get_field('logo', 'partial:header.php');
$copyright = mdfm_get_field('copyright', 'partial:footer.php');

// For partials in subdirectories
$menu = mdfm_get_field('menu_items', 'partial:partials/navigation.php');
```

### `mdfm_get_fields($post_id = null)`

Get all fields at once.

```php
// For current page/post
$fields = mdfm_get_fields();
// Returns: ['hero_title' => 'Welcome', 'description' => '...', ...]

// For partials
$header_data = mdfm_get_fields('partial:header.php');
```

### `mdfm_has_field($field_name, $post_id = null)`

Check if a field exists and has a value.

```php
if (mdfm_has_field('hero_title')) {
  echo '<h1>' . esc_html(mdfm_get_field('hero_title')) . '</h1>';
}

// For partials
if (mdfm_has_field('logo', 'partial:header.php')) {
  $logo = mdfm_get_field('logo', 'partial:header.php');
}
```

**Long-form aliases** are also available:

- `markdown_fm_get_field()`
- `markdown_fm_get_fields()`
- `markdown_fm_has_field()`

## Working with Partials

Partials (like `header.php`, `footer.php`, `sidebar.php`) have **global, site-wide data** that you manage from the Markdown FM admin page.

### Partial Detection

Markdown FM automatically detects partials in two ways:

#### Automatic Detection (Standard Partials)

Common WordPress partials are detected automatically:

- `header.php`, `header-*.php`
- `footer.php`, `footer-*.php`
- `sidebar.php`, `sidebar-*.php`
- `content.php`, `content-*.php`
- `comments.php`, `searchform.php`

#### Manual Detection (Custom Partials)

For custom partials with non-standard names, add the `@mdfm` marker in the file header:

```php
<?php
/**
 * Custom Navigation Partial
 * @mdfm
 */

// Your template code here
```

The marker can appear anywhere in the **first 30 lines** of the file, in any comment style:

```php
<?php
// @mdfm - Enable Markdown FM for this partial

/* @mdfm */

/**
 * Some description
 * @mdfm
 */
```

After adding the marker, click the **"Refresh Template List"** button in the Markdown FM admin page.

### 1. Enable YAML for a Partial

1. Go to **Markdown FM** → scroll to **Template Partials** section
2. Toggle "Enable YAML" for your partial (e.g., `header.php`)
3. Click "Add Schema" to define fields
4. Click "Manage Data" to edit the global values for this partial

### 2. Example: Header Partial

**Schema** for `header.php`:

```yaml
fields:
  - name: logo
  label: Site Logo
  type: image
  - name: site_title
  label: Site Title
  type: string
  - name: show_search
  label: Show Search Bar
  type: boolean
  - name: menu_cta
  label: Menu CTA Button
  type: object
  fields:
    - name: text
    label: Button Text
    type: string
    - name: url
    label: Button URL
    type: string
```

**Usage** in `header.php`:

```php
<?php
$logo = mdfm_get_field('logo', 'partial:header.php');
$site_title = mdfm_get_field('site_title', 'partial:header.php');
$show_search = mdfm_get_field('show_search', 'partial:header.php');
$menu_cta = mdfm_get_field('menu_cta', 'partial:header.php');
?>

<header class="site-header">
  <div class="logo">
  <?php if ($logo): ?>
    <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($site_title); ?>">
  <?php else: ?>
    <h1><?php echo esc_html($site_title); ?></h1>
  <?php endif; ?>
  </div>

  <nav>
  <?php wp_nav_menu(['theme_location' => 'primary']); ?>
  </nav>

  <?php if ($menu_cta): ?>
  <a href="<?php echo esc_url($menu_cta['url']); ?>" class="cta-button">
    <?php echo esc_html($menu_cta['text']); ?>
  </a>
  <?php endif; ?>

  <?php if ($show_search): ?>
  <?php get_search_form(); ?>
  <?php endif; ?>
</header>
```

### 3. Example: Footer Partial

**Schema** for `footer.php`:

```yaml
fields:
  - name: copyright_text
  label: Copyright Text
  type: string
  default: "© 2024 My Company. All rights reserved."
  - name: social_links
  label: Social Media Links
  type: object
  fields:
    - name: facebook
    label: Facebook URL
    type: string
    - name: twitter
    label: Twitter URL
    type: string
    - name: instagram
    label: Instagram URL
    type: string
    - name: linkedin
    label: LinkedIn URL
    type: string
  - name: show_newsletter
  label: Show Newsletter Signup
  type: boolean
  default: true
  - name: footer_columns
  label: Footer Columns
  type: block
  list: true
  blockKey: type
  blocks:
    - name: links
    label: Link Column
    fields:
      - name: title
      label: Column Title
      type: string
      - name: links
      label: Links (one per line)
      type: text
```

**Usage** in `footer.php`:

```php
<?php
$copyright = mdfm_get_field('copyright_text', 'partial:footer.php');
$social = mdfm_get_field('social_links', 'partial:footer.php');
$show_newsletter = mdfm_get_field('show_newsletter', 'partial:footer.php');
$columns = mdfm_get_field('footer_columns', 'partial:footer.php');
?>

<footer class="site-footer">
  <?php if ($columns): ?>
    <div class="footer-columns">
      <?php foreach ($columns as $column): ?>
        <?php if ($column['type'] === 'links'): ?>
          <div class="footer-column">
            <h4><?php echo esc_html($column['title']); ?></h4>
            <?php
            $links = explode("\n", $column['links']);
            echo '<ul>';
            foreach ($links as $link) {
              echo '<li>' . esc_html(trim($link)) . '</li>';
            }
            echo '</ul>';
            ?>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($show_newsletter): ?>
    <div class="newsletter-signup">
      <!-- Your newsletter form here -->
    </div>
  <?php endif; ?>

  <?php if ($social): ?>
    <div class="social-links">
      <?php if (!empty($social['facebook'])): ?>
        <a href="<?php echo esc_url($social['facebook']); ?>">Facebook</a>
      <?php endif; ?>
      <?php if (!empty($social['twitter'])): ?>
        <a href="<?php echo esc_url($social['twitter']); ?>">Twitter</a>
      <?php endif; ?>
      <?php if (!empty($social['instagram'])): ?>
        <a href="<?php echo esc_url($social['instagram']); ?>">Instagram</a>
      <?php endif; ?>
      <?php if (!empty($social['linkedin'])): ?>
        <a href="<?php echo esc_url($social['linkedin']); ?>">LinkedIn</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="copyright">
    <?php echo esc_html($copyright); ?>
  </div>
</footer>
```

## Field Types

Markdown FM supports all field types from Pages CMS:

### String

Single-line text input.

```yaml
- name: title
  label: Page Title
  type: string
  options:
  minlength: 3
  maxlength: 100
```

**Options:**

- `minlength` - Minimum character length
- `maxlength` - Maximum character length

### Text

Multi-line textarea.

```yaml
- name: description
  label: Description
  type: text
  options:
  maxlength: 500
```

**Options:**

- `maxlength` - Maximum character length

### Rich Text

WordPress WYSIWYG editor with full formatting.

```yaml
- name: content
  label: Page Content
  type: rich-text
```

### Code

Code editor with syntax highlighting.

```yaml
- name: custom_css
  label: Custom CSS
  type: code
  options:
  language: css
```

**Options:**

- `language` - Syntax highlighting (html, css, javascript, php, python, etc.)

### Boolean

Checkbox for true/false values.

```yaml
- name: featured
  label: Featured Post
  type: boolean
  default: false
```

### Number

Number input with optional constraints.

```yaml
- name: price
  label: Price
  type: number
  options:
  min: 0
  max: 9999
```

**Options:**

- `min` - Minimum value
- `max` - Maximum value

### Date

Date picker with optional time.

```yaml
- name: event_date
  label: Event Date
  type: date
  options:
  time: true
```

**Options:**

- `time` - Set to `true` to include time selection

### Select

Dropdown selection.

```yaml
- name: category
  label: Category
  type: select
  options:
  multiple: false
  values:
    - value: news
    label: News
    - value: blog
    label: Blog Posts
    - value: events
    label: Events
```

**Options:**

- `multiple` - Allow multiple selections
- `values` - Array of options with `value` and `label` keys

### Image

WordPress media uploader for images.

```yaml
- name: featured_image
  label: Featured Image
  type: image
```

Returns the image URL as a string.

### File

WordPress media uploader for any file type.

```yaml
- name: pdf_brochure
  label: PDF Brochure
  type: file
```

Returns the file URL as a string.

### Object

Nested group of fields.

```yaml
- name: author
  label: Author Information
  type: object
  fields:
    - name: name
      label: Author Name
      type: string
    - name: bio
      label: Biography
      type: text
    - name: photo
      label: Author Photo
      type: image
    - name: social
      label: Social Links
      type: object
      fields:
        - name: twitter
          label: Twitter
          type: string
        - name: website
          label: Website
          type: string
```

**Access nested fields:**

```php
$author = mdfm_get_field('author');
echo $author['name'];
echo $author['bio'];
echo $author['social']['twitter'];
```

### Block

Repeater field with multiple block types. Perfect for flexible page builders!

```yaml
- name: page_sections
  label: Page Sections
  type: block
  list: true
  blockKey: type
  blocks:
    - name: hero
      label: Hero Section
      fields:
        - name: title
          label: Hero Title
          type: string
        - name: subtitle
          label: Subtitle
          type: string
        - name: background_image
          label: Background Image
          type: image
        - name: content
          label: Hero Content
          type: rich-text

    - name: two_column
      label: Two Column Layout
      fields:
        - name: left_content
          label: Left Column
          type: rich-text
        - name: right_content
          label: Right Column
          type: rich-text

    - name: gallery
      label: Image Gallery
      fields:
        - name: images
          label: Gallery Images
          type: text
          description: Enter image URLs, one per line

    - name: cta
      label: Call to Action
      fields:
        - name: heading
          label: CTA Heading
          type: string
        - name: button_text
          label: Button Text
          type: string
        - name: button_url
          label: Button URL
          type: string
```

**Properties:**

- `list: true` - Makes it repeatable
- `blockKey` - Field name that identifies block type (usually "type")
- `blocks` - Array of available block definitions

**Usage in templates:**

```php
<?php
$sections = mdfm_get_field('page_sections');

if ($sections) {
  foreach ($sections as $section) {
    switch ($section['type']) {
      case 'hero':
        ?>
        <section class="hero" style="background-image: url('<?php echo esc_url($section['background_image']); ?>')">
          <h1><?php echo esc_html($section['title']); ?></h1>
          <p class="subtitle"><?php echo esc_html($section['subtitle']); ?></p>
          <div class="content">
            <?php echo wp_kses_post($section['content']); ?>
          </div>
        </section>
        <?php
        break;

      case 'two_column':
        ?>
        <section class="two-column">
          <div class="column">
            <?php echo wp_kses_post($section['left_content']); ?>
          </div>
          <div class="column">
            <?php echo wp_kses_post($section['right_content']); ?>
          </div>
        </section>
        <?php
        break;

      case 'gallery':
        $images = explode("\n", $section['images']);
        ?>
        <section class="gallery">
          <?php foreach ($images as $image_url): ?>
            <img src="<?php echo esc_url(trim($image_url)); ?>" alt="">
          <?php endforeach; ?>
        </section>
        <?php
        break;

      case 'cta':
        ?>
        <section class="cta">
          <h2><?php echo esc_html($section['heading']); ?></h2>
          <a href="<?php echo esc_url($section['button_url']); ?>" class="button">
            <?php echo esc_html($section['button_text']); ?>
          </a>
        </section>
        <?php
        break;
    }
  }
}
?>
```

## Common Field Properties

All field types support these properties:

```yaml
- name: field_name # Required - Unique machine name
  label: Field Label # Display name in admin
  type: string # Required - Field type
  description: Help text # Optional help text for editors
  default: Default value # Default value for new entries
  required: true # Make field required (not enforced yet)
```

## Examples

### Blog Post Template

```yaml
fields:
  - name: featured_image
  label: Featured Image
  type: image
  description: Main image for the post
  - name: excerpt
  label: Custom Excerpt
  type: text
  options:
    maxlength: 300
  - name: reading_time
  label: Reading Time (minutes)
  type: number
  options:
    min: 1
    max: 60
  - name: show_toc
  label: Show Table of Contents
  type: boolean
  default: true
  - name: author_override
  label: Author Override
  type: object
  description: Override default author information
  fields:
    - name: name
    label: Name
    type: string
    - name: avatar
    label: Avatar
    type: image
    - name: bio
    label: Short Bio
    type: text
```

### Product Page Template

```yaml
fields:
  - name: product_info
  label: Product Information
  type: object
  fields:
    - name: name
    label: Product Name
    type: string
    options:
      maxlength: 100
    - name: sku
    label: SKU
    type: string
    - name: price
    label: Price
    type: number
    options:
      min: 0
    - name: sale_price
    label: Sale Price
    type: number
    options:
      min: 0
    - name: in_stock
    label: In Stock
    type: boolean
    default: true

  - name: product_images
  label: Product Images
  type: block
  list: true
  blockKey: type
  blocks:
    - name: image
    label: Product Image
    fields:
      - name: image_url
      label: Image
      type: image
      - name: caption
      label: Caption
      type: string

  - name: product_details
  label: Product Details
  type: object
  fields:
    - name: description
    label: Description
    type: rich-text
    - name: specifications
    label: Specifications
    type: text
    - name: materials
    label: Materials
    type: string
```

## Data Storage

### Page Templates

- **Location**: Post meta with key `_markdown_fm_data`
- **Scope**: Per post/page
- **Editing**: WordPress post/page editor

### Template Partials

- **Location**: WordPress options with key `markdown_fm_partial_data`
- **Scope**: Global (site-wide)
- **Editing**: Markdown FM admin page → "Manage Data" button

### Plugin Settings

- `markdown_fm_template_settings` - Tracks which templates have YAML enabled
- `markdown_fm_schemas` - Stores YAML schemas for each template/partial

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Composer (for installing dependencies)

## Dependencies

- **Symfony YAML Component** (^5.4|^6.0|^7.0) - YAML parsing

## Security

- All admin functionality requires `manage_options` capability (administrator by default)
- AJAX requests protected with WordPress nonces
- Data sanitized and escaped appropriately
- Input validation on all fields

## Uninstallation

When you **delete** (not deactivate) the plugin, it automatically cleans up:

- Template settings (`markdown_fm_template_settings`)
- Schemas (`markdown_fm_schemas`)
- Partial data (`markdown_fm_partial_data`)
- All post meta data (`_markdown_fm_data`)

## Troubleshooting

### Plugin activation error about Composer

**Solution**: Navigate to the plugin directory and run:

```bash
composer install
```

### Schema fields not appearing in post editor

1. Ensure YAML is enabled for the template
2. Verify you selected the correct page template
3. Check YAML syntax (use 2 spaces for indentation)
4. Clear browser cache and refresh

### YAML parsing errors

1. Validate YAML syntax with an online validator
2. Use consistent 2-space indentation (no tabs)
3. Check WordPress debug logs for detailed error messages

### Changes not saving

1. Check browser console for JavaScript errors
2. Ensure you have permission to edit posts/options
3. Verify WordPress AJAX is working

## Contributing

Contributions are welcome! If you'd like to contribute to Markdown FM:

1. Fork the repository at [https://github.com/maliMirkec/markdown-fm](https://github.com/maliMirkec/markdown-fm)
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please ensure your code follows WordPress coding standards and includes appropriate documentation.

## Reporting Issues

Found a bug or have a feature request? Please report it on GitHub:

**[Report an Issue on GitHub](https://github.com/maliMirkec/markdown-fm/issues)**

When reporting issues, please include:

- WordPress version
- PHP version
- Plugin version
- Steps to reproduce the issue
- Expected behavior vs actual behavior
- Any error messages or screenshots

## Credits

- **Author**: [Silvestar Bistrović](https://www.silvestar.codes)
- **Email**: me@silvestar.codes
- **GitHub**: [maliMirkec/markdown-fm](https://github.com/maliMirkec/markdown-fm)
- **Inspired by**: [PagesCMS](https://pagescms.org/) - Open-source CMS for static websites

## License

GPL v2 or later

## Changelog

### Version 1.0.0

- Initial release
- Support for 12+ field types from PagesCMS
- Template and partial support
- ACF-like template functions
- Block/repeater functionality
- WordPress media integration
- Administrator-only access
- Clean uninstall
- Clear buttons for image and file fields
- Reset All Data button for clearing all custom fields on a page
- Confirmation alerts for destructive actions

---

**Built with ❤️ for the WordPress community**
