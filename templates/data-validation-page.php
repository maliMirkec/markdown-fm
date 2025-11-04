<?php
/**
 * Data Validation Template
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get all posts with custom field data and validate them
global $wpdb;
$results = $wpdb->get_results(
  "SELECT p.ID, p.post_title, p.post_name, p.post_type, p.post_status
   FROM {$wpdb->posts} p
   INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_yaml_cf_data'
   WHERE p.post_type IN ('page', 'post')
   AND p.post_status IN ('publish', 'draft', 'pending', 'private')
   ORDER BY p.post_type, p.post_title"
);

$validation_results = [];
$total_posts = count($results);
$posts_with_issues = 0;
$total_missing_attachments = 0;

foreach ($results as $post) {
  $data = get_post_meta($post->ID, '_yaml_cf_data', true);
  if (empty($data)) {
    continue;
  }

  $missing_attachments = validate_yaml_cf_attachments($data);

  if (!empty($missing_attachments)) {
    $posts_with_issues++;
    $total_missing_attachments += count($missing_attachments);
  }

  $validation_results[] = [
    'post' => $post,
    'missing_attachments' => $missing_attachments
  ];
}

// Helper function to recursively find attachment IDs
function validate_yaml_cf_attachments($data, $path = '') {
  $missing = [];

  if (!is_array($data)) {
    return $missing;
  }

  foreach ($data as $key => $value) {
    $current_path = $path ? $path . ' > ' . $key : $key;

    if (is_array($value)) {
      // Recursively check nested arrays
      $nested_missing = validate_yaml_cf_attachments($value, $current_path);
      $missing = array_merge($missing, $nested_missing);
    } elseif (is_numeric($value) && intval($value) > 0) {
      // Check if this is an attachment
      $attachment = get_post(intval($value));
      if (!$attachment || $attachment->post_type !== 'attachment') {
        // Could be a regular post ID, so let's verify it exists
        if (!$attachment) {
          $missing[] = [
            'field' => $current_path,
            'id' => intval($value)
          ];
        }
      }
    }
  }

  return $missing;
}
?>

<div class="wrap">
  <h1><?php esc_html_e('Data Validation', 'yaml-custom-fields'); ?></h1>

  <div class="yaml-cf-validation-container">
    <!-- Summary Card -->
    <div class="card" style="max-width: 100%; margin-top: 20px;">
      <h2><?php esc_html_e('Validation Summary', 'yaml-custom-fields'); ?></h2>

      <div class="yaml-cf-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
        <div class="yaml-cf-stat-box" style="padding: 20px; background: #f0f0f1; border-radius: 4px; text-align: center;">
          <div style="font-size: 32px; font-weight: bold; color: #2271b1;"><?php echo esc_html($total_posts); ?></div>
          <div style="margin-top: 5px; color: #646970;"><?php esc_html_e('Total Posts', 'yaml-custom-fields'); ?></div>
        </div>

        <div class="yaml-cf-stat-box" style="padding: 20px; background: #f0f0f1; border-radius: 4px; text-align: center;">
          <div style="font-size: 32px; font-weight: bold; color: <?php echo $posts_with_issues > 0 ? '#d63638' : '#00a32a'; ?>;">
            <?php echo esc_html($posts_with_issues); ?>
          </div>
          <div style="margin-top: 5px; color: #646970;"><?php esc_html_e('Posts with Issues', 'yaml-custom-fields'); ?></div>
        </div>

        <div class="yaml-cf-stat-box" style="padding: 20px; background: #f0f0f1; border-radius: 4px; text-align: center;">
          <div style="font-size: 32px; font-weight: bold; color: <?php echo $total_missing_attachments > 0 ? '#d63638' : '#00a32a'; ?>;">
            <?php echo esc_html($total_missing_attachments); ?>
          </div>
          <div style="margin-top: 5px; color: #646970;"><?php esc_html_e('Missing Attachments', 'yaml-custom-fields'); ?></div>
        </div>

        <div class="yaml-cf-stat-box" style="padding: 20px; background: #f0f0f1; border-radius: 4px; text-align: center;">
          <div style="font-size: 32px; font-weight: bold; color: #00a32a;">
            <?php echo esc_html($total_posts - $posts_with_issues); ?>
          </div>
          <div style="margin-top: 5px; color: #646970;"><?php esc_html_e('Healthy Posts', 'yaml-custom-fields'); ?></div>
        </div>
      </div>

      <?php if ($posts_with_issues === 0): ?>
        <div class="notice notice-success inline" style="margin-top: 20px;">
          <p><strong><?php esc_html_e('All data is valid!', 'yaml-custom-fields'); ?></strong> <?php esc_html_e('No missing attachments found.', 'yaml-custom-fields'); ?></p>
        </div>
      <?php else: ?>
        <div class="notice notice-warning inline" style="margin-top: 20px;">
          <p>
            <strong><?php esc_html_e('Issues detected!', 'yaml-custom-fields'); ?></strong>
            <?php
            printf(
              esc_html__('%d posts have missing attachments. Review the details below.', 'yaml-custom-fields'),
              $posts_with_issues
            );
            ?>
          </p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Filter Options -->
    <div class="card" style="max-width: 100%; margin-top: 20px;">
      <h3><?php esc_html_e('Filter', 'yaml-custom-fields'); ?></h3>
      <div style="margin-bottom: 15px;">
        <label style="display: inline-flex; align-items: center; margin-right: 20px;">
          <input type="radio" name="filter" value="all" checked style="margin-right: 5px;">
          <?php esc_html_e('Show All', 'yaml-custom-fields'); ?>
        </label>
        <label style="display: inline-flex; align-items: center; margin-right: 20px;">
          <input type="radio" name="filter" value="issues" style="margin-right: 5px;">
          <?php esc_html_e('Only Issues', 'yaml-custom-fields'); ?>
        </label>
        <label style="display: inline-flex; align-items: center;">
          <input type="radio" name="filter" value="healthy" style="margin-right: 5px;">
          <?php esc_html_e('Only Healthy', 'yaml-custom-fields'); ?>
        </label>
      </div>
    </div>

    <!-- Validation Results -->
    <div class="card" style="max-width: 100%; margin-top: 20px;">
      <h2><?php esc_html_e('Validation Details', 'yaml-custom-fields'); ?></h2>

      <?php if (empty($validation_results)): ?>
        <p><?php esc_html_e('No pages or posts with custom field data found.', 'yaml-custom-fields'); ?></p>
      <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th><?php esc_html_e('Title', 'yaml-custom-fields'); ?></th>
              <th><?php esc_html_e('Type', 'yaml-custom-fields'); ?></th>
              <th><?php esc_html_e('Status', 'yaml-custom-fields'); ?></th>
              <th><?php esc_html_e('Validation Status', 'yaml-custom-fields'); ?></th>
              <th><?php esc_html_e('Issues', 'yaml-custom-fields'); ?></th>
              <th><?php esc_html_e('Actions', 'yaml-custom-fields'); ?></th>
            </tr>
          </thead>
          <tbody id="yaml-cf-validation-tbody">
            <?php foreach ($validation_results as $result): ?>
              <?php
              $post = $result['post'];
              $missing = $result['missing_attachments'];
              $has_issues = !empty($missing);
              $data_status = $has_issues ? 'issues' : 'healthy';
              ?>
              <tr class="yaml-cf-validation-row" data-status="<?php echo esc_attr($data_status); ?>">
                <td>
                  <strong>
                    <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" target="_blank">
                      <?php echo esc_html($post->post_title); ?>
                    </a>
                  </strong>
                  <br>
                  <small style="color: #646970;"><?php echo esc_html($post->post_name); ?></small>
                </td>
                <td><?php echo esc_html($post->post_type); ?></td>
                <td>
                  <span class="post-state"><?php echo esc_html($post->post_status); ?></span>
                </td>
                <td>
                  <?php if ($has_issues): ?>
                    <span style="color: #d63638; font-weight: bold;">⚠ <?php esc_html_e('Issues Found', 'yaml-custom-fields'); ?></span>
                  <?php else: ?>
                    <span style="color: #00a32a; font-weight: bold;">✓ <?php esc_html_e('Valid', 'yaml-custom-fields'); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($has_issues): ?>
                    <details>
                      <summary style="cursor: pointer; color: #d63638;">
                        <?php
                        printf(
                          esc_html__('%d missing attachments', 'yaml-custom-fields'),
                          count($missing)
                        );
                        ?>
                      </summary>
                      <ul style="margin: 10px 0 0 20px; list-style: disc;">
                        <?php foreach ($missing as $item): ?>
                          <li>
                            <strong><?php echo esc_html($item['field']); ?>:</strong>
                            <?php
                            printf(
                              esc_html__('ID %d (not found)', 'yaml-custom-fields'),
                              $item['id']
                            );
                            ?>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </details>
                  <?php else: ?>
                    <span style="color: #646970;">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" class="button button-small" target="_blank">
                    <?php esc_html_e('Edit', 'yaml-custom-fields'); ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="card" style="max-width: 100%; margin-top: 20px;">
      <h3><?php esc_html_e('What to do about missing attachments?', 'yaml-custom-fields'); ?></h3>
      <ul style="margin: 10px 0 0 20px; list-style: disc;">
        <li><?php esc_html_e('Missing attachments may occur after importing data from another site', 'yaml-custom-fields'); ?></li>
        <li><?php esc_html_e('Edit each affected post and re-upload the images/files', 'yaml-custom-fields'); ?></li>
        <li><?php esc_html_e('Alternatively, import the media library from the source site first', 'yaml-custom-fields'); ?></li>
        <li><?php esc_html_e('You can also manually update attachment IDs if you know the mapping', 'yaml-custom-fields'); ?></li>
      </ul>
    </div>
  </div>
</div>

<script>
jQuery(document).ready(function($) {
  // Filter functionality
  $('input[name="filter"]').on('change', function() {
    const filter = $(this).val();
    const $rows = $('.yaml-cf-validation-row');

    if (filter === 'all') {
      $rows.show();
    } else if (filter === 'issues') {
      $rows.hide();
      $rows.filter('[data-status="issues"]').show();
    } else if (filter === 'healthy') {
      $rows.hide();
      $rows.filter('[data-status="healthy"]').show();
    }
  });
});
</script>
