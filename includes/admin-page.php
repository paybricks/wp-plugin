<?php
// admin-page.php

function paybricks_settings_page() {
    ?>
    <div class="wrap">
        <h1>PayBricks Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('paybricks_options'); ?>
            <?php do_settings_sections('paybricks'); ?>
            <input type="submit" name="submit" class="button button-primary" value="Save">
        </form>
    </div>
    <?php
}

function paybricks_register_settings() {

    // List of IPs that will have PayBricks enabled when they visit the site. Leave empty for no filtering
    register_setting('paybricks_options', 'paybricks_ip_filter');

    // This controls which PHP code file will be pulled from PayBricks' server to integrate PayBricks into this WP site
    register_setting('paybricks_options', 'paybricks_integration_id');

    register_setting('paybricks_options', 'paybricks_enforced_code_hash', array('default' => 'stable'));    

    register_setting('paybricks_options', 'paybricks_adblocker_params');    

    add_settings_section('paybricks_section', 'Integration Settings', '', 'paybricks');
    add_settings_field('integration_id', 'Integration ID', 'paybricks_integration_id_callback', 'paybricks', 'paybricks_section');
    add_settings_field('enforced_code_hash', 'Release channel', 'paybricks_enforced_code_hash_callback', 'paybricks', 'paybricks_section');
    // add_settings_field('ip_filter', 'IP filter (comma-seperated)', 'paybricks_ip_filter_callback', 'paybricks', 'paybricks_section');
    add_settings_field('adblocker_params', 'Extra custom params (advanced only)', 'paybricks_adblocker_params_callback', 'paybricks', 'paybricks_section');
}

function paybricks_adblocker_params_callback() {
    $value = get_option('paybricks_adblocker_params');
    echo '<input type="text" name="paybricks_adblocker_params" value="' . esc_attr($value) . '">';
}

function paybricks_ip_filter_callback() {
    $value = get_option('paybricks_ip_filter');
    echo '<input type="text" name="paybricks_ip_filter" value="' . esc_attr($value) . '">';
}

function paybricks_integration_id_callback() {
    $value = get_option('paybricks_integration_id');
    echo '<input type="text" name="paybricks_integration_id" value="' . esc_attr($value) . '">';
}

function paybricks_enforced_code_hash_callback() {
    $value = get_option('paybricks_enforced_code_hash');
    echo '<input type="text" name="paybricks_enforced_code_hash" value="' . esc_attr($value) . '">';
}

add_action('admin_menu', function () {
    add_menu_page('PayBricks', 'PayBricks', 'manage_options', 'paybricks', 'paybricks_settings_page');
});

add_action('admin_init', 'paybricks_register_settings');

