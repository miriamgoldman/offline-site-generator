<?php
/**
 * Plugin Name: Offline Site Generator
 * Plugin URI:  https://www.kanopi.com
 * Description: Using the power of a Static Site generator, create a version of your site to be accessible online. Includes multiple deployment methods such as ZIP file, deployment to Dropbox, and deployment to S3.
 * Version:     1.0.0
 * Author:      Miriam Goldman
 * Author URI:  https://www.kanopi.com
 * Text Domain: offline-site-generator
 *
 * @package     OfflineSiteGenerator
 */

ini_set('max_execution_time', 600);

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

if ( file_exists( PLUGIN_PATH . 'vendor/autoload.php' ) ) {
    require_once PLUGIN_PATH . 'vendor/autoload.php';
}

OfflineSiteGenerator\Controller::init( __FILE__ );

function plugin_action_links( $links ) {
    $settings_link =
        '<a href="admin.php?page=offline-site-generator">' .
        __( 'Settings', 'static-html-output-plugin' ) .
        '</a>';
    array_unshift( $links, $settings_link );

    return $links;
}

add_filter(
    'plugin_action_links_' .
    plugin_basename( __FILE__ ),
    'plugin_action_links'
);

function offlineSiteGenerator_deregister_scripts() {
    wp_deregister_script( 'wp-embed' );
    wp_deregister_script( 'comment-reply' );
}

add_action( 'wp_footer', 'offlineSiteGenerator_deregister_scripts' );

remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

if ( defined( 'WP_CLI' ) ) {
    WP_CLI::add_command( 'offline-site-generator', 'OfflineSiteGenerator\CLI' );
    WP_CLI::add_command( 'offline-site-generator options', [ 'OfflineSiteGenerator\CLI', 'options' ] );
}

