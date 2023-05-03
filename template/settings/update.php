<?php

    /**
 	 * Update metabox template
 	 */

    if( ! defined('ABSPATH') ){
        exit;
    }

    global $wt_crawler_settings;

    $update = isset( $wt_crawler_settings['update'] ) ? $wt_crawler_settings['update'] : false;
?>

<table class="form-table">
    <tbody>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Auto Update', WP_MCL_TD); ?></label>
            </th>
            <td>
                <input type="checkbox" name="manga-crawler[update]" value="1" <?php checked( $update, 1 ); ?>> <?php esc_html_e('Enable Auto Update for crawled manga or existing manga (with manga name has to be exactly the same on source site)', WP_MCL_TD ); ?>
            </td>
        </tr>
    </tbody>
</table>
