# Markdown FM Plugin

A WordPress plugin for managing YAML frontmatter schemas in theme templates. Inspired by [PagesCMS](https://pagescms.org/docs/).

## Author
**Silvestar Bistrović**
Email: me@silvestar.codes
Website: https://www.silvestar.codes

## Installation

1. Create the plugin directory structure:
```
wp-content/plugins/markdown-fm/
├── markdown-fm.php (main plugin file)
├── assets/
│   ├── admin.css
│   └── admin.js
└── templates/
    └── admin-page.php
```

2. Copy the files to their respective locations:
   - `markdown-fm.php` → root of plugin folder
   - `admin.css` → `assets/admin.css`
   - `admin.js` → `assets/admin.js`
   - `admin-page.php` → `templates/admin-page.php`

3. Activate the plugin from WordPress Admin → Plugins

## Features

- **Template Management**: Automatically detects all theme templates
- **YAML Schema Support**: Define custom schemas for each template
- **Rich Field Types**: Support for 12+ field types including repeaters
- **Meta Box Integration**: Schemas appear as custom fields in the post editor
- **Clean Uninstall**: Removes all database records when deleted

## Supported Field Types

### Basic Fields
- **boolean**: Checkbox input
- **string**: Single-line text (supports `minlength`, `maxlength`)
- **text**: Multi-line textarea (supports `maxlength`)
- **number**: Number input (supports `min`, `max`)
- **date**: Date picker (supports `time: true` for datetime)

### Rich Content
- **rich-text**: WordPress WYSIWYG editor
- **code**: Code editor (supports `language` option)

### Selection
- **select**: Dropdown menu (supports `multiple` and custom `values`)

### Media
- **image**: WordPress media uploader for images
- **file**: WordPress media uploader for any file type

### Advanced
- **object**: Group of nested fields
- **block**: Repeater field with multiple block types (set `list: true`)

## Usage

### Step 1: Enable YAML for a Template

1. Go to **WordPress Admin → Markdown FM**
2. Find your template in the list
3. Toggle the **Enable YAML** switch
4. Click **Add Schema**

### Step 2: Define a Schema

Add your YAML schema in the modal editor. Example:

```yaml
fields:
  - name: title
    label: Page Title
    type: string
    options:
      maxlength: 100

  - name: featured
    label: Featured Post
    type: boolean
    default: false

  - name: publish_date
    label: Publish Date
    type: date
    options:
      time: true
      format: dd-MM-yyyy HH:mm

  - name: description
    label: Description
    type: text
    options:
      minlength: 20
      maxlength: 160

  - name: author
    label: Author
    type: select
    options:
      multiple: false
      values:
        - value: john
          label: John Doe
        - value: jane
          label: Jane Smith

  - name: featured_image
    label: Featured Image
    type: image

  - name: sections
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
          - name: content
            label: Hero Content
            type: rich-text

      - name: text
        label: Text Block
        fields:
          - name: content
            label: Content
            type: rich-text
```

### Step 3: Use in Pages/Posts

1. Create or edit a page/post
2. Select your template from the **Template** dropdown
3. Scroll down to see the **Markdown FM Schema** meta box
4. Fill in your custom fields
5. Publish!

## Schema Examples

### Simple Contact Form

```yaml
fields:
  - name: contact
    label: Contact Information
    type: object
    fields:
      - name: first_name
        label: First Name
        type: string
      - name: last_name
        label: Last Name
        type: string
      - name: email
        label: Email Address
        type: string
      - name: phone
        label: Phone Number
        type: string
```

### Product Page

```yaml
fields:
  - name: product_name
    label: Product Name
    type: string
    options:
      maxlength: 100

  - name: price
    label: Price
    type: number
    options:
      min: 0

  - name: in_stock
    label: In Stock
    type: boolean
    default: true

  - name: images
    label: Product Images
    type: block
    list: true
    blockKey: type
    blocks:
      - name: image
        label: Add Image
        fields:
          - name: image_url
            label: Image
            type: image
          - name: caption
            label: Caption
            type: string
```

### Blog Post with Sections

```yaml
fields:
  - name: excerpt
    label: Excerpt
    type: text
    options:
      maxlength: 200

  - name: reading_time
    label: Reading Time (minutes)
    type: number
    options:
      min: 1

  - name: cover_image
    label: Cover Image
    type: image

  - name: content_sections
    label: Content Sections
    type: block
    list: true
    blockKey: type
    blocks:
      - name: paragraph
        label: Paragraph
        fields:
          - name: content
            label: Content
            type: rich-text

      - name: quote
        label: Quote Block
        fields:
          - name: quote
            label: Quote Text
            type: text
          - name: author
            label: Quote Author
            type: string

      - name: code_snippet
        label: Code Snippet
        fields:
          - name: code
            label: Code
            type: code
            options:
              language: javascript
```

## Accessing Data in Templates

Retrieve the saved data in your theme templates:

```php
<?php
$markdown_fm_data = get_post_meta(get_the_ID(), '_markdown_fm_data', true);

if (!empty($markdown_fm_data)) {
    // Access simple fields
    $title = isset($markdown_fm_data['title']) ? $markdown_fm_data['title'] : '';
    $featured = isset($markdown_fm_data['featured']) ? $markdown_fm_data['featured'] : false;

    // Access object fields
    if (isset($markdown_fm_data['contact'])) {
        $first_name = $markdown_fm_data['contact']['first_name'];
        $email = $markdown_fm_data['contact']['email'];
    }

    // Access block/repeater fields
    if (isset($markdown_fm_data['sections']) && is_array($markdown_fm_data['sections'])) {
        foreach ($markdown_fm_data['sections'] as $section) {
            $type = $section['type']; // Block type

            if ($type === 'hero') {
                echo '<div class="hero-section">';
                echo '<h1>' . esc_html($section['title']) . '</h1>';
                echo '<div>' . wp_kses_post($section['content']) . '</div>';
                echo '</div>';
            } elseif ($type === 'text') {
                echo '<div class="text-block">';
                echo wp_kses_post($section['content']);
                echo '</div>';
            }
        }
    }
}
?>
```

## Database Structure

The plugin stores data in two WordPress options:

1. **markdown_fm_template_settings**: Tracks which templates have YAML enabled
   ```php
   [
       'page.php' => true,
       'single.php' => false,
       'template-custom.php' => true
   ]
   ```

2. **markdown_fm_schemas**: Stores YAML schemas for each template
   ```php
   [
       'page.php' => 'fields:...',
       'template-custom.php' => 'fields:...'
   ]
   ```

Post meta is stored with the key `_markdown_fm_data` containing all field values.

## Advanced Features

### Repeater Blocks with BlockKey

The `blockKey` property determines which field identifies the block type:

```yaml
fields:
  - name: sections
    label: Page Sections
    type: block
    list: true
    blockKey: type  # This field will store the block identifier
    blocks:
      - name: hero
        label: Hero Section
        fields:
          - name: title
            label: Title
            type: string
```

### Nested Objects

Create complex data structures with nested objects:

```yaml
fields:
  - name: settings
    label: Page Settings
    type: object
    fields:
      - name: seo
        label: SEO Settings
        type: object
        fields:
          - name: meta_title
            label: Meta Title
            type: string
          - name: meta_description
            label: Meta Description
            type: text
      - name: layout
        label: Layout Options
        type: object
        fields:
          - name: sidebar
            label: Show Sidebar
            type: boolean
          - name: width
            label: Content Width
            type: select
            options:
              values:
                - value: narrow
                  label: Narrow
                - value: wide
                  label: Wide
                - value: full
                  label: Full Width
```

### Field Validation Options

Add validation to your fields:

```yaml
fields:
  # String length validation
  - name: username
    label: Username
    type: string
    options:
      minlength: 3
      maxlength: 20

  # Number range validation
  - name: age
    label: Age
    type: number
    options:
      min: 18
      max: 100

  # Textarea character limit
  - name: bio
    label: Biography
    type: text
    options:
      maxlength: 500
```

## Troubleshooting

### Schemas Not Appearing

1. **Check template is enabled**: Ensure the YAML toggle is ON for your template
2. **Verify template selection**: Make sure the correct template is selected for your page/post
3. **Check schema format**: Validate your YAML syntax (indentation matters!)

### Fields Not Saving

1. **Check user permissions**: Ensure you have permission to edit posts
2. **Verify nonce**: The plugin uses WordPress nonces for security
3. **Check browser console**: Look for JavaScript errors

### YAML Parsing Issues

If you don't have the PHP YAML extension installed, the plugin uses a simple parser. For complex schemas, consider installing the PHP YAML extension:

```bash
# Ubuntu/Debian
sudo apt-get install php-yaml

# CentOS/RHEL
sudo yum install php-yaml
```

## Uninstallation

When you delete the plugin:
- `markdown_fm_template_settings` option is removed
- `markdown_fm_schemas` option is removed
- All `_markdown_fm_data` post meta is deleted

**Note**: This happens only when you DELETE the plugin, not when you deactivate it.

## Hooks for Developers

### Accessing Data Programmatically

```php
// Get all schemas
$schemas = get_option('markdown_fm_schemas', []);

// Get schema for specific template
$page_schema = isset($schemas['page.php']) ? $schemas['page.php'] : '';

// Get all enabled templates
$enabled_templates = get_option('markdown_fm_template_settings', []);

// Get field data for a post
$post_id = 123;
$field_data = get_post_meta($post_id, '_markdown_fm_data', true);
```

### Helper Function Example

Add this to your theme's `functions.php`:

```php
/**
 * Get Markdown FM field value
 */
function get_markdown_fm_field($field_name, $post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $data = get_post_meta($post_id, '_markdown_fm_data', true);

    if (isset($data[$field_name])) {
        return $data[$field_name];
    }

    return null;
}

// Usage in templates:
$title = get_markdown_fm_field('title');
$sections = get_markdown_fm_field('sections');
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- jQuery (included with WordPress)
- WordPress Media Library

## Support

For issues, questions, or contributions:
- Email: me@silvestar.codes
- Website: https://www.silvestar.codes

## License

GPL v2 or later

## Changelog

### Version 1.0.0
- Initial release
- Support for all PagesCMS field types
- Block/repeater functionality
- Media uploader integration
- Clean uninstall functionality

---

**Inspired by [PagesCMS](https://pagescms.org/docs/)** - A simple, lightweight CMS for static sites.
