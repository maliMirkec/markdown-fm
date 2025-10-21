<?php
/**
 * Admin Page Template
 * File: templates/admin-page.php
 */

if (!defined('ABSPATH')) {
  exit;
}
?>

<div class="wrap">
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

  <div class="markdown-fm-admin-container">
    <div class="markdown-fm-intro">
    <p><?php _e('Markdown FM allows you to define YAML frontmatter schemas for your theme templates. Enable YAML for templates, define schemas, and manage structured content directly in the WordPress editor.', 'markdown-fm'); ?></p>
    <p><strong><?php _e('Inspired by', 'markdown-fm'); ?> <a href="https://pagescms.org/docs/" target="_blank">PagesCMS</a></strong></p>
    </div>

    <h2><?php _e('Theme Templates', 'markdown-fm'); ?></h2>

    <?php if (empty($templates)) : ?>
    <p><?php _e('No templates found in the current theme.', 'markdown-fm'); ?></p>
    <?php else : ?>
    <table class="wp-list-table widefat fixed striped">
      <thead>
          <tr>
              <th><?php _e('Template Name', 'markdown-fm'); ?></th>
              <th><?php _e('File', 'markdown-fm'); ?></th>
              <th><?php _e('Enable YAML', 'markdown-fm'); ?></th>
              <th><?php _e('Schema', 'markdown-fm'); ?></th>
          </tr>
      </thead>
      <tbody>
          <?php foreach ($templates as $template) : ?>
              <?php
              $is_enabled = isset($template_settings[$template['file']]) && $template_settings[$template['file']];
              $has_schema = isset($schemas[$template['file']]) && !empty($schemas[$template['file']]);
              ?>
              <tr>
                  <td><strong><?php echo esc_html($template['name']); ?></strong></td>
                  <td><code><?php echo esc_html($template['file']); ?></code></td>
                  <td>
                      <label class="markdown-fm-switch">
                          <input type="checkbox"
                                  class="markdown-fm-enable-yaml"
                                  data-template="<?php echo esc_attr($template['file']); ?>"
                                  <?php checked($is_enabled); ?> />
                          <span class="markdown-fm-slider"></span>
                      </label>
                  </td>
                  <td>
                      <?php if ($is_enabled) : ?>
                          <button type="button"
                                  class="button markdown-fm-edit-schema"
                                  data-template="<?php echo esc_attr($template['file']); ?>"
                                  data-name="<?php echo esc_attr($template['name']); ?>">
                              <?php echo $has_schema ? __('Edit Schema', 'markdown-fm') : __('Add Schema', 'markdown-fm'); ?>
                          </button>
                          <?php if ($has_schema) : ?>
                              <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                          <?php endif; ?>
                      <?php else : ?>
                          <span class="description"><?php _e('Enable YAML first', 'markdown-fm'); ?></span>
                      <?php endif; ?>
                  </td>
              </tr>
          <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>

    <div class="markdown-fm-schema-examples">
    <h2><?php _e('Schema Example', 'markdown-fm'); ?></h2>
    <p><?php _e('Here\'s an example schema in YAML format:', 'markdown-fm'); ?></p>
    <pre><code>fields:
  - name: title
    label: Page Title
    type: string
    options:
    maxlength: 100
  - name: description
    label: Description
    type: text
    options:
    maxlength: 160
  - name: featured_image
    label: Featured Image
    type: image
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
        type: rich-text</code></pre>

    <h3><?php _e('Supported Field Types', 'markdown-fm'); ?></h3>
    <ul>
      <li><strong>boolean</strong> - Checkbox</li>
      <li><strong>string</strong> - Single line text (supports minlength, maxlength)</li>
      <li><strong>text</strong> - Multi-line textarea (supports maxlength)</li>
      <li><strong>rich-text</strong> - WordPress WYSIWYG editor</li>
      <li><strong>code</strong> - Code editor (supports language option)</li>
      <li><strong>number</strong> - Number input (supports min, max)</li>
      <li><strong>date</strong> - Date picker (supports time option for datetime)</li>
      <li><strong>select</strong> - Dropdown (supports multiple and values options)</li>
      <li><strong>image</strong> - WordPress media uploader for images</li>
      <li><strong>file</strong> - WordPress media uploader for any file</li>
      <li><strong>object</strong> - Nested fields group</li>
      <li><strong>block</strong> - Repeater field with multiple block types (list: true for repeatable)</li>
    </ul>
    </div>
  </div>
</div>

<!-- Schema Editor Modal -->
<div id="markdown-fm-schema-modal" class="markdown-fm-modal" style="display: none;">
  <div class="markdown-fm-modal-content">
    <div class="markdown-fm-modal-header">
    <h2><?php _e('Edit Schema', 'markdown-fm'); ?>: <span id="markdown-fm-template-name"></span></h2>
    <button type="button" class="markdown-fm-modal-close">&times;</button>
    </div>
    <div class="markdown-fm-modal-body">
    <p><?php _e('Enter your YAML schema below:', 'markdown-fm'); ?></p>
    <textarea id="markdown-fm-schema-editor" rows="20" class="large-text code"></textarea>
    <input type="hidden" id="markdown-fm-current-template" value="" />
    </div>
    <div class="markdown-fm-modal-footer">
    <button type="button" class="button button-primary markdown-fm-save-schema"><?php _e('Save Schema', 'markdown-fm'); ?></button>
    <button type="button" class="button markdown-fm-modal-close"><?php _e('Cancel', 'markdown-fm'); ?></button>
    </div>
  </div>
</div>
