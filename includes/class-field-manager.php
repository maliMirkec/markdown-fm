<?php
namespace MarkdownFM;

if (!defined('ABSPATH')) exit;

class Field_Manager {

  private static $schemas = [];

  public static function init() {
    // Load saved schemas from database
    self::$schemas = get_option('markdown_fm_schemas', []);
  }

  public static function get_schemas() {
    return self::$schemas;
  }

  public static function get_fields_for_template($template_name) {
    return self::$schemas[$template_name]['fields'] ?? [];
  }

  public static function save_schema($template_name, $fields) {
    self::$schemas[$template_name] = ['fields' => $fields];
    update_option('markdown_fm_schemas', self::$schemas);
  }

  public static function render_fields($fields, $data = [], $prefix = 'markdown_fm_data') {
    foreach ($fields as $field) {
      $value = $data[$field['name']] ?? '';
      echo '<p>';
      echo '<label><strong>' . esc_html($field['label'] ?? $field['name']) . '</strong></label><br>';
      if ($field['type'] === 'string') {
        echo '<input type="text" name="' . esc_attr($prefix . '[' . $field['name'] . ']') . '" value="' . esc_attr($value) . '" class="widefat">';
      } elseif ($field['type'] === 'code') {
        echo '<textarea name="' . esc_attr($prefix . '[' . $field['name'] . ']') . '" rows="8" class="widefat">' . esc_textarea($value) . '</textarea>';
      }
      echo '</p>';
    }
  }

}
