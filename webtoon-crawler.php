<?php

	/*
		Plugin Name: webtoon Manga Crawler
		Plugin URI: https://github.com
		Description: Automatic crawl Manga from www.webtoon.xyz (webtoon) and autopost
		Version: 1.0.1
		Author: Fuck ads
		Author URI: https://github.com
	*/

	if ( ! defined( 'WP_MCL_WT_PATH' ) ) {
		define( 'WP_MCL_WT_PATH', plugin_dir_path( __FILE__ ) );
	}

	if ( ! defined( 'WP_MCL_WT_URL' ) ) {
		define( 'WP_MCL_WT_URL', plugin_dir_url( __FILE__ ) );
	}

	if( ! defined( 'WP_MCL_TD' ) ){
		define( 'WP_MCL_TD', 'madara' );
	}

	if ( ! class_exists( 'WT_CRAWLER_IMPLEMENT' ) ) {

		class WP_MANGA_WT_CRAWLER {

			public function __construct() {
				$this->init();
				$this->hooks();
			}

			private function hooks(){
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			}

			private function init(){

				if( ini_get('max_execution_time') < 600 ){
					ini_set('max_execution_time', 600);
				}

				if( ini_get('max_input_time') < 600 ){
					ini_set('max_input_time', 600);
				}

				if( !function_exists( 'wt_file_get_html' ) ){
					require_once WP_MCL_WT_PATH . 'libs/simplehtmldom_1_5/simple_html_dom.php';
				}

				$includes = array(
					'includes' => array(
						'helper',
						'JavaScriptUnpacker',
						'import',
						'crawl',
						'implement' => array(
							'helper',
							'implement',
							'crawl-single'
						),
						'cronjob',
						'settings',
						'debug'
					)
				);

				foreach( $includes as $dir => $files ){
					foreach( $files as $index => $file ){
						if( is_array( $file ) ){
							foreach( $file as $f ){
								require_once( WP_MCL_WT_PATH . "{$dir}/{$index}/{$f}.php" );
							}
						}else{
							require_once( WP_MCL_WT_PATH . "{$dir}/{$file}.php" );
						}
					}
				}	
			}

			public function admin_enqueue_scripts() {
				if( class_exists('WT_CRAWLER_IMPLEMENT') && WT_CRAWLER_HELPERS::is_settings_page() ){
					wp_enqueue_style( 'wp-crawler-style', WP_MCL_WT_URL . 'assets/css/admin.css' );

					if( isset( $_GET['tab'] ) && $_GET['tab'] == 'crawl-progress' ){
						wp_enqueue_script( 'wt-crawler-task', WP_MCL_WT_URL . 'assets/js/wt-crawler-task.js', array( 'jquery' ) );
					}
				}
			}


		}

	    $license_key = 'DRAKIUS';
	    $WP_MANGA_WT_CRAWLER = new WP_MANGA_WT_CRAWLER();
		register_activation_hook(__FILE__, 'webtoon_activation');
	}
