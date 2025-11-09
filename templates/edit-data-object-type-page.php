<?php
/**
 * Edit Data Object Type Page
 * Create or edit a data object type and its schema
 */

if (!defined('ABSPATH')) {
  exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter
$type_slug = isset($_GET['type']) ? sanitize_key($_GET['type']) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$data_object_types = get_option('yaml_cf_data_object_types', []);
$is_editing = !empty($type_slug) && isset($data_object_types[$type_slug]);

// Handle form submission
if (isset($_POST['yaml_cf_save_data_object_type_nonce'])) {
  if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['yaml_cf_save_data_object_type_nonce'])), 'yaml_cf_save_data_object_type')) {
    wp_die(esc_html__('Security check failed', 'yaml-custom-fields'));
  }

  $type_name = isset($_POST['type_name']) ? sanitize_text_field(wp_unslash($_POST['type_name'])) : '';
  $new_type_slug = isset($_POST['type_slug']) ? sanitize_key($_POST['type_slug']) : '';
  $schema_yaml = isset($_POST['schema']) ? wp_unslash($_POST['schema']) : '';

  if (empty($type_name) || empty($new_type_slug)) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Type name and slug are required.', 'yaml-custom-fields') . '</p></div>';
  } else {
    $data_object_types[$new_type_slug] = [
      'name' => $type_name,
      'schema' => $schema_yaml,
    ];

    update_option('yaml_cf_data_object_types', $data_object_types);

    echo '<div class="notice notice-success"><p>' . esc_html__('Data object type saved successfully!', 'yaml-custom-fields') . '</p></div>';

    // Update vars for display
    $type_slug = $new_type_slug;
    $is_editing = true;
  }
}

// Get current data
$type_name = $is_editing ? $data_object_types[$type_slug]['name'] : '';
$schema_yaml = $is_editing ? $data_object_types[$type_slug]['schema'] : '';
?>

<div class="wrap">
  <div class="yaml-cf-admin-container">
    <div class="yaml-cf-header">
      <div class="yaml-cf-header-content">
        <img src="<?php echo esc_url(YAML_CF_PLUGIN_URL . 'icon-256x256.png'); ?>" alt="YAML Custom Fields" class="yaml-cf-logo" />
        <div class="yaml-cf-header-text">
          <h1><?php echo $is_editing ? esc_html($type_name) : esc_html__('New Data Object Type', 'yaml-custom-fields'); ?></h1>
          <p class="yaml-cf-tagline"><?php esc_html_e('Define the schema for this data object type', 'yaml-custom-fields'); ?></p>
        </div>
      </div>
    </div>

    <div class="yaml-cf-intro">
      <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-data-objects')); ?>" class="button">
          <span class="dashicons dashicons-arrow-left-alt2"></span>
          <?php esc_html_e('Back to Data Objects', 'yaml-custom-fields'); ?>
        </a>
        <?php if ($is_editing) : ?>
          <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-manage-data-object-entries&type=' . urlencode($type_slug))); ?>" class="button button-secondary" style="margin-left: 10px;">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php esc_html_e('Manage Entries', 'yaml-custom-fields'); ?>
          </a>
        <?php endif; ?>
      </p>
      <?php if ($is_editing) : ?>
        <p><strong><?php esc_html_e('Type Slug:', 'yaml-custom-fields'); ?></strong> <code><?php echo esc_html($type_slug); ?></code></p>
      <?php endif; ?>
      <p><?php esc_html_e('Define the YAML schema that specifies which fields each entry of this type will have.', 'yaml-custom-fields'); ?></p>
    </div>

    <form method="post" action="">
      <?php wp_nonce_field('yaml_cf_save_data_object_type', 'yaml_cf_save_data_object_type_nonce'); ?>

      <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1); margin-bottom: 20px;">
        <h2><?php esc_html_e('Type Information', 'yaml-custom-fields'); ?></h2>

        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="type_name"><?php esc_html_e('Type Name', 'yaml-custom-fields'); ?></label>
            </th>
            <td>
              <input type="text" name="type_name" id="type_name" value="<?php echo esc_attr($type_name); ?>" class="regular-text" required />
              <p class="description"><?php esc_html_e('e.g., "Universities", "Companies", "Team Members"', 'yaml-custom-fields'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="type_slug"><?php esc_html_e('Type Slug', 'yaml-custom-fields'); ?></label>
            </th>
            <td>
              <input type="text" name="type_slug" id="type_slug" value="<?php echo esc_attr($type_slug); ?>" class="regular-text" <?php echo $is_editing ? 'readonly' : ''; ?> required />
              <p class="description"><?php esc_html_e('e.g., "universities", "companies" (lowercase, no spaces). Cannot be changed after creation.', 'yaml-custom-fields'); ?></p>
            </td>
          </tr>
        </table>
      </div>

      <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
        <h2><?php esc_html_e('Schema Definition', 'yaml-custom-fields'); ?></h2>
        <p><?php esc_html_e('Enter your YAML schema below:', 'yaml-custom-fields'); ?></p>
        <textarea name="schema" id="yaml-cf-schema-editor" rows="20" class="large-text code" style="font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.6; width: 100%; border: 1px solid #ddd; padding: 10px;"><?php echo esc_textarea($schema_yaml); ?></textarea>
      </div>

      <p class="submit" style="margin-top: 20px;">
        <button type="submit" class="button button-primary button-large">
          <?php esc_html_e('Save Data Object Type', 'yaml-custom-fields'); ?>
        </button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-cf-data-objects')); ?>" class="button button-large">
          <?php esc_html_e('Cancel', 'yaml-custom-fields'); ?>
        </a>
      </p>
    </form>

    <div class="yaml-cf-schema-examples">
      <h2><?php esc_html_e('Schema Example', 'yaml-custom-fields'); ?></h2>
      <p><?php esc_html_e('Example schema for a "Universities" data object type:', 'yaml-custom-fields'); ?></p>
      <pre>fields:
  - name: name
    label: University Name
    type: string
    required: true
  - name: logo
    label: Logo
    type: image
  - name: website
    label: Website URL
    type: string
  - name: description
    label: Description
    type: text
  - name: location
    label: Location
    type: string
  - name: founded
    label: Founded Year
    type: number</pre>
    </div>

    <?php if ($is_editing) : ?>
    <div class="yaml-cf-schema-examples" style="margin-top: 30px;">
      <h2><?php esc_html_e('Usage in Page Schemas', 'yaml-custom-fields'); ?></h2>
      <p><?php esc_html_e('Reference this data object type in your page template schemas:', 'yaml-custom-fields'); ?></p>
      <div style="position: relative;">
        <button type="button" class="yaml-cf-copy-snippet button button-small" data-snippet="- name: <?php echo esc_attr($type_slug); ?>&#10;  label: <?php echo esc_attr($type_name); ?>&#10;  type: data_object&#10;  options:&#10;    object_type: <?php echo esc_attr($type_slug); ?>" style="position: absolute; top: 10px; right: 10px;">
          <span class="dashicons dashicons-editor-code"></span>
          <?php esc_html_e('Copy', 'yaml-custom-fields'); ?>
        </button>
        <pre>- name: <?php echo esc_html($type_slug); ?>
  label: <?php echo esc_html($type_name); ?>
  type: data_object
  options:
    object_type: <?php echo esc_html($type_slug); ?></pre>
      </div>
    </div>

    <div class="yaml-cf-schema-examples" style="margin-top: 30px;">
      <h2><?php esc_html_e('Usage in Templates', 'yaml-custom-fields'); ?></h2>
      <p><?php esc_html_e('Retrieve data object entries in your theme templates:', 'yaml-custom-fields'); ?></p>

      <h3 style="margin-top: 20px;"><?php esc_html_e('Get Single Entry', 'yaml-custom-fields'); ?></h3>
      <div style="position: relative;">
        <button type="button" class="yaml-cf-copy-snippet button button-small" data-snippet="&lt;?php&#10;$<?php echo esc_attr($type_slug); ?> = ycf_get_data_object('<?php echo esc_attr($type_slug); ?>');&#10;if ($<?php echo esc_attr($type_slug); ?>) {&#10;  echo '&lt;h2&gt;' . esc_html($<?php echo esc_attr($type_slug); ?>['name']) . '&lt;/h2&gt;';&#10;  // Access other fields: $<?php echo esc_attr($type_slug); ?>['field_name']&#10;}&#10;?&gt;" style="position: absolute; top: 10px; right: 10px;">
          <span class="dashicons dashicons-editor-code"></span>
          <?php esc_html_e('Copy', 'yaml-custom-fields'); ?>
        </button>
        <pre>&lt;?php
$<?php echo esc_html($type_slug); ?> = ycf_get_data_object('<?php echo esc_html($type_slug); ?>');
if ($<?php echo esc_html($type_slug); ?>) {
  echo '&lt;h2&gt;' . esc_html($<?php echo esc_html($type_slug); ?>['name']) . '&lt;/h2&gt;';
  // Access other fields: $<?php echo esc_html($type_slug); ?>['field_name']
}
?&gt;</pre>
      </div>

      <h3 style="margin-top: 20px;"><?php esc_html_e('Get All Entries', 'yaml-custom-fields'); ?></h3>
      <div style="position: relative;">
        <button type="button" class="yaml-cf-copy-snippet button button-small" data-snippet="&lt;?php&#10;$all_<?php echo esc_attr($type_slug); ?> = ycf_get_data_objects('<?php echo esc_attr($type_slug); ?>');&#10;foreach ($all_<?php echo esc_attr($type_slug); ?> as $entry_id => $entry) {&#10;  echo '&lt;h3&gt;' . esc_html($entry['name']) . '&lt;/h3&gt;';&#10;}&#10;?&gt;" style="position: absolute; top: 10px; right: 10px;">
          <span class="dashicons dashicons-editor-code"></span>
          <?php esc_html_e('Copy', 'yaml-custom-fields'); ?>
        </button>
        <pre>&lt;?php
$all_<?php echo esc_html($type_slug); ?> = ycf_get_data_objects('<?php echo esc_html($type_slug); ?>');
foreach ($all_<?php echo esc_html($type_slug); ?> as $entry_id => $entry) {
  echo '&lt;h3&gt;' . esc_html($entry['name']) . '&lt;/h3&gt;';
}
?&gt;</pre>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
