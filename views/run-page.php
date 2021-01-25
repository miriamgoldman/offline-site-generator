<?php $run_nonce = wp_create_nonce( "offline-site-generator-run-page" ); ?>

<script type="text/javascript">
var latest_log_row = 0;

jQuery(document).ready(function($){
    
    var run_data = {
        action: 'offlineSiteGeneratorRun',
        security: '<?php echo $run_nonce; ?>',
    };

    var log_data = {
        dataType: 'text',
        action: 'offlineSiteGeneratorPollLog',
        startRow: latest_log_row,
        security: '<?php echo $run_nonce; ?>',
    };

    function responseErrorHandler( jqXHR, textStatus, errorThrown ) {
        $("#offline-site-generator-spinner").removeClass("is-active");
        $("#offline-site-generator-run" ).prop('disabled', false);

        console.log(jqXHR);

        console.log(errorThrown);
        console.log(jqXHR.responseText);

        
    }

    function pollLogs() {
        $.post(ajaxurl, log_data, function(response) {
            $('#offline-site-generator-run-log').val(response);
            $("#offline-site-generator-poll-logs" ).prop('disabled', false);
        });

    }

    $( "#offline-site-generator-run" ).click(function() {
        $("#offline-site-generator-spinner").addClass("is-active");
        $("#offline-site-generator-run" ).prop('disabled', true);
        console.log(run_data);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: "json", 
            data: run_data,
            success: function() {
                $("#offline-site-generator-spinner").removeClass("is-active");
                $("#offline-site-generator-run" ).prop('disabled', false);
                pollLogs();
            },
            error: responseErrorHandler
        });

    });

    $( "#offline-site-generator-poll-logs" ).click(function() {
        $("#offline-site-generator-poll-logs" ).prop('disabled', true);
        pollLogs();
    });
});
</script>

<div class="wrap">
    <br>

    <button class="button button-primary generate-static-site" id="offline-site-generator-run">Generate static site</button>

    <div id="offline-site-generator-spinner" class="spinner" style="padding:2px;float:none;"></div>

    <br>
    <br>

    <button class="button" id="offline-site-generator-poll-logs">Refresh logs</button>
    <br>
    <br>
    <textarea id="offline-site-generator-run-log" rows=30 style="width:99%;">
    Logs will appear here on completion or click "Refresh logs" to check progress
    </textarea>
</div>
