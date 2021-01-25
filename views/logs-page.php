<?php $logs_nonce = wp_nonce_field( $view['nonce_action'] ); ?>

<script type="text/javascript">

jQuery(document).ready(function($){
    

    function responseErrorHandler( jqXHR, textStatus, errorThrown ) {

        console.log(jqXHR);

        console.log(errorThrown);
        console.log(jqXHR.responseText);
        
    }

    $('.btn-delete-log').click(function(e) {
        e.preventDefault();
       
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'offlineSiteGenerator_log_delete',
                security: '<?php echo $logs_nonce; ?>'
            },
            success: function(response) {
               location.reload();
            },
            error: responseErrorHandler
        });
    });


 
});
</script>

<div class="wrap">
	<br>

	<table class="widefat striped">
		<thead>
			<tr>
				<th>When</th>
				<th>What</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $view['logs'] ) : ?>
				<tr>
					<td colspan="2">Logs are empty.</td>
				</tr>
			<?php endif; ?>


			<?php foreach ( $view['logs'] as $log ) : ?>
				<tr>
					<td><?php echo $log->time; ?></td>
					<td><?php echo $log->log; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<br> 

	<?php if ( $view['logs'] ) : ?>
		<form
			name="offline-site-generator-log-delete"
			method="POST"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" return false>

		<input name="action" type="hidden" value="offlineSiteGeneratorLogDelete" />

		<button class="offline-site-generator-button button btn-danger btn-delete-log">Delete Log</button>

		</form>
	<?php endif; ?>
</div>
