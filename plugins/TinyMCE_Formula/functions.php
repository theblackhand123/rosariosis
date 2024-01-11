<?php
/**
 * Functions.
 *
 * @package TinyMCE Formula plugin
 */

add_action( 'functions/Inputs.php|tinymce_before_init', 'TinyMCEFormulaPlugin' );

function TinyMCEFormulaPlugin()
{
	if ( function_exists( 'RosarioURL' ) )
	{
		$site_url = RosarioURL();
	}
	else
	{
		$site_url = 'http://';

		if ( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' )
			|| ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' )
			|| ( isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ) )
		{
			// Fix detect https inside Docker or behind reverse proxy.
			$site_url = 'https://';
		}

		$site_url .= $_SERVER['SERVER_NAME'];

		if ( $_SERVER['SERVER_PORT'] != '80'
			&& $_SERVER['SERVER_PORT'] != '443' )
		{
			$site_url .= ':' . $_SERVER['SERVER_PORT'];
		}

		$site_url .= dirname( $_SERVER['SCRIPT_NAME'] ) === DIRECTORY_SEPARATOR ?
			// Add trailing slash.
			'/' : dirname( $_SERVER['SCRIPT_NAME'] ) . '/';
	}

	$plugin_url = $site_url . 'plugins/TinyMCE_Formula/tinymce-formula/';
	$plugin_js_url = $plugin_url . 'plugin.min.js';
	?>
	<script>
		tinymceSettings.plugins += ' formula';
		tinymceSettings.toolbar += ' formula';
		tinymceSettings.external_plugins.formula = <?php echo json_encode( $plugin_js_url ); ?>;
	</script>
	<?php
}
