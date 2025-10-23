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
  <div class="markdown-fm-admin-container">
    <div class="markdown-fm-header">
      <div class="markdown-fm-header-content">
        <img src="<?php echo esc_url(MARKDOWN_FM_PLUGIN_URL . 'icon-256x256.png'); ?>" alt="Markdown FM" class="markdown-fm-logo" />
        <div class="markdown-fm-header-text">
          <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
          <p class="markdown-fm-tagline"><?php _e('YAML-powered content schemas for WordPress themes', 'markdown-fm'); ?></p>
        </div>
      </div>
    </div>

    <div class="markdown-fm-intro">
    <p><?php _e('Markdown FM allows you to define YAML frontmatter schemas for your theme templates. Enable YAML for templates, define schemas, and manage structured content directly in the WordPress editor.', 'markdown-fm'); ?></p>
    <p><strong><?php _e('Inspired by', 'markdown-fm'); ?> <a href="https://pagescms.org/docs/" target="_blank">PagesCMS</a></strong></p>
    <p>
      <a href="<?php echo esc_url(add_query_arg('refresh_mdfm', '1')); ?>" class="button">
        <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
        <?php _e('Refresh Template List', 'markdown-fm'); ?>
      </a>
      <span class="description" style="margin-left: 10px;">
        <?php _e('Scan theme files for new templates and partials with @mdfm markers', 'markdown-fm'); ?>
      </span>
    </p>
    <p>
      <button type="button" class="button markdown-fm-export-settings">
        <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
        <?php _e('Export Settings', 'markdown-fm'); ?>
      </button>
      <button type="button" class="button markdown-fm-import-settings-trigger" style="margin-left: 10px;">
        <span class="dashicons dashicons-upload" style="margin-top: 3px;"></span>
        <?php _e('Import Settings', 'markdown-fm'); ?>
      </button>
      <input type="file" id="markdown-fm-import-file" accept=".json" style="display: none;" />
      <span class="description" style="margin-left: 10px;">
        <?php _e('Backup or restore all schemas and settings', 'markdown-fm'); ?>
      </span>
    </p>
    </div>

    <h2><?php _e('Page Templates', 'markdown-fm'); ?></h2>
    <p><?php _e('Configure YAML schemas for page templates. Data for these templates is stored per post/page.', 'markdown-fm'); ?></p>

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
                                  name="enable-yaml"
                                  data-template="<?php echo esc_attr($template['file']); ?>"
                                  <?php checked($is_enabled); ?> />
                          <span class="markdown-fm-slider"></span>
                      </label>
                  </td>
                  <td>
                      <?php if ($is_enabled) : ?>
                          <a href="<?php echo esc_url(admin_url('admin.php?page=markdown-fm-edit-schema&template=' . urlencode($template['file']))); ?>"
                             class="button">
                              <?php echo $has_schema ? __('Edit Schema', 'markdown-fm') : __('Add Schema', 'markdown-fm'); ?>
                          </a>
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

    <h2 style="margin-top: 40px;"><?php _e('Template Partials', 'markdown-fm'); ?></h2>
    <p><?php _e('Configure YAML schemas for template partials (header, footer, sidebar, etc.). Data for partials is stored globally and can be managed below.', 'markdown-fm'); ?></p>

    <?php if (empty($partials)) : ?>
    <p><?php _e('No partials found in the current theme.', 'markdown-fm'); ?></p>
    <?php else : ?>
    <table class="wp-list-table widefat fixed striped">
      <thead>
          <tr>
              <th><?php _e('Partial Name', 'markdown-fm'); ?></th>
              <th><?php _e('File', 'markdown-fm'); ?></th>
              <th><?php _e('Enable YAML', 'markdown-fm'); ?></th>
              <th><?php _e('Schema', 'markdown-fm'); ?></th>
              <th><?php _e('Data', 'markdown-fm'); ?></th>
          </tr>
      </thead>
      <tbody>
          <?php foreach ($partials as $partial) : ?>
              <?php
              $is_enabled = isset($template_settings[$partial['file']]) && $template_settings[$partial['file']];
              $has_schema = isset($schemas[$partial['file']]) && !empty($schemas[$partial['file']]);
              ?>
              <tr>
                  <td><strong><?php echo esc_html($partial['name']); ?></strong></td>
                  <td><code><?php echo esc_html($partial['file']); ?></code></td>
                  <td>
                      <label class="markdown-fm-switch">
                          <input type="checkbox"
                                  class="markdown-fm-enable-yaml"
                                  name="enable-yaml"
                                  data-template="<?php echo esc_attr($partial['file']); ?>"
                                  <?php checked($is_enabled); ?> />
                          <span class="markdown-fm-slider"></span>
                      </label>
                  </td>
                  <td>
                      <?php if ($is_enabled) : ?>
                          <a href="<?php echo esc_url(admin_url('admin.php?page=markdown-fm-edit-schema&template=' . urlencode($partial['file']))); ?>"
                             class="button">
                              <?php echo $has_schema ? __('Edit Schema', 'markdown-fm') : __('Add Schema', 'markdown-fm'); ?>
                          </a>
                          <?php if ($has_schema) : ?>
                              <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                          <?php endif; ?>
                      <?php else : ?>
                          <span class="description"><?php _e('Enable YAML first', 'markdown-fm'); ?></span>
                      <?php endif; ?>
                  </td>
                  <td>
                      <?php if ($is_enabled && $has_schema) : ?>
                          <a href="<?php echo esc_url(admin_url('admin.php?page=markdown-fm-edit-partial&template=' . urlencode($partial['file']))); ?>"
                             class="button">
                              <?php _e('Manage Data', 'markdown-fm'); ?>
                          </a>
                      <?php else : ?>
                          <span class="description"><?php _e('Add schema first', 'markdown-fm'); ?></span>
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
    <pre>fields:
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
          - name: hero-title
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
            type: rich-text</pre>

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


<?php if (!empty($refresh_message)) : ?>
<script>
jQuery(document).ready(function($) {
  // Trigger refresh notification
  if (typeof MarkdownFM !== 'undefined' && MarkdownFM.showMessage) {
    MarkdownFM.showMessage('<?php echo esc_js($refresh_message); ?>', 'success');
  }
});
</script>
<?php endif; ?>
