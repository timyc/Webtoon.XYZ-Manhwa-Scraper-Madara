<?php

    /**
 	 * Manga Crawler Tasks Import Settings Metabox template
	 */

    if( ! defined('ABSPATH') ){
        exit;
    }

    $ajax_url = admin_url( 'admin-ajax.php' );
    $loading = get_admin_loading_icon();

?>

<div class="setting-section">
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Manga URL', WP_MCL_TD); ?></label>
                </th>
                <td>
                    <input type="text" name="single-manga-url" id="single-manga-url" class="regular-text">
                    <button class="button button-primary" type="button" id="crawl-single-manga"><?php esc_html_e('Crawl', WP_MCL_TD ); ?></button>
                    <p class="description">
                        <?php esc_html_e('Crawl Single Manga might take several minutes to complete. Please stay tune and do not exit before it\'s done.') ?>
                    </p>
                    <p class="warning">

                    </p>
                </td>
            </tr>
            <tr id="progressing" style="display:none;">
                <th scope="row">
                    <label>
                        <?php esc_html_e('Progress', WP_MCL_TD); ?>
                    </label>
                </th>
                <td>
                    <p class="description">
                        <?php echo $loading; ?>
                        <span class="text"></span>
                    </p>
                </td>
            </tr>
            <tr id="chapters" style="display:none;">
                <th scope="row">
                    <label><?php esc_html_e('Chapters', WP_MCL_TD); ?></label>
                </th>
                <td>
                    <ul id="chapters-list">
                    </ul>
                </td>
            </tr>
        </tbody>
    </table>

    <script type="text/javascript">
        $ = jQuery;

            var steps = <?php echo json_encode( array(
                'create_post'          => esc_html__( 'Creating Manga Post...', WP_MCL_TD ),
                'fetch_chapters'       => esc_html__( 'Fetching Chapters List...', WP_MCL_TD ),
                'fetch_single_chapter' => esc_html__( 'Fetching Single Chapter...', WP_MCL_TD ),
                'success'              => esc_html__( 'Crawl Manga Successfully!', WP_MCL_TD ),
                'upload_cloud'         => esc_html__( 'Uploading to cloud server...', WP_MCL_TD ),
            ) ); ?>

            var chapList = $('#chapters-list');

            var spinnerGIF = '<?php echo $loading; ?>',
                successIcon = '<span class="dashicons dashicons-yes"></span>';

            var errorMsg = $('p.warning');

            var progressing = $('#progressing')
                chapters = $('#chapters');


            $(document).on( 'click', '#crawl-single-manga', function(e){
                e.preventDefault();

                $(this).prop('disabled', true);

                resetFetch();

                var loadingMsg = $('#progressing p.description > span');

                errorMsg.empty();

                var mangaURL = $('#single-manga-url').val();

                var postID;

                if( mangaURL.indexOf('http://www.webtoon.xyz/read') !== -1 || mangaURL.indexOf('https://www.webtoon.xyz/read') !== -1 ){
                    $.ajax({
                        method : 'POST',
                        url : "<?php echo esc_url( $ajax_url ); ?>",
                        data : {
                            action : 'wt_create_manga_post',
                            url : mangaURL
                        },
                        beforeSend: function(){
                            progressing.show();
                            loadingMsg.text( steps.create_post );
                        },
                        success : function( response ){
                            if( response.success ){

                                postID = response.data;

                                fetchChapterLists( postID, mangaURL );

                            }
                        },
                        complete : function( xhr ){
                            if( typeof xhr.responseJSON == 'undefined' || ( typeof xhr.responseJSON !== 'undefined' && xhr.responseJSON.success == false ) ){
                                errorMsg.text( "<?php esc_html_e('An error has occurred. Please try again.', WP_MCL_TD ); ?>" );

                                if( typeof xhr.responseJSON.data.message !== 'undefined' ){
                                    errorMsg.append('<br><span>' + xhr.responseJSON.data.message + '</span>');
                                }

                                endFetching();
                            }
                        }
                    });
                }else{
                    errorMsg.text( "<?php esc_html_e('Invalid URL', WP_MCL_TD); ?>" );
                    endFetching();
                }
            });

            function fetchChapterLists( postID, mangaURL ){

                var loadingMsg = $('#progressing p.description > span');

                $.ajax({
                    method : 'POST',
                    url : "<?php echo esc_url( $ajax_url ); ?>",
                    data : {
                        action: 'wt_fetch_chapters',
                        url:    mangaURL,
                        postID: postID
                    },
                    beforeSend : function(){
                        loadingMsg.text( steps.fetch_chapters );
                    },
                    success : function( response ){
                        if( response.success ){

                            chapters.show();

                            $.each( response.data, function( vIndex, volume ){
                                var appendHTML = '';

                                appendHTML = '<li>' + volume.name;

                                if( volume.chapters.length !== 0 ){
                                    appendHTML += '<ul>';

                                    $( volume.chapters ).each( function( cIndex, chapter ){
                                        appendHTML += '<li data-index="' + vIndex + cIndex + '"><div class="mark"></div>' + chapter.name;

                                        if( chapter.extend_name !== '' ){
                                            appendHTML += ' - ' + chapter.extend_name + '</li>';
                                        }
                                    } );

                                    appendHTML += '</ul>';

                                }

                                appendHTML += '</li>';

                                chapList.append( appendHTML );

                            });

                            loadingMsg.text( steps.fetch_single_chapter );

                            fetchSingleChapter( postID, response.data, 0, 0 );

                        }else if( response.data.code == <?php echo ERROR_GET_HTML; ?> ){

                            // If it's error when trying to get content then repeat immediately
                            fetchChapterLists( postID );

                            if( loadingMsg.find( '#loading-error' ).length > 0 ){
                                loadingMsg.find( '#loading-error' ).text( response.data.message );
                            }else{
                                loadingMsg.append( '<span id="loading-error">' + response.data.message + '</span>' );
                            }

                        }else if( response.data.code == <?php echo ERROR_CLOUD_FLARE; ?> ){

                            // Of if it's CloudFlare block the request, then wait for 5seconds to restart
                            if( loadingMsg.find( '#loading-error' ).length > 0 ){
                                loadingMsg.find( '#loading-error' ).text( response.data.message + '. Trying to re-fetch after 5 seconds' );
                            }else{
                                loadingMsg.append( '<span id="loading-error">' + response.data.message + '. Trying to re-fetch after 5 seconds</span>' );
                            }

                            setTimeout( fetchChapterLists, 5000, postID );

                        } else {
                            progressing.hide();
                            errorMsg.text( response.data.message );
                        }
                    },
                    complete : function( xhr, status ){
                        var response = xhr.responseJSON;

                        if( typeof response == 'undefined' || ( typeof response !== 'undefined' && response.success == false && response.data.code != <?php echo ERROR_GET_HTML; ?> && response.data.code != <?php echo ERROR_CLOUD_FLARE; ?> )  ){
                            errorMsg.text( "<?php esc_html_e('An error has occurred. Please try again.', WP_MCL_TD ); ?>" );

                            endFetching();

                            if( typeofresponse !== 'undefined' && typeof response.data.message !== 'undefined' ){
                                errorMsg.append('<br><span>' + response.data.message + '</span>');
                            }
                        }
                    }
                });

            }

            function fetchSingleChapter( postID, data, vIndex, cIndex ){

                console.log( 'Volume Index : ' + vIndex + ' | Chapter Index : ' + cIndex );

                var thisChap = $('#chapters-list > li > ul > li[data-index="' + vIndex + cIndex + '"]'),
                    mark =  thisChap.find('.mark');

                var loadingMsg = $('#progressing p.description > span');
				
				var chapter = data[ vIndex ].chapters[ cIndex ];
				
				
				if(data[ vIndex ].chapters[ cIndex ]){
					
					$title_first_part_index = chapter.name.indexOf(' - ');
					if($title_first_part_index != -1){
						chapter.name = chapter.name.substring(0, $title_first_part_index).trim();
					}

					$.ajax({
						method : 'POST',
						url : "<?php echo esc_url( $ajax_url ); ?>",
						timeout: 250000,
						data : {
							action : 'wt_fetch_single_chapter',
							chapter : chapter,
							volume : data[ vIndex ].name,
							postID : postID,
						},
						beforeSend : function(){
							mark.show();
							mark.html( spinnerGIF );
							mark.addClass('loading');
						},
						success : function( response ){
							if( response.success ){
								mark.html( successIcon );
								mark.removeClass('loading');
								thisChap.find('#loading-error').remove();

								if( cIndex + 1 !== data[ vIndex ].chapters.length  ){ // If this is not latest chapter in volume
									fetchSingleChapter( postID, data, vIndex, ++cIndex );
								}else if( cIndex + 1 === data[ vIndex ].chapters.length && typeof data[ vIndex + 1 ] != 'undefined' ){ // If this is the latest chapter in volume
									fetchSingleChapter( postID, data, vIndex + 1, 0 );
								}else{

									var postURL = response.data;

									<?php if( class_exists('WT_CRAWLER_CRONJOB') ){ ?>

										<?php
											$settings = WT_CRAWLER_HELPERS::get_crawler_settings();

											// If setting is upload to cloud storage
											if( $settings['import']['storage'] !== 'local' ){
												$cronjob = new WT_CRAWLER_CRONJOB();
												?>
												// Upload cloud action
												$.ajax({
													method : 'POST',
													url : "<?php echo esc_url( $ajax_url ); ?>",
													data : {
														action : '<?php echo $cronjob->upload_cloud_action; ?>',
														postID : postID
													},
													beforeSend : function(){
														loadingMsg.text( steps.upload_cloud );
													},
													success : function( response ){
														if( !response.success ) {
															errorMsg.append('<p>' + response.data + '</p>');
														} else {
															loadingMsg.text( response.data );
														}
													},
													complete: function(xhr, status){
														console.log(xhr);
														endFetching( true, postURL );
													}
												});
											<?php }else{ ?>

												// else complete
												endFetching( true, postURL );

											<?php } ?>
									<?php } ?>

								}

							}else if( response.data.code == <?php echo ERROR_GET_HTML; ?> ){
								// If it's error when trying to get content then repeat immediately
								if( thisChap.find('#loading-error').length > 0 ){
									thisChap.find('#loading-error').text( response.data.message );
								}else{
									thisChap.append( '<span id="loading-error">' + response.data.message + '</span>' );
								}

								fetchSingleChapter( postID, data, vIndex, cIndex );

							} else if( response.data.code == <?php echo ERROR_CLOUD_FLARE; ?> ){

								// Of if it's CloudFlare block the request, then wait for 5seconds to restart
								if( thisChap.find('#loading-error').length > 0 ){
									thisChap.find('#loading-error').text( response.data.message + '. Trying to re-fetch after 5 seconds' );
								}else{
									thisChap.append( '<span id="loading-error">' + response.data.message + '. Trying to re-fetch after 5 seconds</span>' );
								}

								setTimeout( fetchSingleChapter, 5000, postID, data, vIndex, cIndex );

							}else {
								removeInserted( postID );
							}
						},
						complete : function( xhr, status ){
							var response = xhr.responseJSON;

							if( typeof response === 'undefined' || ( typeof response !== 'undefined' && response.success == false && response.data.code != <?php echo ERROR_GET_HTML; ?> && response.data.code != <?php echo ERROR_CLOUD_FLARE; ?> )  ){
								errorMsg.text( "<?php esc_html_e('An error has occurred. Turn on WP_DEBUG and see errors in /wp-content/debug.log file', WP_MCL_TD ); ?>" );
								
								if(typeof response == 'undefined' && typeof response.data !== 'undefined'){
									errorMsg.append('<br><span>' + response.data.message + '</span>');
								} else {
								if( typeof xhr.statusText !== 'undefined' ){
									errorMsg.append('<br><span>' + xhr.statusText + '</span>');
								}
								}
								
								jQuery('.mark.loading').hide();
								
								// save current job to continue
								crawl_cindex = cIndex;
								crawl_data = data;
								crawl_vindex = vIndex;
								crawl_manga_id = postID;
								
								errorMsg.append('<p><a href="javascript:void(0)" onclick="errorMsg.empty();jQuery(\'#progressing p.description span.text\').text(\'Trying to continue...\');fetchSingleChapter(crawl_manga_id, crawl_data, crawl_vindex, crawl_cindex);">Continue?</a></p>');

								progressing.hide();
								//endFetching();
							}
						}
					});
				} else {
					endFetching();
				}

            }

            function endFetching( success = false, postURL = '' ){

                $('#crawl-single-manga').prop('disabled', false);

                if( success ){
                    $('#progressing p.description').html( successIcon + ' ' + steps.success + ' <a href="' + postURL + '"><?php echo esc_html__( 'View Post', WP_MCL_TD ); ?></a>' );
                }else{
                    resetFetch();
                }

            }

            function resetFetch(){
                $('#progressing p.description').html( '<?php echo $loading; ?> <span></span>' );
                chapList.empty();
                progressing.hide();
                chapters.hide();
            }

            function removeInserted( postID ){
                $.ajax({
                    method : 'POST',
                    url : "<?php echo esc_url( $ajax_url ); ?>",
                    data : {
                        action : 'wt_remove_inserted',
                        postID : postID
                    },
                });
            }
			
			// to save current job
			var crawl_manga_id, crawl_vindex, crawl_cindex, crawl_data;

    </script>
</div>
