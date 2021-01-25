<?php
/*
    WordPressAdmin

    OfflineSiteGenerator's interface to WordPress Admin functions

    Used for registering hooks, Admin UI components, ...
*/

namespace OfflineSiteGenerator;

class WordPressAdmin {

    /**
     * WordPressAdmin constructor
     */
    public function __construct() {

    }

    /**
     * Register hooks for WordPress and OfflineSiteGenerator actions
     *
     * @param string $bootstrap_file main plugin filepath
     */
    public static function registerHooks( string $bootstrap_file ) : void {
        register_activation_hook(
            $bootstrap_file,
            [ 'OfflineSiteGenerator\Controller', 'activate' ]
        );

        register_deactivation_hook(
            $bootstrap_file,
            [ 'OfflineSiteGenerator\Controller', 'deactivate' ]
        );

        add_filter(
            'offlineSiteGenerator_list_redirects',
            [ 'OfflineSiteGenerator\CrawlCache', 'offlineSiteGenerator_list_redirects' ]
        );

        add_filter(
            'cron_request',
            [ 'OfflineSiteGenerator\WPCron', 'offlineSiteGenerator_cron_with_http_basic_auth' ]
        );

        add_action(
            'wp_ajax_offlineSiteGeneratorRun',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorRun' ]
        );
    
        add_action(
            'wp_ajax_nopriv_offlineSiteGeneratorRun',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorRun' ]
        );

        add_action(
            'wp_ajax_offlineSiteGeneratorPollLog',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorPollLog' ]
        );

        add_action(
            'wp_ajax_nopriv_offlineSiteGeneratorPollLog',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorPollLog' ]
        );

        add_action(
            'wp_ajax_offlineSiteGenerator_ui_save_options',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorUISaveOptions' ]
        );

        add_action(
            'wp_ajax_nopriv_offlineSiteGenerator_ui_save_options',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorUISaveOptions' ]
        );

        add_action(
            'offlineSiteGenerator_register_addon',
            [ 'OfflineSiteGenerator\Addons', 'registerAddon' ],
            10,
            5
        );

        add_action(
            'offlineSiteGenerator_post_deploy_trigger',
            [ 'OfflineSiteGenerator\Controller', 'emailDeployNotification' ],
            10,
            0
        );

        add_action(
            'offlineSiteGenerator_post_deploy_trigger',
            [ 'OfflineSiteGenerator\Controller', 'webhookDeployNotification' ],
            10,
            0
        );

        add_action(
            'admin_post_offlineSiteGenerator_post_processed_site_delete',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorPostProcessedSiteDelete' ],
            10,
            0
        );

        add_action(
            'admin_post_offlineSiteGenerator_post_processed_site_show',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorPostProcessedSiteShow' ],
            10,
            0
        );

        add_action(
            'wp_ajax_offlineSiteGenerator_log_delete',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorLogDelete' ]
        );

        add_action(
            'wp_ajax_nopriv_offlineSiteGenerator_log_delete',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorLogDelete' ]
        );

        add_action(
            'wp_ajax_offlineSiteGenerator_delete_all_caches',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorDeleteAllCaches' ]
        );

        add_action(
            'wp_ajax_nopriv_offlineSiteGenerator_delete_all_caches',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorDeleteAllCaches' ]
        );

        add_action(
            'admin_post_offlineSiteGenerator_delete_jobs_queue',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorDeleteJobsQueue' ],
            10,
            0
        );

        add_action(
            'admin_post_offlineSiteGeneratorProcessJobsQueue',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorProcessJobsQueue' ],
            10,
            0
        );

        add_action(
            'admin_post_offlineSiteGenerator_crawl_queue_delete',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorCrawlQueueDelete' ],
            10,
            0
        );

        add_action(
            'admin_post_offlineSiteGenerator_crawl_queue_show',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorCrawlQueueShow' ],
            10,
            0
        );

        add_action(
            'admin_post_offlineSiteGenerator_deploy_cache_delete',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorDeployCacheDelete' ],
            10,
            0
        );

        add_action(
            'admin_post_offlineSiteGenerator_deploy_cache_show',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorDeployCacheShow' ],
            10,
            0
        );

        add_action(
            'admin_post_offlineSiteGenerator_crawl_cache_delete',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorCrawlCacheDelete' ],
            10,
            0
        );

        add_action(
            'admin_post_offlineSiteGenerator_crawl_cache_show',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorCrawlCacheShow' ],
            10,
            0
        );

        add_action(
            'admin_post_offlineSiteGenerator_static_site_delete',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorStaticSiteDelete' ],
            10,
            0
        );

        add_action(
            'admin_post_offlineSiteGenerator_static_site_show',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorStaticSiteShow' ],
            10,
            0
        );

        add_action(
            'admin_post_offlineSiteGenerator_ui_save_job_options',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorUISaveJobOptions' ],
            10,
            0
        );

        add_action(
            'admin_post_offlineSiteGenerator_manually_enqueue_jobs',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorManuallyEnqueueJobs' ],
            10,
            0
        );

        add_action(
            'wp_ajax_offlineSiteGeneratorToggleAddon',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorToggleAddon' ],
            10,
            0
        );

        add_action(
            'wp_ajax_nopriv_offlineSiteGeneratorToggleAddon',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorToggleAddon' ],
            10,
            0
        );

        add_action(
            'offlineSiteGenerator_process_queue',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorProcessQueue' ],
            10,
            0
        );

        add_action(
            'offlineSiteGenerator_headless_hook',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorHeadless' ],
            10,
            0
        );

        add_action(
            'save_post',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorSavePostHandler' ],
            0
        );

        add_action(
            'trashed_post',
            [ 'OfflineSiteGenerator\Controller', 'offlineSiteGeneratorTrashedPostHandler' ],
            0
        );

        /*
         * Register actions for when we should invalidate cache for
         * a URL(s) or whole site
         *
         */
        $single_url_invalidation_events = [
            'save_post',
            'deleted_post',
        ];

        $full_site_invalidation_events = [
            'switch_theme',
        ];

        foreach ( $single_url_invalidation_events as $invalidation_events ) {
            add_action(
                $invalidation_events,
                [ 'OfflineSiteGenerator\Controller', 'invalidateSingleURLCache' ],
                10,
                2
            );
        }
    }

    /**
     * Add OfflineSiteGenerator elements to WordPress Admin UI
     */
    public static function addAdminUIElements() : void {
        if ( is_admin() ) {
            add_action(
                'admin_menu',
                [ 'OfflineSiteGenerator\Controller', 'registerOptionsPage' ]
            );
            add_filter( 'custom_menu_order', '__return_true' );
            add_filter( 'menu_order', [ 'OfflineSiteGenerator\Controller', 'setMenuOrder' ] );
        }
    }
}

