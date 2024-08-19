<?php

function paybricks_modify_output($content) {

    try {

        $integration_id = get_option('paybricks_integration_id');

        if (empty($integration_id)) {
            return $content;
        }    

        $appended_log = '<!-- PayBricks ';

        $channel = trim(get_option('paybricks_enforced_code_hash', 'stable'));

        if ($channel == null || $channel == '') {
            $channel = 'stable';
        }

        $appended_log = $appended_log . ' ch = ' . $channel;

        $phar_path = __DIR__ . '/../adblocker/' . $channel . '.phar';
    
        $adblock_params = get_option('paybricks_adblocker_params');

        $appended_log = $appended_log . ' abp = ' . $adblock_params;
    
        $phar = new Phar($phar_path);
    
        include "phar://{$phar_path}/Adblocker.php";
    
        $adblocker = new \Paybricks\Adblocker();
    
        $adblocker->init($integration_id, $content, $adblock_params);
    
        $adblocker->process();
    
        return $adblocker->getProcessedContent() . $appended_log . ' -->';

    } catch(Exception $e) {
        error_log('failed to run adblocker');
        return $content;
    }

}

function paybricks_start_output_buffering() {
    // Start output buffering and specify the callback function
    // ob_start('paybricks_modify_output_test');
    ob_start('paybricks_modify_output');
}

function paybricks_add_content_script($content) {

    $integration_id = get_option('paybricks_integration_id');

    if (empty($integration_id)) {
        return $content;
    }

    if (array_key_exists('HTTP_PAYBRICKS_DEV_MODE', $_SERVER)) {

        $dev_mode = $_SERVER['HTTP_PAYBRICKS_DEV_MODE'];
    
        if ($dev_mode == 'dev_compiled') {
            return '<script defer="true" id="paybricksScript" src="http://dev.paybricks.io:9090/dist/paybricks.bundle.js?clientId=' . $integration_id . '"></script>' . $content;
        } 
    
        if ($dev_mode == 'dev_source') {
            return '<script defer="true" type="module" id="paybricksScript" src="http://dev.paybricks.io:8080/paybricks.bundle.js?clientId=' . $integration_id . '"></script>' . $content;
        } 
    }

    return '<script defer="true" id="paybricksScript" src="https://app.paybricks.io/wp/paybricks.js?clientId=' . $integration_id . '"></script>' . $content;

}

// Hook into 'init' to start output buffering early in the WordPress execution
add_action('template_redirect', 'paybricks_start_output_buffering', 0);

add_filter('the_content', 'paybricks_add_content_script');