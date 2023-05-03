<?php

    /**
     * Template for webtoon Crawler Settings Tab Content
     */

    if( ! defined('ABSPATH') || ! class_exists( 'WT_CRAWLER_IMPLEMENT' ) ){
        exit;
    }

?>
<form method="post">
    <div class="settings-wrapper">
        <div class="setting-section">
            <h3 class="setting-section-title">
                <i class="dashicons-admin-settings wp-menu-image dashicons-before"></i> <?php esc_html_e('Source Settings', WP_MCL_TD ); ?>
            </h3>
            <?php WT_CRAWLER_HELPERS::get_template( 'settings/source' ); ?>
        </div>
        <div class="setting-section">
            <h3 class="setting-section-title">
                <i class="dashicons-migrate wp-menu-image dashicons-before"></i> <?php esc_html_e('Import Settings', WP_MCL_TD ); ?>
            </h3>
            <?php WT_CRAWLER_HELPERS::get_template( 'settings/import' ); ?>
        </div>
        <div class="setting-section">
            <h3 class="setting-section-title">
                <i class="dashicons-migrate wp-menu-image dashicons-before"></i> <?php esc_html_e('Auto Update Settings', WP_MCL_TD ); ?>
            </h3>
            <?php WT_CRAWLER_HELPERS::get_template( 'settings/update' ); ?>
        </div>
        <div class="setting-section">
            <h3 class="setting-section-title">
                <i class="dashicons-controls-repeat wp-menu-image dashicons-before"></i> <?php esc_html_e('Cron Job Settings', WP_MCL_TD ); ?>
            </h3>
            <?php WT_CRAWLER_HELPERS::get_template( 'settings/cronjob' ); ?>
        </div>
        <div class="setting-section">
            <h3 class="setting-section-title">
                <i class="dashicons-shield wp-menu-image dashicons-before"></i> <?php esc_html_e('Proxy Settings', WP_MCL_TD ); ?>
            </h3>
            <?php WT_CRAWLER_HELPERS::get_template( 'settings/proxy' ); ?>
        </div>
    </div>
    <button type="submit" name="button" class="button button-primary"><?php esc_html_e( 'Save', WP_MCL_TD ); ?></button>
</form>
