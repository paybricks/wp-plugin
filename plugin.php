<?php
/**
 * Plugin Name: PayBricks
 * Description: Provides an enchanced experience for readers using microtransactions.
 * Version: 0.0.9
 * Author: PayBricks Software LLC
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('PAYBRICKS_PLUGIN_DIR', plugin_dir_path(__FILE__));
require_once PAYBRICKS_PLUGIN_DIR . 'includes/functions.php';
require_once PAYBRICKS_PLUGIN_DIR . 'includes/admin-page.php';

register_activation_hook(__FILE__, 'paybricks_adblocker_downloader_activate');
register_deactivation_hook(__FILE__, 'paybricks_adblocker_downloader_deactivate');

function paybricks_adblocker_downloader_activate() {
    if (!wp_next_scheduled('paybricks_adblocker_downloader_cron_job')) {
        wp_schedule_event(time(), 'five_minutes', 'paybricks_adblocker_downloader_cron_job');
    }
}

function paybricks_adblocker_downloader_deactivate() {
    $timestamp = wp_next_scheduled('paybricks_adblocker_downloader_cron_job');
    wp_unschedule_event($timestamp, 'paybricks_adblocker_downloader_cron_job');
}

add_filter('cron_schedules', 'paybricks_adblocker_custom_cron_schedule');
function paybricks_adblocker_custom_cron_schedule($schedules) {
    $schedules['five_minutes'] = array(
        'interval' => 300,
        'display' => __('Every minutes')
    );
    return $schedules;
}

add_action('paybricks_adblocker_downloader_cron_job', 'paybricks_adlocker_download_phar');

function paybricks_adlocker_download_phar() {

    try {

        $channel = trim(get_option('paybricks_enforced_code_hash', 'stable'));

        if ($channel == null || $channel == '') {
            $channel = 'stable';
        }

        paybricks_adlocker_download_phar_for($channel);
    } catch(Exception $e) {
        error_log('failed to download phars');
    }
}

function paybricks_adlocker_download_phar_for($version) {

    $url = 'https://paybricks-wordpress-adblocker.s3.amazonaws.com/' . $version . '.phar';
    // $upload_dir = wp_upload_dir();
    $phar_dir = __DIR__ . '/adblocker';
    
    if (!file_exists($phar_dir)) {
        wp_mkdir_p($phar_dir);
    }

    error_log('about to download to: ' . $phar_dir);
    
    $phar_copy_path = $phar_dir . '/phar.tmp';
    $phar_final_path = $phar_dir . '/' . $version . '.phar';
    
    $response = wp_remote_get($url, array('timeout' => 300));
    
    if (is_wp_error($response)) {
        error_log('PHAR Downloader: Error downloading file - ' . $response->get_error_message());
        return;
    }
    
    if (wp_remote_retrieve_response_code($response) !== 200) {
        error_log('PHAR Downloader: Unexpected response code - ' . wp_remote_retrieve_response_code($response));
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    
    if (file_put_contents($phar_copy_path, $body) !== false) {
        rename($phar_copy_path, $phar_final_path);
        error_log('PHAR Downloader: Successfully downloaded and updated phar');

        try {
            include "phar://{$phar_final_path}/Updater.php";

            $updater = new \Paybricks\Updater();

            $updater->go();

            error_log('successfully ran paybricks update scripts');
        } catch(Exception $e) {
            error_log('failed to run paybricks update scripts');
        }

    } else {
        error_log('PHAR Downloader: Failed to write');
    }
}
