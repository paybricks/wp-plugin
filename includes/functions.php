<?php

// functions.php

function paybricks_validate_ip() {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $ip_filter = get_option('paybricks_ip_filter');

    if (empty($ip_filter)) {
        return true;
    }

    try {
        // Convert the comma-separated string into an array
        $ip_array = explode(",", $ip_filter);
    
        // Trim each element in the array to remove any leading or trailing spaces
        $ip_array = array_map('trim', $ip_array);
    
        // Check if the $ip_address exists in the $ip_array
        return (in_array($ip_address, $ip_array));
    } catch(Exception $ex1) {
        return false;
    }

}

function paybricks_modify_output($content) {

    $appended_log = '<!-- PayBricks focusedBrowsing ';

    $ip_address = $_SERVER['REMOTE_ADDR'];

    $appended_log = $appended_log . 'ip=' . $ip_address . ' ';

    $ip_filter = get_option('paybricks_ip_filter');

    $appended_log = $appended_log . 'ipf=' . $ip_filter . ' ';

    if (paybricks_validate_ip()) {
        try {
            wp_cache_delete( 'paybricks_integration_id', 'options' );
            wp_cache_delete( 'paybricks_enforced_code_hash', 'options' );
        
            $integration_id = get_option('paybricks_integration_id');
            $enforced_hash = get_option('paybricks_enforced_code_hash');
        
            if (empty($integration_id)) {
                // Integration ID not set, do nothing
                return $content;
            }
        
            $appended_log = $appended_log . ' iID=' . $integration_id . ' ';
        
            $file_name = $integration_id;
        
            if (!empty($enforced_hash)) {
                $enforced_hash = trim($enforced_hash);
                $file_name = $enforced_hash;
                $appended_log = $appended_log . ' eh=' . $enforced_hash . ' ';
            }
        
            $appended_log = $appended_log . ' fn=' . $file_name . ' ';
        
            $code_url = 'https://app.paybricks.io/php/' . $file_name . '.php';
            $code = file_get_contents($code_url);
            
            if ($code !== false) {
        
                $code_hash = hash('sha256', $code);
    
                $appended_log = $appended_log . ' ch=' . $code_hash . ' ';
        
                if (empty($enforced_hash) || $enforced_hash === $code_hash) {
        
                    $appended_log = $appended_log . ' exec=true '; 
        
                    // $code should modify $content in-place
                    try {
                        eval($code);
                    } catch(Exception $e) {
                        $appended_log = $appended_log . ' evalErr ';
                    }
                    
                }
            } else {
                $appended_log = $appended_log . ' no-code ';
            }
    
        } catch(Exception $e1) {
            $appended_log = $appended_log . ' err ';
        }
    }

    
    $appended_log = $appended_log . ' -->';

    $content = $content . $appended_log;

    return $content;
}

function paybricks_modify_output_test($content) {
    // Used for sanity testing, that we can add a comment at the end of the html
    return $content . '<!-- PayBricks -->';
}

function paybricks_start_output_buffering() {
    // Start output buffering and specify the callback function
    // ob_start('paybricks_modify_output_test');
    ob_start('paybricks_modify_output');
}

function paybricks_add_content_script($content) {

    if (paybricks_validate_ip()) {

        $integration_id = get_option('paybricks_integration_id');
    
        if (empty($integration_id)) {
            return $content;
        }
    
        return '<script defer="true" id="paybricksScript" src="https://app.paybricks.io/wp/paybricks.js?clientId=' . $integration_id . '"></script>' . $content;
    } else {
        return $content;
    }

}

// Hook into 'init' to start output buffering early in the WordPress execution
add_action('template_redirect', 'paybricks_start_output_buffering', 0);

add_filter('the_content', 'paybricks_add_content_script');

function paybricks_updated_option_hook($option_name, $old_value, $new_value) {
    error_log('updated option ' . $option_name);
}

add_action('updated_option', 'paybricks_updated_option_hook', 10, 3);
