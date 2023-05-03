<?php

    /**
 	 * Manga Crawler Tasks Import Settings Metabox template
	 */

    if( ! defined('ABSPATH') ){
        exit;
    }

    global $wt_crawler_settings;

?>

<div class="setting-section">
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Next Update', WP_MCL_TD); ?></label>
                </th>
                <td>
                    <span>
                        <?php if( !empty( $wt_crawler_settings['status']['next_update_latest_manga'] ) ){ ?>
                            <?php echo date_i18n( 'Y-m-d H:i:s', $wt_crawler_settings['status']['next_update_latest_manga'] ); ?>
                            -
                            <?php echo human_time_diff( time(), $wt_crawler_settings['status']['next_update_latest_manga'] ) . esc_html__( ' left', WP_MCL_TD ); ?>
                        <?php } ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Date', WP_MCL_TD); ?></label>
                </th>
                <td>
                    <input type="date" id="log-date">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Update Log', WP_MCL_TD); ?></label>
                </th>
                <td>
                    <div id="update-log">
                        <?php esc_html_e( 'Select Date to view Update log', WP_MCL_TD ); ?>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($){

        $('#log-date').on('change', function(){

            var date = $(this).val();

            $.ajax({
                url : '<?php echo admin_url('admin-ajax.php'); ?>',
                method : 'GET',
                data : {
                    action : 'wt_get_update_log',
                    date : date
                },
                beforeSend : function(){
                    $('#update-log').html('<?php echo get_admin_loading_icon(); ?>');
                },
                success : function ( response ) {
                    if( response.success ){

                        $('#update-log > img').hide();

                        $('#update-log').html('<ul></ul>');

                        var list = $('#update-log > ul');

                        $.each( response.data, function( manga, volumes ){
                            var output = '<li>';

                            output += '<div class="manga">' + manga + '</div>';
                            output += '<ul>';

                            $.each( volumes, function( name, chapters ){
                                output += '<li>';
                                output += '<div class="volume">' + name + '</div>';
                                output += '<ul>';

                                $.each( chapters.chapters, function( index, chapter ){
                                    output += '<li>' + chapter + '</li>';
                                });

                                output += '</ul>';
                                output += '</li>';
                            });


                            output += '</ul>';
                            output += '</li>';
                            list.append( output );
                        } );
                    }else{
                        $('#update-log').text( response.data.message );
                    }

                }
            });

        });
    });
</script>
