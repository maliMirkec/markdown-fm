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

// Load Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
  require_once __DIR__ . '/vendor/autoload.php';
} else {
  add_action('admin_notices', function() {
    echo '<div class="notice notice-error"><p>';
    echo '<strong>Markdown FM:</strong> Please run <code>composer install</code> in the plugin directory to install dependencies.';
    echo '</p></div>';
  });
  return;
}

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

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
    add_action('edit_form_after_title', [$this, 'render_schema_meta_box_after_title']);
    add_action('save_post', [$this, 'save_schema_data']);
    add_action('wp_ajax_markdown_fm_save_template_settings', [$this, 'ajax_save_template_settings']);
    add_action('wp_ajax_markdown_fm_save_schema', [$this, 'ajax_save_schema']);
    add_action('wp_ajax_markdown_fm_get_schema', [$this, 'ajax_get_schema']);
    add_action('wp_ajax_markdown_fm_get_partial_data', [$this, 'ajax_get_partial_data']);
    add_action('wp_ajax_markdown_fm_save_partial_data', [$this, 'ajax_save_partial_data']);

    // Clear cache on theme switch
    add_action('switch_theme', [$this, 'clear_template_cache']);
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
    // Load on the plugin settings page
    $is_settings_page = ('toplevel_page_markdown-fm' === $hook);

    // Load on post edit screens
    $current_screen = get_current_screen();
    $is_post_edit = false;

    if ($current_screen) {
      $is_post_edit = in_array($current_screen->base, ['post', 'post-new']) &&
                      in_array($current_screen->post_type, ['page', 'post']);
    }

    // Only load if on settings page or post edit screen
    if (!$is_settings_page && !$is_post_edit) {
      return;
    }

    wp_enqueue_style('markdown-fm-admin', MARKDOWN_FM_PLUGIN_URL . 'assets/admin.css', [], MARKDOWN_FM_VERSION);
    wp_enqueue_script('markdown-fm-admin', MARKDOWN_FM_PLUGIN_URL . 'assets/admin.js', ['jquery'], MARKDOWN_FM_VERSION, true);

    // Get current template and schema for post edit screens
    $schema_data = null;
    if ($is_post_edit && isset($_GET['post'])) {
      $post_id = intval($_GET['post']);
      $template = get_post_meta($post_id, '_wp_page_template', true);
      if (empty($template) || $template === 'default') {
        $template = 'page.php';
      }

      $schemas = get_option('markdown_fm_schemas', []);
      if (isset($schemas[$template]) && !empty($schemas[$template])) {
        $schema_data = $this->parse_yaml_schema($schemas[$template]);
      }
    }

    wp_localize_script('markdown-fm-admin', 'markdownFM', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('markdown_fm_nonce'),
      'schema' => $schema_data
    ]);
  }

  public function render_admin_page() {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.', 'markdown-fm'));
    }

    // Handle refresh action
    if (isset($_GET['refresh_mdfm'])) {
      $this->clear_template_cache();
      add_settings_error(
        'markdown_fm_messages',
        'markdown_fm_message',
        __('Template list refreshed successfully!', 'markdown-fm'),
        'updated'
      );
    }

    $theme_files = $this->get_theme_templates();
    $templates = $theme_files['templates'];
    $partials = $theme_files['partials'];
    $template_settings = get_option('markdown_fm_template_settings', []);
    $schemas = get_option('markdown_fm_schemas', []);

    include MARKDOWN_FM_PLUGIN_DIR . 'templates/admin-page.php';
  }

  private function get_theme_templates() {
    // Check cache first
    $cache_key = 'markdown_fm_templates_' . get_stylesheet();
    $cached = get_transient($cache_key);

    if ($cached !== false && !isset($_GET['refresh_mdfm'])) {
      return $cached;
    }

    $templates = [];
    $partials = [];
    $theme = wp_get_theme();

    // Get all template files
    $template_files = $theme->get_files('php', -1); // -1 for unlimited depth

    // WordPress template hierarchy - only main templates, not partials
    $valid_template_patterns = [
      'index.php',
      'front-page.php',
      'home.php',
      'page.php',
      'single.php',
      'archive.php',
      'category.php',
      'tag.php',
      'taxonomy.php',
      'author.php',
      'date.php',
      'search.php',
      'attachment.php',
      '404.php',
      // Specific templates with prefixes
      'page-*.php',
      'single-*.php',
      'archive-*.php',
      'category-*.php',
      'tag-*.php',
      'taxonomy-*.php',
      'author-*.php'
    ];

    // Partial patterns (automatic detection)
    $partial_patterns = [
      'header.php',
      'footer.php',
      'sidebar.php',
      'header-*.php',
      'footer-*.php',
      'sidebar-*.php',
      'content.php',
      'content-*.php',
      'comments.php',
      'searchform.php'
    ];

    foreach ($template_files as $file => $path) {
      $basename = basename($file);
      $relative_path = str_replace(get_template_directory() . '/', '', $path);

      // Check if it's a root-level main template
      if (dirname($file) === '.') {
        $is_valid_template = false;
        foreach ($valid_template_patterns as $pattern) {
          if ($pattern === $basename || fnmatch($pattern, $basename)) {
            $is_valid_template = true;
            break;
          }
        }

        if ($is_valid_template) {
          $templates[] = [
            'file' => $basename,
            'path' => $path,
            'name' => $this->format_template_name($basename)
          ];
        }
      }

      // Check if it's a partial (automatic detection by pattern)
      $is_partial = false;
      foreach ($partial_patterns as $pattern) {
        if ($pattern === $basename || fnmatch($pattern, $basename)) {
          $is_partial = true;
          break;
        }
      }

      // Also check for @mdfm marker in file header (custom partials)
      if (!$is_partial && $this->has_mdfm_marker($path)) {
        $is_partial = true;
      }

      if ($is_partial) {
        $partials[] = [
          'file' => $relative_path,
          'path' => $path,
          'name' => $this->format_template_name($basename)
        ];
      }
    }

    // Add custom page templates (templates with Template Name header)
    $page_templates = get_page_templates();
    foreach ($page_templates as $name => $file) {
      // Only include templates in the root directory
      if (strpos($file, '/') === false) {
        // Avoid duplicates
        $already_added = false;
        foreach ($templates as $existing) {
          if ($existing['file'] === $file) {
            $already_added = true;
            break;
          }
        }

        if (!$already_added) {
          $templates[] = [
            'file' => $file,
            'path' => get_template_directory() . '/' . $file,
            'name' => $name
          ];
        }
      }
    }

    $result = [
      'templates' => $templates,
      'partials' => $partials
    ];

    // Cache for 1 hour
    set_transient($cache_key, $result, HOUR_IN_SECONDS);

    return $result;
  }

  /**
   * Check if a file has the @mdfm marker in its header
   * Only reads first 30 lines for performance
   */
  private function has_mdfm_marker($file_path) {
    if (!file_exists($file_path) || !is_readable($file_path)) {
      return false;
    }

    $file = fopen($file_path, 'r');
    if (!$file) {
      return false;
    }

    $lines_to_check = 30;
    $line_count = 0;
    $has_marker = false;

    while (!feof($file) && $line_count < $lines_to_check) {
      $line = fgets($file);
      $line_count++;

      // Check for @mdfm marker (case insensitive)
      if (preg_match('/@mdfm/i', $line)) {
        $has_marker = true;
        break;
      }
    }

    fclose($file);
    return $has_marker;
  }

  /**
   * Clear the template cache
   */
  public function clear_template_cache() {
    $cache_key = 'markdown_fm_templates_' . get_stylesheet();
    delete_transient($cache_key);
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

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $template = sanitize_text_field($_POST['template']);
    $schemas = get_option('markdown_fm_schemas', []);

    wp_send_json_success([
      'schema' => isset($schemas[$template]) ? $schemas[$template] : ''
    ]);
  }

  public function add_schema_meta_box() {
    // Meta box is rendered via edit_form_after_title hook instead
  }

  public function render_schema_meta_box_after_title($post) {
    // Only render for post types we support
    if (!in_array($post->post_type, ['page', 'post'])) {
      return;
    }

    wp_nonce_field('markdown_fm_meta_box', 'markdown_fm_meta_box_nonce');

    $template = get_post_meta($post->ID, '_wp_page_template', true);
    if (empty($template) || $template === 'default') {
      $template = 'page.php';
    }

    $template_settings = get_option('markdown_fm_template_settings', []);

    if (!isset($template_settings[$template]) || !$template_settings[$template]) {
      return;
    }

    $schemas = get_option('markdown_fm_schemas', []);

    if (!isset($schemas[$template]) || empty($schemas[$template])) {
      return;
    }

    $schema_yaml = $schemas[$template];
    $schema = $this->parse_yaml_schema($schema_yaml);

    if (!$schema || !isset($schema['fields'])) {
      return;
    }

    // Add link to edit schema
    $edit_schema_url = admin_url('admin.php?page=markdown-fm');

    echo '<div id="markdown-fm-meta-box" class="postbox" style="margin-bottom: 20px;">';
    echo '<div class="postbox-header"><h2 class="hndle">' . __('Markdown FM Schema', 'markdown-fm') . '</h2></div>';
    echo '<div class="inside">';
    echo '<div class="markdown-fm-meta-box-header" style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">';
    echo '<p style="margin: 0;">';
    echo '<strong>' . __('Template:', 'markdown-fm') . '</strong> ' . esc_html($template);
    echo ' | ';
    echo '<a href="' . esc_url($edit_schema_url) . '" target="_blank">' . __('Edit Schema', 'markdown-fm') . '</a>';
    echo '</p>';
    echo '<button type="button" class="button button-secondary markdown-fm-reset-data" data-post-id="' . esc_attr($post->ID) . '">';
    echo '<span class="dashicons dashicons-update-alt" style="margin-top: 3px;"></span> ';
    echo __('Reset All Data', 'markdown-fm');
    echo '</button>';
    echo '</div>';

    $saved_data = get_post_meta($post->ID, '_markdown_fm_data', true);
    if (empty($saved_data)) {
      $saved_data = [];
    }

    echo '<div class="markdown-fm-fields">';
    $this->render_schema_fields($schema['fields'], $saved_data);
    echo '</div>';
    echo '</div>';
    echo '</div>';
  }

  private function parse_yaml_schema($yaml) {
    try {
      return Yaml::parse($yaml);
    } catch (ParseException $e) {
      error_log('Markdown FM: YAML parsing error - ' . $e->getMessage());
      return null;
    }
  }

  private function render_schema_fields($fields, $saved_data, $prefix = '') {
    foreach ($fields as $field) {
      $field_name = $prefix . $field['name'];
      $field_id = 'mdfm_' . str_replace(['[', ']'], ['_', ''], $field_name);
      $field_value = isset($saved_data[$field['name']]) ? $saved_data[$field['name']] : (isset($field['default']) ? $field['default'] : '');
      $field_label = isset($field['label']) ? $field['label'] : ucfirst($field['name']);

      echo '<div class="markdown-fm-field" data-type="' . esc_attr($field['type']) . '">';
      echo '<label for="' . esc_attr($field_id) . '">' . esc_html($field_label) . '</label>';

      switch ($field['type']) {
        case 'boolean':
          echo '<input type="checkbox" name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" value="1" ' . checked($field_value, 1, false) . ' />';
          break;

        case 'string':
          $options = isset($field['options']) ? $field['options'] : [];
          $minlength = isset($options['minlength']) ? 'minlength="' . intval($options['minlength']) . '"' : '';
          $maxlength = isset($options['maxlength']) ? 'maxlength="' . intval($options['maxlength']) . '"' : '';
          echo '<input type="text" name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" value="' . esc_attr($field_value) . '" ' . $minlength . ' ' . $maxlength . ' class="regular-text" />';
          break;

        case 'text':
          $options = isset($field['options']) ? $field['options'] : [];
          $maxlength = isset($options['maxlength']) ? 'maxlength="' . intval($options['maxlength']) . '"' : '';
          echo '<textarea name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" rows="5" class="large-text" ' . $maxlength . '>' . esc_textarea($field_value) . '</textarea>';
          break;

        case 'rich-text':
          wp_editor($field_value, $field_id, [
            'textarea_name' => 'markdown_fm[' . $field['name'] . ']',
            'textarea_rows' => 10
          ]);
          break;

        case 'code':
          $options = isset($field['options']) ? $field['options'] : [];
          $language = isset($options['language']) ? $options['language'] : 'html';
          echo '<textarea name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" rows="10" class="large-text code" data-language="' . esc_attr($language) . '">' . esc_textarea($field_value) . '</textarea>';
          break;

        case 'number':
          $options = isset($field['options']) ? $field['options'] : [];
          $min = isset($options['min']) ? 'min="' . intval($options['min']) . '"' : '';
          $max = isset($options['max']) ? 'max="' . intval($options['max']) . '"' : '';
          echo '<input type="number" name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" value="' . esc_attr($field_value) . '" ' . $min . ' ' . $max . ' class="small-text" />';
          break;

        case 'date':
          $options = isset($field['options']) ? $field['options'] : [];
          $has_time = isset($options['time']) && $options['time'];
          $type = $has_time ? 'datetime-local' : 'date';
          echo '<input type="' . $type . '" name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" value="' . esc_attr($field_value) . '" />';
          break;

        case 'select':
          $options = isset($field['options']) ? $field['options'] : [];
          $multiple = isset($field['multiple']) && $field['multiple'];
          $values = isset($field['values']) ? $field['values'] : [];

          echo '<select name="markdown_fm[' . esc_attr($field['name']) . ']' . ($multiple ? '[]' : '') . '" id="' . esc_attr($field_id) . '" ' . ($multiple ? 'multiple' : '') . '>';
          echo '<option value="">-- Select --</option>';

          if (is_array($values)) {
            foreach ($values as $option) {
              // Handle both array format and simple values
              if (is_array($option)) {
                $opt_value = isset($option['value']) ? $option['value'] : '';
                $opt_label = isset($option['label']) ? $option['label'] : $opt_value;
              } else {
                $opt_value = $option;
                $opt_label = $option;
              }

              $selected = ($field_value === $opt_value) ? 'selected' : '';
              echo '<option value="' . esc_attr($opt_value) . '" ' . $selected . '>' . esc_html($opt_label) . '</option>';
            }
          }

          echo '</select>';
          break;

        case 'image':
          echo '<input type="hidden" name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" value="' . esc_attr($field_value) . '" />';
          echo '<div class="markdown-fm-media-buttons">';
          echo '<button type="button" class="button markdown-fm-upload-image" data-target="' . esc_attr($field_id) . '">Upload Image</button>';
          if ($field_value) {
            echo '<button type="button" class="button markdown-fm-clear-media" data-target="' . esc_attr($field_id) . '">Clear</button>';
          }
          echo '</div>';
          if ($field_value) {
            echo '<div class="markdown-fm-image-preview"><img src="' . esc_url($field_value) . '" style="max-width: 200px; display: block; margin-top: 10px;" /></div>';
          }
          break;

        case 'file':
          echo '<input type="hidden" name="markdown_fm[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" value="' . esc_attr($field_value) . '" />';
          echo '<div class="markdown-fm-media-buttons">';
          echo '<button type="button" class="button markdown-fm-upload-file" data-target="' . esc_attr($field_id) . '">Upload File</button>';
          if ($field_value) {
            echo '<button type="button" class="button markdown-fm-clear-media" data-target="' . esc_attr($field_id) . '">Clear</button>';
          }
          echo '</div>';
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

    if (isset($block_def['fields']) && is_array($block_def['fields'])) {
      echo '<div class="markdown-fm-block-fields">';

      foreach ($block_def['fields'] as $block_field) {
        $block_field_id = 'mdfm_' . $field['name'] . '_' . $index . '_' . $block_field['name'];
        $block_field_value = isset($block_data[$block_field['name']]) ? $block_data[$block_field['name']] : '';
        $block_field_type = isset($block_field['type']) ? $block_field['type'] : 'string';

        echo '<div class="markdown-fm-field">';
        echo '<label for="' . esc_attr($block_field_id) . '">' . esc_html($block_field['label']) . '</label>';

        if ($block_field_type === 'rich-text') {
          wp_editor($block_field_value, $block_field_id, [
            'textarea_name' => 'markdown_fm[' . $field['name'] . '][' . $index . '][' . $block_field['name'] . ']',
            'textarea_rows' => 5
          ]);
        } elseif ($block_field_type === 'text') {
          echo '<textarea name="markdown_fm[' . esc_attr($field['name']) . '][' . $index . '][' . esc_attr($block_field['name']) . ']" id="' . esc_attr($block_field_id) . '" rows="5" class="large-text">' . esc_textarea($block_field_value) . '</textarea>';
        } elseif ($block_field_type === 'number') {
          echo '<input type="number" name="markdown_fm[' . esc_attr($field['name']) . '][' . $index . '][' . esc_attr($block_field['name']) . ']" id="' . esc_attr($block_field_id) . '" value="' . esc_attr($block_field_value) . '" class="small-text" />';
        } else {
          // Default to text input for string and other types
          echo '<input type="text" name="markdown_fm[' . esc_attr($field['name']) . '][' . $index . '][' . esc_attr($block_field['name']) . ']" id="' . esc_attr($block_field_id) . '" value="' . esc_attr($block_field_value) . '" class="regular-text" />';
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

  public function ajax_get_partial_data() {
    check_ajax_referer('markdown_fm_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $template = sanitize_text_field($_POST['template']);

    // Get schema
    $schemas = get_option('markdown_fm_schemas', []);
    $schema_yaml = isset($schemas[$template]) ? $schemas[$template] : '';

    if (empty($schema_yaml)) {
      wp_send_json_error('No schema found');
      return;
    }

    $schema = $this->parse_yaml_schema($schema_yaml);

    // Get existing data
    $partial_data = get_option('markdown_fm_partial_data', []);
    $data = isset($partial_data[$template]) ? $partial_data[$template] : [];

    wp_send_json_success([
      'schema' => $schema,
      'data' => $data
    ]);
  }

  public function ajax_save_partial_data() {
    check_ajax_referer('markdown_fm_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $template = sanitize_text_field($_POST['template']);
    $data = json_decode(wp_unslash($_POST['data']), true);

    // Get existing partial data
    $partial_data = get_option('markdown_fm_partial_data', []);

    // Update data for this partial
    $partial_data[$template] = $data;

    // Save back to options
    update_option('markdown_fm_partial_data', $partial_data);

    wp_send_json_success();
  }
}

function markdown_fm_init() {
  return Markdown_FM::get_instance();
}

add_action('plugins_loaded', 'markdown_fm_init');

/**
 * Get a specific field value from Markdown FM data
 *
 * @param string $field_name The name of the field to retrieve
 * @param int|string $post_id Optional. Post ID or 'partial:filename' for partials. Defaults to current post.
 * @return mixed The field value, or null if not found
 *
 * Usage in templates:
 * - For page/post: $hero = markdown_fm_get_field('hero');
 * - For specific post: $hero = markdown_fm_get_field('hero', 123);
 * - For partial: $logo = markdown_fm_get_field('logo', 'partial:header.php');
 */
function markdown_fm_get_field($field_name, $post_id = null) {
  // Handle partials
  if (is_string($post_id) && strpos($post_id, 'partial:') === 0) {
    $partial_file = str_replace('partial:', '', $post_id);
    $partial_data = get_option('markdown_fm_partial_data', []);

    if (isset($partial_data[$partial_file][$field_name])) {
      return $partial_data[$partial_file][$field_name];
    }

    return null;
  }

  // Handle post/page data
  if ($post_id === null) {
    $post_id = get_the_ID();
  }

  if (!$post_id) {
    return null;
  }

  $data = get_post_meta($post_id, '_markdown_fm_data', true);

  if (is_array($data) && isset($data[$field_name])) {
    return $data[$field_name];
  }

  return null;
}

/**
 * Get all Markdown FM fields for the current post or partial
 *
 * @param int|string $post_id Optional. Post ID or 'partial:filename' for partials. Defaults to current post.
 * @return array Array of all field values
 *
 * Usage in templates:
 * - For page/post: $fields = markdown_fm_get_fields();
 * - For specific post: $fields = markdown_fm_get_fields(123);
 * - For partial: $fields = markdown_fm_get_fields('partial:header.php');
 */
function markdown_fm_get_fields($post_id = null) {
  // Handle partials
  if (is_string($post_id) && strpos($post_id, 'partial:') === 0) {
    $partial_file = str_replace('partial:', '', $post_id);
    $partial_data = get_option('markdown_fm_partial_data', []);

    return isset($partial_data[$partial_file]) ? $partial_data[$partial_file] : [];
  }

  // Handle post/page data
  if ($post_id === null) {
    $post_id = get_the_ID();
  }

  if (!$post_id) {
    return [];
  }

  $data = get_post_meta($post_id, '_markdown_fm_data', true);

  return is_array($data) ? $data : [];
}

/**
 * Check if a field exists
 *
 * @param string $field_name The name of the field to check
 * @param int|string $post_id Optional. Post ID or 'partial:filename' for partials. Defaults to current post.
 * @return bool True if field exists, false otherwise
 */
function markdown_fm_has_field($field_name, $post_id = null) {
  $value = markdown_fm_get_field($field_name, $post_id);
  return $value !== null;
}

// Shorter aliases for convenience
if (!function_exists('mdfm_get_field')) {
  /**
   * Alias for markdown_fm_get_field()
   */
  function mdfm_get_field($field_name, $post_id = null) {
    return markdown_fm_get_field($field_name, $post_id);
  }
}

if (!function_exists('mdfm_get_fields')) {
  /**
   * Alias for markdown_fm_get_fields()
   */
  function mdfm_get_fields($post_id = null) {
    return markdown_fm_get_fields($post_id);
  }
}

if (!function_exists('mdfm_has_field')) {
  /**
   * Alias for markdown_fm_has_field()
   */
  function mdfm_has_field($field_name, $post_id = null) {
    return markdown_fm_has_field($field_name, $post_id);
  }
}

register_uninstall_hook(__FILE__, 'markdown_fm_uninstall');

function markdown_fm_uninstall() {
  delete_option('markdown_fm_template_settings');
  delete_option('markdown_fm_schemas');
  delete_option('markdown_fm_partial_data');

  global $wpdb;
  $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_markdown_fm_data'");
}
