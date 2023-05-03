<?php

    /**
 	 * Progress metabox template
 	 */

    if( ! defined('ABSPATH') || ! class_exists('WT_CRAWLER_IMPLEMENT') ){
        exit;
    }

    $is_task_active = WT_CRAWLER_HELPERS::is_crawler_active();
?>
<div class="setting-section">
    <input type="hidden" name="isTaskActive" value="<?php echo intval( $is_task_active ); ?>">

    <div class="manga-crawler-progress-wrapper">
        <div class="crawler-progress">
            <div class="progress-completed">
            </div>
        </div>
        <div class="crawler-info">
            <div class="crawler-actions">
                <button type="button" class="button button-primary <?php echo !empty( $is_task_active ) ? 'hidden' : ''; ?>" id="wp-mcl-task-start"><span class="dashicons dashicons-controls-play"></span><?php esc_html_e('Start', WP_MCL_TD); ?></button>
                <button type="button" class="button button-secondary <?php echo empty( $is_task_active ) ? 'hidden' : ''; ?>" id="wp-mcl-task-stop"><span class="dashicons dashicons-controls-pause"></span><?php esc_html_e('Stop', WP_MCL_TD); ?></button>
            </div>
            <div class="crawler-stats">
                <span class="completed-stats"></span>/<span class="queue-stats"></span>
            </div>
        </div>
        <div class="crawler-listing">
            <?php
                do_action( 'wt_listing_manga' );
            ?>
        </div>
    </div>
</div>
