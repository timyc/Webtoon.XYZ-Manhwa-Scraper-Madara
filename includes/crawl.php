<?php

    /**
 	 * Includes Crawler Action
 	 * use for all Manga Site Crawler
	 */

    if( ! class_exists('WT_CRAWLER_ACTIONS') && class_exists('WT_CRAWLER_IMPORT') ){

        class WT_CRAWLER_ACTIONS extends WT_CRAWLER_IMPORT{

            /**
         	 * Directory stores all crawler's json files
        	 */
            protected $data_dir;

            /**
         	 * File path
        	 */
            protected $manga_queue_file;
            protected $manga_completed_file;

            /**
         	 * URLs & selectors for crawling
             * will be assign value in implement.php file
        	 */
            protected $manga_listing_url;
            protected $manga_link_selector;
            protected $pagination_link_selector;
            protected $manga_listing_url_paged;
            protected $manga_listing_latest_url;
            protected $manga_latest_link_selector;

            /**
         	 * Crawler status
        	 */
            protected $status;

            public $search_url = '';

            public function __construct(){
                $this->setup();
            }

            private function setup(){

                if( empty( $this->data_dir ) ){
                    return;
                }

                if( !file_exists( $this->data_dir ) ){
                    wp_mkdir_p( $this->data_dir );
                }

                $this->manga_dir = "{$this->data_dir}/manga";

                if( !file_exists( $this->manga_dir ) ){
                    wp_mkdir_p( $this->manga_dir );
                }

                $this->manga_queue_file     = "{$this->data_dir}/queue.json";
                $this->manga_completed_file = "{$this->data_dir}/completed.json";
                $this->upload_cloud_file    = "{$this->data_dir}/upload_cloud.json";

            }

            /**
            * Fetch Single Manga URLs list from Manga Listing page
            * @fire once per call
            */
            public function fetch_manga_listing(){
                $is_manga_listing_has_paged = $this->is_manga_listing_has_paged();

                if( $is_manga_listing_has_paged ){
                    $paged = isset( $this->status['manga_listing_next_page'] ) ? $this->status['manga_listing_next_page'] : 1;

                    // If this manga already has latest page, then don't need to go back to first page
                    if( $paged === null ){
                        return;
                    }

                    $manga_listing_url = $this->get_manga_listing_paged( $paged );
                }elseif( empty( $this->status['manga_list_fetched'] ) ){
                    $manga_listing_url = $this->manga_listing_url;
                }else{
                    return;
                }

                
				
                $html = $this->wt_get_site_html( $manga_listing_url );

                if( empty( $html ) ){
                    wt_error_log_die([
                        'function' => __FUNCTION__,
                        'message'  => "Cannot get content from $manga_listing_url",
                        'cancel'   => true
                    ]);
                }

                $mangas = $html->find( $this->manga_link_selector );

                if( $mangas ){
                    $output = array();

                    foreach( $mangas as $manga ){
                        $manga_url  = $this->manga_url_filter( $manga->href );
                        $manga_slug = $this->manga_slug_filter( $manga->href );
                        $output[ $manga_slug ] = array(
                            'slug' => $manga_slug,
                            'name' => $manga->plaintext,
                            'url'  => $manga_url,
                        );
                    }

                    if( $this->put_manga_queue_list( $output ) ){

                        wt_crawler_log( '...Crawl Manga Listing Done' );

                        if( $is_manga_listing_has_paged && method_exists( $this, 'get_last_page' ) ){

                            $last_page = $this->get_last_page( $html );

                            if( !$last_page || ($last_page >= $paged) ){
                                $next_page = 1;
                            }else{
                                $next_page = ++$paged;
                            }

                            $this->update_status( 'manga_listing_next_page', $next_page );

                        }

                        return true;
                    }

                    return false;
                }

                return false;
            }

            /**
            * Get the first manga from queue list to fetch
            * @fire once per call
            */
	    public function fetch_manga(){
		    $redo = false;
				if(!get_transient('wt_fetch_manga_running')){
					set_transient('wt_fetch_manga_running', 1, 20 * 60); 
					$queue_list = $this->get_queue_list( 6 );
					if( !empty( $queue_list ) ){

						$queue_list = array_values( $queue_list );

						wt_crawler_log( '.Start Fetching Manga' );
						
						foreach( $queue_list as $index => $manga ){

							// If the crawler is inactive and update is enabled and this manga isn't update manga then continue
							if( empty( $this->settings['active'] ) && !empty( $this->settings['update'] ) && empty( $manga['is_update'] ) ){
								continue;
							}

							// For each request, it will fetch the first manga in queue list
							$resp = $this->fetch_single_manga( $manga );
							if ($resp == false) {
                                				$redo = true;
                            				}
							if( is_wp_error( $resp ) && $resp->get_error_code() == 'blocked' ){
								continue;
							} else {
								// fetch 1 manga in 1 call only. We get from queue list 6 mangas because we want to make sure at least 1 of 6 mangas is successful
								break;
							}

						}

						wt_crawler_log( '.Done Fetching Manga' );
					}
					
					delete_transient('wt_fetch_manga_running');
					if ($redo == true) {
                        			set_time_limit(600);
                        			$this->fetch_manga();
                    			}
				} else {
					wt_crawler_log('Another Fetch Manga process running');
				}
            }

            /**
            * Fetch Single Manga - The first manga fetching will create Manga post. Each call will fetch a chapter
            * @fire once per call
            */
            public function fetch_single_manga( $manga ){
                // If this manga is update manga
                if( !empty( $manga['is_update'] ) ){
					wt_crawler_log('Update Manga: ' . $manga['name']);
                    if( !empty( $manga['post_id'] ) ){
                        $post_id = $manga['post_id'];
                    }else{
                        // Get the manga post
                        $post_id = wt_find_manga_post( strtolower(html_entity_decode($manga['name']) ) );
                    }
                } else {
					wt_crawler_log('wt_get_manga_post_by_import_slug: ' . $manga['slug']);
                    $post_id = wt_get_manga_post_by_import_slug( $manga['slug'], strtolower(html_entity_decode($manga['name']) ) );
                }

                // If no post found then create it
                if( empty( $post_id ) ){
					wt_crawler_log('Cannot find existing manga: ' . $manga['name']);
                    $is_manga_completed = true;
					if( !empty( $is_manga_completed ) ){
                        $this->remove_manga_queue_list( $manga );
                        $this->remove_manga_update_list($manga);
                        $this->put_manga_completed_list( $manga );
                    }

                    return true;
                } else {
					wt_crawler_log('Existing Manga: ' . $post_id);
				}
				
				$manga_title = get_the_title( $post_id );

                wt_crawler_log( '..Fetching Manga <strong>' . $manga_title . '</strong>' );

                $this->update_status( 'current_manga', array(
                    'slug'    => $manga['slug'],
                    'post_id' => $post_id,
                ) );

                $completed_lists = $this->get_completed_list();
                $manga_json      = "{$this->manga_dir}/{$manga['slug']}.json";

                if(
                    ( file_exists( $manga_json ) && isset( $completed_lists[ $manga['slug'] ] ) && !empty( $manga['is_update'] ) ) // If this is update manga or manga json doesn't exist, then fetch the chapter list
                    || ! file_exists( $manga_json ) // Or manga json doesn't exist then fetch the chapter list
                ){
					
                    $html = $this->wt_get_site_html( $manga['url'] . '?' . time() );

                    if( empty( $html ) ){
                        wt_error_log_die([
                            'function' => __FUNCTION__,
                            'message'  => "Cannot get content from {$manga['url']}",
                            'cancel'   => true
                        ]);
                    }

                    wt_crawler_log( '...Fetch <strong>' . $manga_title . '</strong> Chapters list' );

                    // Fetch Manga chapters list
                    $chapter_list = $this->fetch_chapter_list( $html );

                    // Put chapters list to a json file
                    $resp = file_put_contents( $manga_json, json_encode( $chapter_list, JSON_PRETTY_PRINT ) );

                    wt_crawler_log( $resp ? '....Fetched Chapters list successfully' : '....Fetched chapters list failed' );

                }

                $chapter_list = file_get_contents( $manga_json );
                $chapter_list = json_decode( $chapter_list, true );
		$is_skipped = false;
                if( !empty( $chapter_list ) ){
                    $number_chapters = $this->settings['cronjob']['number_chapters'];
                    for( $i = 1; $i <= $number_chapters; $i++ ){

                        $resp = $this->fetch_chapters( $chapter_list, $post_id, !empty( $manga['is_update'] ) );
						
                        // If $resp is false then manga fetching is completed
                        if( is_wp_error( $resp ) && $resp->get_error_code() == 'blocked' ){
                            // If manga is blocked by country
                            $manga['is_blocked'] = true;
                        }

			if( empty( $resp ) || $resp == false || !empty( $manga['is_blocked'] ) ){
				$is_skipped = true;
                            $is_manga_completed = true;
                            break;
                        }
                    }

                }else{
                    // If this manga has no chapter then go to next manga
                    $is_manga_completed = true;
                }


                if( !empty( $is_manga_completed ) ){
                    $this->remove_manga_queue_list( $manga );
					$this->remove_manga_update_list($manga);
                    $this->put_manga_completed_list( $manga );
                }

                if ($is_skipped == true) {
                    return false;
                } else {
                    return true;
                }

            }

            /**
            * Update manga has new chapters
            */
            protected function update_latest_manga(){

                if( empty( $this->manga_listing_latest_url ) ){
                    return false;
                }

                wt_crawler_log( '.....Get Update Manga List that released yesterday' );
				// we crawl latest 8 pages
				for($i = 1; $i++; $i <= 8){
					$html = $this->wt_get_site_html( $this->manga_listing_latest_url . ($i == 1 ? '/' : '/page/' . $i . '/') . '?m_orderby=latest');

					if( empty( $html ) ){
						wt_error_log_die([
							'function' => __FUNCTION__,
							'message'  => "Cannot get content from {$this->manga_listing_latest_url}",
							'cancel'   => true
						]);
					}

					if( !empty( $this->manga_latest_link_selector ) ){
						$link_selector = $this->manga_latest_link_selector;
					}elseif( !empty( $this->manga_link_selector ) ){
						$link_selector = $this->manga_link_selector;
					}else{
						return;
					}

					$mangas = $html->find( $link_selector );

					if( $mangas ){
						$output = array();

						foreach( $mangas as $manga ){
							$a_tag = $manga->find('a',0);
							// Find manga post
							$manga_post_id = wt_find_manga_post( $a_tag->title );

							if( !empty( $manga_post_id ) ){
								$manga_url  = $this->manga_url_filter( $a_tag->href );
								$manga_slug = $this->manga_slug_filter( $a_tag->href );

								$output[ $manga_slug ] = array(
									'slug'      => $manga_slug,
									'name'      => $manga['name'],
									'url'       => $manga_url,
									'is_update' => true,
									'post_id'   => $manga_post_id
								);
							}
						}

						$this->put_manga_queue_list( $output );
					}
				}

                wt_crawler_log( '.....Done Get Update Manga List' );

            }
			
			public function remove_manga_update_list( $manga ){
				if( file_exists( $this->manga_update_file ) ){
					$list = json_decode( file_get_contents( $this->manga_update_file ), true );

					if( isset( $list[ $manga['slug'] ] ) ){
						unset( $list[ $manga['slug'] ] );
						return file_put_contents( $this->manga_update_file, json_encode( $list, JSON_PRETTY_PRINT ) );
					}
				}

				return false;

			}

            public function wt_get_update_log( $day ){

                $log_json = "{$this->data_dir}/update_log/$day.json";

                if( file_exists( $log_json ) ){
                    $content = file_get_contents( $log_json );
                    return json_decode( $content, true );
                }

                return [];
            }

            protected function put_update_log( $chapter ){

                $day = date('y-m-d');

                $update = $this->wt_get_update_log( $day );

                $update[ $chapter['manga'] ][ $chapter['volume'] ]['chapters'][] = $chapter['chapter'];

                $update_log_dir = "{$this->data_dir}/update_log";

                if( ! file_exists( $update_log_dir ) ){
                    wp_mkdir_p( $update_log_dir );
                }

                file_put_contents( "{$update_log_dir}/$day.json", json_encode( $update, JSON_PRETTY_PRINT ) );

                return true;
            }

            protected function get_upload_cloud_list(){

                if( file_exists( $this->upload_cloud_file ) ){
                    $content = file_get_contents( $this->upload_cloud_file );

                    return json_decode( $content, true );
                }

                return [];

            }

            protected function put_upload_cloud_list( $item ){

                $list = $this->get_upload_cloud_list();
				
                if( ! isset( $list[ $item['id'] ] ) ){
                    $list[ $item['id'] ] = $item;
					
                    file_put_contents( $this->upload_cloud_file, json_encode( $list, JSON_PRETTY_PRINT ) );
                }

                return true;
            }

            /**
            * A filter for implement can override if the URL structure is different
            */
            protected function manga_url_filter( $manga_url ){
                return $manga_url;
            }

            protected function manga_slug_filter( $manga_url ){
                return $manga_url;
            }

            protected function is_manga_listing_has_paged(){
                if( empty( $this->pagination_link_selector ) ){
                    return false;
                }

                return true;
            }



            public function upload_cloud(){
				if( ! class_exists('WP_MANGA_STORAGE') ) {
					wt_crawler_log('WP Manga Core is missing');
					wp_send_json_error('WP Manga Core is missing');
				}

				if( get_transient( "{$this->fname}_is_upload_cloud_running" ) ){
					if(time() > get_option("_transient_timeout_{$this->fname}_is_upload_cloud_running")){
						wt_crawler_log('Another Upload Cloud is running. Waiting');
						wp_send_json_error('Another Upload Cloud process is running. Wait for maximum ' . ini_get('max_execution_time') . ' seconds');
					}
                }
		
                set_transient( "{$this->fname}_is_upload_cloud_running", true, ini_get('max_execution_time') );

                // Use for remove in wt_error_log_die func
                $GLOBALS['running_transient'] = "{$this->fname}_is_upload_cloud_running";

                $list = $this->get_upload_cloud_list();
				
                if( empty( $list ) ){
					set_transient( "{$this->fname}_is_upload_cloud_running", false );
                    wp_send_json_success('Nothing to upload to cloud');
                }

                global $wp_manga_storage, $wp_manga_functions;

                // If a post id was specified
                if( !empty( $_POST['postID'] ) ){

                    $upload_list = [];

                    foreach( $list as $id => $c ){
                        if( strpos( $id, "{$_POST['postID']}-" ) === 0 ){
                            extract( $c );

                            $result = $wp_manga_storage->wp_manga_upload_single_chapter( $chapter_args, $extract, $extract_uri, $storage );

                            if( !empty( $result ) ){
                                unset( $list[ $id ] );
                                $is_edit = true;
                            }
                        }
                    }

                }else{
                    $number_chapters = $this->settings['cronjob']['number_chapters'];

                    $upload_list = array_slice( $list, 0, $number_chapters );

                    foreach( $upload_list as $id => $c ){

                        extract( $c );
						
						// make sure this chapter does not exist yet
						$existing_chapter = $wp_manga_functions->get_chapter_by_slug($c['chapter_args']['post_id'], $c['chapter_args']['chapter_slug']);
						
						if(!$existing_chapter || $existing_chapter['volume_id'] != $chapter_args['volume_id']){
							set_transient('webtoon_crawler_cloud_uploading', $id, 3600);
							wt_crawler_log('Upload to Cloud: ' . $id);
							$result = $wp_manga_storage->wp_manga_upload_single_chapter( $chapter_args, $extract, $extract_uri, $storage );
							
							delete_transient('webtoon_crawler_cloud_uploading');

							if( ! $result ) {
								wt_crawler_log('upload to cloud failed. Please check this chapter: ' . $c['chapter_args']['chapter_name'] . ' of manga: ' . $c['chapter_args']['post_id']);
								
								wt_crawler_log('As uploading to cloud failed, we upload to Local to bypass');
								$result = $wp_manga_storage->wp_manga_upload_single_chapter( $chapter_args, $extract, $extract_uri, 'local' );
								wt_crawler_log('Upload Local done: ' . var_export($result, true));
								if(!$result){
									wt_crawler_log('Upload Local failed, we bypass this chapter. You can do it manually later');
									$result = true;
								}
								
							} else {
								// delete local folder
								wt_crawler_log('Delete local folder: ' . $extract);
								wt_delete_files($extract);
							}
						}
                        
                        unset( $list[ $id ] );
                        $is_edit = true;
                    }
                }

                if( !empty( $is_edit ) ){
                    file_put_contents( $this->upload_cloud_file, json_encode( $list, JSON_PRETTY_PRINT ) );
                }

                set_transient( "{$this->fname}_is_upload_cloud_running", false );

                wp_send_json_success();

            }

            public function crawl(){

                // Set crawler log dir to restore crawler log
                $GLOBALS['wt_crawler_log_dir'] = $this->data_dir;
				
                if( get_transient( "{$this->fname}_crawler_not_run" ) ){
					// in some case this timeout is missing, we make sure it exists so the crawler does not stuck
					if(!get_option("_transient_timeout_{$this->fname}_crawler_not_run")){
						set_transient( "{$this->fname}_crawler_not_run", true, $this->settings['cronjob']['recurrence'] ? $this->settings['cronjob']['recurrence'] * 60 :  60  );
					}
                    return;
                }

                if( get_transient( "is_{$this->fname}_crawler_running" ) ){
                    return;
                }

                // Use for remove in wt_error_log_die func
                $GLOBALS['running_transient'] = "is_{$this->fname}_crawler_running";

                // Mark that there is crawler task is running on server
                set_transient( "is_{$this->fname}_crawler_running", true, ini_get('max_execution_time') );

                $is_manga_listing_has_paged = $this->is_manga_listing_has_paged();
				
                // If crawler is active, then fetch the manga listing
                if( !empty( $this->settings['active'] ) && (
                    ( $is_manga_listing_has_paged ) // fetch manga listing page every time script is run if manga listing page has pagination
                    || ( ( ! $is_manga_listing_has_paged ) && empty( $this->status['manga_list_fetched'] ) ) // only if it doesn't have pagination, then fetch it once and mark it by 'manga_list_fetched' status
                ) ){
                    $this->fetch_manga_listing();

                    if( ! $is_manga_listing_has_paged ){
                        $this->update_status( 'manga_list_fetched', true );
                        $return = true;
                    }

                }
				                
                // Mark that the crawler task is completed
                set_transient( "is_{$this->fname}_crawler_running", false );

                wt_crawler_log( '<strong>Crawler Finish</strong>' );

                // {$prefix}_crawler_not_run mark the time to run cron job
                set_transient( "{$this->fname}_crawler_not_run", true, $this->settings['cronjob']['recurrence'] ? $this->settings['cronjob']['recurrence'] * 60 :  60  );

            }

        }

    }
