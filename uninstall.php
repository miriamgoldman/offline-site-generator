<?php

// exit uninstall if not called by WP
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

global $wpdb;

$tables_to_drop = [
    'offlineSiteGenerator_core_options',
    'offlineSiteGenerator_crawl_cache',
    'offlineSiteGenerator_deploy_cache',
    'offlineSiteGenerator_jobs',
    'offlineSiteGenerator_log',
    'offlineSiteGenerator_urls',
];

foreach ( $tables_to_drop as $table ) {
    $table_name = $wpdb->prefix . $table;

    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
}

// TODO: delete crawl_cache, processed_site and zip if exist

