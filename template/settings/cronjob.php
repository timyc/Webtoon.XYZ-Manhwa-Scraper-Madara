<?php

    /**
 	 * Progress metabox template
 	 */

    if( ! defined('ABSPATH') ){
        exit;
    }

    global $wt_crawler_settings;

    $recurrence      = isset( $wt_crawler_settings['cronjob']['recurrence'] ) ? $wt_crawler_settings['cronjob']['recurrence'] : '3';
    $number_chapters = isset( $wt_crawler_settings['cronjob']['number_chapters'] ) ? $wt_crawler_settings['cronjob']['number_chapters'] : '1';
?>

<table class="form-table">
    <tbody>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Recurrence', WP_MCL_TD); ?></label>
            </th>
            <td>
                <input type="text" class="regular-text" name="manga-crawler[cronjob][recurrence]" value="<?php echo esc_attr( $recurrence ); ?>">
                <p class="description">
                    <?php esc_html_e('How often the crawler should run. Number in minutes', WP_MCL_TD ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Number of chapters', WP_MCL_TD); ?></label>
            </th>
            <td>
                <input type="text" class="regular-text" name="manga-crawler[cronjob][number_chapters]" value="<?php echo esc_attr( $number_chapters ); ?>">
                <p class="description">
                    <?php esc_html_e('Define number of chapters to fetch for each crawl action', WP_MCL_TD ); ?>
                </p>
            </td>
        </tr>
    </tbody>
</table>
