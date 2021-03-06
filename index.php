<?php
/*
 Plugin Name: Migrate CSS From Simple Custom CSS
 Plugin URI: https://github.com/wsizoo/migrate-simple-custom-css
 Description: Migrates Custom CSS from Simple Custom CSS Into Core (4.7+) Custom CSS Post Type. Multisite Compatible.
 Version: 1.0
 Author: wsizoo
 Author URI: https://github.com/wsizoo
 */


// Add Admin Menu Under Appearance
 if ( is_multisite() ) {
    add_action('network_admin_menu','msccss_add_menu_item');
} else {
    add_action('admin_menu','msccss_add_menu_item');
}
function msccss_add_menu_item() {
    add_menu_page('themes.php', 'Migrate Simple Custom CSS', 'Migrate Simple Custom CSS', 'administrator', 'msccss_migrate_simple_custom_css', 'dashicons-randomize' );
}

// Display Page
function msccss_migrate_simple_custom_css() {
    echo'<h2>Migrate CSS from Custom Simple CSS</h2>';
    // Handles the Single Site post request
    if(!empty($_POST['migration_acceptance_single']) && check_admin_referer('migrate_custom_css','migrate_custom_css')) {
        echo'<p>Processing...</p>';
        msccss_migrate_css();
        echo '<h2>Migration Complete!</h2>';   
    }
    // Handles the Multisite post request
    else if(!empty($_POST['migration_acceptance_multi']) && check_admin_referer('migrate_custom_css','migrate_custom_css')) {
        echo'<p>Processing...</p>';
        // Get list of all sites without limit
        $results = get_sites(array('number' => null));
        if($results){
            foreach($results AS $subsite){
                msccss_migrate_simple_custom_css_multi(get_object_vars($subsite)["blog_id"]);
             }
        }
        echo '<h2>Multisite Migration Complete!</h2>';  
    }
    // Handles default page
    else {
        echo'<p><form action="" method="post">';
        echo wp_nonce_field('migrate_custom_css','migrate_custom_css');
        echo '<p>Please choose your Wordpress configuration Type.</p>';
        echo '<br/>';
        echo'<input type="submit" class="button-primary" value="Multisite Migration" name="migration_acceptance_multi"/> ';
        echo'<input type="submit" class="button-primary" value="Single Site Migration" name="migration_acceptance_single"/></form></p>'; 
        echo '<hr></hr>';
        if ( is_multisite() ) {
            echo '<b>Multisite Blogs List:</b> ';
            // Get list of all sites without limit
            $multsite_ids = get_sites(array('number' => null));
                if($multsite_ids){
                    foreach($multsite_ids AS $subsite_id){
                        echo get_object_vars($subsite_id)["blog_id"] . ', ';
                    }
                }
        }
        echo '<hr></hr>';
        echo '<br/><br/>';
    }     
} // End msccss_migrate_simple_custom_css


// Handles Multsite Configuration
function msccss_migrate_simple_custom_css_multi( $blog_id ) {
    if ( is_multisite() ){
        // Sets blog to designated subsite
        switch_to_blog( $blog_id );
        msccss_migrate_css();
        echo 'CSS Migrated For Blog: ' . $blog_id . '<br/>' ;
        // Returns to root blog
        restore_current_blog();
    }
    else {
        echo 'WARNING! NOT A MULTISITE INSTALL.';
    }
} // End msccss_migrate_simple_custom_css_multi

function msccss_migrate_css() {
    // Check for Admin user permissions
    if(current_user_can('manage_options')) {
        // Check for 4.7 compatibility
        if ( function_exists( 'wp_update_custom_css_post' ) ) {
            // Migrate any existing theme CSS to the core option added in WordPress 4.7
            // Grab Simple Custom CSS 
            $raw_css = get_option( 'sccss_settings' );
            // Extract CSS Content
            $css = isset( $raw_css['sccss-content'] ) && ! empty( $raw_css['sccss-content'] ) ? $raw_css['sccss-content'] : __( '/* Enter Your Custom CSS Here */', 'simple-custom-css' );
            // Remove default header that may exist in the original CSS
            $css = str_replace('/* Enter Your Custom CSS Here */', '', $css);
            if ( $css ) {
                // Preserve any CSS already added to the core option
                $core_css = wp_get_custom_css();
                // Concatinate existing CSS with Simple Custom CSS Data
                $return = wp_update_custom_css_post( $core_css . "\n\n" . $css );
                if ( ! is_wp_error( $return ) ) {
                    // Remove the old SCCSS, so that the CSS is stored in only one place moving forward
                    // Disabled by default for your saftey. Enable at your own risk
                    // delete_option( 'sccss_settings' );
                }
             }
        }
    }
} // End msccss_migrate_css

?>