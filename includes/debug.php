<?php

add_action('wp', 'wp_wt_crawler_debug');

function wp_wt_crawler_debug(){

    if( isset( $_GET['debug'] ) && $_GET['debug'] == 'wt_manga_crawler' ){

         $manga = array(
             "slug" => "1st_year_max_level_manager",
             "name" => "1St Year Max Level Manager",
             "url"  => "https://www.webtoon.xyz/read/1st-year-max-level-manager/"
         );

        // Do something to debug
        $implement = new WT_CRAWLER_IMPLEMENT();
		$result = $implement->fetch_manga_listing();
		dd($result);exit;
		
        // Test get manga single
        // $implement->create_manga( $manga );

        // Test get chapter list
         //$html = $implement->get_site_html( $manga['url'] );
	
        // $chapters = $implement->fetch_chapter_list( $html );
         //dd( $chapters );
		 
        // Test get chapter images
        // $images = $implement->get_chapter_images( '' );
        // dd( $images );

        // Test get manga update
        // $implement->update_latest_manga();

        // Check Crawler running status.
        ?>
        <table>
            <tr>
                <td>Current Time</td>
                <td><?php echo date( 'Y-m-d H:i:s', time() ); ?></td>
            </tr>
            <tr>
                <td>Crawler Running Timeout</td>
                <td>
                    <?php $running_timeout = get_option( '_transient_timeout_is_webtoon_crawler_running' ); ?>
                    <?php echo get_transient('is_webtoon_crawler_running') ? date( 'Y-m-d H:i:s', $running_timeout ) : 'NO'; ?>
                </td>
            </tr>
            <tr>
                <td>Crawler Not Run Timeout</td>
                <td>
                    <?php $not_run_timeout = get_option( '_transient_timeout_webtoon_crawler_not_run' ); ?>
                    <?php echo get_transient( 'webtoon_crawler_not_run' ) ? date( 'Y-m-d H:i:s', $not_run_timeout ) : 'NO'; ?>
                </td>
            </tr>
        </table>
        <?php

        die();
    }
}
