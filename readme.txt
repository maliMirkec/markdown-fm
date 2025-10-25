=== Markdown FM ===
Contributors: starbist
Tags: yaml, frontmatter, custom-fields, cms, page-builder
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin for managing YAML frontmatter schemas in theme templates and partials. Inspired by PagesCMS.

== Description ==

Markdown FM allows you to define structured content schemas with an intuitive interface and ACF-like template functions. Perfect for theme developers who want flexible, schema-based content management without the complexity.

= Features =

* Define YAML schemas for page templates and template partials
* 12+ field types including string, rich-text, images, blocks, and more
* Easy-to-use admin interface for managing schemas and data
* Per-page data for templates (stored in post meta)
* Global data for partials like headers and footers (stored in options)
* Simple template functions with ACF-like syntax
* Administrator-only access for security
* Clean uninstall removes all database records

= Supported Field Types =

* **String** - Single-line text with min/max length
* **Text** - Multi-line textarea
* **Rich Text** - WordPress WYSIWYG editor
* **Code** - Code editor with syntax highlighting
* **Boolean** - Checkbox for true/false values
* **Number** - Number input with min/max constraints
* **Date** - Date picker with optional time
* **Select** - Dropdown with single/multiple selection
* **Image** - WordPress media uploader for images
* **File** - WordPress media uploader for any file
* **Object** - Nested group of fields
* **Block** - Repeatable blocks for flexible page builders

= Usage Example =

In your theme template:

`<?php
$hero_title = mdfm_get_field('hero_title');
$hero_image = mdfm_get_image('hero_image');
$features = mdfm_get_field('features');
?>

<div class="hero">
  <?php if ($hero_image): ?>
    <img src="<?php echo esc_url($hero_image['url']); ?>" alt="<?php echo esc_attr($hero_image['alt']); ?>">
  <?php endif; ?>
  <h1><?php echo esc_html($hero_title); ?></h1>
</div>`

= Inspired By =

This plugin is inspired by [PagesCMS](https://pagescms.org/), an open-source CMS for static websites with YAML-based content schemas.

== Installation ==

= From WordPress Plugin Directory =

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins → Add New**
3. Search for "Markdown FM"
4. Click **Install Now** next to the Markdown FM plugin
5. Click **Activate** after installation completes
6. Go to **Markdown FM** in the admin menu to configure your schemas

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin dashboard
3. Navigate to **Plugins → Add New → Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Click **Activate** after installation completes
6. Go to **Markdown FM** in the admin menu to configure your schemas

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* The plugin includes all necessary dependencies

== Frequently Asked Questions ==

= What is YAML frontmatter? =

YAML frontmatter is a structured way to define metadata for content. It's commonly used in static site generators and headless CMS systems. Markdown FM brings this approach to WordPress themes.

= How is this different from ACF? =

While ACF is a comprehensive custom fields solution, Markdown FM focuses on YAML-based schemas that are portable and version-controllable. It's ideal for developers who prefer code-first approaches and want simpler, more predictable data structures.

= Can I use this with my existing theme? =

Yes! Markdown FM works with any WordPress theme. You define schemas for your templates and use simple PHP functions to retrieve the data in your template files.

= Does this work with Gutenberg? =

Yes, Markdown FM is compatible with both the Classic and Block (Gutenberg) editors. The custom fields appear below the editor regardless of which editor you're using.

= What happens to my data if I deactivate the plugin? =

Your data remains in the database. Only when you **delete** the plugin (not just deactivate) will it clean up all settings, schemas, and custom field data.

= Can I use this for WooCommerce products? =

Currently, Markdown FM supports pages and posts only. Support for custom post types including WooCommerce products may be added in future versions.

= How do I report bugs or request features? =

Please visit the [GitHub repository](https://github.com/maliMirkec/markdown-fm) to report issues or request features.

== Screenshots ==

1. Main Markdown FM admin page showing page templates and template partials with enable/disable toggles
2. Schema editor for main page templates with YAML syntax for defining custom fields
3. Schema editor for partial templates (headers, footers, etc.)
4. Partial data editor for managing global content in template partials
5. Documentation page with comprehensive guides and examples

== Changelog ==

= 1.0.0 =
* Initial release
* Support for 12+ field types from PagesCMS
* Template and partial support
* ACF-like template functions
* Block/repeater functionality
* WordPress media integration
* Administrator-only access
* Clean uninstall
* Clear buttons for image and file fields
* Reset All Data button for clearing all custom fields
* Confirmation alerts for destructive actions

== Upgrade Notice ==

= 1.0.0 =
Initial release of Markdown FM.

== Developer Documentation ==

= Template Functions =

**Get a single field value:**

`$value = mdfm_get_field('field_name');
$value = mdfm_get_field('field_name', 123); // Specific post ID
$value = mdfm_get_field('logo', 'partial:header.php'); // From partial`

**Get all fields:**

`$fields = mdfm_get_fields();
$fields = mdfm_get_fields(123); // Specific post ID
$fields = mdfm_get_fields('partial:footer.php'); // From partial`

**Check if field exists:**

`if (mdfm_has_field('hero_title')) {
    echo mdfm_get_field('hero_title');
}`

= Sample YAML Schema =

`fields:
  - name: hero_title
    label: Hero Title
    type: string
    required: true
    options:
      maxlength: 100
  - name: hero_image
    label: Hero Image
    type: image
  - name: features
    label: Features
    type: block
    list: true
    blockKey: type
    blocks:
      - name: feature
        label: Feature Block
        fields:
          - name: title
            label: Title
            type: string
          - name: description
            label: Description
            type: text`

= Working with Partials =

For custom partials, add the @mdfm marker in the file header:

`<?php
/**
 * Custom Navigation Partial
 * @mdfm
 */`

Then click "Refresh Template List" in the Markdown FM admin page.

= Data Storage =

* **Page/Post data:** Stored in post meta with key `_markdown_fm_data`
* **Partial data:** Stored in options table with key `markdown_fm_partial_data`
* **Schemas:** Stored in options table with key `markdown_fm_schemas`

== Privacy Policy ==

Markdown FM does not collect, store, or transmit any user data outside of your WordPress installation. All data is stored locally in your WordPress database.

== Third-Party Libraries ==

This plugin includes the following third-party libraries:

* **Symfony YAML Component** (v5.4) - Licensed under MIT License (GPL-compatible)
  - Homepage: https://symfony.com/components/Yaml
  - License: https://github.com/symfony/yaml/blob/5.4/LICENSE

== Credits ==

* Author: [Silvestar Bistrovic](https://www.silvestar.codes)
* Inspired by: [PagesCMS](https://pagescms.org/)

== Support ==

For documentation, examples, and support, visit:
* [Plugin Documentation](https://github.com/maliMirkec/markdown-fm)
* [Report Issues](https://github.com/maliMirkec/markdown-fm/issues)
