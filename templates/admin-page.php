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
  <div class="yaml-cf-admin-container">
    <div class="yaml-cf-header">
      <div class="yaml-cf-header-content">
        <img src="<?php echo esc_url(YAML_CF_PLUGIN_URL . 'icon-256x256.png'); ?>" alt="YAML Custom Fields" class="yaml-cf-logo" />
        <div class="yaml-cf-header-text">
          <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
          <p class="yaml-cf-tagline"><?php esc_html_e('YAML-powered content schemas for WordPress themes', 'yaml-custom-fields'); ?></p>
        </div>
      </div>
    </div>

    <div class="yaml-cf-intro">
    <p><?php esc_html_e('YAML Custom Fields allows you to define YAML frontmatter schemas for your theme templates. Enable YAML for templates, define schemas, and manage structured content directly in the WordPress editor.', 'yaml-custom-fields'); ?></p>
    <p><strong><?php esc_html_e('Inspired by', 'yaml-custom-fields'); ?> <a href="https://pagescms.org/docs/" target="_blank">PagesCMS</a></strong></p>
    <p>
      <a href="<?php echo esc_url(add_query_arg('refresh_ycf', '1')); ?>" class="button">
        <span class="dashicons dashicons-update"></span>
        <?php esc_html_e('Refresh Template List', 'yaml-custom-fields'); ?>
      </a>
    </p>
    <p class="description">
      <?php esc_html_e('Scan theme files for new templates and partials with @ycf markers', 'yaml-custom-fields'); ?>
    </p>
    <p>
      <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?yaml_cf_export_settings=1'), 'yaml_cf_export_settings')); ?>" class="button">
        <span class="dashicons dashicons-download"></span>
        <?php esc_html_e('Export Settings', 'yaml-custom-fields'); ?>
      </a>
      <button type="button" class="button yaml-cf-import-settings-trigger" style="margin-left: 10px;">
        <span class="dashicons dashicons-upload"></span>
        <?php esc_html_e('Import Settings', 'yaml-custom-fields'); ?>
      </button>
      <input type="file" id="yaml-cf-import-file" accept=".json" style="display: none;" />
    </p>
    <p class="description">
      <?php esc_html_e('Backup or restore all schemas and settings', 'yaml-custom-fields'); ?>
    </p>
    </div>

    <h2><?php esc_html_e('Page Templates', 'yaml-custom-fields'); ?></h2>
    <p><?php esc_html_e('Configure YAML schemas for page templates. Data for these templates is stored per post/page.', 'yaml-custom-fields'); ?></p>

    <?php if (empty($templates)) : ?>
    <p><?php esc_html_e('No templates found in the current theme.', 'yaml-custom-fields'); ?></p>
    <?php else : ?>
    <div class="wp-table-wrap">
      <table class="wp-list-table widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Template Name', 'yaml-custom-fields'); ?></th>
                <th><?php esc_html_e('File', 'yaml-custom-fields'); ?></th>
                <th><?php esc_html_e('Enable YAML', 'yaml-custom-fields'); ?></th>
                <th><?php esc_html_e('Schema', 'yaml-custom-fields'); ?></th>
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
                        <label class="yaml-cf-switch">
                            <input type="checkbox"
                                    class="yaml-cf-enable-yaml"
                                    name="enable-yaml"
                                    data-template="<?php echo esc_attr($template['file']); ?>"
                                    <?php checked($is_enabled); ?> />
                            <span class="yaml-cf-slider"></span>
                        </label>
                    </td>
                    <td>
                        <?php if ($is_enabled) : ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-edit-schema&template=' . urlencode($template['file']))); ?>"
                              class="button">
                                <?php echo $has_schema ? esc_html__('Edit Schema', 'yaml-custom-fields') : esc_html__('Add Schema', 'yaml-custom-fields'); ?>
                            </a>
                            <?php if ($has_schema) : ?>
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <?php endif; ?>
                        <?php else : ?>
                            <span class="description"><?php esc_html_e('Enable YAML first', 'yaml-custom-fields'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <h2 style="margin-top: 40px;"><?php esc_html_e('Template Partials', 'yaml-custom-fields'); ?></h2>
    <p><?php esc_html_e('Configure YAML schemas for template partials (header, footer, sidebar, etc.). Data for partials is stored globally and can be managed below.', 'yaml-custom-fields'); ?></p>

    <?php if (empty($partials)) : ?>
    <p><?php esc_html_e('No partials found in the current theme.', 'yaml-custom-fields'); ?></p>
    <?php else : ?>
    <div class="wp-table-wrap">
      <table class="wp-list-table widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Partial Name', 'yaml-custom-fields'); ?></th>
                <th><?php esc_html_e('File', 'yaml-custom-fields'); ?></th>
                <th><?php esc_html_e('Enable YAML', 'yaml-custom-fields'); ?></th>
                <th><?php esc_html_e('Schema', 'yaml-custom-fields'); ?></th>
                <th><?php esc_html_e('Data', 'yaml-custom-fields'); ?></th>
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
                        <label class="yaml-cf-switch">
                            <input type="checkbox"
                                    class="yaml-cf-enable-yaml"
                                    name="enable-yaml"
                                    data-template="<?php echo esc_attr($partial['file']); ?>"
                                    <?php checked($is_enabled); ?> />
                            <span class="yaml-cf-slider"></span>
                        </label>
                    </td>
                    <td>
                        <?php if ($is_enabled) : ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-edit-schema&template=' . urlencode($partial['file']))); ?>"
                              class="button">
                                <?php echo $has_schema ? esc_html__('Edit Schema', 'yaml-custom-fields') : esc_html__('Add Schema', 'yaml-custom-fields'); ?>
                            </a>
                            <?php if ($has_schema) : ?>
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <?php endif; ?>
                        <?php else : ?>
                            <span class="description"><?php esc_html_e('Enable YAML first', 'yaml-custom-fields'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_enabled && $has_schema) : ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-edit-partial&template=' . urlencode($partial['file']))); ?>"
                              class="button">
                                <?php esc_html_e('Manage Data', 'yaml-custom-fields'); ?>
                            </a>
                        <?php else : ?>
                            <span class="description"><?php esc_html_e('Add schema first', 'yaml-custom-fields'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <div class="yaml-cf-schema-examples">
    <h2><?php esc_html_e('Schema Example', 'yaml-custom-fields'); ?></h2>
    <p><?php esc_html_e('Here\'s an example schema in YAML format:', 'yaml-custom-fields'); ?></p>
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

    <h3><?php esc_html_e('Supported Field Types', 'yaml-custom-fields'); ?></h3>
    <ul>
      <li><strong>boolean</strong> - Checkbox</li>
      <li><strong>string</strong> - Single line text (supports minlength, maxlength)</li>
      <li><strong>text</strong> - Multi-line textarea (supports maxlength)</li>
      <li><strong>rich-text</strong> - WordPress WYSIWYG editor</li>
      <li><strong>code</strong> - Code editor (supports language option)</li>
      <li><strong>number</strong> - Number input (supports min, max)</li>
      <li><strong>date</strong> - Date picker (supports time option for datetime)</li>
      <li><strong>select</strong> - Dropdown (supports multiple and values options)</li>
      <li><strong>taxonomy</strong> - WordPress categories, tags, or custom taxonomies (supports multiple and taxonomy options)</li>
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
  if (typeof YamlCF !== 'undefined' && YamlCF.showMessage) {
    YamlCF.showMessage('<?php echo esc_js($refresh_message); ?>', 'success');
  }
});
</script>
<?php endif; ?>
