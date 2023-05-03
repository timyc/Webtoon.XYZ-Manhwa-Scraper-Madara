<?php

    if( ! class_exists( 'WT_CRAWLER_IMPORT' ) ){

        class WT_CRAWLER_IMPORT{

            protected $name_selector       = '';
            protected $desc_selector       = '';
            protected $status_selector     = '';
            protected $thumb_selector      = '.summary_image img';
            protected $alter_name_selector = '';
            protected $type_selector       = '';
            protected $release_selector    = '';
            protected $author_selector     = '';
            protected $artist_selector     = '';
            protected $genre_selector      = '';
            protected $tag_selector        = '';
            protected $image_selector      = '';

            /**
         	 * Pick Data from Manga page then Create Manga Post and Fetch Chapter list
             * @param manga['url']
             * @param manga['name']
        	 */
            public function create_manga( $manga, $in_crawler_progress = true ){

                $html = $this->wt_get_site_html( $manga['url'] );

                if( empty( $html ) ){
                    error_log_die([
                        'function' => __FUNCTION__,
                        'message'  => "Cannot get content from {$manga['url']}",
                        'cancel'   => $in_crawler_progress,
                        'code'     => ERROR_GET_HTML
                    ]);
                }

                // If manga is blocked by country
                $block_warning = $html->find('#page .warning', 0);

                if( !empty( $block_warning ) && strpos( $block_warning->plaintext, 'has been licensed, it is not available' ) !== false ){
                    return new WP_Error( 'blocked' );
                }

                if( empty( $manga['name'] ) ){
                    $manga['name'] = $this->get_manga_name( $html );
                    $manga['slug'] = $this->manga_slug_filter( $manga['url'] );
                }

                $post_args = array(
                    'manga_import_slug' => $manga['slug'],
                    'title'             => $manga['name'],
                    'post_status'       => isset( $this->settings['import']['status'] ) ? $this->settings['import']['status'] : 'pending',
                    'description'       => $this->get_manga_desc( $html ),
                    'thumb'             => $this->get_manga_thumb( $html ),
                    'status'            => $this->get_manga_status( $html ),
                    'altername'         => strip_tags( $this->get_manga_alter( $html ) ),
                    'type'              => strip_tags( $this->get_manga_type( $html ) ),
                    'release'           => strip_tags( $this->get_manga_release( $html ) ),
                    'authors'           => strip_tags( $this->get_manga_authors( $html ) ),
                    'artists'           => strip_tags( $this->get_manga_artists( $html ) ),
                );

                if( !empty( $this->settings['fetch']['genres'] ) ){
                    $post_args['genres'] = strip_tags( $this->get_manga_genres( $html ) );
                }else{
                    $post_args['genres'] = '';
                }

                if( !empty( $this->settings['fetch']['views'] ) ){
                    $post_args['views'] = strip_tags( $this->get_manga_views( $html ) );
                }

                if( !empty( $this->settings['fetch']['ratings'] ) ){
                    $post_args['ratings'] = $this->get_manga_ratings( $html ) ;
                }

                if( !empty( $this->settings['fetch']['tags'] ) ){
                    $post_args['tags'] = strip_tags( $this->get_manga_tags( $html ) );
                }else{
                    $post_args['tags'] = '';
                }
				
				//$post_args['chapterType'] = 'text';

                $post_id = $this->create_post( $post_args );

                return $post_id;

            }

            protected function fetch_chapters( $chapter_list, $post_id, $is_update ){

                global $wp_manga_storage, $wp_manga_volume, $wp_manga_chapter;

                if( !empty( $this->status['current_volume'] ) && $this->status['current_volume'] != 'NO-VOLUME' && $this->status['current_volume'] != 'No Volume' && isset( $chapter_list[ $this->status['current_volume'] ] ) ){
                    $volume = $chapter_list[ $this->status['current_volume'] ];
                }else{
                    // If current volume doesn't exist then get the first volume
                    $volume = $chapter_list[ key( $chapter_list ) ];
                }
				
				if($volume['name'] != 'NO-VOLUME' && $volume['name'] != 'No Volume'){
					$find_vols = $wp_manga_volume->get_volumes(
						array(
							'post_id'     => $post_id,
							'volume_name' => 'NO-VOLUME'
						)
					);
					
					if( !empty( $find_vols ) ){
						$volume_id = $find_vols[0]['volume_id'];
						$is_volume_created = true;

					}else{
						// If there isn't current volume or the current volume isn't the current volume in setting then this is a new volume.
						$volume_id = $wp_manga_storage->create_volume( $volume['name'], $post_id );
					}
				} else {
					$is_volume_created = true;
					$volume_id = 0;
				}

                wt_crawler_log( '....Current Volume : ' . $volume['name'] );

                if( empty( $volume['chapters'] ) ){
                    // If this chapter doesn't exist then this is last chapter, and update volume
                    wt_crawler_log( "....{$volume['name']} doesn't have any chapter, go to next volume" );
					
					$all_vols = array_keys($chapter_list);
					$index = array_search($volume['name'], $all_vols);
					if(isset($all_vols[$index+1])){
						$this->update_status('current_volume', $all_vols[$index+1]);
					} else {
						$this->update_status( 'current_volume', '' );
						wt_crawler_log( ".....Cannot find any chapters and no more volume. Exit." );
						return false;
					}

                    return true;
                }
				
				// If this volume is already created
                if( !empty( $is_volume_created ) ){

                    // Find all chapters created in this volume
                    $vol_created_chaps = $wp_manga_volume->get_volume_chapters( $post_id, $volume_id );

                    if( !empty( $vol_created_chaps ) ){

                        $vol_created_chaps = array_column( $vol_created_chaps, 'chapter_name' );
						
                        foreach( $volume['chapters'] as $index => $chapter ){							
                            if( in_array( strtolower(explode(' - ', $chapter['name'])[0]), array_map('strtolower', $vol_created_chaps) ) === false ){
                                $cur_chap_index = $index;
                                break;
                            }
                        }
                    }else{

                        // If there is no chaps created on this volume, then reset the cur chap index to 0.
                        $cur_chap_index = 0;
                    }
                }else{

                    // If this volume is new then cur chap index should be 0
                    $cur_chap_index = 0;
                }
				
                if( isset( $cur_chap_index ) ){

                    $cur_chap = $volume['chapters'][ $cur_chap_index ];

                    wt_crawler_log( ".....Current Chapter : {$cur_chap['name']}" );
					
					if($cur_chap['name'] == ''){
						// invalid name, for some reason
						// ignore this manga
						return false;
					}
					
					$title_first_part_index = strpos($cur_chap['name'], ' - ');
                    if($title_first_part_index !== false){
                        $cur_chap['name'] = trim(substr($cur_chap['name'], 0, $title_first_part_index));
                    }
					
					$resp = $this->fetch_single_chapter( $cur_chap, $volume_id, $post_id );
					
					wt_crawler_log('Fetch single chapter result: ' . var_export($resp, true));
					
                    // If manga is blocked by country
                    if( is_wp_error( $resp ) && $resp->get_error_code() == 'blocked' ){
                        return $resp;
                    }
                }

                // Check if this is the last chapter
                if( ! isset( $cur_chap_index ) || ! isset( $volume['chapters'][ $cur_chap_index + 1 ] ) ){
                    // If doesn't exist next chapter index then this is last chapter, and update volume
					$all_vols = array_keys($chapter_list);
					$index = array_search($this->status['current_volume'], $all_vols);
					if(isset($all_vols[$index+1])){
						$this->update_status('current_volume', $all_vols[$index+1]);
					} else {
						$this->update_status( 'current_volume', '' );
					}
                    $is_last_chap = true;
                }else{
                    // Move current volume cursor to this volume
                    $this->update_status( 'current_volume', $volume['name'] );
                }
				
                if( $is_update && isset($cur_chap_index)){
                    $this->put_update_log( array(
                        'manga'   => get_the_title( $post_id ),
                        'volume'  => 'NO-VOLUME',
                        'chapter' => $volume['chapters'][ $cur_chap_index ]['name'],
                    ) );
                }

                // Get the last key of chapter list and Check if manga fetching is completed
                end( $chapter_list );
                $last_volume = key( $chapter_list );
                reset( $chapter_list );

                // If it's last volume and there isn't any next chapter
                if( $last_volume == $volume['name'] && !empty( $is_last_chap ) ){
					return false;
                }

                return true;

            }

            public function fetch_single_chapter( $chapter, $volume_id, $post_id ){
                // break request if it takes more than 100 seconds (CloudFlare limit).
                $timestamp = time();
                
                // Prepare
                global $wp_manga, $wp_manga_storage;
                $uniqid = $wp_manga->get_uniqid( $post_id );
				
                $slugified_name = $wp_manga_storage->slugify( $chapter['name'] );
				
				// check if chapter exists to prevent duplication
				global $wp_manga_chapter;
				$chapter_2 = $wp_manga_chapter->get_chapter_by_slug( $post_id, $slugified_name );
				if($chapter_2 && $chapter_2['volume_id'] == $volume_id && strcasecmp($chapter_2['chapter_name'], $chapter['name']) == 0){
					return true;
				}
				
				$chapter_images = $this->get_chapter_images( $chapter['url'] );
				#wt_crawler_log($chapter_images);
				
                // If chapter is blocked by current country
                if( is_wp_error( $chapter_images ) && $chapter_images->get_error_code() == 'blocked' ){
                    return $chapter_images;
                }                

                // Download images
                $extract = WP_MANGA_DATA_DIR . $uniqid . '/' . $slugified_name;
                $extract_uri = WP_MANGA_DATA_URL;

                if( ! file_exists( $extract ) ){
                    if( ! wp_mkdir_p( $extract ) ){
                        error_log_die([
                            'function' => __FUNCTION__,
                            'message'  => "Cannot make dir $extract",
                            'cancel'   => true,
                        ]);
                    }
                }
				
				global $need_cookies_response;
				$need_cookies_response = false;
                
                // check if we already download some images in advance
                $existing_images = get_post_meta($post_id, '_crawler_' . $slugified_name . '_image_count', true);
                if(!$existing_images) $existing_images = 0;
                
                $idx = 1;
		$isadultstuff = get_post_meta($post_id, 'manga_adult_content', true);
                foreach( $chapter_images as $image ){
                    if($idx > $existing_images){
                        
                        $data = wt_curl_get_contents( $image );
                        
                        if( isset( $http_response_header[0] ) && $http_response_header[0] == 'HTTP/1.1 404 Not Found' ){
                            continue;
                        }

                        if( $data === false ){
                            error_log("Cannot get content from $image");
                            /*
                            error_log_die([
                                'function' => __FUNCTION__,
                                'message'  => "Cannot get content from $image",
                                'cancel'   => true,
                                'code'     => ERROR_GET_HTML
                            ]);*/
                            
                            continue;
                        }

                        $pathinfo = pathinfo( $image );
			$file_name = urldecode($this->url_file_name_filter( $pathinfo['basename'] ));
			$resp = file_put_contents( "{$extract}/{$file_name}", $data );
            // watermark remover, if someone actually sees this they can put this to better use
			/*try {
				$howmany = count($chapter_images);
			
                        if (($idx == $howmany - 2 || $idx == $howmany - 1 || $idx == $howmany) && $isadultstuff == '') { // we only check the last 3 images
                            $img1 = new imagick("{$extract}/{$file_name}");
                            $up_dir = wp_upload_dir();
                            if ($img1->getImageHeight() >= 150) {
                                // try white bg first
                                $img2 = new imagick("{$extract}/{$file_name}"); // copy of the image
                                $img2->cropImage($img2->getImageWidth() , $img2->getImageHeight() - 200 , 0, 0); // cut the disgusting watermark from the image
				$img3 = new imagick($up_dir['basedir'] . "/2022/02/disgustingshit.jpg"); // this is the logo to replace
				$img4 = new imagick($up_dir['basedir'] . "/2022/02/whitepoggers.jpg");
                                $img1->cropImage($img1->getImageWidth() , $img1->getImageHeight() , 0, $img1->getImageHeight() - 150);

                                $img5 = new imagick(); // white background with digusting watermark pasted over it
                                $img5->newImage($img1->getImageWidth() , $img1->getImageHeight() , new ImagickPixel('white'));
                                $img5->setImageFormat('jpeg');
                                $img5->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
                                $img5->setImageArtifact('compose:args', "1,0,-0.5,0.5");
                                $img6 = clone $img5;
				$img5->compositeImage($img3, Imagick::COMPOSITE_MATHEMATICS, ($img5->getImageWidth() / 2) - ($img3->getImageWidth() / 2), ($img5->getImageHeight() / 2) - ($img3->getImageHeight() / 2) - 2.5);
				$img6->compositeImage($img4, Imagick::COMPOSITE_MATHEMATICS, ($img5->getImageWidth() / 2) - ($img4->getImageWidth() / 2), ($img5->getImageHeight() / 2) - ($img4->getImageHeight() / 2) - 2.5);

                                $cmpresult = $img1->compareImages($img5, Imagick::METRIC_MEANSQUAREERROR);

                                if ($cmpresult[1] < 0.002) {
					$img2->addImage($img6);
					$img2->resetIterator();
					$img2->setImageFormat('jpeg');
					$wombocombo = $img2->appendImages(true);
					$wombocombo->setImageFormat('jpeg');
					$resp = $wombocombo->writeImage( "{$extract}/{$file_name}");
                                }
                                else {
                                    // time to try black bg
                                    $img3 = new imagick($up_dir['basedir'] . "/2022/02/blackshit.jpg"); // this is the logo to replace
					$img4 = new imagick($up_dir['basedir'] . "/2022/02/blackpoggers.jpg");
                                    $img5 = new imagick(); // white background with digusting watermark pasted over it
                                    $img5->newImage($img1->getImageWidth() , $img1->getImageHeight() , new ImagickPixel("rgba(0,0,0,0)"));
                                    $img5->setImageFormat('jpeg');
                                    $img5->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
                                    $img5->setImageArtifact('compose:args', "1,0,-0.5,0.5");
                                    $img6 = clone $img5;
   				    $img5->compositeImage($img3, Imagick::COMPOSITE_MATHEMATICS, ($img5->getImageWidth() / 2) - ($img3->getImageWidth() / 2), ($img5->getImageHeight() / 2) - ($img3->getImageHeight() / 2) - 2.5);
				    $img6->compositeImage($img4, Imagick::COMPOSITE_MATHEMATICS, ($img5->getImageWidth() / 2) - ($img4->getImageWidth() / 2), ($img5->getImageHeight() / 2) - ($img4->getImageHeight() / 2) - 2.5);
                                    $cmpresult = $img1->compareImages($img5, Imagick::METRIC_MEANSQUAREERROR);
                                    if ($cmpresult[1] < 0.003) {
                                        $img2->addImage($img6);
					$img2->resetIterator();
					$img2->setImageFormat('jpeg');
					$wombocombo = $img2->appendImages(true);
					$wombocombo->setImageFormat('jpeg');
					$resp = $wombocombo->writeImage( "{$extract}/{$file_name}");
                                    }
                                }

                            }
			}

                    }
                    catch(Exception $e) {
			    wt_crawler_log("An error occurred when trying to remove cancer.");
				$resp = file_put_contents( "{$extract}/{$file_name}", $data );
                    }*/
                        if( $resp && empty( $has_image ) ){
                            $has_image = true;
                        }
                        
                        if(time() - $timestamp >= 300){
                            // break request after 90 seconds to prevent CloudFlare limit
                            
                            update_post_meta($post_id, '_crawler_' . $slugified_name . '_image_count', $idx);
                            
                            error_log_die([
                                'function' => __FUNCTION__,
                                'message'  => "Break request into smaller calls to prevent CloudFlare limit",
                                'cancel'   => true,
                                'code'     => ERROR_CLOUD_FLARE
                            ]);
			}
                    }
                    
                    $idx++;
                }

                if( empty( $has_image ) ){
                    // if there is no image then add a dummy image as placeholder for chapter.
                    copy( "{$this->plugin_dir}/images/image-placeholder.jpg", "{$extract}/image-placeholder.jpg" );
                }

                // Create Chapter
                $chapter_args = array(
                	'post_id'             => $post_id,
                	'volume_id'           => $volume_id,
                	'chapter_name'        => $chapter['name'],
                	'chapter_name_extend' => $chapter['extend_name'],
                	'chapter_slug'        => $slugified_name,
		);
		/*$newtime = current_time('mysql');
                $post_args = array(
                    'ID'            => $post_id,
                    'post_date'     => $newtime,
                    'post_date_gmt' => get_gmt_from_date( $newtime ),
                );
		wp_update_post( $post_args );*/
		global $wpdb;
                $newtime = current_time('mysql');
                $wpdb->update('wp_posts', array('post_modified' => $newtime, 'post_modified_gmt' => get_gmt_from_date( $newtime )), array('ID' => $post_id));
                $storage = !empty( $this->settings['import']['storage'] ) ? $this->settings['import']['storage'] : 'local';
				
				// check again after download images
				$chapter_2 = $wp_manga_chapter->get_chapter_by_slug( $post_id, $slugified_name );
				if($chapter_2 && $chapter_2['volume_id'] == $volume_id && strcasecmp($chapter_2['chapter_name'], $chapter['name']) == 0){
					return true;
				}
				
                global $is_fetching_single_manga;
                if( $storage == 'local' || (isset($is_fetching_single_manga) && $is_fetching_single_manga)){
                    //upload chapter
                    $result = $wp_manga_storage->wp_manga_upload_single_chapter( $chapter_args, $extract, $extract_uri, $storage );
					
                    return $result;
                }else{
                    // If it's was not local upload, then push to list to upload to server later
                    $this->put_upload_cloud_list(
                        array(
                            'id'           => "{$post_id}-{$volume_id}-{$slugified_name}",
                            'extract'      => $extract,
                            'extract_uri'  => $extract_uri,
                            'storage'      => $storage,
                            'chapter_args' => $chapter_args
                        )
                    );

                    return true;
                }

            }

            protected function get_page_images( $page_url ){
				
                $html = $this->wt_get_site_html( $page_url );

                if( empty( $html ) ){
                    error_log_die([
                        'function' => __FUNCTION__,
                        'message'  => "Cannot get content from $page_url",
                        'cancel'   => true,
                        'code'     => ERROR_GET_HTML
                    ]);
                }

                $images = $html->find( $this->image_selector );
				
                if( empty( $images ) ){
                    return false;
                }

                $images_url = array();

                foreach( $images as $image ){
                    $images_url[] = $image->src;
                }

                return $images_url;

            }

            protected function get_manga_name( $html ){

                if( !empty( $this->name_selector ) ){
                    $name = $html->find( $this->name_selector );
                }

                return !empty( $name ) ? str_replace(['&#8217;', '&#039;', '&#038;'], ["'", "'", "&"], $name[0]->plaintext) : '';

            }

            protected function get_manga_desc( $html ){

                if( !empty( $this->desc_selector ) ){
                    $desc = $html->find( $this->desc_selector );
                }

                $desc = !empty( $desc ) ? $desc[0]->plaintext : '';

                return $desc;
            }

            public function get_manga_thumb( $html ){

                if( !empty( $this->thumb_selector ) ){
                    $thumb = $html->find( $this->thumb_selector );
                }

                $thumb = !empty( $thumb ) ? $thumb[0]->{'data-src'} : null;

                return $thumb;
            }

            protected function get_manga_status( $html ){

                if( !empty( $this->status_selector ) ){
                    $status = $html->find( $this->status_selector );
                }

                $status = !empty( $status ) ? $this->status_filter( $status[0]->plaintext ) : '';

                return $status;
            }

            protected function get_manga_alter( $html ){

                if( !empty( $this->alter_name_selector ) ){
                    $alter_name = $html->find( $this->alter_name_selector );
                }

                $alter_name = !empty( $alter_name ) ? $alter_name[0]->plaintext : '';

                return $alter_name;
            }

            protected function get_manga_type( $html ){

                if( !empty( $this->type_selector ) ){
                    $type = $html->find( $this->type_selector );
                }

                $type = !empty( $type ) ? $type[0]->plaintext : '';

                return $type;
            }

            protected function get_manga_release( $html ){

                if( !empty( $this->release_selector ) ){
                    $release = $html->find( $this->release_selector );
                }

                $release = !empty( $release ) ? $release[0]->plaintext : '';

                return $release;
            }

            protected function get_manga_authors( $html ){

                if( !empty( $this->author_selector ) ){
                    $author = $html->find( $this->author_selector );
                }

                $author = !empty( $author ) ? $author[0]->plaintext : '';

                return $author;
            }

            protected function get_manga_artists( $html ){

                if( !empty( $this->artist_selector ) ){
                    $artist = $html->find( $this->artist_selector );
                }

                $artist = !empty( $artist ) ? $artist[0]->plaintext : '';

                return $artist;
            }

            protected function get_manga_genres( $html ){

                if( !empty( $this->genres ) ){
                    $genres = $html->find( $this->genre_selector );
                    $genres = !empty( $genres ) ? $genres[0]->plaintext : '';
                }

                $genres = !empty( $genres ) ? $genres[0]->plaintext : '';

                return $genres;
            }

            protected function get_manga_tags( $html ){

                if( !empty( $this->tag_selector ) ){
                    $tags = $html->find( $this->tag_selector );
                }

                $tags = !empty( $tags ) ? $tags[0]->plaintext : '';

                return $tags;

            }

            public function get_manga_ratings( $html ){

                if( !empty( $this->rating_selector ) ){
                    $ratings = $html->find( $this->rating_selector );
                }

                $ratings = !empty( $ratings ) ? $ratings[0]->plaintext : '';

                return $ratings;

            }

            protected function get_manga_views( $html ){

                if( !empty( $this->view_selector ) ){
                    $views = $html->find( $this->view_selector );
                }

                $views = !empty( $views ) ? $views[0]->plaintext : '';

                return $views;

            }

            private function create_post( $args ){

                //1. insert post main data
                $post_args = array(
                    'post_title'   => !empty( $args['title'] ) ? $args['title'] : '',
                    'post_content' => !empty( $args['description'] ) ? $args['description'] : '',
                    'post_type'    => 'wp-manga',
                    'post_status'  => isset( $args['post_status'] ) ? $args['post_status'] : 'pending',
                    'meta_input' => array(
                        'crawl_source' => 'WT',
                    ),
                );
				
                $post_id = wp_insert_post( $post_args );

                if( ! $post_id && is_wp_error( $post_id ) ){
                    error_log_die([
                        'function' => __FUNCTION__,
                        'message'  => "Insert new manga post failed. Manga name : {$args['title']}",
                        'cancel'   => true,
                    ]);
                }

                //2. add metadata
                $thumb_id = $this->upload_featured_image( $args['thumb'], $post_id );

                $meta_data = array(
                    '_manga_import_slug'     => $args['manga_import_slug'],
                    '_thumbnail_id'          => $thumb_id,
                    '_wp_manga_alternative'  => isset( $args['altername'] ) ? $args['altername'] : '',
                    '_wp_manga_type'         => isset( $args['type'] ) ? $args['type'] : '',
                    '_wp_manga_status'       => isset( $args['status'] ) ? $args['status'] : '',
                    '_wp_manga_chapter_type' => isset( $args['chapterType'] ) ? $args['chapterType'] : 'manga',
                );

                foreach( $meta_data as $key => $value ){
                    if( !empty( $value ) ){
                        update_post_meta( $post_id, $key, $value );
                    }
                }

                //3.update terms
                $manga_terms = array(
                    'wp-manga-release' => isset( $args['release'] ) ? $args['release'] : null,
                    'wp-manga-author'      => isset( $args['authors'] ) ? $args['authors'] : null,
                    'wp-manga-artist'      => isset( $args['artists'] ) ? $args['artists'] : null,
                    'wp-manga-genre'       => isset( $args['genres'] ) ? $args['genres'] : '',
                    'wp-manga-tag'         => isset( $args['tags'] ) ? $args['tags'] : null,
                );

                foreach( $manga_terms as $tax => $term ){
                    $resp = $this->add_manga_terms( $post_id, $term, $tax );
                }

                // 4. Update ratings and views
                if( !empty( $args['views'] ) ){
                    $this->update_post_views( $post_id, $args['views'] );
                }
                if( !empty( $args['ratings'] ) ){
                    $this->update_post_ratings( $post_id, $args['ratings'] );
                }

                return $post_id;

            }

            private function upload_featured_image( $thumb_url, $post_id ){

                if( !empty( $thumb_url ) ){
                    return wt_wp_mcl_e_upload_file( $thumb_url, $post_id );
                }

                return false;

            }

            private function add_manga_terms( $post_id, $terms, $taxonomy ){

                // Add Taxonomy from Crawler Task Settings
                if( $taxonomy == 'wp-manga-tag' && !empty( $this->settings['import']['tags']  ) ){
                    $terms .= ',' . $this->settings['import']['tags'];
                }

                $terms = explode(',', $terms);

                if( empty( $terms ) ){
                    return false;
                }

                $taxonomy_obj = get_taxonomy( $taxonomy );

                if( $taxonomy_obj->hierarchical ){

                    $output_terms = array();

                    foreach( $terms as $current_term ){

                        if( empty( $current_term ) ){
                            continue;
                        }

                        //check if term is exist
                        $term = term_exists( $current_term, $taxonomy );

                        //then add if it isn't
                        if( ! $term || is_wp_error( $term ) ){
                            $term = wp_insert_term( $current_term, $taxonomy );
                            if( !is_wp_error( $term ) && isset( $term['term_id'] ) ){
                                $term = intval( $term['term_id'] );

                            }else{
                                continue;
                            }
                        }else{
                            $term = intval( $term['term_id'] );
                        }

                        $output_terms[] = $term;
                    }

                    $terms = $output_terms;
                }

                if( $taxonomy == 'wp-manga-genre' && !empty( $this->settings['import']['genres'] ) ){
                    $terms = array_merge( $terms, $this->settings['import']['genres'] );
                }

                $resp = wp_set_post_terms( $post_id, $terms, $taxonomy );

                return $resp;

            }

            private function update_post_views( $post_id, $views ){

                $month = date('m');

                update_post_meta( $post_id, '_wp_manga_month_views', array(
                    'date' => $month,
                    'views' => $views
                ) );
				
				update_post_meta( $post_id, '_wp_manga_views', $views );
				
				$new_year_views = array( 'views' => $views, 'date' => date('y') );
				update_post_meta( $post_id, '_wp_manga_year_views', $new_year_views );
				update_post_meta( $post_id, '_wp_manga_year_views_value', $views ); // clone to sort by value

            }

            public function update_post_ratings( $post_id, $ratings = array() ){

                if( empty( $ratings ) || !isset( $ratings['avg'] ) || !isset( $ratings['numbers'] ) ){
                    return false;
                }

                extract( $ratings );

                $totals = intval( (float)trim($avg) * (float)$numbers );
                $int_avg_totals = intval( $avg ) * $numbers;

                $above_avg_numbers = $totals - $int_avg_totals;
                $int_avg_numbers = $numbers - $above_avg_numbers;

                $rates = array();

                for( $i = 1; $i <= $above_avg_numbers; $i++ ){
                    $rates[] = intval( $avg + 1 );
                }

                for( $i = 1; $i <= $int_avg_numbers; $i++ ){
                    $rates[] = intval( $avg );
                }

                update_post_meta( $post_id, '_manga_avarage_reviews', $avg );
                update_post_meta( $post_id, '_manga_reviews', $rates );

                return true;
            }

            public function get_storage_setting(){

                return isset( $this->settings['import']['storage'] ) ? $this->settings['import']['storage'] : 'local';

            }

        }
    }
