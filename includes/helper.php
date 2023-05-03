<?php

    /**
 	 * Include global helper functions
 	 */

    // Error code cause when cannot get html from link
    if( ! defined( 'ERROR_GET_HTML' ) ){
        define( 'ERROR_GET_HTML', 703 );
    }

    if( ! defined( 'ERROR_CLOUD_FLARE' ) ){
        define( 'ERROR_CLOUD_FLARE', 704 );
    }

    /**
 	 * External Media File Upload Helper
	 */
    if( ! function_exists( 'wt_wp_mcl_e_upload_file_handler' ) ){

        function wt_wp_mcl_e_upload_file_handler(){

            if( isset( $_POST['wp_mcl_e_upload_file'] ) ){

                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                require_once( ABSPATH . 'wp-admin/includes/media.php' );

                $thumb_id = media_handle_upload( 'image', $_POST['post_id'] );

                if( $thumb_id && !is_wp_error( $thumb_id ) ){
                    echo $thumb_id;
                }

                die();
            }

        }
        add_action('wp', 'wt_wp_mcl_e_upload_file_handler');

    }

    if( ! function_exists('wt_wp_mcl_e_upload_file') ){
        function wt_wp_mcl_e_upload_file( $url, $post_id = 0 ){
			$settings = WT_CRAWLER_HELPERS::get_crawler_settings();
			
			if( !empty( $settings['proxy']['status'] ) ){
                $GLOBALS['proxy'] = $settings['proxy'];
            }
			
			if(!empty($settings['proxy']['scraperapi'])){
				$GLOBALS['scraperapi'] = $settings['proxy']['scraperapi'];
			}
				

            include_once( ABSPATH . 'wp-admin/includes/image.php' );
            $content = file_get_contents( $url );

            $pathinfo = pathinfo( $url );

            if( ! $content ){
                return false;
            }

            $upload_dir = wp_upload_dir();
            $file_tmp_path = $upload_dir['basedir'] . '/' . $pathinfo['filename'] . '-' . $post_id . '.' . explode('?',$pathinfo['extension'])[0];

            $file = file_put_contents( $file_tmp_path, $content );
			
			$wp_filetype = wp_check_filetype(basename($file_tmp_path), null );

			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => $post_id,
				'post_content' => '',
				'post_status' => 'inherit'
			);

			$attach_id = wp_insert_attachment( $attachment, $file_tmp_path );

			$imagenew = get_post( $attach_id );
			$fullsizepath = get_attached_file( $imagenew->ID );
			$attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			
			return $attach_id;

        }
    }

    if( !function_exists('wt_get_site_html') ){
        function wt_get_site_html( $url ){

            if( strpos( $url, 'http' ) === false ){
                return false;
            }
			
			$settings = WT_CRAWLER_HELPERS::get_crawler_settings();
			
			if( !empty( $settings['proxy']['status'] ) ){
                $GLOBALS['proxy'] = $settings['proxy'];
            }
			
			if(!empty($settings['proxy']['scraperapi'])){
				$GLOBALS['scraperapi'] = $settings['proxy']['scraperapi'];
			}

            global $request_cookies;

            $html = wt_file_get_html( $url );

            return !empty( $html ) ? $html : false;

        }
    }

    if( ! function_exists('wt_get_manga_post_by_import_slug') ){
        function wt_get_manga_post_by_import_slug( $slug = '', $name = '' ){

            $args = array(
                'post_type'  => 'wp-manga',
                'post_status' => array('publish', 'draft', 'pending'),
                'meta_query' => array(
                    array(
                        'key'     => '_manga_import_slug',
                        'value'   => $slug,
                    ),
                    array(
                        'key'     => 'crawl_source',
                        'value'   => 'WT',
                    ),
                ),
            );

            $query = new WP_Query( $args );

            if( $query->have_posts() ){
                return $query->posts[0]->ID;
            }
			
			// if not, find another case
			if(strpos($slug, 'manga') == 0){
				$args = array(
					'post_type'  => 'wp-manga',
					'post_status' => array('publish', 'draft', 'pending'),
                    'meta_query' => array(
                        array(
                            'key'     => '_manga_import_slug',
                            'value'   => substr($slug, 5),
                        ),
                        array(
                            'key'     => 'crawl_source',
                            'value'   => 'WT',
                        ),
                    ),
				);
			} else {
				$args = array(
					'post_type'  => 'wp-manga',
					'post_status' => array('publish', 'draft', 'pending'),
                    'meta_query' => array(
                        array(
                            'key'     => '_manga_import_slug',
                            'value'   => 'manga' . $slug,
                        ),
                        array(
                            'key'     => 'crawl_source',
                            'value'   => 'WT',
                        ),
                    ),
				);
			}
			
			$query = new WP_Query( $args );

			if( $query->have_posts() ){
				return $query->posts[0]->ID;
			}

            return false;

        }
    }

    if( ! function_exists('wt_find_manga_post') ){
        function wt_find_manga_post( $name, $slug = '' ){

            if( !empty( $slug ) ){
                $post_id = wt_get_manga_post_by_import_slug( $slug, $name );
            }

            if( empty( $post_id ) ){
                // If cannot find by imported slug, then try to find by name (requires to be exactly)
                $find_post = new WP_Query( array(
                    'title'     => $name,
                    'post_type' => 'wp-manga',
					'meta_query' => array(
                        array(
                            'key'     => 'crawl_source',
                            'value'   => 'WT',
                        ),
                    ),
                ) );

                if( $find_post->have_posts() ){
                    return $find_post->posts[0]->ID;
                }
            }

            return false;
        }
    }

    if( ! function_exists( 'wt_error_log_die' ) ){
        function wt_error_log_die( $args ){

            extract( $args );

            // $function, $message, $cancel = false, $data = '', $code = 404

            if( !empty( $cancel ) && !empty( $GLOBALS['running_transient'] ) ){
                set_transient( $GLOBALS['running_transient'], false );
            }

            $function = isset( $function ) ? $function : '';
            $message = isset( $message ) ? $message : '';

            // write plugin own debug log
			$upload_dir = wp_get_upload_dir();

            $log_dir = "{$upload_dir['basedir']}/wp-crawler-cronjob/webtoon-manga-crawler";
			
            file_put_contents( $log_dir . '/error.log', date_i18n( 'Y-m-d H:i:s', time() ) . " | $function | $message \r\n", FILE_APPEND );

            wp_send_json_error([
                'message'  => $message,
                'data'     => isset( $data ) ? $data : '',
                'function' => $function,
                'code'     => isset( $code ) ? $code : '404'
            ]);

            die();
        }
    }

    if( ! function_exists( 'wt_crawler_log' ) ){
        function wt_crawler_log( $message ){
            // write plugin own crawler log
			$upload_dir = wp_get_upload_dir();
            $dir = "{$upload_dir['basedir']}/wp-crawler-cronjob/webtoon-manga-crawler/crawler-log/";

            if( !file_exists( $dir ) ){
                wp_mkdir_p( $dir );
            }
			
			if(!is_string($message)){
				$message = var_export($message, true);
			}

            file_put_contents(  $dir . date('y-m-d') . '.log', date_i18n( 'Y-m-d H:i:s', time() ) . " | $message \r\n", FILE_APPEND );
        }
    }

    if( ! function_exists( 'wt_get_crawler_log' ) ){
        function wt_get_crawler_log( $dir, $date ){

            // write plugin own crawler log
            $dir = "{$dir}/crawler-log/{$date}.log";

            if( !file_exists( $dir ) ){
                return null;
            }

            $content = file_get_contents( $dir );

            $content = str_replace( "\r\n", '<br />', $content );

            return $content;

        }
    }


    if( ! function_exists( 'dd' ) ){
        function dd( $object ){
            echo '<pre>';
            var_dump( $object);
            echo '</pre>';
            die();
        }
    }

    if( ! function_exists( 'check_curl_proxy' ) ){
        function check_curl_proxy( $proxy ){

            if( empty( $proxy ) ){
                return array(
                    'success' => false,
                    'message' => esc_html_e( "There is no Proxy set", WP_MCL_TD )
                );
            }

            $url = "http://dynupdate.no-ip.com/ip.php";

            $GLOBALS['proxy'] = $proxy;

            $resp = wt_curl_get_contents( $url, true );

            $GLOBALS['proxy'] = null;

            if( is_numeric( $resp ) ){
                return array(
                    'success' => false,
                    'message' => curl_strerror( $resp )
                );
            }elseif( !empty( $resp ) ){
                return array(
                    'success' => true,
                    'message' => "Connection Proxy IP : {$resp}"
                );
            }
        }
    }

    if( !function_exists('get_admin_loading_icon') ){
        function get_admin_loading_icon(){
            return '<img src="' . admin_url('images/spinner.gif') . '" style="vertical-align:bottom">';
        }
    }
	
	/* 
	 * php delete function that deals with directories recursively
	 */
	function wt_delete_files($target) {
		if(is_dir($target)){
			$files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned

			foreach( $files as $file ){
				wt_delete_files( $file );      
			}

			rmdir( $target );
		} elseif(is_file($target)) {
			unlink( $target );  
		}
	}
