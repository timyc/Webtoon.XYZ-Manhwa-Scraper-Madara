<?php

    class WT_CRAWLER_SINGLE extends WT_CRAWLER_IMPLEMENT{

        public function __construct(){
            parent::__construct();

			add_action( 'wp_ajax_wt_create_manga_post', array( $this, 'create_manga_post' ) );
            add_action( 'wp_ajax_wt_fetch_chapters', array( $this, 'fetch_manga_chapters' ) );
            add_action( 'wp_ajax_wt_fetch_single_chapter', array( $this, 'fetch_manga_single_chapter' ) );
            add_action( 'wp_ajax_wt_remove_inserted', array( $this, 'remove_inserted_manga' ) );

        }

		public function create_manga_post( $url = ''){
			if(!$url) $url = isset($_POST['url']) ? $_POST['url'] : '';
			
			if( empty( $url ) ){
                wp_send_json_error([
                    'message' => esc_html__("Missing URL", WP_MCL_TD)
                ]);
			}

            $GLOBALS['is_fetching_single_manga'] = true;
			
			$html = $this->wt_get_site_html($url);
			$manga_name = $this->get_manga_name($html);
			
			$post_id = wt_find_manga_post( $manga_name );
			
			if(!$post_id){
				$post_id = wt_get_manga_post_by_import_slug( $this->manga_slug_filter($url), $manga_name );
			}

            if(!$post_id){
				$post_id = $this->create_manga( [ 'url' => $url ] );
			}

            if( $post_id === null ){
                wp_send_json_error([
                    'message' => esc_html__( "This manga is blocked by country. Please use Proxy and try again", WP_MCL_TD )
                ]);
            }

            if( $post_id ){
                wp_send_json_success( $post_id );
            }

            wp_send_json_error( esc_html__('Cannot crawl manga. Please try again later', WP_MCL_TD ) );
		}

        public function fetch_manga_chapters(){

            if( empty( $_POST['url'] ) ){
                wp_send_json_error([
                    'message' => esc_html__("Missing URL", WP_MCL_TD)
                ]);
			}

            // Mark to detect if it's fetching single manga or crawler
            $GLOBALS['is_fetching_single_manga'] = true;

            $html = $this->wt_get_site_html( $_POST['url'] );

            // Fetch Manga chapters list
            $chapter_list = $this->fetch_chapter_list( $html );

            $slug = get_post_meta( $_POST['postID'], '_manga_import_slug', true );

            $manga_json = "{$this->manga_dir}/{$slug}.json";

            // Put chapters list to a json file
            file_put_contents( $manga_json, json_encode( $chapter_list, JSON_PRETTY_PRINT ) );

            wp_send_json_success( array_values( $chapter_list ) );

        }

        public function fetch_manga_single_chapter(){
            global $wp_manga_storage, $wp_manga_volume, $wp_manga_chapter;

            $GLOBALS['is_fetching_single_manga'] = true;
			
			$volume_id = 0;
			if(isset($_POST['volume'])){
				$vol_name = 'NO-VOLUME';
				
				if($vol_name != 'NO-VOLUME'){

					$find_vols = $wp_manga_volume->get_volumes(
						array(
							'post_id'     => $_POST['postID'],
							'volume_name' => 'NO-VOLUME'
						)
					);

					if( !empty( $find_vols ) ){
						$volume_id = $find_vols[0]['volume_id'];
						$is_volume_created = true;
					}else{
						// If there isn't current volume or the current volume isn't the current volume in setting then this is a new volume.
						$volume_id = $wp_manga_storage->create_volume( $_POST['volume'], $_POST['postID'] );
					}
				}
			}
			
			$resp = 1;
			
			if(isset($_POST['chapter'])){
				$resp = $this->fetch_single_chapter( $_POST['chapter'], $volume_id, $_POST['postID'] );
			}

            // If manga is blocked by country
            if( $resp === null ){
                wp_send_json_error([
                    'message' => esc_html__( "This manga is blocked by country. Please use Proxy and try again", WP_MCL_TD )
                ]);
            }

            wp_send_json_success( get_permalink( $_POST['postID'] ) );

        }

        public function remove_inserted_manga(){

            if( empty( $_POST['postID'] ) ){
                wp_send_json_error([
                    'message' => esc_html__("Missing post id", WP_MCL_TD)
                ]);
			}

            if( wp_delete_post( $_POST['postID'], true ) ){
                wp_send_json_success();
            }else{
                wp_send_json_error();
            }

        }

    }

    new WT_CRAWLER_SINGLE();
