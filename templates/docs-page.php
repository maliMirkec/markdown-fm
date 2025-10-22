<?php
/**
 * Documentation Page Template
 * File: templates/docs-page.php
 */

if (!defined('ABSPATH')) {
  exit;
}

// Read the README.md file
$readme_file = MARKDOWN_FM_PLUGIN_DIR . 'README.md';
$readme_content = '';

if (file_exists($readme_file)) {
  $readme_content = file_get_contents($readme_file);

  // Convert markdown to HTML (basic conversion)
  // Remove the first line (title)
  $readme_content = preg_replace('/^# .*\n/', '', $readme_content);

  // Convert headings
  $readme_content = preg_replace('/^### (.*)/m', '<h3>$1</h3>', $readme_content);
  $readme_content = preg_replace('/^## (.*)/m', '<h2>$1</h2>', $readme_content);

  // Convert bold
  $readme_content = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $readme_content);

  // Convert links
  $readme_content = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank">$1</a>', $readme_content);

  // Convert code blocks
  $readme_content = preg_replace('/```(\w+)?\n(.*?)\n```/s', '<pre><code>$2</code></pre>', $readme_content);

  // Convert inline code
  $readme_content = preg_replace('/`([^`]+)`/', '<code>$1</code>', $readme_content);

  // Convert bullet lists
  $readme_content = preg_replace('/^- (.*)/m', '<li>$1</li>', $readme_content);
  $readme_content = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $readme_content);

  // Convert paragraphs
  $readme_content = preg_replace('/\n\n/', '</p><p>', $readme_content);
  $readme_content = '<p>' . $readme_content . '</p>';

  // Clean up empty paragraphs
  $readme_content = preg_replace('/<p>\s*<\/p>/', '', $readme_content);
  $readme_content = preg_replace('/<p>(\s*<[hul])/i', '$1', $readme_content);
  $readme_content = preg_replace('/(<\/[hul]>\s*)<\/p>/i', '$1', $readme_content);
}
?>

<div class="wrap">
  <div class="markdown-fm-admin-container">
    <div class="markdown-fm-header">
      <div class="markdown-fm-header-content">
        <img src="<?php echo esc_url(MARKDOWN_FM_PLUGIN_URL . 'assets/logo.png'); ?>" alt="Markdown FM" class="markdown-fm-logo" />
        <div class="markdown-fm-header-text">
          <h1><?php _e('Documentation', 'markdown-fm'); ?></h1>
          <p class="markdown-fm-tagline"><?php _e('Complete guide to using Markdown FM', 'markdown-fm'); ?></p>
        </div>
      </div>
    </div>

    <div class="markdown-fm-docs-content">
      <?php if (!empty($readme_content)) : ?>
        <?php echo wp_kses_post($readme_content); ?>
      <?php else : ?>
        <p><?php _e('Documentation could not be loaded.', 'markdown-fm'); ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>
