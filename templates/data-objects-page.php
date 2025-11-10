<?php
/**
 * Data Objects Page
 * Main page for managing data object types
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get all data object types
$yaml_cf_data_object_types = get_option('yaml_cf_data_object_types', []);
?>

<div class="wrap">
  <div class="yaml-cf-admin-container">
    <div class="yaml-cf-header">
      <div class="yaml-cf-header-content">
        <img src="<?php echo esc_url(YAML_CF_PLUGIN_URL . 'icon-256x256.png'); ?>" alt="YAML Custom Fields" class="yaml-cf-logo" />
        <div class="yaml-cf-header-text">
          <h1><?php esc_html_e('Data Objects', 'yaml-custom-fields'); ?></h1>
          <p class="yaml-cf-tagline"><?php esc_html_e('Create and manage structured data objects', 'yaml-custom-fields'); ?></p>
        </div>
      </div>
    </div>

    <div class="yaml-cf-intro">
      <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-edit-data-object-type')); ?>" class="button button-primary">
          <?php esc_html_e('Add New Type', 'yaml-custom-fields'); ?>
        </a>
      </p>
      <p><?php esc_html_e('Create and manage structured data objects that can be referenced in your schemas (e.g., Universities, Companies, Team Members).', 'yaml-custom-fields'); ?></p>
    </div>

  <?php if (empty($yaml_cf_data_object_types)) : ?>
    <div class="notice notice-info inline">
      <p><?php esc_html_e('No data object types created yet. Click "Add New Type" to create your first data object type.', 'yaml-custom-fields'); ?></p>
    </div>
  <?php else : ?>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th><?php esc_html_e('Type Name', 'yaml-custom-fields'); ?></th>
          <th><?php esc_html_e('Slug', 'yaml-custom-fields'); ?></th>
          <th><?php esc_html_e('Entries', 'yaml-custom-fields'); ?></th>
          <th><?php esc_html_e('Schema', 'yaml-custom-fields'); ?></th>
          <th><?php esc_html_e('Actions', 'yaml-custom-fields'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($yaml_cf_data_object_types as $yaml_cf_slug => $yaml_cf_type) : ?>
          <?php
          $yaml_cf_entries = get_option('yaml_cf_data_object_entries_' . $yaml_cf_slug, []);
          $yaml_cf_entry_count = count($yaml_cf_entries);
          $yaml_cf_has_schema = !empty($yaml_cf_type['schema']);
          ?>
          <tr>
            <td><strong><?php echo esc_html($yaml_cf_type['name']); ?></strong></td>
            <td><code><?php echo esc_html($yaml_cf_slug); ?></code></td>
            <td><?php echo esc_html($yaml_cf_entry_count); ?></td>
            <td>
              <?php if ($yaml_cf_has_schema) : ?>
                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                <?php esc_html_e('Defined', 'yaml-custom-fields'); ?>
              <?php else : ?>
                <span style="color: #dba617;"><?php esc_html_e('Not defined', 'yaml-custom-fields'); ?></span>
              <?php endif; ?>
            </td>
            <td>
              <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-edit-data-object-type&type=' . urlencode($yaml_cf_slug))); ?>" class="button">
                <?php esc_html_e('Edit Schema', 'yaml-custom-fields'); ?>
              </a>
              <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-manage-data-object-entries&type=' . urlencode($yaml_cf_slug))); ?>" class="button">
                <?php esc_html_e('Manage Entries', 'yaml-custom-fields'); ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
  </div>
</div>
