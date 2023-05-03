<?php

    /**
 	 * Template for webtoon Crawler Settings page
	 */

    if( ! defined('ABSPATH') || ! class_exists( 'WT_CRAWLER_IMPLEMENT' ) ){
        exit;
    }

    $GLOBALS['wt_crawler_settings'] = WT_CRAWLER_HELPERS::get_crawler_settings();

    $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'crawl-settings';

?>
<div class="wrap">

    <h1>
        <?php esc_html_e( 'webtoon Manga Crawler', 'madara' ) ?>
    </h1>

    <div id="wt-crawler-settings-page" class="crawler-settings-page">

        <div class="nav-tab-wrapper">

            <a href="?page=webtoon-manga-crawler-settings&tab=crawl-settings" class="nav-tab <?php echo $active_tab == 'crawl-settings' ? 'nav-tab-active' : ''; ?>">
                <i class="dashicons dashicons-admin-generic"></i>
                <?php esc_html_e( 'Crawler Settings', 'madara' ) ?>
            </a>

            <a href="?page=webtoon-manga-crawler-settings&tab=crawl-progress" class="nav-tab <?php echo $active_tab == 'crawl-progress' ? 'nav-tab-active' : ''; ?>">
                <i class="dashicons-editor-alignleft dashicons"></i>
                <?php esc_html_e( 'Crawler Progress', 'madara' ) ?>
            </a>

            <a href="?page=webtoon-manga-crawler-settings&tab=crawl-single" class="nav-tab <?php echo $active_tab == 'crawl-single' ? 'nav-tab-active' : ''; ?>">
                <i class="dashicons-arrow-down-alt dashicons"></i>
                <?php esc_html_e( 'Crawl Single Manga', 'madara' ) ?>
            </a>

            <a href="?page=webtoon-manga-crawler-settings&tab=update-log" class="nav-tab <?php echo $active_tab == 'update-log' ? 'nav-tab-active' : ''; ?>">
                <i class="dashicons-format-aside dashicons"></i>
                <?php esc_html_e( 'Update Log', 'madara' ) ?>
            </a>

            <a href="?page=webtoon-manga-crawler-settings&tab=crawler-log" class="nav-tab <?php echo $active_tab == 'crawler-log' ? 'nav-tab-active' : ''; ?>">
                <i class="dashicons-format-aside dashicons"></i>
                <?php esc_html_e( 'Crawler Log', 'madara' ) ?>
            </a>
        </div>

        <div class="nav-tab-content">
            <?php WT_CRAWLER_HELPERS::get_template( "tab-content/{$active_tab}" ); ?>
        </div>
    </div>
</div>
