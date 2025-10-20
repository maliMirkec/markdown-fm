<?php
/**
 * Plugin Name: Markdown FM
 * Description: Add custom fields to your templates using Markdown Frontmatter.
 * Version: 0.1
 * Author: Silvestar Bistović <me@silvestar.codes>
 * Author URI:  https://www.silvestar.codes/
 */

if (!defined('ABSPATH')) exit;

// Autoload vendor
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
  require_once __DIR__ . '/vendor/autoload.php';
}

// Include plugin classes
require_once plugin_dir_path(__FILE__) . 'includes/class-plugin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-field-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-meta-box.php';

// Initialize plugin
\MarkdownFM\Plugin::init();
\MarkdownFM\Field_Manager::init();
\MarkdownFM\Admin_Page::init();
\MarkdownFM\Meta_Box::init();
