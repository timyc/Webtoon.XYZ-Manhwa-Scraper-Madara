<?php

    /**
 	 * Progress metabox template
 	 */

    if( ! defined('ABSPATH') ){
        exit;
    }

    global $wt_crawler_settings;

    $address = isset( $wt_crawler_settings['proxy']['address'] ) ? $wt_crawler_settings['proxy']['address'] : '';
    $user    = isset( $wt_crawler_settings['proxy']['user'] ) ? $wt_crawler_settings['proxy']['user'] : '';
    $pass    = isset( $wt_crawler_settings['proxy']['pass'] ) ? $wt_crawler_settings['proxy']['pass'] : '';
    $port    = isset( $wt_crawler_settings['proxy']['port'] ) ? $wt_crawler_settings['proxy']['port'] : '';
	// https://www.scraperapi.com/
	$scraperapi = isset( $wt_crawler_settings['proxy']['scraperapi'] ) ? $wt_crawler_settings['proxy']['scraperapi'] : '';
?>
