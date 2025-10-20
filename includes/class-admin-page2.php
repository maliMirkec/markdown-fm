<?php
namespace MarkdownFM;

class Admin_Page {

  const PAGE_SLUG = 'markdown-fm-templates';
  const OPTION_YAML_ENABLED = 'markdown_fm_yaml_enabled_templates';

  public static function init() {
    add_action('admin_menu', [__CLASS__, 'register_menu']);
    add_action('admin_post_markdown_fm_save_enabled_templates', [__CLASS__, 'save_enabled_templates']);
  }

  /**
   * Register admin menu
   */
  public static function register_menu() {
    add_menu_page(
      'Markdown FM Templates',
      'Markdown FM',
      'manage_options',
      self::PAGE_SLUG,
      [__CLASS__, 'render_templates_page'],
      'dashicons-media-text'
    );
  }

  /**
   * Render templates table
   */
  public static function render_templates_page() {
    // Load all templates from field schemas
    $schemas = Field_Manager::get_schemas();
    $templates = $schemas['content'] ?? [];

    // Load enabled templates from option
    $enabled_templates = get_option(self::OPTION_YAML_ENABLED, []);

    // First-time default: enable first template if none are enabled
    if (empty($enabled_templates) && !empty($templates)) {
      $enabled_templates[] = $templates[0]['name'];
      update_option(self::OPTION_YAML_ENABLED, $enabled_templates);
    }

    ?>
    <div class="wrap">
      <h1>Markdown FM Templates</h1>
      <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="markdown_fm_save_enabled_templates">
        <?php wp_nonce_field('markdown_fm_save_enabled_templates_nonce', 'markdown_fm_nonce'); ?>

        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th>Template</th>
              <th>YAML Enabled</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($templates as $template):
              $template_name = $template['name'] ?? '';
              $template_label = $template['label'] ?? $template_name;
              $checked = in_array($template_name, $enabled_templates) ? 'checked' : '';
            ?>
            <tr>
              <td><?php echo esc_html($template_label); ?></td>
              <td>
                <input type="checkbox" name="yaml_enabled_templates[]" value="<?php echo esc_attr($template_name); ?>" <?php echo $checked; ?>>
              </td>
              <td>
                <?php if ($checked): ?>
                  <a class="button" href="<?php echo admin_url('admin.php?page=' . self::PAGE_SLUG . '&action=edit-schema&template=' . esc_attr($template_name)); ?>">Edit Schema</a>
                <?php else: ?>
                  <span style="color:#999;">Edit Schema</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p><input type="submit" class="button button-primary" value="Save Changes"></p>
      </form>
    </div>
    <?php

    // Render Edit Schema if requested
    if (isset($_GET['action'], $_GET['template']) && $_GET['action'] === 'edit-schema') {
      $template_to_edit = sanitize_text_field($_GET['template']);
      if (in_array($template_to_edit, $enabled_templates)) {
        self::render_edit_schema($template_to_edit);
      } else {
        echo '<div class="notice notice-error"><p>This template is not enabled for YAML.</p></div>';
      }
    }
  }

  /**
   * Render Edit Schema page for a single template
   */
  private static function render_edit_schema($template_name) {
    $fields = Field_Manager::get_fields_for_template($template_name);
    if (empty($fields)) {
      echo '<p>No fields defined for this template.</p>';
      return;
    }

    echo '<h2>Edit Schema: ' . esc_html($template_name) . '</h2>';
    echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
    wp_nonce_field('markdown_fm_save_schema', 'markdown_fm_nonce');
    echo '<input type="hidden" name="action" value="markdown_fm_save_schema">';
    echo '<input type="hidden" name="template_name" value="' . esc_attr($template_name) . '">';

    // Load existing schema data if needed
    $existing_data = get_option('markdown_fm_schema_' . $template_name, []);
    Field_Manager::render_fields($fields, $existing_data, 'schema');

    echo '<p><input type="submit" class="button button-primary" value="Save Schema"></p>';
    echo '</form>';
  }

  /**
   * Save enabled templates
   */
  public static function save_enabled_templates() {
    if (!isset($_POST['markdown_fm_nonce']) || !wp_verify_nonce($_POST['markdown_fm_nonce'], 'markdown_fm_save_enabled_templates_nonce')) {
      wp_die('Security check failed.');
    }

    if (!current_user_can('manage_options')) {
      wp_die('Permission denied.');
    }

    $enabled = isset($_POST['yaml_enabled_templates']) && is_array($_POST['yaml_enabled_templates'])
      ? array_map('sanitize_text_field', $_POST['yaml_enabled_templates'])
      : [];

    update_option(self::OPTION_YAML_ENABLED, $enabled);

    wp_redirect(admin_url('admin.php?page=' . self::PAGE_SLUG));
    exit;
  }
}
