<?php

namespace OfflineSiteGenerator;

use ZipArchive;
use WP_Error;
use WP_CLI;
use WP_Post;
use URL_Extractor;

class Controller {
    const OFFLINESITEGENERATOR_VERSION = '7.1.0';

    /**
     * @var string
     */
    public $bootstrap_file;

    /**
     * Main controller of OfflineSiteGenerator
     *
     * @var \OfflineSiteGenerator\Controller Instance.
     */
    protected static $plugin_instance = null;

    protected function __construct() {}

    /**
     * Returns instance of OfflineSiteGenerator Controller
     *
     * @return \OfflineSiteGenerator\Controller Instance of self.
     */
    public static function getInstance() : Controller {
        if ( null === self::$plugin_instance ) {
            self::$plugin_instance = new self();
        }

        return self::$plugin_instance;
    }

    public static function init( string $bootstrap_file ) : Controller {
        $plugin_instance = self::getInstance();

        WordPressAdmin::registerHooks( $bootstrap_file );
        WordPressAdmin::addAdminUIElements();

        Utils::set_max_execution_time();

        if (! wp_next_scheduled( 'offlineSiteGenerator_process_queue' ) ) :
            $first_run = strtotime( 'next saturday' );
            wp_schedule_event( $first_run, 'weekly', 'offlineSiteGenerator_process_queue' );
        endif;

        return $plugin_instance;
    }

    /**
     * Adjusts position of dashboard menu icons
     *
     * @param string[] $menu_order list of menu items
     * @return string[] list of menu items
     */
    public static function setMenuOrder( array $menu_order ) : array {
        $order = [];
        $file  = plugin_basename( __FILE__ );

        foreach ( $menu_order as $index => $item ) {
            if ( $item === 'index.php' ) {
                $order[] = $item;
            }
        }

        $order = [
            'index.php',
            'offline-site-generator',
            'statichtmloutput',
        ];

        return $order;
    }

    public static function deactivateForSingleSite() : void {
        WPCron::clearRecurringEvent();
    }

    public static function deactivate( bool $network_wide = null ) : void {
        if ( $network_wide ) {
            global $wpdb;

            $query = 'SELECT blog_id FROM %s WHERE site_id = %d;';

            $site_ids = $wpdb->get_col(
                sprintf(
                    $query,
                    $wpdb->blogs,
                    $wpdb->siteid
                )
            );

            foreach ( $site_ids as $site_id ) {
                switch_to_blog( $site_id );
                self::deactivateForSingleSite();
            }

            restore_current_blog();
        } else {
            self::deactivateForSingleSite();
        }
    }

    public static function activateForSingleSite() : void {
        // prepare DB tables
        WsLog::createTable();
        CoreOptions::init();
        CrawlCache::createTable();
        CrawlQueue::createTable();
        DeployCache::createTable();
        JobQueue::createTable();
        Addons::createTable();
    }

    public static function activate( bool $network_wide = null ) : void {
        if ( $network_wide ) {
            global $wpdb;

            $query = 'SELECT blog_id FROM %s WHERE site_id = %d;';

            $site_ids = $wpdb->get_col(
                sprintf(
                    $query,
                    $wpdb->blogs,
                    $wpdb->siteid
                )
            );

            foreach ( $site_ids as $site_id ) {
                switch_to_blog( $site_id );
                self::activateForSingleSite();
            }

            restore_current_blog();
        } else {
            self::activateForSingleSite();
        }
    }

    /**
     * Checks if the named index exists. If it doesn't, create it. This won't
     * alter an existing index. If you need to change an index, give it a new name.
     *
     * WordPress's dbDelta is very unreliable for indexes. It tends to create duplicate
     * indexes, acts badly if whitespace isn't exactly what it expects, and fails
     * silently. It's okay to create the table and primary key with dbDelta,
     * but use ensureIndex for index creation.
     *
     * @param string $table_name The name of the table that the index is for.
     * @param string $index_name The name of the index.
     * @param string $create_index_sql The SQL to execute if the index needs to be created.
     * @return bool true if the index already exists or was created. false if creation failed.
     */
    public static function ensureIndex( string $table_name, string $index_name,
                                        string $create_index_sql ) : bool {
        global $wpdb;

        $query = $wpdb->prepare(
            "SHOW INDEX FROM $table_name WHERE key_name = %s",
            $index_name
        );
        $indexes = $wpdb->query( $query );

        if ( 0 === $indexes ) {
            $result = $wpdb->query( $create_index_sql );
            if ( false === $result ) {
                \OfflineSiteGenerator\WsLog::l( "Failed to create $index_name index on $table_name." );
            }
            return $result;
        } else {
            return true;
        }
    }

    public static function registerOptionsPage() : void {
        add_menu_page(
            'Offline Site Generator',
            'Generate Offline Site',
            'manage_options',
            'offline-site-generator',
            [ 'OfflineSiteGenerator\ViewRenderer', 'renderRunPage' ],
            'dashicons-admin-site-alt3'
        );

        $submenu_pages = [
            'run' => [ 'OfflineSiteGenerator\ViewRenderer', 'renderRunPage' ],
            'options' => [ 'OfflineSiteGenerator\ViewRenderer', 'renderOptionsPage' ],
            'caches' => [ 'OfflineSiteGenerator\ViewRenderer', 'renderCachesPage' ],
            'diagnostics' => [ 'OfflineSiteGenerator\ViewRenderer', 'renderDiagnosticsPage' ],
            'logs' => [ 'OfflineSiteGenerator\ViewRenderer', 'renderLogsPage' ],
            'addons' => [ 'OfflineSiteGenerator\ViewRenderer', 'renderAddonsPage' ],
        ];

        foreach ( $submenu_pages as $slug => $method ) {
            $menu_slug =
                $slug === 'run' ? 'offline-site-generator' : 'offline-site-generator-' . $slug;

            $title = ucfirst( $slug );

            // @phpstan-ignore-next-line
            add_submenu_page(
                'offline-site-generator',
                'OfflineSiteGenerator ' . ucfirst( $slug ),
                $title,
                'manage_options',
                $menu_slug,
                $method
            );
        }

        add_submenu_page(
            '',
            'OfflineSiteGenerator Crawl Queue',
            'Crawl Queue',
            'manage_options',
            'offline-site-generator-crawl-queue',
            [ 'OfflineSiteGenerator\ViewRenderer', 'renderCrawlQueue' ]
        );

        add_submenu_page(
            '',
            'OfflineSiteGenerator Crawl Cache',
            'Crawl Cache',
            'manage_options',
            'offline-site-generator-crawl-cache',
            [ 'OfflineSiteGenerator\ViewRenderer', 'renderCrawlCache' ]
        );

        add_submenu_page(
            '',
            'OfflineSiteGenerator Deploy Cache',
            'Deploy Cache',
            'manage_options',
            'offline-site-generator-deploy-cache',
            [ 'OfflineSiteGenerator\ViewRenderer', 'renderDeployCache' ]
        );

        add_submenu_page(
            '',
            'OfflineSiteGenerator Static Site',
            'Static Site',
            'manage_options',
            'offline-site-generator-static-site',
            [ 'OfflineSiteGenerator\ViewRenderer', 'renderStaticSitePaths' ]
        );

        add_submenu_page(
            '',
            'OfflineSiteGenerator Post Processed Site',
            'Post Processed Site',
            'manage_options',
            'offline-site-generator-post-processed-site',
            [ 'OfflineSiteGenerator\ViewRenderer', 'renderPostProcessedSitePaths' ]
        );
    }

    public function crawlSite() : void {
        $crawler = new Crawler();

        // TODO: if WordPressSite methods are static and we only need detectURLs
        // here, pass in iterable to URLs here?
        $crawler->crawlSite( StaticSite::getPath() );
    }

    // TODO: why is this here? Move to CrawlQueue if still needed
    public function deleteCrawlCache() : void {
        // we now have modified file list in DB
        global $wpdb;

        $table_name = $wpdb->prefix . 'offlineSiteGenerator_crawl_cache';
        echo '<pre>';
        echo $table_name;
        echo '</pre>';

        $wpdb->query( "TRUNCATE TABLE $table_name" );

        $sql =
            "SELECT count(*) FROM $table_name";

        $count = $wpdb->get_var( $sql );

        if ( $count === '0' ) {
            http_response_code( 200 );

            echo 'SUCCESS';
        } else {
            http_response_code( 500 );
        }
    }

    public function userIsAllowed() : bool {
        if ( defined( 'WP_CLI' ) ) {
            return true;
        }

        $referred_by_admin = check_admin_referer( 'offline-site-generator-options' );
        $user_can_manage_options = current_user_can( 'manage_options' );

        return $referred_by_admin && $user_can_manage_options;
    }

    public function resetDefaultSettings() : void {
        CoreOptions::seedOptions();
    }

    public function deleteDeployCache() : void {
        DeployCache::truncate();
    }

    public static function offlineSiteGeneratorUISaveOptions() : void {
    //    check_admin_referer( 'offline-site-generator-ui-options' );

        $form_data = $_POST['form_data'];
        foreach($form_data as $input) {
            $postname = $input['name'];
            $_POST[$postname] = $input['value'];
        }
    
        CoreOptions::savePosted( 'core' );

        wp_die();
   
    }

    public static function offlineSiteGeneratorCrawlQueueDelete() : void {
        check_admin_referer( 'offline-site-generator-caches-page' );

        CrawlQueue::truncate();

        wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-caches' ) );
        exit;
    }

    public static function offlineSiteGeneratorCrawlQueueShow() : void {
        check_admin_referer( 'offline-site-generator-caches-page' );

        wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-crawl-queue' ) );
        exit;
    }

    public static function offlineSiteGeneratorDeleteJobsQueue() : void {
        check_admin_referer( 'offline-site-generator-ui-job-options' );

        JobQueue::truncate();

        wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-jobs' ) );
        exit;
    }

    public static function offlineSiteGeneratorDeleteAllCaches() : void {
     
        self::deleteAllCaches();

        wp_die();
    }

    public static function deleteAllCaches() : void {
        CrawlQueue::truncate();
        CrawlCache::truncate();
        StaticSite::delete();
        ProcessedSite::delete();
        DeployCache::truncate();
    }

    public static function offlineSiteGeneratorProcessJobsQueue() : void {
        check_admin_referer( 'offline-site-generator-ui-job-options' );

        WsLog::l( 'Manually processing JobQueue' );

        self::offlineSiteGeneratorProcessQueue();

        wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-jobs' ) );
        exit;
    }

    public static function offlineSiteGeneratorDeployCacheDelete() : void {
        check_admin_referer( 'offline-site-generator-caches-page' );

        if ( isset( $_POST['deploy_namespace'] ) ) {
            DeployCache::truncate( $_POST['deploy_namespace'] );
        } else {
            DeployCache::truncate();
        }

        wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-caches' ) );
        exit;
    }

    public static function offlineSiteGeneratorDeployCacheShow() : void {
        check_admin_referer( 'offline-site-generator-caches-page' );

        if ( isset( $_POST['deploy_namespace'] ) ) {
            wp_safe_redirect(
                admin_url(
                    'admin.php?page=offline-site-generator-deploy-cache&deploy_namespace=' .
                    urlencode( $_POST['deploy_namespace'] )
                )
            );
        } else {
            wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-deploy-cache' ) );
        }

        exit;
    }

    public static function offlineSiteGeneratorCrawlCacheDelete() : void {
        check_admin_referer( 'offline-site-generator-caches-page' );

        CrawlCache::truncate();

        wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-caches' ) );
        exit;
    }

    public static function offlineSiteGeneratorCrawlCacheShow() : void {
        check_admin_referer( 'offline-site-generator-caches-page' );

        wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-crawl-cache' ) );
        exit;
    }

    public static function offlineSiteGeneratorPostProcessedSiteDelete() : void {
        check_admin_referer( 'offline-site-generator-caches-page' );

        ProcessedSite::delete();

        wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-caches' ) );
        exit;
    }

    public static function offlineSiteGeneratorPostProcessedSiteShow() : void {
        check_admin_referer( 'offline-site-generator-caches-page' );

        wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-post-processed-site' ) );
        exit;
    }

    public static function offlineSiteGeneratorLogDelete() : void {
       

        WsLog::truncate();
        wp_die();

    }

    public static function offlineSiteGeneratorStaticSiteDelete() : void {
        check_admin_referer( 'offline-site-generator-caches-page' );

        StaticSite::delete();

        wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-caches' ) );
        exit;
    }

    public static function offlineSiteGeneratorStaticSiteShow() : void {
        check_admin_referer( 'offline-site-generator-caches-page' );

        wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-static-site' ) );
        exit;
    }

    public static function offlineSiteGeneratorUISaveJobOptions() : void {
        CoreOptions::savePosted( 'jobs' );

        do_action( 'offlineSiteGenerator_addon_ui_save_job_options' );

        check_admin_referer( 'offline-site-generator-ui-job-options' );

        wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-jobs' ) );
        exit;
    }

    public static function offlineSiteGeneratorSavePostHandler( int $post_id ) : void {
        if ( CoreOptions::getValue( 'queueJobOnPostSave' ) &&
             get_post_status( $post_id ) === 'publish' ) {
            self::offlineSiteGeneratorEnqueueJobs();
        }
    }

    public static function offlineSiteGeneratorTrashedPostHandler() : void {
        if ( CoreOptions::getValue( 'queueJobOnPostDelete' ) ) {
            self::offlineSiteGeneratorEnqueueJobs();
        }
    }

    public static function offlineSiteGeneratorEnqueueJobs() : void {
        // check each of these in order we want to enqueue
        $job_types = [
            'autoJobQueueDetection' => 'detect',
            'autoJobQueueCrawling' => 'crawl',
            'autoJobQueuePostProcessing' => 'post_process',
            'autoJobQueueDeployment' => 'deploy',
        ];

        foreach ( $job_types as $key => $job_type ) {
            if ( (int) CoreOptions::getValue( $key ) === 1 ) {
           //     JobQueue::addJob( $job_type );
            }
        }
    }

    public static function offlineSiteGeneratorToggleAddon() : void {
   //     check_admin_referer( 'offline-site-generator-addons-page' );

        $addon_slug = sanitize_text_field( $_POST['addon_slug'] );

        global $wpdb;

        $table_name = $wpdb->prefix . 'offlineSiteGenerator_addons';

        // get target addon's current state
        $addon =
            $wpdb->get_row( "SELECT enabled, type FROM $table_name WHERE slug = '$addon_slug'" );

        // if deploy type, disable others when enabling this one
        if ( $addon->type === 'deploy' ) {
            $wpdb->update(
                $table_name,
                [ 'enabled' => 0 ],
                [ 'enabled' => 1 ]
            );
        }

        // toggle the target addon's state
        $wpdb->update(
            $table_name,
            [ 'enabled' => ! $addon->enabled ],
            [ 'slug' => $addon_slug ]
        );
         var_dump($_POST);

        wp_die();
    }

    public static function offlineSiteGeneratorManuallyEnqueueJobs() : void {
        check_admin_referer( 'offline-site-generator-manually-enqueue-jobs' );

        // TODO: consider using a transient based notifications system to
        // persist through wp_safe_redirect calls
        // ie, https://github.com/wpscholar/wp-transient-admin-notices/blob/master/TransientAdminNotices.php

        // check each of these in order we want to enqueue
        $job_types = [
            'autoJobQueueDetection' => 'detect',
            'autoJobQueueCrawling' => 'crawl',
            'autoJobQueuePostProcessing' => 'post_process',
            'autoJobQueueDeployment' => 'deploy',
        ];

        foreach ( $job_types as $key => $job_type ) {
            if ( (int) CoreOptions::getValue( $key ) === 1 ) {
           //     JobQueue::addJob( $job_type );
            }
        }

        wp_safe_redirect( admin_url( 'admin.php?page=offline-site-generator-jobs' ) );
        exit;
    }

    /*
        Should only process at most 4 jobs here (1 per type), with
        earlier jobs of the same type having been "squashed" first
    */
    public static function offlineSiteGeneratorProcessQueue() : void {
        // skip any earlier jobs of same type still in 'waiting' status
        JobQueue::squashQueue();

        if ( JobQueue::jobsInProgress() ) {
            WsLog::l(
                'Job in progress when attempting to process queue.
                  No new jobs will be processed until current in progress is complete.'
            );

            return;
        }

        // get all with status 'waiting' in order of oldest to newest
        $jobs = JobQueue::getProcessableJobs();

        foreach ( $jobs as $job ) {
            JobQueue::setStatus( $job->id, 'processing' );

            switch ( $job->job_type ) {
                case 'detect':
                    WsLog::l( 'Starting URL detection' );
                    $detected_count = URLDetector::detectURLs();
                    WsLog::l( "URL detection completed ($detected_count URLs detected)" );
                    break;
                case 'crawl':
                    WsLog::l( 'Starting crawling' );
                    $crawler = new Crawler();
                    $crawler->crawlSite( StaticSite::getPath() );
                    WsLog::l( 'Crawling completed' );
                    break;
                case 'post_process':
                    WsLog::l( 'Starting post-processing' );
                    $post_processor = new PostProcessor();
                    $processed_site_dir =
                        SiteInfo::getPath( 'uploads' ) . 'offline-site-generator-processed-site';
                    $processed_site = new ProcessedSite();
                    $post_processor->processStaticSite( StaticSite::getPath() );
                    WsLog::l( 'Post-processing completed' );
                    break;
                case 'deploy':
                    if ( Addons::getDeployer() === 'no-enabled-deployment-addons' ) {
                        WsLog::l( 'No deployment add-ons are enabled, skipping deployment.' );
                    } else {
                        WsLog::l( 'Starting deployment' );
                        do_action(
                            'offlineSiteGenerator_deploy',
                            ProcessedSite::getPath(),
                            Addons::getDeployer()
                        );
                        do_action( 'offlineSiteGenerator_post_deploy_trigger', Addons::getDeployer() );
                    }

                    break;
                default:
                    WsLog::l( 'Trying to process unknown job type' );
            }

            JobQueue::setStatus( $job->id, 'completed' );
        }
    }

    public static function offlineSiteGeneratorHeadless() : void {
        WsLog::l( 'Running OfflineSiteGenerator\Controller::offlineSiteGeneratorHeadless()' );
        WsLog::l( 'Starting URL detection' );
        $detected_count = URLDetector::detectURLs();
        WsLog::l( "URL detection completed ($detected_count URLs detected)" );

        WsLog::l( 'Starting crawling' );
        $crawler = new Crawler();
        $crawler->crawlSite( StaticSite::getPath() );
        WsLog::l( 'Crawling completed' );
        WsLog::l( 'Starting post-processing' );
        $post_processor = new PostProcessor();
        $processed_site_dir =
            SiteInfo::getPath( 'uploads' ) . 'offline-site-generator-processed-site';
        $processed_site = new ProcessedSite();
        $post_processor->processStaticSite( StaticSite::getPath() );
        WsLog::l( 'Post-processing completed' );

        if ( Addons::getDeployer() === 'no-enabled-deployment-addons' ) {
            WsLog::l( 'No deployment add-ons are enabled, skipping deployment.' );
        } else {
            WsLog::l( 'Starting deployment' );
            do_action( 'offlineSiteGenerator_deploy', ProcessedSite::getPath(), Addons::getDeployer() );
            do_action( 'offlineSiteGenerator_post_deploy_trigger', Addons::getDeployer() );
        }
    }

    public static function invalidateSingleURLCache(
        int $post_id = 0,
        WP_Post $post = null
    ) : void {
        if ( ! $post ) {
            return;
        }

        $permalink = get_permalink(
            $post->ID
        );

        $site_url = SiteInfo::getUrl( 'site' );

        if ( ! is_string( $permalink ) || ! is_string( $site_url ) ) {
            return;
        }

        $url = str_replace(
            $site_url,
            '/',
            $permalink
        );

        CrawlCache::rmUrl( $url );
    }

    public static function emailDeployNotification() : void {
        if ( empty( CoreOptions::getValue( 'completionEmail' ) ) ) {
            return;
        }

        WsLog::l( 'Sending deployment notification email...' );

        $to = CoreOptions::getValue( 'completionEmail' );
        $subject = 'OfflineSiteGenerator deployment complete on site: ' .
            $site_title = get_bloginfo( 'name' );
        $body = 'OfflineSiteGenerator deployment complete!';
        $headers = [];

        if ( wp_mail( $to, $subject, $body, $headers ) ) {
            WsLog::l( 'Deployment notification email sent without error.' );
        } else {
            WsLog::l( 'Failed to send deployment notificaiton email.' );
        }
    }

    public static function webhookDeployNotification() : void {
        $webhook_url = CoreOptions::getValue( 'completionWebhook' );

        if ( empty( $webhook_url ) ) {
            return;
        }

        WsLog::l( 'Sending deployment notification webhook...' );

        $http_method = CoreOptions::getValue( 'completionWebhookMethod' );

        $body = $http_method === 'POST' ? 'OfflineSiteGenerator deployment complete!' :
            [ 'message' => 'OfflineSiteGenerator deployment complete!' ];

        $webhook_response = wp_remote_request(
            $webhook_url,
            [
                'method' => CoreOptions::getValue( 'completionWebhookMethod' ),
                'timeout' => 30,
                'user-agent' => 'OfflineSiteGenerator.com',
                'body' => $body,
            ]
        );

        WsLog::l(
            'Webhook response code: ' . wp_remote_retrieve_response_code( $webhook_response )
        );
    }

    public static function offlineSiteGeneratorRun() {
        
        check_ajax_referer( 'offline-site-generator-run-page', 'security' );

       WsLog::l( 'Running full workflow from UI' );

       self::offlineSiteGeneratorHeadless();
     

        wp_die();
    }




    /**
     * Give logs to UI
     */
    public static function offlineSiteGeneratorPollLog() : void {
        check_ajax_referer( 'offline-site-generator-run-page', 'security' );

        $logs = WsLog::poll();

        echo $logs;

        wp_die();
    }

    
}
