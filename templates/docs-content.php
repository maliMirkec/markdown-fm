<?php
/**
 * Documentation Content (HTML)
 * File: templates/docs-content.php
 */

if (!defined('ABSPATH')) {
  exit;
}
?>

<p>A WordPress plugin for managing YAML frontmatter schemas in theme templates and partials. Inspired by <a href="https://pagescms.org/docs/" target="_blank">PagesCMS</a>, Markdown FM allows you to define structured content schemas with an intuitive interface and ACF-like template functions.</p>

<blockquote>Vibe-coded with Claude.</blockquote>

<h2>Features</h2>

<ul>
  <li>🎨 <strong>Define YAML schemas</strong> for page templates and template partials</li>
  <li>📝 <strong>12+ field types</strong> including string, rich-text, images, blocks, and more</li>
  <li>🔧 <strong>Beautiful admin interface</strong> with branded header and intuitive controls</li>
  <li>🎯 <strong>Per-page data</strong> for templates (stored in post meta)</li>
  <li>🌐 <strong>Global data</strong> for partials like headers and footers (stored in options)</li>
  <li>🚀 <strong>Simple template functions</strong> with ACF-like syntax</li>
  <li>🗑️ <strong>Clear buttons</strong> for image and file fields</li>
  <li>🔄 <strong>Reset all data</strong> button with confirmation</li>
  <li>🔒 <strong>Administrator-only access</strong> for security</li>
  <li>🧹 <strong>Clean uninstall</strong> removes all database records</li>
</ul>

<h2>Installation</h2>

<h3>From WordPress Plugin Directory (Recommended)</h3>

<ol>
  <li>Log in to your WordPress admin dashboard</li>
  <li>Navigate to <strong>Plugins → Add New</strong></li>
  <li>Search for "Markdown FM"</li>
  <li>Click <strong>Install Now</strong> next to the Markdown FM plugin</li>
  <li>Click <strong>Activate</strong> after installation completes</li>
  <li>Go to <strong>Markdown FM</strong> in the admin menu to configure your schemas</li>
</ol>

<h3>Manual Installation</h3>

<p>If you're installing from source or a ZIP file:</p>

<ol>
  <li>Upload the <code>markdown-fm</code> folder to <code>/wp-content/plugins/</code></li>
  <li>If installing from source, navigate to the plugin directory and install dependencies:
    <pre><code>cd wp-content/plugins/markdown-fm
composer install</code></pre>
    <strong>Note:</strong> If you downloaded from the WordPress plugin directory, dependencies are already included.
  </li>
  <li>Activate the plugin through the <strong>Plugins</strong> menu in WordPress</li>
  <li>Go to <strong>Markdown FM</strong> in the admin menu to configure your schemas</li>
</ol>

<h2>Quick Start</h2>

<h3>1. Enable YAML for a Template</h3>

<ol>
  <li>Go to <strong>Markdown FM</strong> in your WordPress admin</li>
  <li>Find your template in the "Page Templates" section</li>
  <li>Toggle the "Enable YAML" switch</li>
  <li>Click "Add Schema" or "Edit Schema"</li>
</ol>

<h3>2. Define a Schema</h3>

<p>Here's an example schema for a landing page template:</p>

<pre><code>fields:
  - name: hero_title
    label: Page Title
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
            type: image</code></pre>

<h3>3. Edit Page Data</h3>

<ol>
  <li>Create or edit a page/post</li>
  <li>Select your template from the <strong>Template</strong> dropdown</li>
  <li>The <strong>Markdown FM Schema</strong> meta box appears below the editor</li>
  <li>Fill in your custom fields</li>
  <li>Publish!</li>
</ol>

<h3>4. Use Fields in Your Template</h3>

<p>In your theme template file (e.g., <code>page-landing.php</code>):</p>

<pre><code>&lt;?php
// Get individual fields using short alias
$hero_title = mdfm_get_field('hero_title');
$hero_image = mdfm_get_field('hero_image');
$description = mdfm_get_field('description');
$cta = mdfm_get_field('cta_button');
$features = mdfm_get_field('features');
?&gt;

&lt;div class="hero"&gt;
  &lt;?php if ($hero_image): ?&gt;
    &lt;img src="&lt;?php echo esc_url($hero_image); ?&gt;" alt="Hero"&gt;
  &lt;?php endif; ?&gt;

  &lt;h1&gt;&lt;?php echo esc_html($hero_title); ?&gt;&lt;/h1&gt;
  &lt;p&gt;&lt;?php echo esc_html($description); ?&gt;&lt;/p&gt;

  &lt;?php if ($cta): ?&gt;
    &lt;a href="&lt;?php echo esc_url($cta['url']); ?&gt;" class="button"&gt;
      &lt;?php echo esc_html($cta['text']); ?&gt;
    &lt;/a&gt;
  &lt;?php endif; ?&gt;
&lt;/div&gt;

&lt;?php if ($features): ?&gt;
  &lt;div class="features"&gt;
    &lt;?php foreach ($features as $feature): ?&gt;
      &lt;div class="feature"&gt;
        &lt;?php if (!empty($feature['icon'])): ?&gt;
          &lt;img src="&lt;?php echo esc_url($feature['icon']); ?&gt;" alt=""&gt;
        &lt;?php endif; ?&gt;
        &lt;h3&gt;&lt;?php echo esc_html($feature['title']); ?&gt;&lt;/h3&gt;
        &lt;p&gt;&lt;?php echo esc_html($feature['description']); ?&gt;&lt;/p&gt;
      &lt;/div&gt;
    &lt;?php endforeach; ?&gt;
  &lt;/div&gt;
&lt;?php endif; ?&gt;</code></pre>

<h2>Template Functions</h2>

<p>Markdown FM provides ACF-like template functions for retrieving your data:</p>

<h3><code>mdfm_get_field($field_name, $post_id = null)</code></h3>

<p>Get a single field value.</p>

<pre><code>// For current page/post
$title = mdfm_get_field('hero_title');

// For specific post ID
$title = mdfm_get_field('hero_title', 123);

// For partials
$logo = mdfm_get_field('logo', 'partial:header.php');
$copyright = mdfm_get_field('copyright', 'partial:footer.php');

// For partials in subdirectories
$menu = mdfm_get_field('menu_items', 'partial:partials/navigation.php');</code></pre>

<h3><code>mdfm_get_fields($post_id = null)</code></h3>

<p>Get all fields at once.</p>

<pre><code>// For current page/post
$fields = mdfm_get_fields();
// Returns: ['hero_title' => 'Welcome', 'description' => '...', ...]

// For partials
$header_data = mdfm_get_fields('partial:header.php');</code></pre>

<h3><code>mdfm_has_field($field_name, $post_id = null)</code></h3>

<p>Check if a field exists and has a value.</p>

<pre><code>if (mdfm_has_field('hero_title')) {
  echo '&lt;h1&gt;' . esc_html(mdfm_get_field('hero_title')) . '&lt;/h1&gt;';
}

// For partials
if (mdfm_has_field('logo', 'partial:header.php')) {
  $logo = mdfm_get_field('logo', 'partial:header.php');
}</code></pre>

<p><strong>Long-form aliases</strong> are also available:</p>
<ul>
  <li><code>markdown_fm_get_field()</code></li>
  <li><code>markdown_fm_get_fields()</code></li>
  <li><code>markdown_fm_has_field()</code></li>
</ul>

<h2>Working with Partials</h2>

<p>Partials (like <code>header.php</code>, <code>footer.php</code>, <code>sidebar.php</code>) have <strong>global, site-wide data</strong> that you manage from the Markdown FM admin page.</p>

<h3>Partial Detection</h3>

<p>Markdown FM automatically detects partials in two ways:</p>

<h4>Automatic Detection (Standard Partials)</h4>

<p>Common WordPress partials are detected automatically:</p>
<ul>
  <li><code>header.php</code>, <code>header-*.php</code></li>
  <li><code>footer.php</code>, <code>footer-*.php</code></li>
  <li><code>sidebar.php</code>, <code>sidebar-*.php</code></li>
  <li><code>content.php</code>, <code>content-*.php</code></li>
  <li><code>comments.php</code>, <code>searchform.php</code></li>
</ul>

<h4>Manual Detection (Custom Partials)</h4>

<p>For custom partials with non-standard names, add the <code>@mdfm</code> marker in the file header:</p>

<pre><code>&lt;?php
/**
 * Custom Navigation Partial
 * @mdfm
 */

// Your template code here</code></pre>

<p>The marker can appear anywhere in the <strong>first 30 lines</strong> of the file, in any comment style:</p>

<pre><code>&lt;?php
// @mdfm - Enable Markdown FM for this partial

/* @mdfm */

/**
 * Some description
 * @mdfm
 */</code></pre>

<p>After adding the marker, click the <strong>"Refresh Template List"</strong> button in the Markdown FM admin page.</p>

<h3>Example: Header Partial</h3>

<p><strong>Schema</strong> for <code>header.php</code>:</p>

<pre><code>fields:
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
        type: string</code></pre>

<p><strong>Usage</strong> in <code>header.php</code>:</p>

<pre><code>&lt;?php
$logo = mdfm_get_field('logo', 'partial:header.php');
$site_title = mdfm_get_field('site_title', 'partial:header.php');
$show_search = mdfm_get_field('show_search', 'partial:header.php');
$menu_cta = mdfm_get_field('menu_cta', 'partial:header.php');
?&gt;

&lt;header class="site-header"&gt;
  &lt;div class="logo"&gt;
    &lt;?php if ($logo): ?&gt;
      &lt;img src="&lt;?php echo esc_url($logo); ?&gt;" alt="&lt;?php echo esc_attr($site_title); ?&gt;"&gt;
    &lt;?php else: ?&gt;
      &lt;h1&gt;&lt;?php echo esc_html($site_title); ?&gt;&lt;/h1&gt;
    &lt;?php endif; ?&gt;
  &lt;/div&gt;

  &lt;nav&gt;
    &lt;?php wp_nav_menu(['theme_location' => 'primary']); ?&gt;
  &lt;/nav&gt;

  &lt;?php if ($menu_cta): ?&gt;
    &lt;a href="&lt;?php echo esc_url($menu_cta['url']); ?&gt;" class="cta-button"&gt;
      &lt;?php echo esc_html($menu_cta['text']); ?&gt;
    &lt;/a&gt;
  &lt;?php endif; ?&gt;

  &lt;?php if ($show_search): ?&gt;
    &lt;?php get_search_form(); ?&gt;
  &lt;?php endif; ?&gt;
&lt;/header&gt;</code></pre>

<h2>Field Types</h2>

<p>Markdown FM supports all field types from Pages CMS:</p>

<h3>String</h3>

<p>Single-line text input.</p>

<pre><code>- name: title
  label: Page Title
  type: string
  options:
    minlength: 3
    maxlength: 100</code></pre>

<p><strong>Options:</strong></p>
<ul>
  <li><code>minlength</code> - Minimum character length</li>
  <li><code>maxlength</code> - Maximum character length</li>
</ul>

<h3>Text</h3>

<p>Multi-line textarea.</p>

<pre><code>- name: description
  label: Description
  type: text
  options:
    maxlength: 500</code></pre>

<p><strong>Options:</strong></p>
<ul>
  <li><code>maxlength</code> - Maximum character length</li>
</ul>

<h3>Rich Text</h3>

<p>WordPress WYSIWYG editor with full formatting.</p>

<pre><code>- name: content
  label: Page Content
  type: rich-text</code></pre>

<h3>Code</h3>

<p>Code editor with syntax highlighting.</p>

<pre><code>- name: custom_css
  label: Custom CSS
  type: code
  options:
    language: css</code></pre>

<p><strong>Options:</strong></p>
<ul>
  <li><code>language</code> - Syntax highlighting (html, css, javascript, php, python, etc.)</li>
</ul>

<h3>Boolean</h3>

<p>Checkbox for true/false values.</p>

<pre><code>- name: featured
  label: Featured Post
  type: boolean
  default: false</code></pre>

<h3>Number</h3>

<p>Number input with optional constraints.</p>

<pre><code>- name: price
  label: Price
  type: number
  options:
    min: 0
    max: 9999</code></pre>

<p><strong>Options:</strong></p>
<ul>
  <li><code>min</code> - Minimum value</li>
  <li><code>max</code> - Maximum value</li>
</ul>

<h3>Date</h3>

<p>Date picker with optional time.</p>

<pre><code>- name: event_date
  label: Event Date
  type: date
  options:
    time: true</code></pre>

<p><strong>Options:</strong></p>
<ul>
  <li><code>time</code> - Set to <code>true</code> to include time selection</li>
</ul>

<h3>Select</h3>

<p>Dropdown selection.</p>

<pre><code>- name: category
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
        label: Events</code></pre>

<p><strong>Options:</strong></p>
<ul>
  <li><code>multiple</code> - Allow multiple selections</li>
  <li><code>values</code> - Array of options with <code>value</code> and <code>label</code> keys</li>
</ul>

<h3>Image</h3>

<p>WordPress media uploader for images.</p>

<pre><code>- name: featured_image
  label: Featured Image
  type: image</code></pre>

<p>Returns the attachment ID. Use <code>mdfm_get_image()</code> helper function to get full image data.</p>

<h3>File</h3>

<p>WordPress media uploader for any file type.</p>

<pre><code>- name: pdf_brochure
  label: PDF Brochure
  type: file</code></pre>

<p>Returns the attachment ID. Use <code>mdfm_get_file()</code> helper function to get full file data.</p>

<h3>Object</h3>

<p>Nested group of fields.</p>

<pre><code>- name: author
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
      type: image</code></pre>

<p><strong>Access nested fields:</strong></p>

<pre><code>$author = mdfm_get_field('author');
echo $author['name'];
echo $author['bio'];</code></pre>

<h3>Block</h3>

<p>Repeater field with multiple block types. Perfect for flexible page builders!</p>

<pre><code>- name: page_sections
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
        - name: background_image
          label: Background Image
          type: image
    - name: two_column
      label: Two Column Layout
      fields:
        - name: left_content
          label: Left Column
          type: rich-text
        - name: right_content
          label: Right Column
          type: rich-text</code></pre>

<p><strong>Properties:</strong></p>
<ul>
  <li><code>list: true</code> - Makes it repeatable</li>
  <li><code>blockKey</code> - Field name that identifies block type (usually "type")</li>
  <li><code>blocks</code> - Array of available block definitions</li>
</ul>

<p><strong>Usage in templates:</strong></p>

<pre><code>&lt;?php
$sections = mdfm_get_field('page_sections');

if ($sections) {
  foreach ($sections as $section) {
    switch ($section['type']) {
      case 'hero':
        ?&gt;
        &lt;section class="hero"&gt;
          &lt;h1&gt;&lt;?php echo esc_html($section['title']); ?&gt;&lt;/h1&gt;
        &lt;/section&gt;
        &lt;?php
        break;

      case 'two_column':
        ?&gt;
        &lt;section class="two-column"&gt;
          &lt;div class="column"&gt;
            &lt;?php echo wp_kses_post($section['left_content']); ?&gt;
          &lt;/div&gt;
          &lt;div class="column"&gt;
            &lt;?php echo wp_kses_post($section['right_content']); ?&gt;
          &lt;/div&gt;
        &lt;/section&gt;
        &lt;?php
        break;
    }
  }
}
?&gt;</code></pre>

<h2>Helper Functions for Images and Files</h2>

<p>Since image and file fields store attachment IDs, Markdown FM provides helper functions to retrieve full attachment data:</p>

<h3><code>mdfm_get_image($field_name, $post_id = null, $size = 'full')</code></h3>

<p>Get comprehensive image data including URL, alt text, dimensions, and more.</p>

<pre><code>$image = mdfm_get_image('featured_image');

if ($image) {
  echo '&lt;img src="' . esc_url($image['url']) . '"
        alt="' . esc_attr($image['alt']) . '"
        width="' . esc_attr($image['width']) . '"
        height="' . esc_attr($image['height']) . '" /&gt;';
}</code></pre>

<p><strong>Returns:</strong></p>
<ul>
  <li><code>id</code> - Attachment ID</li>
  <li><code>url</code> - Image URL at specified size</li>
  <li><code>alt</code> - Alt text</li>
  <li><code>title</code> - Image title</li>
  <li><code>caption</code> - Image caption</li>
  <li><code>description</code> - Image description</li>
  <li><code>width</code> - Image width</li>
  <li><code>height</code> - Image height</li>
</ul>

<h3><code>mdfm_get_file($field_name, $post_id = null)</code></h3>

<p>Get comprehensive file data including URL, path, size, and MIME type.</p>

<pre><code>$pdf = mdfm_get_file('pdf_brochure');

if ($pdf) {
  echo '&lt;a href="' . esc_url($pdf['url']) . '" download&gt;';
  echo 'Download ' . esc_html($pdf['filename']);
  echo ' (' . size_format($pdf['filesize']) . ')';
  echo '&lt;/a&gt;';
}</code></pre>

<p><strong>Returns:</strong></p>
<ul>
  <li><code>id</code> - Attachment ID</li>
  <li><code>url</code> - File URL</li>
  <li><code>path</code> - Server file path</li>
  <li><code>filename</code> - File name</li>
  <li><code>filesize</code> - File size in bytes</li>
  <li><code>mime_type</code> - MIME type</li>
  <li><code>title</code> - File title</li>
</ul>

<h2>Common Field Properties</h2>

<p>All field types support these properties:</p>

<pre><code>- name: field_name        # Required - Unique machine name
  label: Field Label      # Display name in admin
  type: string            # Required - Field type
  description: Help text  # Optional help text for editors
  default: Default value  # Default value for new entries
  required: true          # Make field required (not enforced yet)</code></pre>

<h2>Data Storage</h2>

<h3>Page Templates</h3>
<ul>
  <li><strong>Location</strong>: Post meta with key <code>_markdown_fm_data</code></li>
  <li><strong>Scope</strong>: Per post/page</li>
  <li><strong>Editing</strong>: WordPress post/page editor</li>
</ul>

<h3>Template Partials</h3>
<ul>
  <li><strong>Location</strong>: WordPress options with key <code>markdown_fm_partial_data</code></li>
  <li><strong>Scope</strong>: Global (site-wide)</li>
  <li><strong>Editing</strong>: Markdown FM admin page → "Manage Data" button</li>
</ul>

<h3>Plugin Settings</h3>
<ul>
  <li><code>markdown_fm_template_settings</code> - Tracks which templates have YAML enabled</li>
  <li><code>markdown_fm_schemas</code> - Stores YAML schemas for each template/partial</li>
</ul>

<h2>Requirements</h2>

<ul>
  <li>WordPress 5.0 or higher</li>
  <li>PHP 7.4 or higher</li>
  <li>Composer (for installing dependencies from source)</li>
</ul>

<h2>Dependencies</h2>

<ul>
  <li><strong>Symfony YAML Component</strong> (^5.4|^6.0|^7.0) - YAML parsing</li>
</ul>

<h2>Security</h2>

<ul>
  <li>All admin functionality requires <code>manage_options</code> capability (administrator by default)</li>
  <li>AJAX requests protected with WordPress nonces</li>
  <li>Data sanitized and escaped appropriately</li>
  <li>Input validation on all fields</li>
</ul>

<h2>Uninstallation</h2>

<p>When you <strong>delete</strong> (not deactivate) the plugin, it automatically cleans up:</p>
<ul>
  <li>Template settings (<code>markdown_fm_template_settings</code>)</li>
  <li>Schemas (<code>markdown_fm_schemas</code>)</li>
  <li>Partial data (<code>markdown_fm_partial_data</code>)</li>
  <li>All post meta data (<code>_markdown_fm_data</code>)</li>
</ul>

<h2>Troubleshooting</h2>

<h3>Plugin activation error about Composer</h3>

<p><strong>Solution</strong>: Navigate to the plugin directory and run:</p>
<pre><code>composer install</code></pre>

<h3>Schema fields not appearing in post editor</h3>

<ol>
  <li>Ensure YAML is enabled for the template</li>
  <li>Verify you selected the correct page template</li>
  <li>Check YAML syntax (use 2 spaces for indentation)</li>
  <li>Clear browser cache and refresh</li>
</ol>

<h3>YAML parsing errors</h3>

<ol>
  <li>Validate YAML syntax with an online validator</li>
  <li>Use consistent 2-space indentation (no tabs)</li>
  <li>Check WordPress debug logs for detailed error messages</li>
</ol>

<h3>Changes not saving</h3>

<ol>
  <li>Check browser console for JavaScript errors</li>
  <li>Ensure you have permission to edit posts/options</li>
  <li>Verify WordPress AJAX is working</li>
</ol>

<h2>Contributing</h2>

<p>Contributions are welcome! If you'd like to contribute to Markdown FM:</p>

<ol>
  <li>Fork the repository at <a href="https://github.com/maliMirkec/markdown-fm" target="_blank">https://github.com/maliMirkec/markdown-fm</a></li>
  <li>Create a feature branch (<code>git checkout -b feature/amazing-feature</code>)</li>
  <li>Commit your changes (<code>git commit -m 'Add amazing feature'</code>)</li>
  <li>Push to the branch (<code>git push origin feature/amazing-feature</code>)</li>
  <li>Open a Pull Request</li>
</ol>

<p>Please ensure your code follows WordPress coding standards and includes appropriate documentation.</p>

<h2>Reporting Issues</h2>

<p>Found a bug or have a feature request? Please report it on GitHub:</p>

<p><a href="https://github.com/maliMirkec/markdown-fm/issues" target="_blank" class="button button-primary">Report an Issue on GitHub</a></p>

<p>When reporting issues, please include:</p>
<ul>
  <li>WordPress version</li>
  <li>PHP version</li>
  <li>Plugin version</li>
  <li>Steps to reproduce the issue</li>
  <li>Expected behavior vs actual behavior</li>
  <li>Any error messages or screenshots</li>
</ul>

<h2>Credits</h2>

<ul>
  <li><strong>Author</strong>: <a href="https://www.silvestar.codes" target="_blank">Silvestar Bistrović</a></li>
  <li><strong>Email</strong>: me@silvestar.codes</li>
  <li><strong>GitHub</strong>: <a href="https://github.com/maliMirkec/markdown-fm" target="_blank">maliMirkec/markdown-fm</a></li>
  <li><strong>Inspired by</strong>: <a href="https://pagescms.org/" target="_blank">PagesCMS</a> - Open-source CMS for static websites</li>
</ul>

<h2>License</h2>

<p>GPL v2 or later</p>

<h2>Changelog</h2>

<h3>Version 1.0.0</h3>
<ul>
  <li>Initial release</li>
  <li>Support for 12+ field types from PagesCMS</li>
  <li>Template and partial support</li>
  <li>ACF-like template functions</li>
  <li>Block/repeater functionality</li>
  <li>WordPress media integration with attachment ID storage</li>
  <li>Helper functions for images and files (<code>mdfm_get_image()</code>, <code>mdfm_get_file()</code>)</li>
  <li>Administrator-only access</li>
  <li>Clean uninstall</li>
  <li>Clear buttons for image and file fields</li>
  <li>Reset All Data button for clearing all custom fields on a page</li>
  <li>Confirmation alerts for destructive actions</li>
</ul>

<hr>

<p><strong>Built with ❤️ for the WordPress community</strong></p>
