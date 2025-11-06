<?php
/**
 * Data Validation Template
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get all posts with custom field data and validate them
global $wpdb;

// Try to get from cache first
$cache_key = 'yaml_cf_validation_posts';
$results = wp_cache_get($cache_key, 'yaml-custom-fields');

if (false === $results) {
  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached above with wp_cache_get/set
  $results = $wpdb->get_results(
    "SELECT p.ID, p.post_title, p.post_name, p.post_type, p.post_status
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_yaml_cf_data'
     INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_yaml_cf_imported'
     WHERE p.post_type IN ('page', 'post')
     AND p.post_status IN ('publish', 'draft', 'pending', 'private')
     ORDER BY p.post_type, p.post_title"
  );
  // Cache for 5 minutes
  wp_cache_set($cache_key, $results, 'yaml-custom-fields', 300);
}

$validation_results = [];
$total_posts = count($results);
$posts_with_issues = 0;
$total_missing_attachments = 0;

foreach ($results as $post) {
  $data = get_post_meta($post->ID, '_yaml_cf_data', true);
  if (empty($data)) {
    continue;
  }

  // Get the schema for this post
  $schema = get_post_meta($post->ID, '_yaml_cf_schema', true);

  $missing_attachments = validate_yaml_cf_attachments($data, '', $schema);

  if (!empty($missing_attachments)) {
    $posts_with_issues++;
    $total_missing_attachments += count($missing_attachments);
  }

  $validation_results[] = [
    'post' => $post,
    'missing_attachments' => $missing_attachments
  ];
}

// Helper function to recursively find attachment IDs using schema
function validate_yaml_cf_attachments($data, $path = '', $schema = null) {
  $missing = [];

  if (!is_array($data)) {
    return $missing;
  }

  // If no schema provided, skip validation (can't determine which fields are attachments)
  if (empty($schema)) {
    return $missing;
  }

  foreach ($data as $key => $value) {
    $current_path = $path ? $path . ' > ' . $key : $key;

    // Find the field definition in the schema
    $field_schema = find_field_in_schema($schema, $key);

    if (is_array($value)) {
      // For list fields (arrays), check each item
      if ($field_schema && isset($field_schema['list']) && $field_schema['list']) {
        // This is a list field, validate each item
        foreach ($value as $index => $item) {
          $item_path = $current_path . ' > ' . $index;
          if (is_array($item)) {
            // Get the schema for list items (could be blocks)
            $item_schema = $field_schema;
            if (isset($field_schema['fields'])) {
              $item_schema = $field_schema['fields'];
            } elseif (isset($field_schema['blocks'])) {
              // For block fields, find the matching block type
              if (isset($item['type']) && isset($field_schema['blocks'])) {
                foreach ($field_schema['blocks'] as $block) {
                  if (isset($block['name']) && $block['name'] === $item['type']) {
                    $item_schema = isset($block['fields']) ? $block['fields'] : [];
                    break;
                  }
                }
              }
            }
            $nested_missing = validate_yaml_cf_attachments($item, $item_path, $item_schema);
            $missing = array_merge($missing, $nested_missing);
          }
        }
      } else {
        // Regular nested object, pass the nested schema
        $nested_schema = null;
        if ($field_schema && isset($field_schema['fields'])) {
          $nested_schema = $field_schema['fields'];
        }
        $nested_missing = validate_yaml_cf_attachments($value, $current_path, $nested_schema);
        $missing = array_merge($missing, $nested_missing);
      }
    } elseif ($field_schema && in_array($field_schema['type'], ['image', 'file'], true)) {
      // Only validate if this field is defined as image or file type in schema
      if (is_numeric($value) && intval($value) > 0) {
        $attachment = get_post(intval($value));
        if (!$attachment || $attachment->post_type !== 'attachment') {
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

// Helper function to find a field definition in schema
function find_field_in_schema($schema, $field_name) {
  if (!is_array($schema)) {
    return null;
  }

  foreach ($schema as $field) {
    if (isset($field['name']) && $field['name'] === $field_name) {
      return $field;
    }
  }

  return null;
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
            /* translators: %d: number of posts with issues */
            printf(esc_html__('%d posts have missing attachments. Review the details below.', 'yaml-custom-fields'), absint($posts_with_issues));
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
                        /* translators: %d: number of missing attachments */
                        printf(esc_html__('%d missing attachments', 'yaml-custom-fields'), count($missing));
                        ?>
                      </summary>
                      <ul style="margin: 10px 0 0 20px; list-style: disc;">
                        <?php foreach ($missing as $item): ?>
                          <li>
                            <strong><?php echo esc_html($item['field']); ?>:</strong>
                            <?php
                            /* translators: %d: attachment ID */
                            printf(esc_html__('ID %d (not found)', 'yaml-custom-fields'), absint($item['id']));
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
