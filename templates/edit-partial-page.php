<?php
/**
 * Edit Partial Data Page Template
 * File: templates/edit-partial-page.php
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
          <p class="markdown-fm-tagline"><?php esc_html_e('Edit global data for this partial', 'markdown-fm'); ?></p>
        </div>
      </div>
    </div>

    <div class="markdown-fm-intro">
      <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=markdown-fm')); ?>" class="button">
          <span class="dashicons dashicons-arrow-left-alt2"></span>
          <?php esc_html_e('Back to Templates', 'markdown-fm'); ?>
        </a>
      </p>
      <p><strong><?php esc_html_e('Template File:', 'markdown-fm'); ?></strong> <code><?php echo esc_html($template); ?></code></p>
      <p><?php esc_html_e('This data is global and will be used wherever this partial is included in your theme.', 'markdown-fm'); ?></p>
    </div>

    <form id="markdown-fm-partial-form" method="post">
      <?php wp_nonce_field('markdown_fm_save_partial', 'markdown_fm_partial_nonce'); ?>
      <input type="hidden" name="template" value="<?php echo esc_attr($template); ?>" />

      <div class="markdown-fm-fields" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
        <?php
        if (!empty($schema['fields'])) {
          $plugin = Markdown_FM::get_instance();
          $plugin->render_schema_fields($schema['fields'], $template_data);
        } else {
          echo '<p>' . esc_html__('No fields defined in schema.', 'markdown-fm') . '</p>';
        }
        ?>
      </div>

      <p class="submit" style="margin-top: 20px;">
        <button type="submit" class="button button-primary button-large">
          <?php esc_html_e('Save Partial Data', 'markdown-fm'); ?>
        </button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=markdown-fm')); ?>" class="button button-large">
          <?php esc_html_e('Cancel', 'markdown-fm'); ?>
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

<script>
jQuery(document).ready(function($) {
  let hasUnsavedChanges = false;
  let originalFormData = {};

  // Capture original form state
  function captureFormState() {
    const formData = {};
    $('#markdown-fm-partial-form .markdown-fm-fields').find('input, textarea, select').each(function() {
      const $field = $(this);
      const name = $field.attr('name');
      if (!name) return;

      if ($field.attr('type') === 'checkbox') {
        formData[name] = $field.is(':checked');
      } else if ($field.is('select') && $field.prop('multiple')) {
        formData[name] = JSON.stringify($field.val() || []);
      } else {
        formData[name] = $field.val();
      }
    });
    return formData;
  }

  // Compare current state with original
  function checkForChanges() {
    const currentData = captureFormState();
    const changed = JSON.stringify(originalFormData) !== JSON.stringify(currentData);

    if (changed !== hasUnsavedChanges) {
      hasUnsavedChanges = changed;
      toggleUnsavedIndicator(changed);
    }
  }

  // Show/hide unsaved changes indicator
  function toggleUnsavedIndicator(show) {
    if (show) {
      if (typeof MarkdownFM !== 'undefined' && MarkdownFM.showMessage) {
        MarkdownFM.showMessage('<?php echo esc_js(__('You have unsaved changes', 'markdown-fm')); ?>', 'warning', true);
      }
    } else {
      if (typeof MarkdownFM !== 'undefined' && MarkdownFM.hideMessage) {
        MarkdownFM.hideMessage('warning');
      }
    }
  }

  // Capture initial state after page loads
  setTimeout(function() {
    originalFormData = captureFormState();
  }, 500);

  // Watch for changes
  $('#markdown-fm-partial-form').on('input change', 'input, textarea, select', function() {
    checkForChanges();
  });

  // Warn before leaving page with unsaved changes
  $(window).on('beforeunload', function(e) {
    if (hasUnsavedChanges) {
      const message = '<?php echo esc_js(__('You have unsaved changes. Are you sure you want to leave?', 'markdown-fm')); ?>';
      e.returnValue = message;
      return message;
    }
  });

  // Clear unsaved flag on submit
  $('#markdown-fm-partial-form').on('submit', function() {
    hasUnsavedChanges = false;
  });
});
</script>
