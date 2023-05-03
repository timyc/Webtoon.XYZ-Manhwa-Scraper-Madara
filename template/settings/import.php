<?php

    /**
 	 * Manga Crawler Tasks Import Settings Metabox template
	 */

    if( ! defined('ABSPATH') ){
        exit;
    }

    global $wt_crawler_settings;

    $status = isset( $wt_crawler_settings['import']['status'] ) ? $wt_crawler_settings['import']['status'] : 'pending';
    $genres = isset( $wt_crawler_settings['import']['genres'] ) ? $wt_crawler_settings['import']['genres'] : array();
    $tags   = isset( $wt_crawler_settings['import']['tags'] ) ? $wt_crawler_settings['import']['tags'] : '';
    $storage   = isset( $wt_crawler_settings['import']['storage'] ) ? $wt_crawler_settings['import']['storage'] : '';

?>

<table class="form-table">
    <tbody>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Posts Status', WP_MCL_TD); ?></label>
            </th>
            <td>
                <select class="regular-text" name="manga-crawler[import][status]" id="wp-manga-crawler-site-url">
                    <option value="publish" <?php selected( $status , 'publish' ); ?>><?php esc_html_e( 'Publish', WP_MCL_TD ); ?></option>
                    <option value="pending" <?php selected( $status , 'pending' ); ?>><?php esc_html_e( 'Pending', WP_MCL_TD ); ?></option>
                </select>
                <p class="description">
                    <?php esc_html_e('Select Post status for imported posts', WP_MCL_TD ); ?>
                </p>
            </td>
        </tr>
        <?php if( $all_genres = get_terms( 'wp-manga-genre', array( 'hide_empty' => false ) ) ){ ?>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Manga Genres', WP_MCL_TD); ?></label>
                </th>
                <td>
                    <div class="crawler-task-genres">
                        <?php foreach( $all_genres as $genre ){ ?>
                            <label for="term_<?php echo $genre->term_id; ?>">
                                <input type="checkbox" name="manga-crawler[import][genres][]" value="<?php echo esc_attr( $genre->term_id ); ?>" id="term_<?php echo $genre->term_id; ?>" <?php echo in_array( $genre->term_id, $genres ) ? 'checked' : '' ?>>
                                <?php echo esc_html( $genre->name ); ?>
                            </label>
                        <?php } ?>
                    </div>
                    <p class="description">
                        <?php esc_html_e('Assign Manga genre for imported manga', WP_MCL_TD); ?>
                    </p>
                </td>
            </tr>
        <?php } ?>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Manga Tags', WP_MCL_TD); ?></label>
            </th>
            <td>
                <input class="regular-text" type="text" name="manga-crawler[import][tags]" value="<?php echo esc_attr( $tags ); ?>">
                <p class="description">
                    <?php esc_html_e('Assign tags for imported manga. Separated tags by comma.', WP_MCL_TD ); ?>
                </p>
            </td>
        </tr>
        <?php if( class_exists('WP_MANGA') ){ ?>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Upload Storage', WP_MCL_TD); ?></label>
                </th>
                <td>
                    <select id="storage" name="manga-crawler[import][storage]">
                        <?php
                        foreach ( $GLOBALS['wp_manga']->get_available_host() as $host ) { ?>
                            <option value="<?php echo esc_attr( $host['value'] ) ?>" <?php selected( $host['value'], $storage ); ?>><?php echo esc_attr( $host['text'] ) ?></option>
                        <?php
                        }
                        ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Select storage to store crawl manga', WP_MCL_TD ); ?>
                    </p>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>
