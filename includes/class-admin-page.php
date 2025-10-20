<?php
namespace MarkdownFM;

if (!defined('ABSPATH')) exit;

class Admin_Page {

  public static function init() {
    add_action('admin_menu', [__CLASS__, 'register_menu']);
    add_action('admin_post_markdown_fm_save_enabled', [__CLASS__, 'save_enabled_templates']);
    add_action('admin_post_markdown_fm_save_schema', [__CLASS__, 'save_schema']);
  }

  public static function register_menu() {
    add_menu_page(
      'Markdown FM Templates',
      'Markdown FM',
      'manage_options',
      'markdown-fm-templates',
      [__CLASS__, 'render_templates_page'],
      'dashicons-editor-code',
      30
    );

    add_submenu_page(
      null,
      'Edit YAML Schema',
      'Edit Schema',
      'manage_options',
      'markdown-fm-schema',
      [__CLASS__, 'render_schema_page']
    );
  }

  public static function render_templates_page() {
    $theme_dir = get_stylesheet_directory();
    $templates = glob($theme_dir . '/template-*.php');
    $enabled = get_option('markdown_fm_templates_enabled', []);

    ?>
    <div class="wrap">
      <h1>Markdown FM Templates</h1>
      <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success"><p>Changes saved.</p></div>
      <?php endif; ?>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="markdown_fm_save_enabled">
        <?php wp_nonce_field('markdown_fm_save_enabled_nonce'); ?>
        <table class="widefat fixed striped">
          <thead>
            <tr>
              <th>Template</th>
              <th>YAML Enabled</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($templates)): ?>
            <?php foreach ($templates as $file): ?>
              <?php
                $filename = basename($file);
                $is_enabled = !empty($enabled[$filename]);
              ?>
              <tr>
                <td><?php echo esc_html($filename); ?></td>
                <td>
                  <input type="checkbox" name="enabled_templates[<?php echo esc_attr($filename); ?>]" value="1" <?php checked($is_enabled, true); ?>>
                </td>
                <td>
                  <?php if ($is_enabled): ?>
                    <a href="<?php echo admin_url('admin.php?page=markdown-fm-schema&template=' . urlencode($filename)); ?>">Edit Schema</a>
                  <?php else: ?>
                    <span style="color:#888;">Enable YAML first</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="3">No templates found in the current theme.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
        <p><input type="submit" class="button button-primary" value="Save Changes"></p>
      </form>
    </div>
    <?php
  }

  public static function save_enabled_templates() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized user');
    check_admin_referer('markdown_fm_save_enabled_nonce');
    $enabled = array_map('intval', $_POST['enabled_templates'] ?? []);
    update_option('markdown_fm_templates_enabled', $enabled);
    wp_redirect(admin_url('admin.php?page=markdown-fm-templates&updated=1'));
    exit;
  }

  public static function render_schema_page() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized user');

    $template = sanitize_text_field($_GET['template'] ?? '');
    if (!$template) {
      echo '<div class="notice notice-error"><p>No template specified.</p></div>';
      return;
    }

    // Load saved schema
    $schemas = get_option('markdown_fm_schemas', []);
    $yaml = $schemas[$template]['yaml'] ?? ''; // <-- leave empty if not saved

    ?>
    <div class="wrap">
      <h1>Edit Schema: <?php echo esc_html($template); ?></h1>

      <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success"><p>Schema saved.</p></div>
      <?php endif; ?>
      <div class="notice notice-info">
        <p>
          <strong>Schema Help:</strong> Each section in your schema must include <code>name</code> and <code>title</code> keys.
          Additional keys are optional and depend on the section type.
        </p>
        <details>
          <summary>Example YAML for an article page schema:</summary>
          <div>
            <pre>
fields:
  - name: Boolean
    label: Boolean
    type: boolean
    default: true
  - name: start_time
    label: Starts at
    type: date
    options:
      time: true
      format: dd-MM-yyyy HH:mm
  - name: description
    label: Description
    type: string
    options:
      minlength: 20
      maxlength: 160
  - name: tweet
    label: Tweet
    type: text
    options:
      maxlength: 140
  - name: body
    label: Body
    type: rich-text
  - name: body
    label: Body
    type: code
    options:
      language: html
  - name: age
    label: Age
    type: number
    options:
      min: 21
  - name: author
    label: Author
    type: select
    options:
      multiple: false
      values:
        - value: bob
          label: Bob Smith
        - value: patricia
          label: Patricia Wills
        - value: alice
          label: Alice Brown
  - name: cover
    label: Cover Image
    type: image
  - name: attachment
    label: Attachment
    type: file
  - name: sections
    label: Page Sections
    type: block
    list: true # Often used with type: block to allow multiple sections
    blockKey: type # Optional: customize the key used to store the block name, defaults to `_block`
    blocks:
      - name: hero
        label: Add Hero Section
        component: hero # Assumes a 'hero' component is defined and that its type is `object`
      - name: text
        label: Add Text Block
        fields:
          - name: content
            label: Text Content
            type: rich-text
            </pre>
          </div>
        </details>
        <p>
          For full documentation and additional examples, visit
          <a href="https://pagescms.org/docs/configuration/" target="_blank">Markdown FM Docs</a>.
        </p>
      </div>

      <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="markdown_fm_save_schema">
        <input type="hidden" name="template" value="<?php echo esc_attr($template); ?>">
        <?php wp_nonce_field('markdown_fm_save_schema_nonce'); ?>

        <p>Enter YAML for this template:</p>
        <textarea name="markdown_fm_yaml" rows="20" class="widefat" style="font-family:monospace"><?php echo esc_textarea($yaml); ?></textarea>

        <p>
          <input type="submit" class="button button-primary" value="Save Schema">
          <a href="<?php echo admin_url('admin.php?page=markdown-fm-templates'); ?>" class="button button-secondary" style="background:#fff;color:#000;border:1px solid #ccc;">View all templates</a>
        </p>
      </form>
    </div>
    <?php
  }

  public static function save_schema() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized user');
    check_admin_referer('markdown_fm_save_schema_nonce');

    $template = sanitize_text_field($_POST['template'] ?? '');
    $yaml = wp_unslash($_POST['markdown_fm_yaml'] ?? '');

    $fields = [];

    if (!empty(trim($yaml))) {
      try {
        $data = \Symfony\Component\Yaml\Yaml::parse($yaml);

        // Validation: ensure every section has a 'type' key
        if (isset($data['sections']) && is_array($data['sections'])) {
          foreach ($data['sections'] as $i => $section) {
            if (empty($section['type'])) {
              wp_die('Invalid schema: section #' . ($i + 1) . ' is missing a "type" key.');
            }
          }
          $fields = $data['sections'];
        }
      } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
        wp_die('YAML parse error: ' . esc_html($e->getMessage()));
      }
    }

    // Save YAML and parsed fields
    $schemas = get_option('markdown_fm_schemas', []);
    $schemas[$template] = [
      'yaml' => $yaml,
      'fields' => $fields
    ];
    update_option('markdown_fm_schemas', $schemas);

    wp_redirect(admin_url('admin.php?page=markdown-fm-schema&template=' . urlencode($template) . '&updated=1'));
    exit;
  }

}
