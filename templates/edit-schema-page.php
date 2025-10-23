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
  <div class="markdown-fm-admin-container">
    <div class="markdown-fm-header">
      <div class="markdown-fm-header-content">
        <img src="<?php echo esc_url(MARKDOWN_FM_PLUGIN_URL . 'icon-256x256.png'); ?>" alt="Markdown FM" class="markdown-fm-logo" />
        <div class="markdown-fm-header-text">
          <h1><?php echo esc_html($template_name); ?></h1>
          <p class="markdown-fm-tagline"><?php _e('Edit YAML schema for this template', 'markdown-fm'); ?></p>
        </div>
      </div>
    </div>

    <div class="markdown-fm-intro">
      <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=markdown-fm')); ?>" class="button">
          <span class="dashicons dashicons-arrow-left-alt2" style="margin-top: 3px;"></span>
          <?php _e('Back to Templates', 'markdown-fm'); ?>
        </a>
      </p>
      <p><strong><?php _e('Template File:', 'markdown-fm'); ?></strong> <code><?php echo esc_html($template); ?></code></p>
      <p><?php _e('Define the YAML schema that specifies which fields are available for this template.', 'markdown-fm'); ?></p>
    </div>

    <form method="post" action="">
      <?php wp_nonce_field('markdown_fm_save_schema', 'markdown_fm_save_schema_nonce'); ?>
      <input type="hidden" name="template" value="<?php echo esc_attr($template); ?>" />

      <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
        <p><?php _e('Enter your YAML schema below:', 'markdown-fm'); ?></p>
        <textarea name="schema" id="markdown-fm-schema-editor" rows="20" class="large-text code" style="font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.6; width: 100%; border: 1px solid #ddd; padding: 10px;"><?php echo esc_textarea($schema_yaml); ?></textarea>
      </div>

      <p class="submit" style="margin-top: 20px;">
        <button type="submit" class="button button-primary button-large">
          <?php _e('Save Schema', 'markdown-fm'); ?>
        </button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=markdown-fm')); ?>" class="button button-large">
          <?php _e('Cancel', 'markdown-fm'); ?>
        </a>
      </p>
    </form>
  </div>
</div>

<?php if (!empty($success_message)) : ?>
<script>
jQuery(document).ready(function($) {
  if (typeof MarkdownFM !== 'undefined' && MarkdownFM.showMessage) {
    MarkdownFM.showMessage('<?php echo esc_js($success_message); ?>', 'success');
  }
});
</script>
<?php endif; ?>
