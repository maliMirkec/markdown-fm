<?php
namespace MarkdownFM;

use Symfony\Component\Yaml\Yaml;

class Field_Manager {

  const OPTION_NAME = 'markdown_fm_field_schemas';

  /**
   * Load all field schemas from WP option
   */
  public static function get_schemas() {
    $yaml_text = get_option(self::OPTION_NAME, '');
    if (!$yaml_text) return [];

    try {
      $schemas = Yaml::parse($yaml_text);
      if (!is_array($schemas)) return [];
      return $schemas;
    } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
      return []; // fail gracefully
    }
  }

  /**
   * Get fields for a specific template or collection name
   */
  public static function get_fields_for_template($template_name) {
    $schemas = self::get_schemas();
    if (empty($schemas['content'])) return [];

    foreach ($schemas['content'] as $collection) {
      if (($collection['name'] ?? '') === $template_name) {
        return $collection['fields'] ?? [];
      }
    }

    return [];
  }

  /**
   * Render fields in meta box
   * $data is existing post meta
   */
  public static function render_fields($fields, $data = [], $prefix = 'markdown_fm_data') {
    foreach ($fields as $index => $field) {
      $name = $field['name'] ?? 'field_' . $index;
      $label = $field['label'] ?? ucfirst($name);
      $type = $field['type'] ?? 'string';
      $hidden = $field['hidden'] ?? false;
      $default = $field['default'] ?? '';
      $value = $data[$name] ?? $default;

      $field_name_attr = $prefix . '[' . esc_attr($name) . ']';

      if ($hidden) {
        echo '<input type="hidden" name="' . $field_name_attr . '" value="' . esc_attr($value) . '">';
        continue;
      }

      echo '<p><label>' . esc_html($label) . '</label><br>';

      switch ($type) {
        case 'string':
          echo '<input type="text" style="width:100%;" name="' . $field_name_attr . '" value="' . esc_attr($value) . '">';
          break;

        case 'date':
          $format = $field['options']['format'] ?? 'Y-m-d H:i:s';
          echo '<input type="datetime-local" style="width:100%;" name="' . $field_name_attr . '" value="' . esc_attr($value) . '">';
          break;

        case 'code':
          $language = $field['options']['language'] ?? 'text';
          echo '<textarea style="width:100%;height:200px;" data-language="' . esc_attr($language) . '" name="' . $field_name_attr . '">' . esc_textarea($value) . '</textarea>';
          break;

        case 'object':
          if (!empty($field['fields'])) {
            echo '<div style="padding-left:20px; border-left:2px solid #ddd; margin-bottom:10px;">';
            self::render_fields($field['fields'], $value, $field_name_attr);
            echo '</div>';
          }
          break;

        case 'collection':
          if (!empty($value) && is_array($value)) {
            foreach ($value as $i => $item) {
              echo '<div style="padding-left:20px; border-left:2px solid #ccc; margin-bottom:10px;">';
              self::render_fields($field['fields'], $item, $field_name_attr . '[' . $i . ']');
              echo '</div>';
            }
          }
          // Optionally, button to add new items via JS
          break;

        default:
          echo '<input type="text" style="width:100%;" name="' . $field_name_attr . '" value="' . esc_attr($value) . '">';
      }

      echo '</p>';
    }
  }

  /**
   * Save fields data
   */
  public static function save_post_fields($post_id, $post_data) {
    update_post_meta($post_id, Meta_Box::META_KEY, $post_data);
  }
}
