<?php $addon_nonce = wp_nonce_field( $view['nonce_action'] ); ?>

<script type="text/javascript">

jQuery(document).ready(function($){
    

    function responseErrorHandler( jqXHR, textStatus, errorThrown ) {
     //   $("#offline-site-generator-spinner").removeClass("is-active");
     //   $("#offline-site-generator-run" ).prop('disabled', false);

        console.log(jqXHR);

        console.log(errorThrown);
        console.log(jqXHR.responseText);
        
    }

    $('.addon__toggle').click(function() {
        var addon_slug = $(this).data('addon-slug');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'offlineSiteGeneratorToggleAddon',
                security: '<?php echo $addon_nonce; ?>',
                addon_slug: addon_slug,
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
				<th>Enabled</th>
				<th>Name</th>
				<th>Type</th>
				<th>Documentation URL</th>
				<th>Configure</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $view['addons'] ) : ?>
				<tr>
					<td colspan="4">No addons are installed. <a href="https://offline-site-generator.com/download">Get Add-Ons</a></td>
				</tr>
			<?php endif; ?>


			<?php foreach ( $view['addons'] as $addon ) : ?>
				<tr>
					<td>

						<button class="addon__toggle" data-addon-slug="<?php echo $addon->slug; ?>"><?php echo $addon->enabled ? 'Enabled' : 'Disabled'; ?></button>

					

					</td>
					<td>
						<?php echo $addon->name; ?>
						<br>
						<?php echo $addon->description; ?>
					</td>
					<td><?php echo $addon->type; ?></td>
					<td>
						<a href="<?php echo $addon->docs_url; ?>"><span class="dashicons dashicons-book-alt"></span></a>
					</td>
					<td>
						<a href="<?php echo esc_url( admin_url( "admin.php?page={$addon->slug}" ) ); ?>"><span class="dashicons dashicons-admin-generic"></span></a>
					</td>

				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<br>
</div>
