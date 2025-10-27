<?php
/**
 * Edit Schema Page Template
 * File: templates/edit-schema-page.php
 */

if (!defined('ABSPATH')) {
  exit;
}
?>

<div class="wrap">
  <div class="yaml-cf-admin-container">
    <div class="yaml-cf-header">
      <div class="yaml-cf-header-content">
        <img src="<?php echo esc_url(YAML_CF_PLUGIN_URL . 'icon-256x256.png'); ?>" alt="YAML Custom Fields" class="yaml-cf-logo" />
        <div class="yaml-cf-header-text">
          <h1><?php echo esc_html($template_name); ?></h1>
          <p class="yaml-cf-tagline"><?php esc_html_e('Edit YAML schema for this template', 'yaml-custom-fields'); ?></p>
        </div>
      </div>
    </div>

    <div class="yaml-cf-intro">
      <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-custom-fields')); ?>" class="button">
          <span class="dashicons dashicons-arrow-left-alt2"></span>
          <?php esc_html_e('Back to Templates', 'yaml-custom-fields'); ?>
        </a>
      </p>
      <p><strong><?php esc_html_e('Template File:', 'yaml-custom-fields'); ?></strong> <code><?php echo esc_html($template); ?></code></p>
      <p><?php esc_html_e('Define the YAML schema that specifies which fields are available for this template.', 'yaml-custom-fields'); ?></p>
    </div>

    <form method="post" action="">
      <?php wp_nonce_field('yaml_cf_save_schema', 'yaml_cf_save_schema_nonce'); ?>
      <input type="hidden" name="template" value="<?php echo esc_attr($template); ?>" />

      <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
        <p><?php esc_html_e('Enter your YAML schema below:', 'yaml-custom-fields'); ?></p>
        <textarea name="schema" id="yaml-cf-schema-editor" rows="20" class="large-text code" style="font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.6; width: 100%; border: 1px solid #ddd; padding: 10px;"><?php echo esc_textarea($schema_yaml); ?></textarea>
      </div>

      <p class="submit" style="margin-top: 20px;">
        <button type="submit" class="button button-primary button-large">
          <?php esc_html_e('Save Schema', 'yaml-custom-fields'); ?>
        </button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-custom-fields')); ?>" class="button button-large">
          <?php esc_html_e('Cancel', 'yaml-custom-fields'); ?>
        </a>
      </p>
    </form>
  </div>
</div>

<?php if (!empty($success_message)) : ?>
<script>
jQuery(document).ready(function($) {
  if (typeof YamlCF !== 'undefined' && YamlCF.showMessage) {
    YamlCF.showMessage('<?php echo esc_js($success_message); ?>', 'success');
  }
});
</script>
<?php endif; ?>
