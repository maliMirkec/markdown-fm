<?php
/**
 * Manage Data Object Entries Page
 * CRUD interface for data object entries
 */

if (!defined('ABSPATH')) {
  exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter
$type_slug = isset($_GET['type']) ? sanitize_key($_GET['type']) : '';
$entry_id = isset($_GET['entry']) ? sanitize_key($_GET['entry']) : '';
$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

if (empty($type_slug)) {
  wp_safe_redirect(admin_url('admin.php?page=yaml-cf-data-objects'));
  exit;
}

$data_object_types = get_option('yaml_cf_data_object_types', []);
if (!isset($data_object_types[$type_slug])) {
  wp_safe_redirect(admin_url('admin.php?page=yaml-cf-data-objects'));
  exit;
}

$type_name = $data_object_types[$type_slug]['name'];
$schema_yaml = $data_object_types[$type_slug]['schema'];

// Parse schema
$plugin = YAML_Custom_Fields::get_instance();
$schema = $plugin->parse_yaml_schema($schema_yaml);

// Get all entries
$entries = get_option('yaml_cf_data_object_entries_' . $type_slug, []);

// Handle form submissions
if (isset($_POST['yaml_cf_save_entry_nonce'])) {
  if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['yaml_cf_save_entry_nonce'])), 'yaml_cf_save_entry')) {
    wp_die(esc_html__('Security check failed', 'yaml-custom-fields'));
  }

  $entry_id_to_save = isset($_POST['entry_id']) ? sanitize_key($_POST['entry_id']) : uniqid('entry_', true);

  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_field_data()
  $entry_data = isset($_POST['entry_data']) ? $plugin->sanitize_field_data(wp_unslash($_POST['entry_data']), $schema) : [];

  $entries[$entry_id_to_save] = $entry_data;
  update_option('yaml_cf_data_object_entries_' . $type_slug, $entries);

  echo '<div class="notice notice-success"><p>' . esc_html__('Entry saved successfully!', 'yaml-custom-fields') . '</p></div>';

  $action = 'list';
}

// Handle delete
if (isset($_POST['yaml_cf_delete_entry_nonce'])) {
  if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['yaml_cf_delete_entry_nonce'])), 'yaml_cf_delete_entry')) {
    wp_die(esc_html__('Security check failed', 'yaml-custom-fields'));
  }

  $entry_id_to_delete = isset($_POST['entry_id']) ? sanitize_key($_POST['entry_id']) : '';
  if (isset($entries[$entry_id_to_delete])) {
    unset($entries[$entry_id_to_delete]);
    update_option('yaml_cf_data_object_entries_' . $type_slug, $entries);
    echo '<div class="notice notice-success"><p>' . esc_html__('Entry deleted successfully!', 'yaml-custom-fields') . '</p></div>';
  }

  $action = 'list';
}

// Get entry data for editing
$entry_data = [];
if ($action === 'edit' && isset($entries[$entry_id])) {
  $entry_data = $entries[$entry_id];
}
?>

<div class="wrap">
  <div class="yaml-cf-admin-container">
    <div class="yaml-cf-header">
      <div class="yaml-cf-header-content">
        <img src="<?php echo esc_url(YAML_CF_PLUGIN_URL . 'icon-256x256.png'); ?>" alt="YAML Custom Fields" class="yaml-cf-logo" />
        <div class="yaml-cf-header-text">
          <h1><?php echo esc_html($type_name); ?></h1>
          <p class="yaml-cf-tagline"><?php esc_html_e('Manage entries', 'yaml-custom-fields'); ?></p>
        </div>
      </div>
    </div>

    <div class="yaml-cf-intro">
      <p>
        <?php if ($action === 'list') : ?>
          <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-data-objects')); ?>" class="button">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php esc_html_e('Back to Data Objects', 'yaml-custom-fields'); ?>
          </a>
          <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-edit-data-object-type&type=' . urlencode($type_slug))); ?>" class="button button-secondary" style="margin-left: 10px;">
            <span class="dashicons dashicons-edit"></span>
            <?php esc_html_e('Edit Schema', 'yaml-custom-fields'); ?>
          </a>
          <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-manage-data-object-entries&type=' . urlencode($type_slug) . '&action=add')); ?>" class="button button-primary" style="margin-left: 10px;">
            <?php esc_html_e('Add New Entry', 'yaml-custom-fields'); ?>
          </a>
        <?php else : ?>
          <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-manage-data-object-entries&type=' . urlencode($type_slug))); ?>" class="button">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php esc_html_e('Back to Entries', 'yaml-custom-fields'); ?>
          </a>
        <?php endif; ?>
      </p>
      <p><strong><?php esc_html_e('Type:', 'yaml-custom-fields'); ?></strong> <code><?php echo esc_html($type_slug); ?></code></p>
    </div>

    <?php if ($action === 'list') : ?>
      <!-- List View -->
      <?php if (empty($entries)) : ?>
        <div class="notice notice-info inline">
          <p><?php esc_html_e('No entries yet. Click "Add New Entry" to create your first entry.', 'yaml-custom-fields'); ?></p>
        </div>
      <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th><?php esc_html_e('Entry ID', 'yaml-custom-fields'); ?></th>
              <th><?php esc_html_e('Data', 'yaml-custom-fields'); ?></th>
              <th><?php esc_html_e('Actions', 'yaml-custom-fields'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($entries as $yaml_cf_entry_id => $yaml_cf_entry) : ?>
              <tr>
                <td><code><?php echo esc_html($yaml_cf_entry_id); ?></code></td>
                <td>
                  <?php
                  // Display first field value as preview
                  if (!empty($schema['fields']) && !empty($yaml_cf_entry)) {
                    $first_field = $schema['fields'][0];
                    $first_value = isset($yaml_cf_entry[$first_field['name']]) ? $yaml_cf_entry[$first_field['name']] : '';
                    if (is_string($first_value)) {
                      echo esc_html(wp_trim_words($first_value, 10));
                    } else {
                      echo '<em>' . esc_html__('(Complex data)', 'yaml-custom-fields') . '</em>';
                    }
                  }
                  ?>
                </td>
                <td>
                  <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-manage-data-object-entries&type=' . urlencode($type_slug) . '&action=edit&entry=' . urlencode($yaml_cf_entry_id))); ?>" class="button">
                    <?php esc_html_e('Edit', 'yaml-custom-fields'); ?>
                  </a>
                  <form method="post" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete this entry?', 'yaml-custom-fields'); ?>');">
                    <?php wp_nonce_field('yaml_cf_delete_entry', 'yaml_cf_delete_entry_nonce'); ?>
                    <input type="hidden" name="entry_id" value="<?php echo esc_attr($yaml_cf_entry_id); ?>" />
                    <button type="submit" class="button button-link-delete"><?php esc_html_e('Delete', 'yaml-custom-fields'); ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

    <?php else : ?>
      <!-- Add/Edit Form -->
      <form method="post">
        <?php wp_nonce_field('yaml_cf_save_entry', 'yaml_cf_save_entry_nonce'); ?>
        <?php if ($action === 'edit') : ?>
          <input type="hidden" name="entry_id" value="<?php echo esc_attr($entry_id); ?>" />
        <?php endif; ?>

        <div class="yaml-cf-fields" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
          <?php
          if (!empty($schema['fields'])) {
            $yaml_cf_context = ['type' => 'data_object', 'object_type' => $type_slug];
            $plugin->render_schema_fields($schema['fields'], $entry_data, 'entry_data', $yaml_cf_context);
          } else {
            echo '<p>' . esc_html__('No fields defined in schema.', 'yaml-custom-fields') . '</p>';
          }
          ?>
        </div>

        <p class="submit" style="margin-top: 20px;">
          <button type="submit" class="button button-primary button-large">
            <?php esc_html_e('Save Entry', 'yaml-custom-fields'); ?>
          </button>
          <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-manage-data-object-entries&type=' . urlencode($type_slug))); ?>" class="button button-large">
            <?php esc_html_e('Cancel', 'yaml-custom-fields'); ?>
          </a>
        </p>
      </form>
    <?php endif; ?>
  </div>
</div>
