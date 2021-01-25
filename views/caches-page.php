<?php $cache_nonce = wp_nonce_field( $view['nonce_action'] ); ?>

<script type="text/javascript">

jQuery(document).ready(function($){

	function responseErrorHandler( jqXHR, textStatus, errorThrown ) {

		console.log(jqXHR);

		console.log(errorThrown);
		console.log(jqXHR.responseText);	
	}

	$('.btn-purge-all-caches').click(function(e) {
		e.preventDefault();
	   
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'html',
			data: {
				action: 'offlineSiteGenerator_delete_all_caches',
				security: '<?php echo $cache_nonce; ?>'
			},
			success: function(response) {
                location.reload();
			},
			error: responseErrorHandler
		});
	});


 
});
</script>
<style>
select.offline-site-generator-select {
	width: 165px;
}
</style>

<div class="wrap">
	<p><i><a href="<?php echo admin_url( 'admin.php?page=offline-site-generator-caches' ); ?>">Refresh page</a> to see latest status</i><p>

	<table class="widefat striped">
		<thead>
			<tr>
				<th>Cache Type</th>
				<th>Statistics</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>Crawl Queue (Detected URLs)</td>
				<td><?php echo $view['crawlQueueTotalURLs']; ?> URLs in database</td>
				<td>
	<!-- TODO: allow downloading zipped CSV of all lists  <a href="#"><button class="button btn-danger">Download List</button></a> -->

					<form
						name="offline-site-generator-crawl-queue-delete"
						method="POST"
						action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

						<?php wp_nonce_field( $view['nonce_action'] ); ?>

						<select name="action" class="offline-site-generator-select">
							<option value="offlineSiteGeneratorCrawlQueueShow">Show URLs</option>
						</select>

						<button class="button btn-danger">Go</button>

					</form>
				</td>
			</tr>
			<tr>
				<td>Crawl Cache</td>
				<td><?php echo $view['crawlCacheTotalURLs']; ?> URLs in database</td>
				<td>
					<form
						name="offline-site-generator-crawl-cache-delete"
						method="POST"
						action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

						<?php wp_nonce_field( $view['nonce_action'] ); ?>

						<select name="action" class="offline-site-generator-select">
							<option value="offlineSiteGeneratorCrawlCacheShow">Show URLs</option>
						</select>

						<button class="button btn-danger">Go</button>

					</form>
				</td>
			</tr>
			<tr>
				<td>Generated Static Site</td>
				<td><?php echo $view['exportedSiteFileCount']; ?> files, using <?php echo $view['exportedSiteDiskSpace']; ?>
					<br>

					<a href="file://<?php echo $view['uploads_path']; ?>offline-site-generator-exported-site" />Path</a>

				</td>
				<td>
					<form
						name="offline-site-generator-static-site-delete"
						method="POST"
						action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

						<?php wp_nonce_field( $view['nonce_action'] ); ?>

						<select name="action" class="offline-site-generator-select">
							<option value="offlineSiteGeneratorStaticSiteShow">Show Paths</option>
						</select>

						<button class="button btn-danger">Go</button>

					</form>
				</td>
			</tr>
			<tr>
				<td>Post-processed Static Site</td>
				<td><?php echo $view['processedSiteFileCount']; ?> files, using <?php echo $view['processedSiteDiskSpace']; ?>
					<br>

					<a href="file://<?php echo $view['uploads_path']; ?>offline-site-generator-processed-site" />Path</a>
				</td>
				<td>
					<form
						name="offline-site-generator-post-processed-site-delete"
						method="POST"
						action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

						<?php wp_nonce_field( $view['nonce_action'] ); ?>

						<select name="action" class="offline-site-generator-select">
							<option value="offlineSiteGeneratorPostProcessedSiteShow">Show Paths</option>
						</select>

						<button class="button btn-danger">Go</button>

					</form>
				</td>
			</tr>

			<?php
			$deploy_cache_rows
				= isset( $view['deployCacheTotalPathsByNamespace'] )
				? count( $view['deployCacheTotalPathsByNamespace'] )
				: 1;
			?>
			<tr>
				<td rowspan="<?php echo $deploy_cache_rows; ?>">Deploy Cache</td>
				<?php if ( isset( $view['deployCacheTotalPathsByNamespace'] ) ) : ?>
					<?php $namespaces = array_keys( $view['deployCacheTotalPathsByNamespace'] ); ?>
					<td><?php echo $view['deployCacheTotalPathsByNamespace'][ $namespaces[0] ]; ?> Paths in database for <code><?php echo $namespaces[0]; ?></code></td>
					<td>
						<form
							name="offline-site-generator-post-processed-site-delete"
							method="POST"
							action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

							<?php wp_nonce_field( $view['nonce_action'] ); ?>

							<select name="action" class="offline-site-generator-select">
								<option value="offlineSiteGeneratorDeployCacheShow">Show Paths</option>
							</select>

							<input name="deploy_namespace" type="hidden" value="<?php echo $namespaces[0]; ?>" />

							<button class="button btn-danger">Go</button>

						</form>
					</td>
					<?php for ( $i = 1; $i < $deploy_cache_rows; $i++ ) : ?>
						</tr>
						<tr>
						<td><?php echo $view['deployCacheTotalPathsByNamespace'][ $namespaces[ $i ] ]; ?> Paths in database for <code><?php echo $namespaces[ $i ]; ?></code></td>
						<td>
							<form
								name="offline-site-generator-deploy-cache-delete"
								method="POST"
								action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

								<?php wp_nonce_field( $view['nonce_action'] ); ?>

								<select name="action" class="offline-site-generator-select">
									<option value="offlineSiteGeneratorDeployCacheShow">Show Paths</option>
								</select>

								<input name="deploy_namespace" type="hidden" value="<?php echo $namespaces[ $i ]; ?>" />

								<button class="button btn-danger">Go</button>

							</form>
						</td>
					<?php endfor; ?>
				<?php else : ?>
					<td><?php echo $view['deployCacheTotalPaths']; ?> Paths in database</td>
					<td>
						<form
							name="offline-site-generator-deploy-cache-delete"
							method="POST"
							action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

							<?php wp_nonce_field( $view['nonce_action'] ); ?>

							<select name="action" class="offline-site-generator-select">
								<option value="offlineSiteGeneratorDeployCacheShow">Show Paths</option>
							</select>

							<button class="button btn-danger">Go</button>

						</form>
					</td>
				<?php endif; ?>
			</tr>
			</tbody>
		</table>

		<br>

		<form
			name="offline-site-generator-delete-all-caches"
			method="POST"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" return false>

			<?php wp_nonce_field( $view['nonce_action'] ); ?>

			<button class="button btn-danger btn-purge-all-caches">Delete all caches</button>

		</form>
	</div>
</div>
