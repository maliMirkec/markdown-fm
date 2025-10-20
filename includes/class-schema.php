<?php
namespace MarkdownFM;

use Symfony\Component\Yaml\Yaml;

class Schema {

  const OPTION_NAME = 'markdown_fm_yaml_schemas';

  /**
   * Get all schemas
   */
  public static function get_schemas() {
    return get_option(self::OPTION_NAME, []);
  }

  /**
   * Get schema for a single template
   */
  public static function get_schema($template_file) {
    $schemas = self::get_schemas();
    return $schemas[$template_file] ?? [];
  }

  /**
   * Save schema for a template
   */
  public static function save_schema($template_file, $schema) {
    $schemas = self::get_schemas();
    $schemas[$template_file] = $schema;
    update_option(self::OPTION_NAME, $schemas);
  }

  /**
   * Render schema editor form
   */
  public static function render_editor($template_file) {
    // Load saved schema
    $schema = self::get_schema($template_file);

    // Check for unsaved YAML transient
    $unsaved_yaml = get_transient('markdown_fm_unsaved_yaml_' . $template_file);
    if ($unsaved_yaml) {
      $yaml_text = $unsaved_yaml;
    } else {
      $yaml_text = !empty($schema) ? Yaml::dump($schema, 4) : '';
    }

    echo '<div class="wrap">';
    echo '<h1>Edit YAML Schema for ' . esc_html($template_file) . '</h1>';

    settings_errors('markdown_fm_schema');

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="markdown_fm_save_schema">';
    echo '<input type="hidden" name="template_file" value="' . esc_attr($template_file) . '">';

    echo '<textarea name="schema_yaml" style="width:100%;height:400px;">' . esc_textarea($yaml_text) . '</textarea>';

    echo '<p><input type="submit" class="button button-primary" value="Save Schema"></p>';
    echo '</form>';
    echo '</div>';
  }

  /**
   * Handle form submission and save schema
   */
  public static function save_schema_post() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    if (!isset($_POST['template_file'], $_POST['schema_yaml'])) {
      wp_die('Invalid request.');
    }

    $template_file = sanitize_text_field($_POST['template_file']);
    $yaml_text = wp_unslash($_POST['schema_yaml']);

    try {
      // Optional: remove lines with only --- to avoid multiple document error
      $yaml_clean = preg_replace('/^\s*---\s*$/m', '', $yaml_text);

      // Parse YAML safely
      $schema = Yaml::parse($yaml_clean);

      if (!is_array($schema)) {
        throw new \Exception('YAML must be a valid mapping (associative array).');
      }

      // Save schema
      self::save_schema($template_file, $schema);

      // Clear any transient
      delete_transient('markdown_fm_unsaved_yaml_' . $template_file);

      wp_redirect(admin_url('admin.php?page=markdown-fm-templates'));
      exit;

    } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
      // Save raw YAML temporarily so user doesn’t lose it
      set_transient('markdown_fm_unsaved_yaml_' . $template_file, $yaml_text, 300);

      // Add admin error notice
      add_settings_error(
        'markdown_fm_schema',
        'yaml_error',
        'YAML parse error: ' . esc_html($e->getMessage()),
        'error'
      );

      wp_redirect(admin_url('admin.php?page=markdown-fm-edit-schema&template=' . urlencode($template_file)));
      exit;

    } catch (\Exception $e) {
      wp_die('Error saving schema: ' . esc_html($e->getMessage()));
    }
  }
}
