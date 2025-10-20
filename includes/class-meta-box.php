<?php
namespace MarkdownFM;

use Symfony\Component\Yaml\Yaml;

if (!defined('ABSPATH')) exit;

class Meta_Box {

  public static function init() {
    // Hook to conditionally register the meta box
    add_action('add_meta_boxes', [__CLASS__, 'conditionally_register_meta_box'], 10, 2);
    add_action('save_post', [__CLASS__, 'save_meta_box']);
  }

  /**
   * Register the meta box only if YAML is enabled and schema exists
   */
  public static function conditionally_register_meta_box($post_type, $post) {
    if (!$post) return;

    $template = get_page_template_slug($post->ID);
    if (!$template) return;

    $enabled_templates = get_option('markdown_fm_templates_enabled', []);
    $schemas = get_option('markdown_fm_schemas', []);
    $yaml_schema = $schemas[$template]['yaml'] ?? '';

    // Only add the meta box if the template is enabled and has a YAML schema
    if (!empty($enabled_templates[$template]) && !empty($yaml_schema)) {
      add_meta_box(
        'markdown_fm_meta_box',
        'Markdown FM Fields',
        [__CLASS__, 'render_meta_box'],
        null, // all post types
        'normal',
        'high'
      );

      // Hide default content editor
      remove_post_type_support($post_type, 'editor');
    }
  }

  /**
   * Render the meta box fields from YAML
   */
  public static function render_meta_box($post) {
    $template = get_page_template_slug($post->ID);

    $schemas = get_option('markdown_fm_schemas', []);
    $yaml_schema = $schemas[$template]['yaml'] ?? '';

    if (empty($yaml_schema)) {
      echo '<p>No YAML schema found for this template.</p>';
      echo '<p>Go to <strong>Markdown FM → Templates</strong> to define one.</p>';
      return;
    }

      // Add a button to edit the schema
      echo '<p style="margin-bottom:20px;">';
      echo '<a href="' . esc_url(admin_url('admin.php?page=markdown-fm-schema&template=' . urlencode($template))) . '" class="button button-secondary">Edit YAML Schema</a>';
      echo '</p>';

    try {
      $schema = Yaml::parse($yaml_schema);
    } catch (\Exception $e) {
      echo '<p><strong>YAML Parse Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
      return;
    }

    $fields = $schema['fields'] ?? [];
    if (empty($fields)) {
      echo '<p>No fields defined in schema.</p>';
      return;
    }

    $saved_data = get_post_meta($post->ID, '_markdown_fm_data', true);
    wp_nonce_field('markdown_fm_save_meta_box', 'markdown_fm_nonce');

    echo '<div class="markdown-fm-fields">';

    foreach ($fields as $field) {
      $name  = $field['name'] ?? '';
      $label = $field['label'] ?? $name;
      $type  = $field['type'] ?? 'string';
      $value = $saved_data[$name] ?? '';

      echo '<p>';
      echo '<label><strong>' . esc_html($label) . '</strong></label><br>';

      switch ($type) {
        case 'string':
          echo '<input type="text" name="markdown_fm_data[' . esc_attr($name) . ']" value="' . esc_attr($value) . '" class="widefat">';
          break;

        case 'textarea':
          echo '<textarea name="markdown_fm_data[' . esc_attr($name) . ']" rows="4" class="widefat">' . esc_textarea($value) . '</textarea>';
          break;

        case 'code':
          echo '<textarea name="markdown_fm_data[' . esc_attr($name) . ']" rows="8" class="widefat markdown-code-field">' . esc_textarea($value) . '</textarea>';
          echo '<p class="description">Markdown-enabled field</p>';
          break;

        case 'date':
          echo '<input type="datetime-local" name="markdown_fm_data[' . esc_attr($name) . ']" value="' . esc_attr($value) . '" class="widefat">';
          break;

        case 'object':
          echo '<fieldset style="border:1px solid #ccc; padding:10px;">';
          echo '<legend>' . esc_html($label) . '</legend>';
          $subfields = $field['fields'] ?? [];
          foreach ($subfields as $subfield) {
            $subname  = $subfield['name'] ?? '';
            $subtype  = $subfield['type'] ?? 'string';
            $subvalue = $value[$subname] ?? '';
            echo '<p><label>' . esc_html($subfield['label'] ?? $subname) . '</label><br>';
            echo '<input type="text" name="markdown_fm_data[' . esc_attr($name) . '][' . esc_attr($subname) . ']" value="' . esc_attr($subvalue) . '" class="widefat"></p>';
          }
          echo '</fieldset>';
          break;

        default:
          echo '<input type="text" name="markdown_fm_data[' . esc_attr($name) . ']" value="' . esc_attr($value) . '" class="widefat">';
          break;
      }

      echo '</p>';
    }

    $yaml_post_data = '';

    if (!empty($saved_data)) {
      try {
        $yaml_post_data = Yaml::dump($saved_data, 4, 2);
      } catch (\Exception $e) {
        $yaml_post_data = 'Error generating YAML: ' . $e->getMessage();
      }
    }

    echo '<details>';
    echo '<summary>Show raw data</summary>';
    echo '<div>';
    // Show current page/post data in YAML
    echo '<h4>Current Page Data (YAML)</h4>';
    echo '<textarea readonly rows="12" class="widefat" style="background:#f9f9f9; font-family:monospace;">'
     . esc_textarea($yaml_post_data)
     . '</textarea>';
    echo '</div>';
    echo '</details>';
    echo '</div>';
  }

  /**
   * Save meta box data
   */
  public static function save_meta_box($post_id) {
    if (!isset($_POST['markdown_fm_nonce']) || !wp_verify_nonce($_POST['markdown_fm_nonce'], 'markdown_fm_save_meta_box')) {
      return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['markdown_fm_data']) && is_array($_POST['markdown_fm_data'])) {
      update_post_meta($post_id, '_markdown_fm_data', $_POST['markdown_fm_data']);
    }
  }
}
