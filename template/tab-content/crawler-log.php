<?php

    /**
 	 * Manga Crawler Tasks Import Settings Metabox template
	 */

    if( ! defined('ABSPATH') ){
        exit;
    }

    global $wt_crawler_settings;
    $implement = new WT_CRAWLER_IMPLEMENT();
?>

<div class="setting-section">
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Date', WP_MCL_TD); ?></label>
                </th>
                <td>
                    <input type="date" id="log-date" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Crawler Log', WP_MCL_TD); ?></label>
                </th>
                <td>
                    <div id="crawler-log">
                        <?php echo wt_get_crawler_log( $implement->data_dir, date( 'y-m-d' ) ); ?>
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
                    action : 'wt_get_crawler_log',
                    date : date
                },
                beforeSend : function(){
                    $('#crawler-log').html('<?php echo get_admin_loading_icon(); ?>');
                },
                success : function ( response ) {
                    if( response.success ){

                        $('#crawler-log > img').hide();

                        $('#crawler-log').html( response.data );
                    }else{
                        $('#crawler-log').text( response.data.message );
                    }

                }
            });

        });
    });
</script>
