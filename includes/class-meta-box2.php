<?php
namespace MarkdownFM;

class Meta_Box {

  const META_KEY = '_markdown_fm_data';
  const TEMPLATE_KEY = '_markdown_fm_template';

  /**
   * Register meta box
   */
  public static function init() {
    add_action('add_meta_boxes', [__CLASS__, 'register_meta_box']);
    add_action('save_post', [__CLASS__, 'save_post'], 10, 2);
    add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
  }

  /**
   * Enqueue scripts/styles (optional: for markdown editor or JS)
   */
  public static function enqueue_assets($hook) {
    if (in_array($hook, ['post.php', 'post-new.php'])) {
      wp_enqueue_style('markdown-fm-admin', plugin_dir_url(__DIR__) . 'assets/admin.css');
      // Optional: enqueue SimpleMDE or other Markdown editors here
    }
  }

  /**
   * Add meta box to page/post
   */
  public static function register_meta_box() {
    add_meta_box(
      'markdown_fm_meta_box',
      'Markdown FM Fields',
      [__CLASS__, 'render_meta_box'],
      ['post', 'page'],
      'normal',
      'high'
    );
  }

  /**
   * Render meta box
   */
  public static function render_meta_box($post) {
    wp_nonce_field('markdown_fm_save_meta', 'markdown_fm_nonce');

    // Load available templates
    $schemas = Field_Manager::get_schemas();
    $templates = [];
    if (!empty($schemas['content'])) {
      foreach ($schemas['content'] as $collection) {
        $templates[$collection['name']] = $collection['label'] ?? $collection['name'];
      }
    }

    // Load selected template
    $selected_template = get_post_meta($post->ID, self::TEMPLATE_KEY, true);

    // Render template selector
    echo '<p><label for="markdown_fm_template"><strong>Select YAML Template:</strong></label><br>';
    echo '<select id="markdown_fm_template" name="markdown_fm_template">';
    echo '<option value="">-- Select template --</option>';
    foreach ($templates as $name => $label) {
      $selected = ($selected_template === $name) ? 'selected' : '';
      echo '<option value="' . esc_attr($name) . '" ' . $selected . '>' . esc_html($label) . '</option>';
    }
    echo '</select></p>';

    // If template is selected, render its fields
    if ($selected_template) {
      $fields = Field_Manager::get_fields_for_template($selected_template);
      if (empty($fields)) {
        echo '<p>No fields defined for this template.</p>';
        return;
      }

      // Load saved data
      $data = get_post_meta($post->ID, self::META_KEY, true);
      if (!is_array($data)) $data = [];

      // Render fields
      Field_Manager::render_fields($fields, $data);
    }
  }

  /**
   * Save meta box data
   */
  public static function save_post($post_id, $post) {
    // Security checks
    if (!isset($_POST['markdown_fm_nonce']) || !wp_verify_nonce($_POST['markdown_fm_nonce'], 'markdown_fm_save_meta')) {
      return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Save selected template
    if (isset($_POST['markdown_fm_template'])) {
      $template = sanitize_text_field($_POST['markdown_fm_template']);
      update_post_meta($post_id, self::TEMPLATE_KEY, $template);
    }

    // Save field data
    if (isset($_POST['markdown_fm_data']) && is_array($_POST['markdown_fm_data'])) {
      $data = $_POST['markdown_fm_data'];
      Field_Manager::save_post_fields($post_id, $data);
    }
  }
}
