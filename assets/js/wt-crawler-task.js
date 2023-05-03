jQuery(function($){
    $(document).ready(function(){

        var startBtn = $('#wp-mcl-task-start'),
            stopBtn = $('#wp-mcl-task-stop');

        updateStats();

        function updateStats(){
            var completed = $('li.completed-manga').length,
                queue     = $('li.queue-manga').length,
                percent   = queue == 0 ? '0.1' : completed / ( queue + completed );

            $('.completed-stats').text( completed );
            $('.queue-stats').text( queue );
            $('.manga-crawler-progress-wrapper .crawler-progress .progress-completed').css( 'max-width', percent.toString() + '%' );
        }

        $(document).on('click', '#wp-mcl-task-start', function(){

            $.ajax({
                url : ajaxurl,
                type : 'POST',
                data : {
                    action : 'wt_crawler_active_task',
                },
                beforeSend : function(){
                    startBtn.prop( 'disabled', true );
                },
                success : function( response ){
                    if( response.success ){
                        startBtn.hide();
                        stopBtn.show();
                        $('input[name="isTaskActive"]').val('1');
                        alert('Start Successfully!');
                        location.reload();
                    }else{
                        alert('Start Failed!');
                    }
                },
                complete : function(){
                    startBtn.prop( 'disabled', false );
                }
            });
        });
        $(document).on('click', '#wp-mcl-task-stop', function(){

            $.ajax({
                url : ajaxurl,
                type : 'POST',
                data : {
                    action : 'wt_crawler_deactive_task',
                },
                beforeSend : function(){
                    stopBtn.prop( 'disabled', true );
                },
                success : function( response ){
                    if( response.success ){
                        stopBtn.hide();
                        startBtn.show();
                        $('input[name="isTaskActive"]').val('0');
                        alert('Stop Successfully!');
                    }else{
                        alert('Stop Failed!');
                    }
                },
                complete : function(){
                    stopBtn.prop( 'disabled', false );
                },
            });
        });

        setInterval(function(){
            if( $('input[name="isTaskActive"]').val() != 0 ){
                $.ajax({
                    url : ajaxurl,
                    type : "POST",
                    data : {
                        action : 'wt_refresh_list',
                        doaction : 'wt_listing_manga',
                    },
                    success : function( response ){
                        if( response.success ){
                            $('.crawler-listing').html( response.data );
                            updateStats();
                        }
                    }
                });
            }
        }, 30000);

    });
});
