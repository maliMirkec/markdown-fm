=== YAML Custom Fields ===
Contributors: starbist
Tags: yaml, frontmatter, custom-fields, cms, page-builder
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin for managing YAML frontmatter schemas in theme templates and partials.

== Description ==

YAML Custom Fields allows you to define structured content schemas with an intuitive interface and ACF-like template functions. Perfect for theme developers who want flexible, schema-based content management without the complexity.

= Features =

* Define YAML schemas for page templates and template partials
* 15+ field types including string, rich-text, images, blocks, taxonomies, data objects, and more
* Easy-to-use admin interface for managing schemas and data
* Per-page data for templates (stored in post meta)
* Global data for partials like headers and footers (stored in options)
* Data Objects for managing structured, reusable data (universities, companies, etc.)
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
* **Taxonomy** - WordPress categories, tags, or custom taxonomies with single/multiple selection
* **Post Type** - Dropdown to select registered post types (Post, Page, custom post types)
* **Data Object** - Reference to structured data objects managed independently (universities, companies, team members, etc.)
* **Image** - WordPress media uploader for images
* **File** - WordPress media uploader for any file
* **Object** - Nested group of fields
* **Block** - Repeatable blocks for flexible page builders

= Usage Example =

In your theme template:

`<?php
$hero_title = ycf_get_field('hero_title');
$hero_image = ycf_get_image('hero_image', null, 'full');
$category = ycf_get_term('category');
$post_type = ycf_get_post_type('content_type');
$university = ycf_get_data_object('university');
$features = ycf_get_field('features');
?>

<div class="hero">
  <?php if ($hero_image): ?>
    <img src="<?php echo esc_url($hero_image['url']); ?>" alt="<?php echo esc_attr($hero_image['alt']); ?>">
  <?php endif; ?>
  <h1><?php echo esc_html($hero_title); ?></h1>
  <?php if ($category): ?>
    <span class="category"><?php echo esc_html($category->name); ?></span>
  <?php endif; ?>
  <?php if ($university): ?>
    <p><?php echo esc_html($university['name']); ?></p>
  <?php endif; ?>
</div>`

== Installation ==

= From WordPress Plugin Directory =

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins → Add New**
3. Search for "YAML Custom Fields"
4. Click **Install Now** next to the YAML Custom Fields plugin
5. Click **Activate** after installation completes
6. Go to **YAML Custom Fields** in the admin menu to configure your schemas

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin dashboard
3. Navigate to **Plugins → Add New → Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Click **Activate** after installation completes
6. Go to **YAML Custom Fields** in the admin menu to configure your schemas

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* The plugin includes all necessary dependencies

== Frequently Asked Questions ==

= What is YAML frontmatter? =

YAML frontmatter is a structured way to define metadata for content. It's commonly used in static site generators and headless CMS systems. YAML Custom Fields brings this approach to WordPress themes.

= How is this different from ACF? =

While ACF is a comprehensive custom fields solution, YAML Custom Fields focuses on YAML-based schemas that are portable and version-controllable. It's ideal for developers who prefer code-first approaches and want simpler, more predictable data structures.

= Can I use this with my existing theme? =

Yes! YAML Custom Fields works with any WordPress theme. You define schemas for your templates and use simple PHP functions to retrieve the data in your template files.

= Does this work with Gutenberg? =

Yes, YAML Custom Fields is compatible with both the Classic and Block (Gutenberg) editors. The custom fields appear below the editor regardless of which editor you're using.

= What happens to my data if I deactivate the plugin? =

Your data remains in the database. Only when you **delete** the plugin (not just deactivate) will it clean up all settings, schemas, and custom field data.

= Can I use this for WooCommerce products? =

Currently, YAML Custom Fields supports pages and posts only. Support for custom post types including WooCommerce products may be added in future versions.

= How do I report bugs or request features? =

Please visit the [GitHub repository](https://github.com/maliMirkec/yaml-custom-fields) to report issues or request features.

== Screenshots ==

1. Main YAML Custom Fields admin page showing page templates and template partials with enable/disable toggles
2. Schema editor for main page templates with YAML syntax for defining custom fields
3. Schema editor for partial templates (headers, footers, etc.)
4. Partial data editor for managing global content in template partials
5. Documentation page with comprehensive guides and examples

== Changelog ==

= 1.0.0 =
* Initial release
* Support for 15+ field types
* Template and partial support
* ACF-like template functions with context_data parameter for block fields
* Taxonomy field type for categories, tags, and custom taxonomies (single/multiple selection)
* Post Type field type for selecting registered WordPress post types
* Data Objects feature for managing structured, reusable data (universities, companies, etc.)
* Enhanced helper functions: ycf_get_field(), ycf_get_image(), ycf_get_file(), ycf_get_term(), ycf_get_post_type(), ycf_get_data_object(), ycf_get_data_objects()
* Block/repeater functionality with context-aware field access
* WordPress media integration
* Administrator-only access
* Clean uninstall
* Clear buttons for image and file fields
* Reset All Data button for clearing all custom fields
* Confirmation alerts for destructive actions
* Copy snippet buttons for all field types with complete function signatures

== Upgrade Notice ==

= 1.0.0 =
Initial release of YAML Custom Fields.

== Developer Documentation ==

= Template Functions =

**Get a single field value:**

`$value = ycf_get_field('field_name');
$value = ycf_get_field('field_name', 123); // Specific post ID
$value = ycf_get_field('logo', 'partial:header.php'); // From partial
$value = ycf_get_field('title', null, $block); // From block context`

**Get image field with details:**

`$image = ycf_get_image('field_name', null, 'full');
$image = ycf_get_image('field_name', 123, 'thumbnail'); // Specific post ID
$image = ycf_get_image('icon', null, 'medium', $block); // From block context

// Returns: array('id', 'url', 'alt', 'title', 'caption', 'description', 'width', 'height')`

**Get file field with details:**

`$file = ycf_get_file('field_name', null);
$file = ycf_get_file('field_name', 123); // Specific post ID
$file = ycf_get_file('document', null, $block); // From block context

// Returns: array('id', 'url', 'path', 'filename', 'filesize', 'mime_type', 'title')`

**Get taxonomy field (term or terms):**

`$term = ycf_get_term('field_name', null);
$term = ycf_get_term('field_name', 123); // Specific post ID
$term = ycf_get_term('category', null, $block); // From block context

// Returns: WP_Term object or array of WP_Term objects (for multiple selection)`

**Get post type field:**

`$post_type = ycf_get_post_type('field_name', null);
$post_type = ycf_get_post_type('field_name', 123); // Specific post ID
$post_type = ycf_get_post_type('content_type', null, $block); // From block context

// Returns: WP_Post_Type object or null`

**Get data object field:**

`$university = ycf_get_data_object('field_name', null);
$university = ycf_get_data_object('field_name', 123); // Specific post ID
$university = ycf_get_data_object('university', null, $block); // From block context

// Returns: Array with data object entry fields or null`

**Get all entries of a data object type:**

`$all_universities = ycf_get_data_objects('universities');
foreach ($all_universities as $entry_id => $university) {
    echo esc_html($university['name']);
}

// Returns: Array of all entries for the specified data object type`

**Get all fields:**

`$fields = ycf_get_fields();
$fields = ycf_get_fields(123); // Specific post ID
$fields = ycf_get_fields('partial:footer.php'); // From partial`

**Check if field exists:**

`if (ycf_has_field('hero_title')) {
    echo ycf_get_field('hero_title');
}`

**Working with Block fields:**

`$blocks = ycf_get_field('features');

if (!empty($blocks)) {
    foreach ($blocks as $block) {
        // Access nested fields using context_data parameter
        $title = ycf_get_field('title', null, $block);
        $icon = ycf_get_image('icon', null, 'thumbnail', $block);
        $category = ycf_get_term('category', null, $block);

        echo '<h3>' . esc_html($title) . '</h3>';
        if ($icon) {
            echo '<img src="' . esc_url($icon['url']) . '">';
        }
        if ($category) {
            echo '<span>' . esc_html($category->name) . '</span>';
        }
    }
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
  - name: category
    label: Category
    type: taxonomy
    options:
      taxonomy: category
  - name: tags
    label: Tags
    type: taxonomy
    multiple: true
    options:
      taxonomy: post_tag
  - name: content_type
    label: Content Type
    type: post_type
  - name: university
    label: University
    type: data_object
    options:
      object_type: universities
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
          - name: icon
            label: Icon
            type: image
          - name: description
            label: Description
            type: text`

= Working with Partials =

For custom partials, add the @ycf marker in the file header:

`<?php
/**
 * Custom Navigation Partial
 * @ycf
 */`

Then click "Refresh Template List" in the YAML Custom Fields admin page.

= Data Storage =

* **Page/Post data:** Stored in post meta with key `_yaml_cf_data`
* **Partial data:** Stored in options table with key `yaml_cf_partial_data`
* **Schemas:** Stored in options table with key `yaml_cf_schemas`
* **Data Object Types:** Stored in options table with key `yaml_cf_data_object_types`
* **Data Object Entries:** Stored in options table with keys `yaml_cf_data_object_entries_{type_slug}`

== Privacy Policy ==

YAML Custom Fields does not collect, store, or transmit any user data outside of your WordPress installation. All data is stored locally in your WordPress database.

== Third-Party Libraries ==

This plugin includes the following third-party libraries:

* **Symfony YAML Component** (v5.4) - Licensed under MIT License (GPL-compatible)
  - Homepage: https://symfony.com/components/Yaml
  - License: https://github.com/symfony/yaml/blob/5.4/LICENSE

== Credits ==

* Author: [Silvestar Bistrovic](https://www.silvestar.codes)

== Support ==

For documentation, examples, and support, visit:
* [Plugin Documentation](https://github.com/maliMirkec/yaml-custom-fields)
* [Report Issues](https://github.com/maliMirkec/yaml-custom-fields/issues)
