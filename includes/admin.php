<?php

/**
 * Enqueue css
 */
function jsondi_admin_css()
{
	wp_enqueue_style('jsondi_admin_css', JSONDI_PLUGIN_URL . 'jsondi.css', false);
}

/**
 * Manage options page...
 */
function jsondi_options_page()
{
	$options = get_option(JSONDI_OPTIONS);
	if (!$options) {
		jsondi_default_options();
		$options = get_option(JSONDI_OPTIONS);
	}

	// update
	if ((isset($_POST['nounce_name'])) and (wp_verify_nonce($_POST['nounce_name'], 'nounce_action'))) {
		if (isset($_POST['reset_transient'])) {
			jsondi_file_delete($options["securitykey"]); // delete file, clear cache
		}

		if (isset($_POST['options']['securitykey'])) {
			$_POST['options']['securitykey'] = sanitize_title($_POST['options']['securitykey']);

			if (strlen($_POST['options']['securitykey']) < 8) {
				$_POST['options']['securitykey'] = jsondi_random_securitykey();
			}

			if (empty($_POST['options']['transient_timeout'])) {
				$_POST['options']['transient_timeout'] = 0;
			}
		} else {
			$_POST['options']['securitykey'] = jsondi_random_securitykey();
		}
		update_option(JSONDI_OPTIONS, $_POST['options']);
		$options = get_option(JSONDI_OPTIONS);
	}
?>


	<div class="wrap">

		<?php $datas = jsondi_get_datas(); ?>

		<h1><?= JSONDI_TITLE; ?></h1>

		<p>
			<a target="_blank" href="https://carlier.biz">Richard Carlier</a>
			-
			<a target="_blank" href="https://github.com/rcarlier/json-dashboard-infos"><?php _e('Visit plugin site'); ?></a>
		</p>

		<?php $url = get_home_url() . '/' . JSONDI_ROUTE . '/' . $options['securitykey']; ?>
		<p>
			<?php _e("JSON to fetch:", "json-dashboard-infos"); ?>
			<code><a href="<?php print $url; ?>" target="_blank"><?php print $url; ?></a></code>
		</p>


		<table class="form-table jsondi-table">
			<tr>
				<td class="jsondi-col-left">

					<form action="" method="post">
						<?php wp_nonce_field('nounce_action', 'nounce_name'); ?>
						<table>
							<tr>
								<th><label for="securitykey"><?php _e("Security Key", "json-dashboard-infos"); ?></label></th>
								<td colspan="2">
									<input type="text" required="" minlength="8" class="securitykey" name="options[securitykey]" id="securitykey" value="<?php print $options['securitykey']; ?>">


									<p class="jsondi-warning">
										<?php _e("Do NOT change without good reasons!", "json-dashboard-infos"); ?>
										<?php _e("If you do, no spaces, no specials cars... (try suggestions bellow).", "json-dashboard-infos"); ?>
										<?php _e("Don't forget to <b>update permalinks</b> if needed...", "json-dashboard-infos"); ?>
									</p>
									<p>
										<code><?= jsondi_random_securitykey() ?></code>
										<code><?= jsondi_random_securitykey() ?></code>
										<code><?= jsondi_random_securitykey() ?></code>
										<code><?= jsondi_random_securitykey() ?></code>
									</p>
								</td>
							</tr>
							<tr>
								<th><label for="transient_timeout"><?php _e("Transient timeout", "json-dashboard-infos"); ?></label></th>
								<td colspan="2">
									<input type="number" min="0" class="transient_timeout" name="options[transient_timeout]" id="transient_timeout" value="<?php print $options['transient_timeout']; ?>">
									<?php _e("In hour.", "json-dashboard-infos"); ?>

									<p class="jsondi-warning">
										<?php _e("0 to disable the transient (no cache).", "json-dashboard-infos"); ?>
									</p>
									<label>
										<input type="checkbox" name="reset_transient" value="Y">
										<?php _e("Reset cache", "json-dashboard-infos"); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th>
									<label for="code"><?php _e("Code", "json-dashboard-infos"); ?></label>
								</th>
								<td colspan="2">
									<input type="text" class="code" name="options[code]" id="code" value="<?php print $options['code']; ?>">

									<p class="jsondi-warning">
										<?php _e("If needed...", "json-dashboard-infos"); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th><?php _e("Expose Infos", "json-dashboard-infos"); ?></th>
								<td><?php jsondi_radio_yesno('options[show_infos]', $options['show_infos']); ?></td>
								<td><?php jsondi_get_datas_infos(true); ?></td>
							</tr>
							<tr>
								<th><?php _e("Expose Updates", "json-dashboard-infos"); ?></th>
								<td><?php jsondi_radio_yesno('options[show_updates]', $options['show_updates']); ?></td>
								<td><?php jsondi_get_datas_updates(true); ?></td>
							</tr>
							<tr>
								<th><?php _e("Expose Comments", "json-dashboard-infos"); ?></th>
								<td><?php jsondi_radio_yesno('options[show_comments]', $options['show_comments']); ?></td>
								<td><?php jsondi_get_datas_comments(true); ?></td>
							</tr>
							<tr>
								<th><?php _e("Expose Users", "json-dashboard-infos"); ?></th>
								<td><?php jsondi_radio_yesno('options[show_users]', $options['show_users']); ?></td>
								<td><?php jsondi_get_datas_users(true); ?></td>
							</tr>
							<tr>
								<th><?php _e("Expose Sizes", "json-dashboard-infos"); ?></th>
								<td><?php jsondi_radio_yesno('options[show_sizes]', $options['show_sizes']); ?></td>
								<td>
									<?php jsondi_get_datas_sizes(true); ?>
								</td>
							</tr>
							<tr>
								<th><?php _e("Expose Contents", "json-dashboard-infos"); ?></th>
								<td><?php jsondi_radio_yesno('options[show_contents]', $options['show_contents']); ?></td>
								<td><?php jsondi_get_datas_contents(true); ?></td>
							</tr>
						</table>

						<p><input class="button-primary" type="submit" value="<?php _e("Update", "json-dashboard-infos"); ?>"></p>
					</form>

				</td>
				<td class="jsondi-col-right">
					<p><?php _e("JSON generated:", "json-dashboard-infos"); ?></p>
					<pre class="json"><?php print json_encode($datas, JSON_PRETTY_PRINT); ?></pre>
				</td>
			</tr>
		</table>

	</div>
<?php
}
