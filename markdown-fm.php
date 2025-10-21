<?php
/**
 * Plugin Name: Markdown FM
 * Plugin URI: https://www.silvestar.codes
 * Description: A WordPress plugin for managing YAML frontmatter schemas in theme templates
 * Version: 1.0.0
 * Author: Silvestar Bistrović
 * Author URI: https://www.silvestar.codes
 * Author Email: me@silvestar.codes
 * License: GPL v2 or later
 * Text Domain: markdown-fm
 */

if (!defined('ABSPATH')) {
  exit;
}

define('MARKDOWN_FM_VERSION', '1.0.0');
define('MARKDOWN_FM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MARKDOWN_FM_PLUGIN_URL', plugin_dir_url(__FILE__));

class Markdown_FM {
  private static $instance = null;

  public static function get_instance() {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function __construct() {
    $this->init_hooks();
  }

  private function init_hooks() {
    add_action('admin_menu', [$this, 'add_admin_menu']);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    add_action('add_meta_boxes', [$this, 'add_schema_meta_box']);
    add_action('save_post', [$this, 'save_schema_data']);
    add_action('wp_ajax_markdown_fm_save_template_settings', [$this, 'ajax_save_template_settings']);
    add_action('wp_ajax_markdown_fm_save_schema', [$this, 'ajax_save_schema']);
    add_action('wp_ajax_markdown_fm_get_schema', [$this, 'ajax_get_schema']);
  }

  public function add_admin_menu() {
    add_menu_page(
      __('Markdown FM', 'markdown-fm'),
      __('Markdown FM', 'markdown-fm'),
      'manage_options',
      'markdown-fm',
      [$this, 'render_admin_page'],
      'dashicons-edit-page',
      30
    );
  }

  public function enqueue_admin_assets($hook) {
    if ('toplevel_page_markdown-fm' !== $hook && 'post.php' !== get_current_screen()->base && 'post-new.php' !== get_current_screen()->base) {
      return;
    }

    wp_enqueue_style('markdown-fm-admin', MARKDOWN_FM_PLUGIN_URL . 'assets/admin.css', [], MARKDOWN_FM_VERSION);
    wp_enqueue_script('markdown-fm-admin', MARKDOWN_FM_PLUGIN_URL . 'assets/admin.js', ['jquery'], MARKDOWN_FM_VERSION, true);

    wp_localize_script('markdown-fm-admin', 'markdownFM', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('markdown_fm_nonce')
    ]);
  }

  public function render_admin_page() {
    if (!current_user_can('manage_options')) {
      return;
    }

    $templates = $this->get_theme_templates();
    $template_settings = get_option('markdown_fm_template_settings', []);
    $schemas = get_option('markdown_fm_schemas', []);

    include MARKDOWN_FM_PLUGIN_DIR . 'templates/admin-page.php';
  }

  private function get_theme_templates() {
    $templates = [];
    $theme = wp_get_theme();
    $template_files = $theme->get_files('php', 1);

    foreach ($template_files as $file => $path) {
      if (preg_match('/^(header|footer|sidebar|searchform|comments|archive|single|page|index|404|category|tag|author|date|search|attachment)/', basename($file))) {
        $templates[] = [
          'file' => basename($file),
          'path' => $path,
          'name' => $this->format_template_name(basename($file))
        ];
      }
    }

    $page_templates = get_page_templates();
    foreach ($page_templates as $name => $file) {
      $templates[] = [
        'file' => $file,
        'path' => get_template_directory() . '/' . $file,
        'name' => $name
      ];
    }

    return $templates;
  }

  private function format_template_name($filename) {
    $name = str_replace(['-', '_', '.php'], [' ', ' ', ''], $filename);
    return ucwords($name);
  }

  public function ajax_save_template_settings() {
    check_ajax_referer('markdown_fm_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $template = sanitize_text_field($_POST['template']);
    $enabled = isset($_POST['enabled']) && $_POST['enabled'] === 'true';

    $settings = get_option('markdown_fm_template_settings', []);
    $settings[$template] = $enabled;

    update_option('markdown_fm_template_settings', $settings);

    wp_send_json_success();
  }

  public function ajax_save_schema() {
    check_ajax_referer('markdown_fm_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $template = sanitize_text_field($_POST['template']);
    $schema = wp_unslash($_POST['schema']);

    $schemas = get_option('markdown_fm_schemas', []);
    $schemas[$template] = $schema;

    update_option('markdown_fm_schemas', $schemas);

    wp_send_json_success();
  }

  public function ajax_get_schema() {
    check_ajax_referer('markdown_fm_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
      wp_send_json_error('Permission denied');
    }

    $template = sanitize_text_field($_POST['template']);
    $schemas = get_option('markdown_fm_schemas', []);

    wp_send_json_success([
      'schema' => isset($schemas[$template]) ? $schemas[$template] : ''
    ]);
  }

  public function add_schema_meta_box() {
    $post_types = ['page', 'post'];

    foreach ($post_types as $post_type) {
      add_meta_box(
        'markdown_fm_schema',
        __('Markdown FM Schema', 'markdown-fm'),
        [$this, 'render_schema_meta_box'],
        $post_type,
        'normal',
        'high'
      );
    }
  }

  public function render_schema_meta_box($post) {
    wp_nonce_field('markdown_fm_meta_box', 'markdown_fm_meta_box_nonce');

    $template = get_post_meta($post->ID, '_wp_page_template', true);
    if (empty($template) || $template === 'default') {
      $template = 'page.php';
    }

    $template_settings = get_option('markdown_fm_template_settings', []);

    if (!isset($template_settings[$template]) || !$template_settings[$template]) {
      echo '<p>' . __('This template does not have YAML frontmatter enabled.', 'markdown-fm') . '</p>';
      return;
    }

    $schemas = get_option('markdown_fm_schemas', []);

    if (!isset($schemas[$template]) || empty($schemas[$template])) {
      echo '<p>' . __('No schema defined for this template.', 'markdown-fm') . '</p>';
      return;
    }

    $schema_yaml = $schemas[$template];
    $schema = $this->parse_yaml_schema($schema_yaml);

    if (!$schema || !isset($schema['fields'])) {
      echo '<p>' . __('Invalid schema format.', 'markdown-fm') . '</p>';
      return;
    }

    $saved_data = get_post_meta($post->ID, '_markdown_fm_data', true);
    if (empty($saved_data)) {
      $saved_data = [];
    }

    echo '<div class="markdown-fm-fields">';
    echo '<pre>';
    var_dump($schema['fields'], $saved_data);
    echo '</pre>';
    $this->render_schema_fields($schema['fields'], $saved_data);
    echo '</div>';
  }

  private function parse_yaml_schema($yaml) {
    if (!function_exists('yaml_parse')) {
      return $this->simple_yaml_parse($yaml);
    }
    return yaml_parse($yaml);
  }

  private function simple_yaml_parse($yaml) {
    $lines = explode("\n", $yaml);
    $result = [];
    $current = &$result;
    $stack = [];
    $last_indent = 0;

    foreach ($lines as $line) {
      if (trim($line) === '' || strpos(trim($line), '#') === 0) {
        continue;
      }

      preg_match('/^(\s*)/', $line, $indent_match);
      $indent = strlen($indent_match[1]);
      $line = trim($line);

      if (strpos($line, ':') !== false) {
        list($key, $value) = array_map('trim', explode(':', $line, 2));

        if ($indent > $last_indent) {
          $stack[] = &$current;
        } elseif ($indent < $last_indent) {
          array_pop($stack);
          if (!empty($stack)) {
            $current = &$stack[count($stack) - 1];
          }
        }

        if ($value === '' || $value === null) {
          $current[$key] = [];
          $stack[] = &$current;
          $current = &$current[$key];
        } else {
          $current[$key] = $value;
        }

        $last_indent = $indent;
      } elseif (strpos($line, '- ') === 0) {
        $value = substr($line, 2);
        if (!is_array(end($current))) {
          $keys = array_keys($current);
          $last_key = end($keys);
          $current[$last_key] = [];
        }
        $keys = array_keys($current);
        $last_key = end($keys);

        if (strpos($value, ':') !== false) {
          list($k, $v) = array_map('trim', explode(':', $value, 2));
          $current[$last_key][] = [$k => $v];
        } else {
          $current[$last_key][] = $value;
        }
      }
    }

    return $result;
  }

  private function render_schema_fields($fields, $saved_data, $prefix = '') {
    foreach ($fields as $field) {
      $field_name = $prefix . $field['name'];
      $field_value = isset($saved_data[$field['name']]) ? $saved_data[$field['name']] : (isset($field['default']) ? $field['default'] : '');
      $field_label = isset($field['label']) ? $field['label'] : ucfirst($field['name']);

      echo '<div class="markdown-fm-field" data-type="' . esc_attr($field['type']) . '">';
      echo '<label for="' . esc_attr($field_name) . '">' . esc_html($field_label) . '</label>';

      switch ($field['type']) {
        case 'boolean':
          echo '<input type="checkbox" name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_name) . '" value="1" ' . checked($field_value, 1, false) . ' />';
          break;

        case 'string':
          $options = isset($field['options']) ? $field['options'] : [];
          $minlength = isset($options['minlength']) ? 'minlength="' . intval($options['minlength']) . '"' : '';
          $maxlength = isset($options['maxlength']) ? 'maxlength="' . intval($options['maxlength']) . '"' : '';
          echo '<input type="text" name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '" ' . $minlength . ' ' . $maxlength . ' class="regular-text" />';
          break;

        case 'text':
          $options = isset($field['options']) ? $field['options'] : [];
          $maxlength = isset($options['maxlength']) ? 'maxlength="' . intval($options['maxlength']) . '"' : '';
          echo '<textarea name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_name) . '" rows="5" class="large-text" ' . $maxlength . '>' . esc_textarea($field_value) . '</textarea>';
          break;

        case 'rich-text':
          wp_editor($field_value, $field_name, [
            'textarea_name' => 'markdown_fm[' . $field['name'] . ']',
            'textarea_rows' => 10
          ]);
          break;

        case 'code':
          $options = isset($field['options']) ? $field['options'] : [];
          $language = isset($options['language']) ? $options['language'] : 'html';
          echo '<textarea name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_name) . '" rows="10" class="large-text code" data-language="' . esc_attr($language) . '">' . esc_textarea($field_value) . '</textarea>';
          break;

        case 'number':
          $options = isset($field['options']) ? $field['options'] : [];
          $min = isset($options['min']) ? 'min="' . intval($options['min']) . '"' : '';
          $max = isset($options['max']) ? 'max="' . intval($options['max']) . '"' : '';
          echo '<input type="number" name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '" ' . $min . ' ' . $max . ' class="small-text" />';
          break;

        case 'date':
          $options = isset($field['options']) ? $field['options'] : [];
          $has_time = isset($options['time']) && $options['time'];
          $type = $has_time ? 'datetime-local' : 'date';
          echo '<input type="' . $type . '" name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '" />';
          break;

        case 'select':
          $options = isset($field['options']) ? $field['options'] : [];
          $multiple = isset($options['multiple']) && $options['multiple'];
          $values = isset($options['values']) ? $options['values'] : [];

          echo '<select name="markdown_fm[' . esc_attr($field['name']) . ']' . ($multiple ? '[]' : '') . '" id="' . esc_attr($field_name) . '" ' . ($multiple ? 'multiple' : '') . '>';
          echo '<option value="">-- Select --</option>';
          foreach ($values as $option) {
            $selected = ($field_value === $option['value']) ? 'selected' : '';
            echo '<option value="' . esc_attr($option['value']) . '" ' . $selected . '>' . esc_html($option['label']) . '</option>';
          }
          echo '</select>';
          break;

        case 'image':
          echo '<input type="hidden" name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '" />';
          echo '<button type="button" class="button markdown-fm-upload-image" data-target="' . esc_attr($field_name) . '">Upload Image</button>';
          if ($field_value) {
            echo '<div class="markdown-fm-image-preview"><img src="' . esc_url($field_value) . '" style="max-width: 200px; display: block; margin-top: 10px;" /></div>';
          }
          break;

        case 'file':
          echo '<input type="hidden" name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '" />';
          echo '<button type="button" class="button markdown-fm-upload-file" data-target="' . esc_attr($field_name) . '">Upload File</button>';
          if ($field_value) {
            echo '<div class="markdown-fm-file-name">' . esc_html(basename($field_value)) . '</div>';
          }
          break;

        case 'object':
          if (isset($field['fields'])) {
            echo '<div class="markdown-fm-object">';
            $object_data = is_array($field_value) ? $field_value : [];
            $this->render_schema_fields($field['fields'], $object_data, $field['name'] . '_');
            echo '</div>';
          }
          break;

        case 'block':
          $is_list = isset($field['list']) && $field['list'];
          $blocks = isset($field['blocks']) ? $field['blocks'] : [];
          $block_key = isset($field['blockKey']) ? $field['blockKey'] : 'type';

          echo '<div class="markdown-fm-block-container" data-field-name="' . esc_attr($field['name']) . '">';

          if ($is_list) {
            $block_values = is_array($field_value) ? $field_value : [];
            echo '<div class="markdown-fm-block-list">';

            foreach ($block_values as $index => $block_data) {
              $this->render_block_item($field, $blocks, $block_data, $index, $block_key);
            }

            echo '</div>';
            echo '<div class="markdown-fm-block-controls">';
            echo '<select class="markdown-fm-block-type-select">';
            echo '<option value="">-- Add Block --</option>';
            foreach ($blocks as $block) {
              echo '<option value="' . esc_attr($block['name']) . '">' . esc_html($block['label']) . '</option>';
            }
            echo '</select>';
            echo '<button type="button" class="button markdown-fm-add-block">Add Block</button>';
            echo '</div>';
          }

          echo '</div>';
          break;
      }

      echo '</div>';
    }
  }

  private function render_block_item($field, $blocks, $block_data, $index, $block_key) {
    $block_type = isset($block_data[$block_key]) ? $block_data[$block_key] : '';
    $block_def = null;

    foreach ($blocks as $block) {
      if ($block['name'] === $block_type) {
        $block_def = $block;
        break;
      }
    }

    if (!$block_def) {
      return;
    }

    echo '<div class="markdown-fm-block-item" data-block-type="' . esc_attr($block_type) . '">';
    echo '<div class="markdown-fm-block-header">';
    echo '<strong>' . esc_html($block_def['label']) . '</strong>';
    echo '<button type="button" class="button markdown-fm-remove-block">Remove</button>';
    echo '</div>';
    echo '<input type="hidden" name="markdown_fm[' . esc_attr($field['name']) . '][' . $index . '][' . esc_attr($block_key) . ']" value="' . esc_attr($block_type) . '" />';

    if (isset($block_def['fields'])) {
      echo '<div class="markdown-fm-block-fields">';
      foreach ($block_def['fields'] as $block_field) {
        $block_field_name = $field['name'] . '[' . $index . '][' . $block_field['name'] . ']';
        $block_field_value = isset($block_data[$block_field['name']]) ? $block_data[$block_field['name']] : '';

        echo '<div class="markdown-fm-field">';
        echo '<label>' . esc_html($block_field['label']) . '</label>';

        if ($block_field['type'] === 'rich-text') {
          wp_editor($block_field_value, 'mfm_' . $index . '_' . $block_field['name'], [
            'textarea_name' => 'markdown_fm[' . $block_field_name . ']',
            'textarea_rows' => 5
          ]);
        } else {
          echo '<input type="text" name="markdown_fm[' . esc_attr($block_field_name) . ']" value="' . esc_attr($block_field_value) . '" class="regular-text" />';
        }

        echo '</div>';
      }
      echo '</div>';
    }

    echo '</div>';
  }

  public function save_schema_data($post_id) {
    if (!isset($_POST['markdown_fm_meta_box_nonce'])) {
      return;
    }

    if (!wp_verify_nonce($_POST['markdown_fm_meta_box_nonce'], 'markdown_fm_meta_box')) {
      return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    if (!current_user_can('edit_post', $post_id)) {
      return;
    }

    if (isset($_POST['markdown_fm'])) {
      update_post_meta($post_id, '_markdown_fm_data', $_POST['markdown_fm']);
    }
  }
}

function markdown_fm_init() {
  return Markdown_FM::get_instance();
}

add_action('plugins_loaded', 'markdown_fm_init');

register_uninstall_hook(__FILE__, 'markdown_fm_uninstall');

function markdown_fm_uninstall() {
  delete_option('markdown_fm_template_settings');
  delete_option('markdown_fm_schemas');

  global $wpdb;
  $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_markdown_fm_data'");
}
