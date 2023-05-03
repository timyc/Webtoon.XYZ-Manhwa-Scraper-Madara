<?php

    if( ! class_exists('WT_CRAWLER_CRONJOB') && class_exists('WT_CRAWLER_IMPLEMENT') ){

        class WT_CRAWLER_CRONJOB{

            public $implement;
            public $crawl_action;
            public $upload_cloud_action;
            public $js_func;

            public function __construct(){

                $this->implement = new WT_CRAWLER_IMPLEMENT();
                $this->crawl_action = "__{$this->implement->sname}_crawler_cronjob_action";
                $this->upload_cloud_action = "__{$this->implement->sname}_upload_cloud_cronjob_action";
                $this->js_func = "{$this->implement->sname}_cj_run";

                add_filter( 'cron_schedules', array($this, '_add_cron_interval' ));
            }
			
			function _add_cron_interval( $schedules ) { 
				$settings = WT_CRAWLER_HELPERS::get_crawler_settings();
				
				$schedules['crawl_interval'] = array(
					'interval' => $settings['cronjob']['recurrence'] * 60,
					'display'  => __( 'Run every ' . $settings['cronjob']['recurrence'] . ' minutes. Configured in Crawler Settings' ) );
					
				$schedules['crawl_update_interval'] = array(
					'interval' => 3 * 60 * 60, // 3 hours
					'display'  => __( 'Run every 3 hours to check for new updates' ) );
					
				return $schedules;
			}
        }

        new WT_CRAWLER_CRONJOB();
		
		function webtoon_activation() {
			if (! wp_next_scheduled ( 'wt_crawler_upload_cloud' )) {
				wp_schedule_event(time(), 'crawl_interval', 'wt_crawler_upload_cloud');
			}
			
			if (! wp_next_scheduled ( 'wt_crawler_event' )) {
				wp_schedule_event(time(), 'crawl_interval', 'wt_crawler_event');
			}
			
			if (! wp_next_scheduled ( 'wt_crawler_update_event' )) {
				
				wp_schedule_event(time(), 'crawl_update_interval', 'wt_crawler_update_event');
			}
			
			if (! wp_next_scheduled ( 'wt_crawler_fetch_event' )) {
				
				wp_schedule_event(time(), 'crawl_interval', 'wt_crawler_fetch_event');
			}
		}
		 
		add_action('wt_crawler_event', 'wt_do_crawl');
		add_action('wt_crawler_update_event', 'wt_check_updates');
		add_action('wt_crawler_fetch_event', 'wt_fetch_queue');
		add_action('wt_crawler_upload_cloud', 'wt_crawler_do_upload_cloud');
		 
		function wt_do_crawl() {
			$settings = WT_CRAWLER_HELPERS::get_crawler_settings();
			
			if($settings['active']){
				$crawler = new WT_CRAWLER_IMPLEMENT();
				$crawler->crawl();
			}
		}
		
		function wt_fetch_queue() {
			$settings = WT_CRAWLER_HELPERS::get_crawler_settings();
			if($settings['active']){
				$crawler = new WT_CRAWLER_IMPLEMENT();
				$crawler->fetch_manga();
			}
		}
		
		function wt_check_updates() {
			$settings = WT_CRAWLER_HELPERS::get_crawler_settings();
			if($settings['update']){
				$crawler = new WT_CRAWLER_IMPLEMENT();
				$crawler->update_latest_manga();
				
				$crawler->update_status( 'next_update_latest_manga', time() + 3 * 60 * 60 );
			}
		}
		
		function wt_crawler_do_upload_cloud() {
			$settings = WT_CRAWLER_HELPERS::get_crawler_settings();
			if(($settings['active'] || $settings['update']) && $settings['import']['storage'] != 'local'){
				$crawler = new WT_CRAWLER_IMPLEMENT();
				$crawler->upload_cloud();
			}
		}		
    }
