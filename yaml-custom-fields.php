<?php
/**
 * Plugin Name: YAML Custom Fields
 * Plugin URI: https://github.com/maliMirkec/yaml-custom-fields
 * Description: A WordPress plugin for managing YAML frontmatter schemas in theme templates
 * Version: 1.0.0
 * Author: Silvestar Bistrović
 * Author URI: https://www.silvestar.codes
 * Author Email: me@silvestar.codes
 * License: GPL v2 or later
 * Text Domain: yaml-custom-fields
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
    echo '<strong>YAML Custom Fields:</strong> Please run <code>composer install</code> in the plugin directory to install dependencies.';
    echo '</p></div>';
  });
  return;
}

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

define('YAML_CF_VERSION', '1.0.0');
define('YAML_CF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YAML_CF_PLUGIN_URL', plugin_dir_url(__FILE__));

class YAML_Custom_Fields {
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
    add_action('admin_init', [$this, 'handle_form_submissions']);
    add_action('admin_init', [$this, 'handle_single_post_export']);
    add_action('admin_menu', [$this, 'add_admin_menu']);
    add_action('admin_head', [$this, 'hide_submenu_items']);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    // Only use edit_form_after_title to avoid duplicate rendering
    add_action('edit_form_after_title', [$this, 'render_schema_meta_box_after_title']);
    add_action('save_post', [$this, 'save_schema_data']);
    add_action('wp_ajax_yaml_cf_save_template_settings', [$this, 'ajax_save_template_settings']);
    add_action('wp_ajax_yaml_cf_save_schema', [$this, 'ajax_save_schema']);
    add_action('wp_ajax_yaml_cf_get_schema', [$this, 'ajax_get_schema']);
    add_action('wp_ajax_yaml_cf_get_partial_data', [$this, 'ajax_get_partial_data']);
    add_action('wp_ajax_yaml_cf_save_partial_data', [$this, 'ajax_save_partial_data']);
    add_action('wp_ajax_yaml_cf_export_settings', [$this, 'ajax_export_settings']);
    add_action('wp_ajax_yaml_cf_import_settings', [$this, 'ajax_import_settings']);
    add_action('wp_ajax_yaml_cf_export_page_data', [$this, 'ajax_export_page_data']);
    add_action('wp_ajax_yaml_cf_import_page_data', [$this, 'ajax_import_page_data']);
    add_action('wp_ajax_yaml_cf_get_posts_with_data', [$this, 'ajax_get_posts_with_data']);

    // Highlight parent menu for dynamic pages
    add_filter('parent_file', [$this, 'set_parent_file']);
    add_filter('submenu_file', [$this, 'set_submenu_file']);

    // Clear cache on theme switch
    add_action('switch_theme', [$this, 'clear_template_cache']);
  }

  public function add_admin_menu() {
    add_menu_page(
      __('YAML Custom Fields', 'yaml-custom-fields'),
      __('YAML CF', 'yaml-custom-fields'),
      'manage_options',
      'yaml-custom-fields',
      [$this, 'render_admin_page'],
      'dashicons-edit-page',
      30
    );

    // Register hidden pages (accessible via URL but not shown in menu by default)
    add_submenu_page(
      'yaml-custom-fields',
      __('Edit Schema', 'yaml-custom-fields'),
      __('Edit Schema', 'yaml-custom-fields'),
      'manage_options',
      'yaml-cf-edit-schema',
      [$this, 'render_edit_schema_page']
    );

    add_submenu_page(
      'yaml-custom-fields',
      __('Edit Partial', 'yaml-custom-fields'),
      __('Edit Partial', 'yaml-custom-fields'),
      'manage_options',
      'yaml-cf-edit-partial',
      [$this, 'render_edit_partial_page']
    );

    // Export Page Data
    add_submenu_page(
      'yaml-custom-fields',
      __('Export Page Data', 'yaml-custom-fields'),
      __('Export Page Data', 'yaml-custom-fields'),
      'manage_options',
      'yaml-cf-export-data',
      [$this, 'render_export_data_page']
    );

    // Data Validation
    add_submenu_page(
      'yaml-custom-fields',
      __('Data Validation', 'yaml-custom-fields'),
      __('Data Validation', 'yaml-custom-fields'),
      'manage_options',
      'yaml-cf-data-validation',
      [$this, 'render_data_validation_page']
    );

    // Documentation (added last to appear at the bottom)
    add_submenu_page(
      'yaml-custom-fields',
      __('Documentation', 'yaml-custom-fields'),
      __('Documentation', 'yaml-custom-fields'),
      'manage_options',
      'yaml-cf-docs',
      [$this, 'render_docs_page']
    );
  }

  public function hide_submenu_items() {
    global $submenu;

    if (isset($submenu['yaml-custom-fields'])) {
      // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin menu navigation, no nonce needed
      $current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
      // phpcs:enable WordPress.Security.NonceVerification.Recommended

      foreach ($submenu['yaml-custom-fields'] as $key => $menu_item) {
        $menu_slug = $menu_item[2];

        // Hide "Edit Schema" if not on edit schema page
        if ($menu_slug === 'yaml-cf-edit-schema' && $current_page !== 'yaml-cf-edit-schema') {
          unset($submenu['yaml-custom-fields'][$key]);
        }

        // Hide "Edit Partial" if not on edit partial page
        if ($menu_slug === 'yaml-cf-edit-partial' && $current_page !== 'yaml-cf-edit-partial') {
          unset($submenu['yaml-custom-fields'][$key]);
        }
      }
    }
  }

  public function set_parent_file($parent_file) {
    global $submenu_file, $submenu;

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin menu navigation, no nonce needed
    if (isset($_GET['page']) && isset($_GET['template'])) {
      $page = sanitize_text_field(wp_unslash($_GET['page']));
      // Update submenu titles and URLs dynamically
      if ($page === 'yaml-cf-edit-schema' || $page === 'yaml-cf-edit-partial') {
        $template = sanitize_text_field(wp_unslash($_GET['template']));
        $theme_files = $this->get_theme_templates();
        $template_name = $template;

        // Find template name
        if ($page === 'yaml-cf-edit-schema') {
          foreach (array_merge($theme_files['templates'], $theme_files['partials']) as $item) {
            if ($item['file'] === $template) {
              $template_name = $item['name'];
              break;
            }
          }
        } else {
          foreach ($theme_files['partials'] as $partial) {
            if ($partial['file'] === $template) {
              $template_name = $partial['name'];
              break;
            }
          }
        }

        // Update the submenu title and URL
        if (isset($submenu['yaml-custom-fields'])) {
          foreach ($submenu['yaml-custom-fields'] as $key => $menu_item) {
            if ($menu_item[2] === $page) {
              if ($page === 'yaml-cf-edit-schema') {
                /* translators: %s: template name */
                $submenu['yaml-custom-fields'][$key][0] = sprintf(__('Edit Schema: %s', 'yaml-custom-fields'), $template_name);
                // Use admin.php?page= format for proper WordPress menu handling
                $submenu['yaml-custom-fields'][$key][2] = 'admin.php?page=yaml-cf-edit-schema&template=' . urlencode($template);
                /* translators: %s: template name */
                $submenu['yaml-custom-fields'][$key][3] = sprintf(__('Edit Schema: %s', 'yaml-custom-fields'), $template_name);
              } else {
                /* translators: %s: template name */
                $submenu['yaml-custom-fields'][$key][0] = sprintf(__('Edit Partial: %s', 'yaml-custom-fields'), $template_name);
                $submenu['yaml-custom-fields'][$key][2] = 'admin.php?page=yaml-cf-edit-partial&template=' . urlencode($template);
                /* translators: %s: template name */
                $submenu['yaml-custom-fields'][$key][3] = sprintf(__('Edit Partial: %s', 'yaml-custom-fields'), $template_name);
              }
              break;
            }
          }
        }

        $parent_file = 'yaml-custom-fields';
      }
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    return $parent_file;
  }

  public function set_submenu_file($submenu_file) {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin menu navigation, no nonce needed
    if (isset($_GET['page']) && isset($_GET['template'])) {
      $page = sanitize_text_field(wp_unslash($_GET['page']));
      if ($page === 'yaml-cf-edit-schema' || $page === 'yaml-cf-edit-partial') {
        $template = sanitize_text_field(wp_unslash($_GET['template']));
        $submenu_file = 'admin.php?page=' . $page . '&template=' . urlencode($template);
      }
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    return $submenu_file;
  }

  public function enqueue_admin_assets($hook) {
    // Load on the plugin settings page
    $is_settings_page = ('toplevel_page_yaml-custom-fields' === $hook);
    $is_docs_page = ('yaml-cf_page_yaml-cf-docs' === $hook);
    $is_edit_template_page = (strpos($hook, 'yaml-cf-edit-template') !== false);
    $is_edit_partial_page = (strpos($hook, 'yaml-cf-edit-partial') !== false);
    $is_edit_schema_page = (strpos($hook, 'yaml-cf-edit-schema') !== false);
    $is_export_data_page = ('yaml-cf_page_yaml-cf-export-data' === $hook);
    $is_validation_page = ('yaml-cf_page_yaml-cf-data-validation' === $hook);

    // Load on post edit screens
    $current_screen = get_current_screen();
    $is_post_edit = false;

    if ($current_screen) {
      $is_post_edit = in_array($current_screen->base, ['post', 'post-new']) &&
                      in_array($current_screen->post_type, ['page', 'post']);
    }

    // Only load if on plugin pages or post edit screen
    if (!$is_settings_page && !$is_docs_page && !$is_edit_template_page && !$is_edit_partial_page && !$is_edit_schema_page && !$is_export_data_page && !$is_validation_page && !$is_post_edit) {
      return;
    }

    // Enqueue WordPress media library (needed for image/file uploads)
    wp_enqueue_media();

    wp_enqueue_style('yaml-cf-admin', YAML_CF_PLUGIN_URL . 'assets/admin.css', [], YAML_CF_VERSION);
    wp_enqueue_script('yaml-cf-admin', YAML_CF_PLUGIN_URL . 'assets/admin.js', ['jquery'], YAML_CF_VERSION, true);

    // Get current template and schema for post edit screens
    $schema_data = null;
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Reading post ID for editor, no nonce needed
    if ($is_post_edit && isset($_GET['post'])) {
      $post_id = intval(wp_unslash($_GET['post']));
      // phpcs:enable WordPress.Security.NonceVerification.Recommended
      $template = get_post_meta($post_id, '_wp_page_template', true);
      if (empty($template) || $template === 'default') {
        $template = 'page.php';
      }

      $schemas = get_option('yaml_cf_schemas', []);
      if (isset($schemas[$template]) && !empty($schemas[$template])) {
        $schema_data = $this->parse_yaml_schema($schemas[$template]);
      }
    }

    wp_localize_script('yaml-cf-admin', 'yamlCF', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'admin_url' => admin_url(),
      'nonce' => wp_create_nonce('yaml_cf_nonce'),
      'schema' => $schema_data
    ]);
  }

  public function render_admin_page() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'yaml-custom-fields'));
    }

    // Handle refresh action
    $refresh_message = '';
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin action, capability already checked
    if (isset($_GET['refresh_ycf']) && sanitize_text_field(wp_unslash($_GET['refresh_ycf'])) === '1') {
      // phpcs:enable WordPress.Security.NonceVerification.Recommended
      $this->clear_template_cache();
      $refresh_message = esc_html__('Template list refreshed successfully!', 'yaml-custom-fields');
    }

    $theme_files = $this->get_theme_templates();
    $templates = $theme_files['templates'];
    $partials = $theme_files['partials'];
    $template_settings = get_option('yaml_cf_template_settings', []);
    $schemas = get_option('yaml_cf_schemas', []);

    include YAML_CF_PLUGIN_DIR . 'templates/admin-page.php';
  }

  public function render_edit_template_page() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'yaml-custom-fields'));
    }

    // This page is for future use - editing template-specific data
    // For now, redirect to main page
    wp_redirect(admin_url('admin.php?page=yaml-custom-fields'));
    exit;
  }

  public function render_edit_partial_page() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'yaml-custom-fields'));
    }

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin page render, capability already checked
    if (!isset($_GET['template'])) {
      wp_die(esc_html__('No template specified.', 'yaml-custom-fields'));
    }

    $template = sanitize_text_field(wp_unslash($_GET['template']));
    $schemas = get_option('yaml_cf_schemas', []);

    if (!isset($schemas[$template])) {
      wp_die(esc_html__('No schema found for this template.', 'yaml-custom-fields'));
    }

    $schema_yaml = $schemas[$template];
    $schema = $this->parse_yaml_schema($schema_yaml);

    if (!$schema || !isset($schema['fields'])) {
      wp_die(esc_html__('Invalid schema for this template.', 'yaml-custom-fields'));
    }

    // Get partial data
    $partial_data = get_option('yaml_cf_partial_data', []);
    $template_data = isset($partial_data[$template]) ? $partial_data[$template] : [];

    // Get template name from theme files
    $theme_files = $this->get_theme_templates();
    $template_name = $template;
    foreach ($theme_files['partials'] as $partial) {
      if ($partial['file'] === $template) {
        $template_name = $partial['name'];
        break;
      }
    }

    // Check for success message
    $success_message = '';
    if (isset($_GET['saved']) && sanitize_text_field(wp_unslash($_GET['saved'])) === '1') {
      $success_message = __('Partial data saved successfully!', 'yaml-custom-fields');
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    // Localize schema data for JavaScript
    wp_localize_script('yaml-cf-admin', 'yamlCF', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'admin_url' => admin_url(),
      'nonce' => wp_create_nonce('yaml_cf_nonce'),
      'schema' => $schema
    ]);

    include YAML_CF_PLUGIN_DIR . 'templates/edit-partial-page.php';
  }

  public function render_edit_schema_page() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'yaml-custom-fields'));
    }

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin page render, capability already checked
    if (!isset($_GET['template'])) {
      wp_die(esc_html__('No template specified.', 'yaml-custom-fields'));
    }

    $template = sanitize_text_field(wp_unslash($_GET['template']));
    $schemas = get_option('yaml_cf_schemas', []);
    $schema_yaml = isset($schemas[$template]) ? $schemas[$template] : '';

    // Check if there's a validation error and restore the invalid schema
    $error_message = '';
    if (isset($_GET['error']) && sanitize_text_field(wp_unslash($_GET['error'])) === '1') {
      $invalid_schema = get_transient('yaml_cf_invalid_schema_' . get_current_user_id());
      if ($invalid_schema !== false) {
        $schema_yaml = $invalid_schema;
        delete_transient('yaml_cf_invalid_schema_' . get_current_user_id());
      }

      if (isset($_GET['error_msg'])) {
        $error_message = sanitize_text_field(wp_unslash($_GET['error_msg']));
      } else {
        $error_message = __('Invalid YAML schema. Please check your syntax and try again.', 'yaml-custom-fields');
      }
    }

    // Get template name from theme files
    $theme_files = $this->get_theme_templates();
    $template_name = $template;
    foreach (array_merge($theme_files['templates'], $theme_files['partials']) as $item) {
      if ($item['file'] === $template) {
        $template_name = $item['name'];
        break;
      }
    }

    // Check for success message
    $success_message = '';
    if (isset($_GET['saved']) && sanitize_text_field(wp_unslash($_GET['saved'])) === '1') {
      $success_message = __('Schema saved successfully!', 'yaml-custom-fields');
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    include YAML_CF_PLUGIN_DIR . 'templates/edit-schema-page.php';
  }

  public function render_docs_page() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'yaml-custom-fields'));
    }

    include YAML_CF_PLUGIN_DIR . 'templates/docs-page.php';
  }

  public function render_export_data_page() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'yaml-custom-fields'));
    }

    include YAML_CF_PLUGIN_DIR . 'templates/export-data-page.php';
  }

  public function render_data_validation_page() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'yaml-custom-fields'));
    }

    include YAML_CF_PLUGIN_DIR . 'templates/data-validation-page.php';
  }

  public function handle_single_post_export() {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified below
    if (!isset($_GET['yaml_cf_export_post']) || !isset($_GET['_wpnonce'])) {
      return;
    }

    $post_id = intval($_GET['yaml_cf_export_post']);
    $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    if (!wp_verify_nonce($nonce, 'yaml_cf_export_post_' . $post_id)) {
      wp_die(esc_html__('Security check failed', 'yaml-custom-fields'));
    }

    if (!current_user_can('edit_post', $post_id)) {
      wp_die(esc_html__('Permission denied', 'yaml-custom-fields'));
    }

    $post = get_post($post_id);
    if (!$post) {
      wp_die(esc_html__('Post not found', 'yaml-custom-fields'));
    }

    $template = get_post_meta($post_id, '_wp_page_template', true);
    if (empty($template) || $template === 'default') {
      $template = 'page.php';
    }

    $data = get_post_meta($post_id, '_yaml_cf_data', true);
    if (empty($data)) {
      wp_die(esc_html__('No custom field data found for this post', 'yaml-custom-fields'));
    }

    $export_data = [
      'plugin' => 'yaml-custom-fields',
      'version' => YAML_CF_VERSION,
      'exported_at' => current_time('mysql'),
      'site_url' => get_site_url(),
      'type' => 'single-post',
      'post' => [
        'id' => $post->ID,
        'title' => $post->post_title,
        'slug' => $post->post_name,
        'type' => $post->post_type,
        'template' => $template,
        'data' => $data
      ]
    ];

    // Set headers for file download
    $filename = 'yaml-cs-content-' . sanitize_file_name($post->post_name) . '-' . gmdate('Y-m-d-H-i-s') . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON output
    echo wp_json_encode($export_data, JSON_PRETTY_PRINT);
    exit;
  }

  public function handle_form_submissions() {
    // Handle single post import
    if (isset($_POST['yaml_cf_import_post_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['yaml_cf_import_post_nonce'])), 'yaml_cf_import_post')) {

      $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

      if (!current_user_can('edit_post', $post_id)) {
        wp_die(esc_html__('Permission denied', 'yaml-custom-fields'));
      }

      if (!isset($_FILES['yaml_cf_import_file']) || $_FILES['yaml_cf_import_file']['error'] !== UPLOAD_ERR_OK) {
        wp_redirect(add_query_arg([
          'post' => $post_id,
          'action' => 'edit',
          'yaml_cf_import_error' => 'upload_failed'
        ], admin_url('post.php')));
        exit;
      }

      // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Validated via wp_check_filetype
      $file = $_FILES['yaml_cf_import_file'];
      $file_type = wp_check_filetype($file['name']);

      if ($file_type['ext'] !== 'json') {
        wp_redirect(add_query_arg([
          'post' => $post_id,
          'action' => 'edit',
          'yaml_cf_import_error' => 'invalid_file'
        ], admin_url('post.php')));
        exit;
      }

      // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading uploaded file
      $json_data = file_get_contents($file['tmp_name']);
      $import_data = json_decode($json_data, true);

      if (!$import_data || !isset($import_data['plugin']) || $import_data['plugin'] !== 'yaml-custom-fields') {
        wp_redirect(add_query_arg([
          'post' => $post_id,
          'action' => 'edit',
          'yaml_cf_import_error' => 'invalid_format'
        ], admin_url('post.php')));
        exit;
      }

      if (!isset($import_data['post']) || !isset($import_data['post']['data'])) {
        wp_redirect(add_query_arg([
          'post' => $post_id,
          'action' => 'edit',
          'yaml_cf_import_error' => 'no_data'
        ], admin_url('post.php')));
        exit;
      }

      // Validate and clean attachment data
      $cleaned_data = $this->validate_and_clean_attachment_data($import_data['post']['data']);

      // Update post meta
      update_post_meta($post_id, '_yaml_cf_data', $cleaned_data);

      // Redirect with success message
      wp_redirect(add_query_arg([
        'post' => $post_id,
        'action' => 'edit',
        'yaml_cf_imported' => '1'
      ], admin_url('post.php')));
      exit;
    }

    // Handle schema save
    if (isset($_POST['yaml_cf_save_schema_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['yaml_cf_save_schema_nonce'])), 'yaml_cf_save_schema')) {

      if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Permission denied', 'yaml-custom-fields'));
      }

      $template = isset($_POST['template']) ? sanitize_text_field(wp_unslash($_POST['template'])) : '';
      $schema = isset($_POST['schema']) ? sanitize_textarea_field(wp_unslash($_POST['schema'])) : '';

      // Validate YAML syntax before saving
      if (!empty($schema)) {
        $validation_result = $this->validate_yaml_schema($schema);
        if (!$validation_result['valid']) {
          // Store the invalid schema in a transient so we can display it back
          set_transient('yaml_cf_invalid_schema_' . get_current_user_id(), $schema, 60);

          // Redirect back with error message
          wp_redirect(add_query_arg([
            'page' => 'yaml-cf-edit-schema',
            'template' => urlencode($template),
            'error' => '1',
            'error_msg' => urlencode($validation_result['message'])
          ], admin_url('admin.php')));
          exit;
        }
      }

      $schemas = get_option('yaml_cf_schemas', []);
      $schemas[$template] = $schema;
      update_option('yaml_cf_schemas', $schemas);

      // Clear any stored invalid schema
      delete_transient('yaml_cf_invalid_schema_' . get_current_user_id());

      // Redirect with success message
      wp_redirect(add_query_arg([
        'page' => 'yaml-cf-edit-schema',
        'template' => urlencode($template),
        'saved' => '1'
      ], admin_url('admin.php')));
      exit;
    }

    // Handle partial data save
    if (isset($_POST['yaml_cf_partial_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['yaml_cf_partial_nonce'])), 'yaml_cf_save_partial')) {

      if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Permission denied', 'yaml-custom-fields'));
      }

      $template = isset($_POST['template']) ? sanitize_text_field(wp_unslash($_POST['template'])) : '';
      $field_data = [];

      // Get schema for validation
      $schemas = get_option('yaml_cf_schemas', []);
      $schema = null;
      if (isset($schemas[$template])) {
        $schema = $this->parse_yaml_schema($schemas[$template]);
      }

      // Collect all field data
      if (isset($_POST['yaml_cf']) && is_array($_POST['yaml_cf'])) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_field_data()
        $field_data = $this->sanitize_field_data(wp_unslash($_POST['yaml_cf']), $schema);
      }

      $partial_data = get_option('yaml_cf_partial_data', []);
      $partial_data[$template] = $field_data;
      update_option('yaml_cf_partial_data', $partial_data);

      // Redirect with success message
      wp_redirect(add_query_arg([
        'page' => 'yaml-cf-edit-partial',
        'template' => urlencode($template),
        'saved' => '1'
      ], admin_url('admin.php')));
      exit;
    }
  }

  private function sanitize_field_data($data, $schema = null, $field_name = '') {
    if (is_array($data)) {
      $sanitized = [];
      foreach ($data as $key => $value) {
        $sanitized[sanitize_text_field($key)] = $this->sanitize_field_data($value, $schema, $key);
      }
      return $sanitized;
    } elseif (is_string($data)) {
      // Check if this is a code field
      if ($schema && $field_name && $this->is_code_field($schema, $field_name)) {
        return $this->sanitize_code_field($data, $schema, $field_name);
      }
      // Use sanitize_textarea_field to preserve newlines and structure
      return sanitize_textarea_field($data);
    }
    return $data;
  }

  private function is_code_field($schema, $field_name) {
    if (!isset($schema['fields']) || !is_array($schema['fields'])) {
      return false;
    }

    foreach ($schema['fields'] as $field) {
      if (isset($field['name']) && $field['name'] === $field_name && isset($field['type']) && $field['type'] === 'code') {
        return true;
      }
      // Check nested fields in objects
      if (isset($field['fields']) && is_array($field['fields'])) {
        if ($this->is_code_field(['fields' => $field['fields']], $field_name)) {
          return true;
        }
      }
      // Check blocks
      if (isset($field['blocks']) && is_array($field['blocks'])) {
        foreach ($field['blocks'] as $block) {
          if (isset($block['fields']) && is_array($block['fields'])) {
            if ($this->is_code_field(['fields' => $block['fields']], $field_name)) {
              return true;
            }
          }
        }
      }
    }

    return false;
  }

  private function get_code_field_language($schema, $field_name) {
    if (!isset($schema['fields']) || !is_array($schema['fields'])) {
      return 'html';
    }

    foreach ($schema['fields'] as $field) {
      if (isset($field['name']) && $field['name'] === $field_name && isset($field['type']) && $field['type'] === 'code') {
        return isset($field['options']['language']) ? $field['options']['language'] : 'html';
      }
      // Check nested fields in objects
      if (isset($field['fields']) && is_array($field['fields'])) {
        $lang = $this->get_code_field_language(['fields' => $field['fields']], $field_name);
        if ($lang !== 'html' || $this->is_code_field(['fields' => $field['fields']], $field_name)) {
          return $lang;
        }
      }
      // Check blocks
      if (isset($field['blocks']) && is_array($field['blocks'])) {
        foreach ($field['blocks'] as $block) {
          if (isset($block['fields']) && is_array($block['fields'])) {
            $lang = $this->get_code_field_language(['fields' => $block['fields']], $field_name);
            if ($lang !== 'html' || $this->is_code_field(['fields' => $block['fields']], $field_name)) {
              return $lang;
            }
          }
        }
      }
    }

    return 'html';
  }

  private function sanitize_code_field($code, $schema, $field_name) {
    $language = $this->get_code_field_language($schema, $field_name);

    // For users with unfiltered_html capability (administrators), allow raw code
    if (current_user_can('unfiltered_html')) {
      switch (strtolower($language)) {
        case 'css':
          // Still sanitize CSS to remove dangerous patterns even for admins
          return $this->sanitize_css_code($code);

        case 'javascript':
        case 'js':
        case 'html':
        default:
          // Allow raw HTML/JS for administrators
          // Just preserve newlines and basic textarea sanitization
          return wp_unslash($code);
      }
    }

    // For non-administrators, be more restrictive
    switch (strtolower($language)) {
      case 'css':
        return $this->sanitize_css_code($code);

      case 'javascript':
      case 'js':
        // Strip all tags for non-admins
        return wp_strip_all_tags($code);

      case 'html':
      default:
        // For HTML, use wp_kses_post which allows safe HTML tags
        return wp_kses_post($code);
    }
  }

  private function sanitize_css_code($css) {
    // Remove potentially dangerous CSS
    $dangerous_patterns = [
      '/expression\s*\(/i',           // IE CSS expressions
      '/javascript\s*:/i',            // JavaScript protocol
      '/vbscript\s*:/i',              // VBScript protocol
      '/@import\s+/i',                // Prevent external CSS imports
      '/behavior\s*:/i',              // IE behaviors
      '/\-moz\-binding\s*:/i',        // Firefox XBL bindings
      '/data\s*:\s*text\/html/i',    // Data URI with HTML
    ];

    $cleaned_css = $css;
    foreach ($dangerous_patterns as $pattern) {
      $cleaned_css = preg_replace($pattern, '', $cleaned_css);
    }

    // Basic sanitization while preserving newlines
    return sanitize_textarea_field($cleaned_css);
  }

  private function get_theme_templates() {
    // Check cache first
    $cache_key = 'yaml_cf_templates_' . get_stylesheet();
    $cached = get_transient($cache_key);

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Cache invalidation parameter check
    if ($cached !== false && !isset($_GET['refresh_ycf'])) {
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

      // Also check for @ycf marker in file header (custom partials)
      if (!$is_partial && $this->has_ycf_marker($path)) {
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
   * Check if a file has the @ycf marker in its header
   * Only reads first 30 lines for performance
   */
  private function has_ycf_marker($file_path) {
    if (!file_exists($file_path) || !is_readable($file_path)) {
      return false;
    }

    $content = file_get_contents($file_path, false, null, 0, 8192); // Read first 8KB
    if ($content === false) {
      return false;
    }

    // Check first 30 lines for @ycf marker
    $lines = explode("\n", $content);
    $lines_to_check = min(30, count($lines));

    for ($i = 0; $i < $lines_to_check; $i++) {
      if (preg_match('/@ycf/i', $lines[$i])) {
        return true;
      }
    }

    return false;
  }

  /**
   * Clear the template cache
   */
  public function clear_template_cache() {
    $cache_key = 'yaml_cf_templates_' . get_stylesheet();
    delete_transient($cache_key);
  }

  private function format_template_name($filename) {
    $name = str_replace(['-', '_', '.php'], [' ', ' ', ''], $filename);
    return ucwords($name);
  }

  public function ajax_save_template_settings() {
    check_ajax_referer('yaml_cf_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $template = isset($_POST['template']) ? sanitize_text_field(wp_unslash($_POST['template'])) : '';
    $enabled = isset($_POST['enabled']) && sanitize_text_field(wp_unslash($_POST['enabled'])) === 'true';

    $settings = get_option('yaml_cf_template_settings', []);
    $settings[$template] = $enabled;

    update_option('yaml_cf_template_settings', $settings);

    // Check if schema exists for this template
    $schemas = get_option('yaml_cf_schemas', []);
    $has_schema = isset($schemas[$template]) && !empty($schemas[$template]);

    wp_send_json_success([
      'has_schema' => $has_schema
    ]);
  }

  public function ajax_save_schema() {
    check_ajax_referer('yaml_cf_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $template = isset($_POST['template']) ? sanitize_text_field(wp_unslash($_POST['template'])) : '';
    $schema = isset($_POST['schema']) ? sanitize_textarea_field(wp_unslash($_POST['schema'])) : '';

    $schemas = get_option('yaml_cf_schemas', []);
    $schemas[$template] = $schema;

    update_option('yaml_cf_schemas', $schemas);

    wp_send_json_success();
  }

  public function ajax_get_schema() {
    check_ajax_referer('yaml_cf_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $template = isset($_POST['template']) ? sanitize_text_field(wp_unslash($_POST['template'])) : '';
    $schemas = get_option('yaml_cf_schemas', []);

    wp_send_json_success([
      'schema' => isset($schemas[$template]) ? $schemas[$template] : ''
    ]);
  }

  public function render_schema_meta_box_after_title($post) {
    // Only render for post types we support
    if (!in_array($post->post_type, ['page', 'post'])) {
      return;
    }

    wp_nonce_field('yaml_cf_meta_box', 'yaml_cf_meta_box_nonce');

    $template = get_post_meta($post->ID, '_wp_page_template', true);
    if (empty($template) || $template === 'default') {
      $template = 'page.php';
    }

    $template_settings = get_option('yaml_cf_template_settings', []);

    if (!isset($template_settings[$template]) || !$template_settings[$template]) {
      return;
    }

    $schemas = get_option('yaml_cf_schemas', []);

    if (!isset($schemas[$template]) || empty($schemas[$template])) {
      return;
    }

    $schema_yaml = $schemas[$template];
    $schema = $this->parse_yaml_schema($schema_yaml);

    if (!$schema || !isset($schema['fields'])) {
      return;
    }

    // Add link to edit schema
    $edit_schema_url = admin_url('admin.php?page=yaml-cf-edit-schema&template=' . urlencode($template));

    echo '<div id="yaml-cf-meta-box" class="postbox" style="margin-bottom: 20px;">';
    echo '<div class="postbox-header"><h2 class="hndle">' . esc_html__('YAML Custom Fields Schema', 'yaml-custom-fields') . '</h2></div>';
    echo '<div class="inside">';

    // Display import/export messages
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display messages only
    if (isset($_GET['yaml_cf_imported']) && $_GET['yaml_cf_imported'] === '1') {
      echo '<div class="notice notice-success inline" style="margin: 10px 0;"><p>' . esc_html__('Data imported successfully!', 'yaml-custom-fields') . '</p></div>';
    }
    if (isset($_GET['yaml_cf_import_error'])) {
      $error_msg = sanitize_text_field(wp_unslash($_GET['yaml_cf_import_error']));
      $error_messages = [
        'upload_failed' => __('File upload failed. Please try again.', 'yaml-custom-fields'),
        'invalid_file' => __('Invalid file type. Please upload a JSON file.', 'yaml-custom-fields'),
        'invalid_format' => __('Invalid file format. Please upload a valid YAML CF export file.', 'yaml-custom-fields'),
        'no_data' => __('No data found in the import file.', 'yaml-custom-fields')
      ];
      $message = isset($error_messages[$error_msg]) ? $error_messages[$error_msg] : __('Import failed.', 'yaml-custom-fields');
      echo '<div class="notice notice-error inline" style="margin: 10px 0;"><p>' . esc_html($message) . '</p></div>';
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    $export_url = wp_nonce_url(
      add_query_arg('yaml_cf_export_post', $post->ID, admin_url('post.php')),
      'yaml_cf_export_post_' . $post->ID
    );

    echo '<div class="yaml-cf-meta-box-header" style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;">';
    echo '<p style="margin: 0;">';
    echo '<strong>' . esc_html__('Template:', 'yaml-custom-fields') . '</strong> ' . esc_html($template);
    echo ' | ';
    echo '<a href="' . esc_url($edit_schema_url) . '" target="_blank">' . esc_html__('Edit Schema', 'yaml-custom-fields') . '</a>';
    echo ' | ';

    // Export link (simple text link)
    echo '<a href="' . esc_url($export_url) . '">' . esc_html__('Export', 'yaml-custom-fields') . '</a>';
    echo ' | ';

    // Import form (inline text link style)
    echo '<span style="display: inline-block;">';
    echo '<form method="post" enctype="multipart/form-data" style="display: inline; margin: 0;">';
    wp_nonce_field('yaml_cf_import_post', 'yaml_cf_import_post_nonce');
    echo '<input type="hidden" name="post_id" value="' . esc_attr($post->ID) . '">';
    echo '<input type="file" name="yaml_cf_import_file" accept=".json" id="yaml-cf-import-file-' . esc_attr($post->ID) . '" style="display: none;" onchange="if(confirm(\'' . esc_js(__('⚠️ WARNING: This will replace ALL custom field data for this page. Continue?', 'yaml-custom-fields')) . '\')) { this.form.submit(); } else { this.value = \'\'; }">';
    echo '<label for="yaml-cf-import-file-' . esc_attr($post->ID) . '" style="cursor: pointer; color: #2271b1; text-decoration: underline;">';
    echo esc_html__('Import', 'yaml-custom-fields');
    echo '</label>';
    echo '</form>';
    echo '</span>';
    echo ' | ';

    // Reset All Data (simple text link)
    echo '<a href="#" class="yaml-cf-reset-data" data-post-id="' . esc_attr($post->ID) . '" style="color: #d63638;">';
    echo esc_html__('Reset All Data', 'yaml-custom-fields');
    echo '</a>';

    echo '</p>';
    echo '</div>';

    $saved_data = get_post_meta($post->ID, '_yaml_cf_data', true);
    if (empty($saved_data)) {
      $saved_data = [];
    }

    echo '<div class="yaml-cf-fields">';
    $context = ['type' => 'page'];
    $this->render_schema_fields($schema['fields'], $saved_data, '', $context);
    echo '</div>';
    echo '</div>';
    echo '</div>';
  }

  private function parse_yaml_schema($yaml) {
    try {
      return Yaml::parse($yaml);
    } catch (ParseException $e) {
      // Log error for debugging but fail gracefully
      if (defined('WP_DEBUG') && WP_DEBUG) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only logs when WP_DEBUG is enabled
        error_log('YAML Custom Fields: YAML parsing error - ' . $e->getMessage());
      }
      return null;
    }
  }

  private function validate_yaml_schema($yaml) {
    try {
      $parsed = Yaml::parse($yaml);

      // Check if parsed successfully
      if ($parsed === null) {
        return [
          'valid' => false,
          'message' => 'Empty or invalid YAML content'
        ];
      }

      // Check for required 'fields' key
      if (!isset($parsed['fields']) || !is_array($parsed['fields'])) {
        return [
          'valid' => false,
          'message' => 'Schema must contain a "fields" array'
        ];
      }

      // Basic validation of field structure
      foreach ($parsed['fields'] as $index => $field) {
        if (!is_array($field)) {
          return [
            'valid' => false,
            'message' => 'Field at index ' . $index . ' is not a valid array'
          ];
        }

        if (!isset($field['name'])) {
          return [
            'valid' => false,
            'message' => 'Field at index ' . $index . ' is missing required "name" property'
          ];
        }

        if (!isset($field['type'])) {
          return [
            'valid' => false,
            'message' => 'Field "' . $field['name'] . '" is missing required "type" property'
          ];
        }
      }

      return [
        'valid' => true,
        'message' => 'Schema is valid'
      ];
    } catch (ParseException $e) {
      return [
        'valid' => false,
        'message' => 'YAML syntax error: ' . $e->getMessage()
      ];
    }
  }

  public function render_schema_fields($fields, $saved_data, $prefix = '', $context = null) {
    foreach ($fields as $field) {
      $field_name = $prefix . $field['name'];
      $field_id = 'ycf_' . str_replace(['[', ']'], ['_', ''], $field_name);
      $field_value = isset($saved_data[$field['name']]) ? $saved_data[$field['name']] : (isset($field['default']) ? $field['default'] : '');
      $field_label = isset($field['label']) ? $field['label'] : ucfirst($field['name']);

      echo '<div class="yaml-cf-field" data-type="' . esc_attr($field['type']) . '">';

      // Generate code snippet
      $code_snippet = '';
      $popover_id = '';
      if ($context && is_array($context) && isset($context['type'])) {
        // Determine the function name based on field type
        $function_name = 'ycf_get_field';
        if (isset($field['type'])) {
          if ($field['type'] === 'image') {
            $function_name = 'ycf_get_image';
          } elseif ($field['type'] === 'file') {
            $function_name = 'ycf_get_file';
          }
        }

        if ($context['type'] === 'partial' && isset($context['template'])) {
          $code_snippet = $function_name . "('" . esc_js($field['name']) . "', 'partial:" . esc_js($context['template']) . "')";
        } else {
          $code_snippet = $function_name . "('" . esc_js($field['name']) . "')";
        }
        $popover_id = 'snippet-' . sanitize_html_class($field_id);
      }

      if($field['type'] === 'image' || $field['type'] === 'file') {
        echo '<p>' . esc_html($field_label);
        if ($code_snippet) {
          echo ' <span class="yaml-cf-snippet-wrapper">';
          echo '<button type="button" class="yaml-cf-copy-snippet" data-snippet="' . esc_attr($code_snippet) . '" data-popover="' . esc_attr($popover_id) . '">';
          echo '<span class="dashicons dashicons-editor-code"></span>';
          echo '<span class="snippet-text">' . esc_html__('Copy snippet', 'yaml-custom-fields') . '</span>';
          echo '</button>';
          echo '<span class="yaml-cf-snippet-popover" id="' . esc_attr($popover_id) . '" role="tooltip">';
          echo '<code>' . esc_html($code_snippet) . '</code>';
          echo '<span class="snippet-hint">' . esc_html__('Click button to copy', 'yaml-custom-fields') . '</span>';
          echo '</span>';
          echo '</span>';
        }
        echo '</p>';
      } else {
        echo '<label for="' . esc_attr($field_id) . '">' . esc_html($field_label);
        if ($code_snippet) {
          echo ' <span class="yaml-cf-snippet-wrapper">';
          echo '<button type="button" class="yaml-cf-copy-snippet" data-snippet="' . esc_attr($code_snippet) . '" data-popover="' . esc_attr($popover_id) . '">';
          echo '<span class="dashicons dashicons-editor-code"></span>';
          echo '<span class="snippet-text">' . esc_html__('Copy snippet', 'yaml-custom-fields') . '</span>';
          echo '</button>';
          echo '<span class="yaml-cf-snippet-popover" id="' . esc_attr($popover_id) . '" role="tooltip">';
          echo '<code>' . esc_html($code_snippet) . '</code>';
          echo '<span class="snippet-hint">' . esc_html__('Click button to copy', 'yaml-custom-fields') . '</span>';
          echo '</span>';
          echo '</span>';
        }
        echo '</label>';
      }

      switch ($field['type']) {
        case 'boolean':
          echo '<input type="checkbox" name="yaml_cf[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" value="1" ' . checked($field_value, 1, false) . ' />';
          break;

        case 'string':
          $options = isset($field['options']) ? $field['options'] : [];
          $minlength = isset($options['minlength']) ? 'minlength="' . intval($options['minlength']) . '"' : '';
          $maxlength = isset($options['maxlength']) ? 'maxlength="' . intval($options['maxlength']) . '"' : '';
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo '<input type="text" name="yaml_cf[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" value="' . esc_attr($field_value) . '" ' . $minlength . ' ' . $maxlength . ' class="regular-text" />';
          break;

        case 'text':
          $options = isset($field['options']) ? $field['options'] : [];
          $maxlength = isset($options['maxlength']) ? 'maxlength="' . intval($options['maxlength']) . '"' : '';
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo '<textarea name="yaml_cf[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" rows="5" class="large-text" ' . $maxlength . '>' . esc_textarea($field_value) . '</textarea>';
          break;

        case 'rich-text':
          wp_editor($field_value, $field_id, [
            'textarea_name' => 'yaml_cf[' . $field['name'] . ']',
            'textarea_rows' => 10,
            'media_buttons' => true,
            'tinymce' => [
              'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink',
            ],
            '_content_editor_dfw' => false
          ]);
          break;

        case 'code':
          $options = isset($field['options']) ? $field['options'] : [];
          $language = isset($options['language']) ? $options['language'] : 'html';
          echo '<textarea name="yaml_cf[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" rows="10" class="large-text code" data-language="' . esc_attr($language) . '">' . esc_textarea($field_value) . '</textarea>';
          break;

        case 'number':
          $options = isset($field['options']) ? $field['options'] : [];
          $min = isset($options['min']) ? 'min="' . intval($options['min']) . '"' : '';
          $max = isset($options['max']) ? 'max="' . intval($options['max']) . '"' : '';
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo '<input type="number" name="yaml_cf[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" value="' . esc_attr($field_value) . '" ' . $min . ' ' . $max . ' class="small-text" />';
          break;

        case 'date':
          $options = isset($field['options']) ? $field['options'] : [];
          $has_time = isset($options['time']) && $options['time'];
          $type = $has_time ? 'datetime-local' : 'date';
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo '<input type="' . esc_attr($type) . '" name="yaml_cf[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" value="' . esc_attr($field_value) . '" />';
          break;

        case 'select':
          $options = isset($field['options']) ? $field['options'] : [];
          $multiple = isset($field['multiple']) && $field['multiple'];
          $values = isset($field['values']) ? $field['values'] : [];

          echo '<select name="yaml_cf[' . esc_attr($field['name']) . ']' . ($multiple ? '[]' : '') . '" id="' . esc_attr($field_id) . '" ' . ($multiple ? 'multiple' : '') . '>';
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

              // Use loose comparison to handle string/int type differences
              $selected = '';
              if ($multiple && is_array($field_value)) {
                // For multiple select, check if value is in array
                $selected = in_array($opt_value, $field_value, false) ? 'selected' : '';
              } else {
                // For single select, use loose comparison
                $selected = ($field_value == $opt_value && $field_value !== '') ? 'selected' : '';
              }
              // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
              echo '<option value="' . esc_attr($opt_value) . '" ' . $selected . '>' . esc_html($opt_label) . '</option>';
            }
          }

          echo '</select>';
          break;

        case 'image':
          echo '<input type="hidden" name="yaml_cf[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" value="' . esc_attr($field_value) . '" />';
          echo '<div class="yaml-cf-media-buttons">';
          echo '<button type="button" class="button yaml-cf-upload-image" data-target="' . esc_attr($field_id) . '">Upload Image</button>';
          if ($field_value) {
            echo '<button type="button" class="button yaml-cf-clear-media" data-target="' . esc_attr($field_id) . '">Clear</button>';
          }
          echo '</div>';
          if ($field_value) {
            // Field value is now attachment ID, get the image URL
            $image_url = wp_get_attachment_image_url($field_value, 'medium');
            if ($image_url) {
              echo '<div class="yaml-cf-image-preview"><img src="' . esc_url($image_url) . '" style="max-width: 200px; display: block; margin-top: 10px;" /></div>';
            }
          }
          break;

        case 'file':
          echo '<input type="hidden" name="yaml_cf[' . esc_attr($field['name']) . ']" id="' . esc_attr($field_id) . '" value="' . esc_attr($field_value) . '" />';
          echo '<div class="yaml-cf-media-buttons">';
          echo '<button type="button" class="button yaml-cf-upload-file" data-target="' . esc_attr($field_id) . '">Upload File</button>';
          if ($field_value) {
            echo '<button type="button" class="button yaml-cf-clear-media" data-target="' . esc_attr($field_id) . '">Clear</button>';
          }
          echo '</div>';
          if ($field_value) {
            // Field value is now attachment ID, get the filename
            $file_path = get_attached_file($field_value);
            if ($file_path) {
              echo '<div class="yaml-cf-file-name">' . esc_html(basename($file_path)) . '</div>';
            }
          }
          break;

        case 'object':
          if (isset($field['fields'])) {
            echo '<div class="yaml-cf-object">';
            $object_data = is_array($field_value) ? $field_value : [];
            $this->render_schema_fields($field['fields'], $object_data, $field['name'] . '_');
            echo '</div>';
          }
          break;

        case 'block':
          $is_list = isset($field['list']) && $field['list'];
          $blocks = isset($field['blocks']) ? $field['blocks'] : [];
          $block_key = isset($field['blockKey']) ? $field['blockKey'] : 'type';

          echo '<div class="yaml-cf-block-container" data-field-name="' . esc_attr($field['name']) . '">';

          if ($is_list) {
            $block_values = is_array($field_value) ? $field_value : [];
            echo '<div class="yaml-cf-block-list">';

            foreach ($block_values as $index => $block_data) {
              $this->render_block_item($field, $blocks, $block_data, $index, $block_key);
            }

            echo '</div>';
            echo '<div class="yaml-cf-block-controls">';
            echo '<select class="yaml-cf-block-type-select">';
            echo '<option value="">-- Add Block --</option>';
            foreach ($blocks as $block) {
              echo '<option value="' . esc_attr($block['name']) . '">' . esc_html($block['label']) . '</option>';
            }
            echo '</select>';
            echo '<button type="button" class="button yaml-cf-add-block">Add Block</button>';
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

    echo '<div class="yaml-cf-block-item" data-block-type="' . esc_attr($block_type) . '">';
    echo '<div class="yaml-cf-block-header">';
    echo '<strong>' . esc_html($block_def['label']) . '</strong>';
    echo '<button type="button" class="button yaml-cf-remove-block">Remove</button>';
    echo '</div>';
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '<input type="hidden" name="yaml_cf[' . esc_attr($field['name']) . '][' . esc_attr($index) . '][' . esc_attr($block_key) . ']" value="' . esc_attr($block_type) . '" />';

    if (isset($block_def['fields']) && is_array($block_def['fields'])) {
      echo '<div class="yaml-cf-block-fields">';

      foreach ($block_def['fields'] as $block_field) {
        $block_field_id = 'ycf_' . $field['name'] . '_' . $index . '_' . $block_field['name'];
        $block_field_value = isset($block_data[$block_field['name']]) ? $block_data[$block_field['name']] : '';
        $block_field_type = isset($block_field['type']) ? $block_field['type'] : 'string';

        echo '<div class="yaml-cf-field">';
        echo '<label for="' . esc_attr($block_field_id) . '">' . esc_html($block_field['label']) . '</label>';

        if ($block_field_type === 'boolean') {
          echo '<input type="checkbox" name="yaml_cf[' . esc_attr($field['name']) . '][' . esc_attr($index) . '][' . esc_attr($block_field['name']) . ']" id="' . esc_attr($block_field_id) . '" value="1" ' . checked($block_field_value, 1, false) . ' />';
        } elseif ($block_field_type === 'rich-text') {
          wp_editor($block_field_value, $block_field_id, [
            'textarea_name' => 'yaml_cf[' . $field['name'] . '][' . $index . '][' . $block_field['name'] . ']',
            'textarea_rows' => 5,
            'media_buttons' => true,
            'tinymce' => [
              'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink',
            ],
            '_content_editor_dfw' => false
          ]);
        } elseif ($block_field_type === 'text') {
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo '<textarea name="yaml_cf[' . esc_attr($field['name']) . '][' . esc_attr($index) . '][' . esc_attr($block_field['name']) . ']" id="' . esc_attr($block_field_id) . '" rows="5" class="large-text">' . esc_textarea($block_field_value) . '</textarea>';
        } elseif ($block_field_type === 'code') {
          $block_field_options = isset($block_field['options']) ? $block_field['options'] : [];
          $language = isset($block_field_options['language']) ? $block_field_options['language'] : 'html';
          echo '<textarea name="yaml_cf[' . esc_attr($field['name']) . '][' . esc_attr($index) . '][' . esc_attr($block_field['name']) . ']" id="' . esc_attr($block_field_id) . '" rows="10" class="large-text code" data-language="' . esc_attr($language) . '">' . esc_textarea($block_field_value) . '</textarea>';
        } elseif ($block_field_type === 'number') {
          $block_field_options = isset($block_field['options']) ? $block_field['options'] : [];
          $min = isset($block_field_options['min']) ? 'min="' . intval($block_field_options['min']) . '"' : '';
          $max = isset($block_field_options['max']) ? 'max="' . intval($block_field_options['max']) . '"' : '';
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo '<input type="number" name="yaml_cf[' . esc_attr($field['name']) . '][' . esc_attr($index) . '][' . esc_attr($block_field['name']) . ']" id="' . esc_attr($block_field_id) . '" value="' . esc_attr($block_field_value) . '" ' . $min . ' ' . $max . ' class="small-text" />';
        } elseif ($block_field_type === 'date') {
          $block_field_options = isset($block_field['options']) ? $block_field['options'] : [];
          $has_time = isset($block_field_options['time']) && $block_field_options['time'];
          $type = $has_time ? 'datetime-local' : 'date';
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo '<input type="' . esc_attr($type) . '" name="yaml_cf[' . esc_attr($field['name']) . '][' . esc_attr($index) . '][' . esc_attr($block_field['name']) . ']" id="' . esc_attr($block_field_id) . '" value="' . esc_attr($block_field_value) . '" />';
        } elseif ($block_field_type === 'select') {
          $block_field_options = isset($block_field['options']) ? $block_field['options'] : [];
          $multiple = isset($block_field['multiple']) && $block_field['multiple'];
          $values = isset($block_field['values']) ? $block_field['values'] : [];

          echo '<select name="yaml_cf[' . esc_attr($field['name']) . '][' . esc_attr($index) . '][' . esc_attr($block_field['name']) . ']' . ($multiple ? '[]' : '') . '" id="' . esc_attr($block_field_id) . '" ' . ($multiple ? 'multiple' : '') . '>';
          echo '<option value="">-- Select --</option>';

          if (is_array($values)) {
            foreach ($values as $option) {
              if (is_array($option)) {
                $opt_value = isset($option['value']) ? $option['value'] : '';
                $opt_label = isset($option['label']) ? $option['label'] : $opt_value;
              } else {
                $opt_value = $option;
                $opt_label = $option;
              }

              // Use loose comparison to handle string/int type differences
              $selected = '';
              if ($multiple && is_array($block_field_value)) {
                // For multiple select, check if value is in array
                $selected = in_array($opt_value, $block_field_value, false) ? 'selected' : '';
              } else {
                // For single select, use loose comparison
                $selected = ($block_field_value == $opt_value && $block_field_value !== '') ? 'selected' : '';
              }
              // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
              echo '<option value="' . esc_attr($opt_value) . '" ' . $selected . '>' . esc_html($opt_label) . '</option>';
            }
          }

          echo '</select>';
        } elseif ($block_field_type === 'image') {
          echo '<input type="hidden" name="yaml_cf[' . esc_attr($field['name']) . '][' . esc_attr($index) . '][' . esc_attr($block_field['name']) . ']" id="' . esc_attr($block_field_id) . '" value="' . esc_attr($block_field_value) . '" />';
          echo '<div class="yaml-cf-media-buttons">';
          echo '<button type="button" class="button yaml-cf-upload-image" data-target="' . esc_attr($block_field_id) . '">Upload Image</button>';
          if ($block_field_value) {
            echo '<button type="button" class="button yaml-cf-clear-media" data-target="' . esc_attr($block_field_id) . '">Clear</button>';
          }
          echo '</div>';
          if ($block_field_value) {
            $image_url = wp_get_attachment_image_url($block_field_value, 'medium');
            if ($image_url) {
              echo '<div class="yaml-cf-image-preview"><img src="' . esc_url($image_url) . '" style="max-width: 200px; display: block; margin-top: 10px;" /></div>';
            }
          }
        } elseif ($block_field_type === 'file') {
          echo '<input type="hidden" name="yaml_cf[' . esc_attr($field['name']) . '][' . esc_attr($index) . '][' . esc_attr($block_field['name']) . ']" id="' . esc_attr($block_field_id) . '" value="' . esc_attr($block_field_value) . '" />';
          echo '<div class="yaml-cf-media-buttons">';
          echo '<button type="button" class="button yaml-cf-upload-file" data-target="' . esc_attr($block_field_id) . '">Upload File</button>';
          if ($block_field_value) {
            echo '<button type="button" class="button yaml-cf-clear-media" data-target="' . esc_attr($block_field_id) . '">Clear</button>';
          }
          echo '</div>';
          if ($block_field_value) {
            $file_path = get_attached_file($block_field_value);
            if ($file_path) {
              echo '<div class="yaml-cf-file-name">' . esc_html(basename($file_path)) . '</div>';
            }
          }
        } elseif ($block_field_type === 'string') {
          $block_field_options = isset($block_field['options']) ? $block_field['options'] : [];
          $minlength = isset($block_field_options['minlength']) ? 'minlength="' . intval($block_field_options['minlength']) . '"' : '';
          $maxlength = isset($block_field_options['maxlength']) ? 'maxlength="' . intval($block_field_options['maxlength']) . '"' : '';
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo '<input type="text" name="yaml_cf[' . esc_attr($field['name']) . '][' . esc_attr($index) . '][' . esc_attr($block_field['name']) . ']" id="' . esc_attr($block_field_id) . '" value="' . esc_attr($block_field_value) . '" ' . $minlength . ' ' . $maxlength . ' class="regular-text" />';
        } else {
          // Default to text input for unknown types
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo '<input type="text" name="yaml_cf[' . esc_attr($field['name']) . '][' . esc_attr($index) . '][' . esc_attr($block_field['name']) . ']" id="' . esc_attr($block_field_id) . '" value="' . esc_attr($block_field_value) . '" class="regular-text" />';
        }

        echo '</div>';
      }
      echo '</div>';
    }

    echo '</div>';
  }

  public function save_schema_data($post_id) {
    if (!isset($_POST['yaml_cf_meta_box_nonce'])) {
      return;
    }

    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['yaml_cf_meta_box_nonce'])), 'yaml_cf_meta_box')) {
      return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    if (!current_user_can('edit_post', $post_id)) {
      return;
    }

    if (isset($_POST['yaml_cf'])) {
      // Get the template for this post
      $template = get_post_meta($post_id, '_wp_page_template', true);
      if (empty($template) || $template === 'default') {
        $template = 'page.php';
      }

      // Get schema for validation
      $schemas = get_option('yaml_cf_schemas', []);
      $schema = null;
      if (isset($schemas[$template])) {
        $schema = $this->parse_yaml_schema($schemas[$template]);
      }

      // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_field_data()
      $sanitized_data = $this->sanitize_field_data(wp_unslash($_POST['yaml_cf']), $schema);
      update_post_meta($post_id, '_yaml_cf_data', $sanitized_data);
    }
  }

  public function ajax_get_partial_data() {
    check_ajax_referer('yaml_cf_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $template = isset($_POST['template']) ? sanitize_text_field(wp_unslash($_POST['template'])) : '';

    // Get schema
    $schemas = get_option('yaml_cf_schemas', []);
    $schema_yaml = isset($schemas[$template]) ? $schemas[$template] : '';

    if (empty($schema_yaml)) {
      wp_send_json_error('No schema found');
      return;
    }

    $schema = $this->parse_yaml_schema($schema_yaml);

    // Get existing data
    $partial_data = get_option('yaml_cf_partial_data', []);
    $data = isset($partial_data[$template]) ? $partial_data[$template] : [];

    wp_send_json_success([
      'schema' => $schema,
      'data' => $data
    ]);
  }

  public function ajax_save_partial_data() {
    check_ajax_referer('yaml_cf_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $template = isset($_POST['template']) ? sanitize_text_field(wp_unslash($_POST['template'])) : '';
    $json_data = isset($_POST['data']) ? sanitize_textarea_field(wp_unslash($_POST['data'])) : '{}';
    $data = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $data = [];
    }

    // Get existing partial data
    $partial_data = get_option('yaml_cf_partial_data', []);

    // Update data for this partial
    $partial_data[$template] = $data;

    // Save back to options
    update_option('yaml_cf_partial_data', $partial_data);

    wp_send_json_success();
  }

  public function ajax_export_settings() {
    check_ajax_referer('yaml_cf_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    // Gather all settings
    $export_data = [
      'plugin' => 'yaml-custom-fields',
      'version' => YAML_CF_VERSION,
      'exported_at' => current_time('mysql'),
      'site_url' => get_site_url(),
      'settings' => [
        'template_settings' => get_option('yaml_cf_template_settings', []),
        'schemas' => get_option('yaml_cf_schemas', []),
        'partial_data' => get_option('yaml_cf_partial_data', [])
      ]
    ];

    wp_send_json_success($export_data);
  }

  public function ajax_import_settings() {
    check_ajax_referer('yaml_cf_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    if (!isset($_POST['data'])) {
      wp_send_json_error('No data provided');
    }

    $import_data = json_decode(sanitize_textarea_field(wp_unslash($_POST['data'])), true);

    // Validate import data
    if (!$import_data || !isset($import_data['plugin']) || $import_data['plugin'] !== 'yaml-custom-fields') {
      wp_send_json_error('Invalid import file format');
    }

    if (!isset($import_data['settings'])) {
      wp_send_json_error('No settings found in import file');
    }

    $settings = $import_data['settings'];
    $merge = isset($_POST['merge']) && $_POST['merge'] === 'true';

    // Import template settings
    if (isset($settings['template_settings'])) {
      if ($merge) {
        $existing = get_option('yaml_cf_template_settings', []);
        $settings['template_settings'] = array_merge($existing, $settings['template_settings']);
      }
      update_option('yaml_cf_template_settings', $settings['template_settings']);
    }

    // Import schemas
    if (isset($settings['schemas'])) {
      if ($merge) {
        $existing = get_option('yaml_cf_schemas', []);
        $settings['schemas'] = array_merge($existing, $settings['schemas']);
      }
      update_option('yaml_cf_schemas', $settings['schemas']);
    }

    // Import partial data
    if (isset($settings['partial_data'])) {
      if ($merge) {
        $existing = get_option('yaml_cf_partial_data', []);
        $settings['partial_data'] = array_merge($existing, $settings['partial_data']);
      }
      update_option('yaml_cf_partial_data', $settings['partial_data']);
    }

    // Clear template cache
    $this->clear_template_cache();

    wp_send_json_success([
      'message' => 'Settings imported successfully',
      'imported_from' => isset($import_data['site_url']) ? $import_data['site_url'] : 'unknown',
      'exported_at' => isset($import_data['exported_at']) ? $import_data['exported_at'] : 'unknown'
    ]);
  }

  public function ajax_get_posts_with_data() {
    check_ajax_referer('yaml_cf_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    global $wpdb;

    // Get all posts and pages that have custom field data
    $results = $wpdb->get_results(
      "SELECT p.ID, p.post_title, p.post_name, p.post_type, p.post_status, pm.meta_value as template
       FROM {$wpdb->posts} p
       INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_yaml_cf_data'
       LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_page_template'
       WHERE p.post_type IN ('page', 'post')
       AND p.post_status IN ('publish', 'draft', 'pending', 'private')
       ORDER BY p.post_type, p.post_title"
    );

    $posts = [];
    foreach ($results as $row) {
      $template = $row->template ?: 'page.php';
      if (empty($template) || $template === 'default') {
        $template = 'page.php';
      }

      $posts[] = [
        'id' => $row->ID,
        'title' => $row->post_title,
        'slug' => $row->post_name,
        'type' => $row->post_type,
        'status' => $row->post_status,
        'template' => $template,
        'edit_url' => get_edit_post_link($row->ID)
      ];
    }

    wp_send_json_success(['posts' => $posts]);
  }

  public function ajax_export_page_data() {
    check_ajax_referer('yaml_cf_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    $post_ids = isset($_POST['post_ids']) ? array_map('intval', wp_unslash($_POST['post_ids'])) : [];
    $match_by = isset($_POST['match_by']) ? sanitize_text_field(wp_unslash($_POST['match_by'])) : 'slug';

    if (empty($post_ids)) {
      wp_send_json_error('No posts selected');
    }

    $export_data = [
      'plugin' => 'yaml-custom-fields',
      'version' => YAML_CF_VERSION,
      'exported_at' => current_time('mysql'),
      'site_url' => get_site_url(),
      'match_by' => $match_by,
      'posts' => []
    ];

    foreach ($post_ids as $post_id) {
      $post = get_post($post_id);
      if (!$post) {
        continue;
      }

      $template = get_post_meta($post_id, '_wp_page_template', true);
      if (empty($template) || $template === 'default') {
        $template = 'page.php';
      }

      $data = get_post_meta($post_id, '_yaml_cf_data', true);
      if (empty($data)) {
        continue;
      }

      $export_data['posts'][] = [
        'id' => $post->ID,
        'title' => $post->post_title,
        'slug' => $post->post_name,
        'type' => $post->post_type,
        'template' => $template,
        'data' => $data
      ];
    }

    wp_send_json_success($export_data);
  }

  public function ajax_import_page_data() {
    check_ajax_referer('yaml_cf_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Permission denied');
    }

    if (!isset($_POST['data'])) {
      wp_send_json_error('No data provided');
    }

    $import_data = json_decode(sanitize_textarea_field(wp_unslash($_POST['data'])), true);

    // Validate import data
    if (!$import_data || !isset($import_data['plugin']) || $import_data['plugin'] !== 'yaml-custom-fields') {
      wp_send_json_error('Invalid import file format');
    }

    if (!isset($import_data['posts']) || !is_array($import_data['posts'])) {
      wp_send_json_error('No posts found in import file');
    }

    $match_by = isset($import_data['match_by']) ? $import_data['match_by'] : 'slug';
    $imported = 0;
    $skipped = 0;
    $errors = [];

    foreach ($import_data['posts'] as $post_data) {
      $target_post = null;

      // Find the target post based on match_by preference
      if ($match_by === 'id') {
        $target_post = get_post($post_data['id']);
      } else {
        // Match by slug
        $args = [
          'name' => $post_data['slug'],
          'post_type' => $post_data['type'],
          'post_status' => 'any',
          'posts_per_page' => 1
        ];
        $posts = get_posts($args);
        if (!empty($posts)) {
          $target_post = $posts[0];
        }
      }

      if (!$target_post) {
        $skipped++;
        $errors[] = sprintf('Post not found: %s (slug: %s, id: %d)', $post_data['title'], $post_data['slug'], $post_data['id']);
        continue;
      }

      // Validate attachments (images/files) and clean up missing ones
      $cleaned_data = $this->validate_and_clean_attachment_data($post_data['data']);

      // Update the post meta
      update_post_meta($target_post->ID, '_yaml_cf_data', $cleaned_data);
      $imported++;
    }

    wp_send_json_success([
      'message' => sprintf('Import complete. %d imported, %d skipped.', $imported, $skipped),
      'imported' => $imported,
      'skipped' => $skipped,
      'errors' => $errors,
      'imported_from' => isset($import_data['site_url']) ? $import_data['site_url'] : 'unknown',
      'exported_at' => isset($import_data['exported_at']) ? $import_data['exported_at'] : 'unknown'
    ]);
  }

  private function validate_and_clean_attachment_data($data) {
    if (!is_array($data)) {
      return $data;
    }

    foreach ($data as $key => $value) {
      if (is_array($value)) {
        // Recursively clean nested arrays
        $data[$key] = $this->validate_and_clean_attachment_data($value);
      } elseif (is_numeric($value) && intval($value) > 0) {
        // Check if this might be an attachment ID
        $attachment = get_post(intval($value));
        if ($attachment && $attachment->post_type === 'attachment') {
          // Valid attachment, keep it
          continue;
        } elseif ($attachment) {
          // It's a valid post ID but not an attachment, keep it
          continue;
        } else {
          // Attachment doesn't exist, set to empty
          $data[$key] = '';
        }
      }
    }

    return $data;
  }

}

function yaml_cf_init() {
  return YAML_Custom_Fields::get_instance();
}

add_action('plugins_loaded', 'yaml_cf_init');

/**
 * Get a specific field value from YAML Custom Fields data
 *
 * @param string $field_name The name of the field to retrieve
 * @param int|string $post_id Optional. Post ID or 'partial:filename' for partials. Defaults to current post.
 * @return mixed The field value, or null if not found
 *
 * Usage in templates:
 * - For page/post: $hero = yaml_cf_get_field('hero');
 * - For specific post: $hero = yaml_cf_get_field('hero', 123);
 * - For partial: $logo = yaml_cf_get_field('logo', 'partial:header.php');
 */
function yaml_cf_get_field($field_name, $post_id = null) {
  // Handle partials
  if (is_string($post_id) && strpos($post_id, 'partial:') === 0) {
    $partial_file = str_replace('partial:', '', $post_id);
    $partial_data = get_option('yaml_cf_partial_data', []);

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

  $data = get_post_meta($post_id, '_yaml_cf_data', true);

  if (is_array($data) && isset($data[$field_name])) {
    return $data[$field_name];
  }

  return null;
}

/**
 * Get all YAML Custom Fields fields for the current post or partial
 *
 * @param int|string $post_id Optional. Post ID or 'partial:filename' for partials. Defaults to current post.
 * @return array Array of all field values
 *
 * Usage in templates:
 * - For page/post: $fields = yaml_cf_get_fields();
 * - For specific post: $fields = yaml_cf_get_fields(123);
 * - For partial: $fields = yaml_cf_get_fields('partial:header.php');
 */
function yaml_cf_get_fields($post_id = null) {
  // Handle partials
  if (is_string($post_id) && strpos($post_id, 'partial:') === 0) {
    $partial_file = str_replace('partial:', '', $post_id);
    $partial_data = get_option('yaml_cf_partial_data', []);

    return isset($partial_data[$partial_file]) ? $partial_data[$partial_file] : [];
  }

  // Handle post/page data
  if ($post_id === null) {
    $post_id = get_the_ID();
  }

  if (!$post_id) {
    return [];
  }

  $data = get_post_meta($post_id, '_yaml_cf_data', true);

  return is_array($data) ? $data : [];
}

/**
 * Check if a field exists
 *
 * @param string $field_name The name of the field to check
 * @param int|string $post_id Optional. Post ID or 'partial:filename' for partials. Defaults to current post.
 * @return bool True if field exists, false otherwise
 */
function yaml_cf_has_field($field_name, $post_id = null) {
  $value = yaml_cf_get_field($field_name, $post_id);
  return $value !== null;
}

// Shorter aliases for convenience
if (!function_exists('ycf_get_field')) {
  /**
   * Alias for yaml_cf_get_field()
   */
  function ycf_get_field($field_name, $post_id = null) {
    return yaml_cf_get_field($field_name, $post_id);
  }
}

if (!function_exists('ycf_get_fields')) {
  /**
   * Alias for yaml_cf_get_fields()
   */
  function ycf_get_fields($post_id = null) {
    return yaml_cf_get_fields($post_id);
  }
}

if (!function_exists('ycf_has_field')) {
  /**
   * Alias for yaml_cf_has_field()
   */
  function ycf_has_field($field_name, $post_id = null) {
    return yaml_cf_has_field($field_name, $post_id);
  }
}

/**
 * Get image data for an image field
 * Returns an array with image information
 *
 * @param string $field_name The name of the image field
 * @param int|string $post_id Optional. Post ID or 'partial:filename' for partials. Defaults to current post.
 * @param string $size Optional. Image size (thumbnail, medium, large, full). Defaults to 'full'.
 * @return array|null Array with 'id', 'url', 'alt', 'width', 'height' keys or null if not found
 */
function ycf_get_image($field_name, $post_id = null, $size = 'full') {
  $attachment_id = ycf_get_field($field_name, $post_id);

  if (!$attachment_id || !is_numeric($attachment_id)) {
    return null;
  }

  $image_data = [
    'id' => $attachment_id,
    'url' => wp_get_attachment_image_url($attachment_id, $size),
    'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
    'title' => get_the_title($attachment_id),
    'caption' => wp_get_attachment_caption($attachment_id),
    'description' => get_post_field('post_content', $attachment_id),
  ];

  $metadata = wp_get_attachment_metadata($attachment_id);
  if ($metadata && isset($metadata['width']) && isset($metadata['height'])) {
    $image_data['width'] = $metadata['width'];
    $image_data['height'] = $metadata['height'];
  }

  // Get specific size dimensions if not full
  if ($size !== 'full' && isset($metadata['sizes'][$size])) {
    $image_data['width'] = $metadata['sizes'][$size]['width'];
    $image_data['height'] = $metadata['sizes'][$size]['height'];
  }

  return $image_data;
}

/**
 * Get file data for a file field
 * Returns an array with file information
 *
 * @param string $field_name The name of the file field
 * @param int|string $post_id Optional. Post ID or 'partial:filename' for partials. Defaults to current post.
 * @return array|null Array with 'id', 'url', 'filename', 'filesize', 'mime_type' keys or null if not found
 */
function ycf_get_file($field_name, $post_id = null) {
  $attachment_id = ycf_get_field($field_name, $post_id);

  if (!$attachment_id || !is_numeric($attachment_id)) {
    return null;
  }

  $file_path = get_attached_file($attachment_id);
  $file_url = wp_get_attachment_url($attachment_id);

  if (!$file_path || !$file_url) {
    return null;
  }

  return [
    'id' => $attachment_id,
    'url' => $file_url,
    'path' => $file_path,
    'filename' => basename($file_path),
    'filesize' => filesize($file_path),
    'mime_type' => get_post_mime_type($attachment_id),
    'title' => get_the_title($attachment_id),
  ];
}

register_uninstall_hook(__FILE__, 'yaml_cf_uninstall');

function yaml_cf_uninstall() {
  delete_option('yaml_cf_template_settings');
  delete_option('yaml_cf_schemas');
  delete_option('yaml_cf_partial_data');

  // Delete all post meta for this plugin across all posts
  delete_post_meta_by_key('_yaml_cf_data');

  // Clear template cache
  delete_transient('yaml_cf_templates_' . get_stylesheet());
}
