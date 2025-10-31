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
  <div class="yaml-cf-admin-container">
    <div class="yaml-cf-header">
      <div class="yaml-cf-header-content">
        <img src="<?php echo esc_url(YAML_CF_PLUGIN_URL . 'icon-256x256.png'); ?>" alt="YAML Custom Fields" class="yaml-cf-logo" />
        <div class="yaml-cf-header-text">
          <h1><?php echo esc_html($template_name); ?></h1>
          <p class="yaml-cf-tagline"><?php esc_html_e('Edit global data for this partial', 'yaml-custom-fields'); ?></p>
        </div>
      </div>
    </div>

    <div class="yaml-cf-intro">
      <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=yaml-custom-fields')); ?>" class="button">
          <span class="dashicons dashicons-arrow-left-alt2"></span>
          <?php esc_html_e('Back to Templates', 'yaml-custom-fields'); ?>
        </a>
        <?php
        $edit_schema_url = admin_url('admin.php?page=yaml-cf-edit-schema&template=' . urlencode($template));
        ?>
        <a href="<?php echo esc_url($edit_schema_url); ?>" class="button button-secondary" style="margin-left: 10px;">
          <span class="dashicons dashicons-edit"></span>
          <?php esc_html_e('Edit Schema', 'yaml-custom-fields'); ?>
        </a>
      </p>
      <p><strong><?php esc_html_e('Template File:', 'yaml-custom-fields'); ?></strong> <code><?php echo esc_html($template); ?></code></p>
      <p><?php esc_html_e('This data is global and will be used wherever this partial is included in your theme.', 'yaml-custom-fields'); ?></p>
    </div>

    <form id="yaml-cf-partial-form" method="post">
      <?php wp_nonce_field('yaml_cf_save_partial', 'yaml_cf_partial_nonce'); ?>
      <input type="hidden" name="template" value="<?php echo esc_attr($template); ?>" />

      <div class="yaml-cf-fields" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
        <?php
        if (!empty($schema['fields'])) {
          $plugin = YAML_Custom_Fields::get_instance();
          $context = ['type' => 'partial', 'template' => $template];
          $plugin->render_schema_fields($schema['fields'], $template_data, '', $context);
        } else {
          echo '<p>' . esc_html__('No fields defined in schema.', 'yaml-custom-fields') . '</p>';
        }
        ?>
      </div>

      <p class="submit" style="margin-top: 20px;">
        <button type="submit" class="button button-primary button-large">
          <?php esc_html_e('Save Partial Data', 'yaml-custom-fields'); ?>
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

<script>
jQuery(document).ready(function($) {
  let hasUnsavedChanges = false;
  let originalFormData = {};

  // Capture original form state
  function captureFormState() {
    const formData = {};
    $('#yaml-cf-partial-form .yaml-cf-fields').find('input, textarea, select').each(function() {
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
      if (typeof YamlCF !== 'undefined' && YamlCF.showMessage) {
        YamlCF.showMessage('<?php echo esc_js(__('You have unsaved changes', 'yaml-custom-fields')); ?>', 'warning', true);
      }
    } else {
      if (typeof YamlCF !== 'undefined' && YamlCF.hideMessage) {
        YamlCF.hideMessage('warning');
      }
    }
  }

  // Capture initial state after page loads
  setTimeout(function() {
    originalFormData = captureFormState();
  }, 500);

  // Watch for changes
  $('#yaml-cf-partial-form').on('input change', 'input, textarea, select', function() {
    checkForChanges();
  });

  // Warn before leaving page with unsaved changes
  $(window).on('beforeunload', function(e) {
    if (hasUnsavedChanges) {
      const message = '<?php echo esc_js(__('You have unsaved changes. Are you sure you want to leave?', 'yaml-custom-fields')); ?>';
      e.returnValue = message;
      return message;
    }
  });

  // Clear unsaved flag on submit
  $('#yaml-cf-partial-form').on('submit', function() {
    hasUnsavedChanges = false;
  });
});
</script>
