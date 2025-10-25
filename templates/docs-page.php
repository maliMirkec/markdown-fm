<?php
/**
 * Documentation Page Template
 * File: templates/docs-page.php
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
          <h1><?php esc_html_e('Documentation', 'markdown-fm'); ?></h1>
          <p class="markdown-fm-tagline"><?php esc_html_e('Complete guide to using Markdown FM', 'markdown-fm'); ?></p>
        </div>
      </div>
    </div>

    <div class="markdown-fm-docs-content">
      <?php include MARKDOWN_FM_PLUGIN_DIR . 'templates/docs-content.php'; ?>
    </div>
  </div>
</div>
