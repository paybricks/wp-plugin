<?php
/**
 * Plugin Name: PayBricks
 * Description: Provides an enchanced experience for readers in exchange for small payments.
 * Version: 0.0.35
 * Author: PayBricks Software LLC
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('PAYBRICKS_PLUGIN_DIR', plugin_dir_path(__FILE__));
require_once PAYBRICKS_PLUGIN_DIR . 'includes/functions.php';
require_once PAYBRICKS_PLUGIN_DIR . 'includes/admin-page.php';