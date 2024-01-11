<?php
/**
 * Plugin configuration interface
 *
 * @package REST_API plugin
 */

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['REST_API']
	|| $_REQUEST['modfunc'] !== 'config' )
{
	$error[] = _( 'You\'re not allowed to use this program!' );

	echo ErrorMessage( $error, 'fatal' );
}

// Note: no need to call ProgramTitle() here!

if ( isset( $_REQUEST['save'] )
	&& $_REQUEST['save'] === 'true' )
{
	if ( $_REQUEST['values']['program_user_config']
		&& $_POST['values']
		&& AllowEdit() )
	{
		// Update the program_user_config table.
		$sql = '';

		if ( isset( $_REQUEST['values']['program_user_config'] )
			&& is_array( $_REQUEST['values']['program_user_config'] ) )
		{
			foreach ( (array) $_REQUEST['values']['program_user_config'] as $column => $value )
			{
				ProgramUserConfig( 'REST_API', User( 'STAFF_ID' ), [ $column => $value ] );
			}
		}

		if ( $sql != '' )
		{
			DBQuery( $sql );

			$note[] = button( 'check' ) . '&nbsp;' . _( 'The plugin configuration has been modified.' );
		}
	}

	// Unset save & values & redirect URL.
	RedirectURL( [ 'save', 'values' ] );
}


if ( empty( $_REQUEST['save'] ) )
{
	$error = _PHPPostgreSQLCheck();

	// Check Secret passphrase is defined in config.inc.php.
	if ( ! defined( 'ROSARIO_REST_API_SECRET' ) )
	{
		$error[] = dgettext( 'REST_API', 'Please define the <code>ROSARIO_REST_API_SECRET</code> constant in the config.inc.php file. Check installation instructions for more information.' );
	}

	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=REST_API&save=true' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=REST_API&save=true' ) ) . '" method="POST">';

	DrawHeader( '', SubmitButton( _( 'Save' ) ) );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	PopTable(
		'header',
		dgettext( 'REST_API', 'REST API' )
	);

	if ( function_exists( 'RosarioURL' ) )
	{
		$script_url = RosarioURL( 'script' );
	}
	else
	{
		$script_url = 'http://';

		if ( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' )
			|| ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' )
			|| ( isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ) )
		{
			// Fix detect https inside Docker or behind reverse proxy.
			$script_url = 'https://';
		}

		$script_url .= $_SERVER['SERVER_NAME'];

		if ( $_SERVER['SERVER_PORT'] != '80'
			&& $_SERVER['SERVER_PORT'] != '443' )
		{
			$script_url .= ':' . $_SERVER['SERVER_PORT'];
		}

		$script_url .= $_SERVER['SCRIPT_NAME'];
	}

	$api_url = str_replace( 'Modules.php', 'plugins/REST_API/api.php', $script_url );

	$auth_url = str_replace( 'Modules.php', 'plugins/REST_API/auth.php', $script_url );

	$example_client_url = str_replace( 'Modules.php', 'plugins/REST_API/client-example.php', $script_url );

	$api_config = ProgramUserConfig( 'REST_API' );

	$api_user_token = ! empty( $api_config['USER_TOKEN'] ) ? $api_config['USER_TOKEN'] : '';

	echo '<table class="width-100p">';

	echo '<tr><td>' . NoInput(
		'<code>' . $api_url . '</code>',
		dgettext( 'REST_API', 'API URL' )
	) . '</td></tr>';

	echo '<tr><td>' . NoInput(
		'<code>' . $auth_url . '</code>',
		dgettext( 'REST_API', 'Authentication URL' )
	) . '</td></tr>';

	$example_client_title = dgettext( 'REST_API', 'Example client' );
	$example_client_value = '<code>' . $example_client_url . '</code>';

	if ( ! empty( $api_user_token ) )
	{
		$example_client_value = '<a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( $example_client_url ) :
			_myURLEncode( $example_client_url ) ) . '" target="_blank">' . $example_client_url . '</a>';
	}

	echo '<tr><td>' . NoInput(
		$example_client_value,
		$example_client_title
	) . '</td></tr>';

	// @since RosarioSIS 11.0 Fix PHP fatal error if openssl PHP extension is missing
	$api_user_token_value = empty( $api_user_token ) ? bin2hex( function_exists( 'openssl_random_pseudo_bytes' ) ?
		openssl_random_pseudo_bytes( 16 ) :
		( function_exists( 'random_bytes' ) ? random_bytes( 16 ) :
			mb_substr( sha1( rand( 999999999, 9999999999 ), true ), 0, 16 ) ) ) : $api_user_token;

	echo '<tr><td>' . TextInput(
		$api_user_token_value,
		'values[program_user_config][USER_TOKEN]',
		dgettext( 'REST_API', 'User Token' ),
		'pattern=".{32,}"',
		! empty( $api_user_token )
	) . '</td></tr>';

	echo '</table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div></form>';
}

/**
 * Check PHP min version, PostgreSQL min version.
 *
 * @return array Error messages for failed checks.
 */
function _PHPPostgreSQLCheck()
{
	global $DatabaseType;

	$ret = [];

	if ( version_compare( PHP_VERSION, '7.0' ) == -1 )
	{
		$ret[] = 'REST API requires PHP 7.0+ to run, your version is : ' . PHP_VERSION;
	}

	if ( $DatabaseType === 'postgresql' )
	{
		$postgresql_version = (float) DBGetOne( "SHOW server_version;" );

		if ( version_compare( $postgresql_version, '9.1' ) == -1 )
		{
			$ret[] = 'REST API requires PostgreSQL 9.1+ to run, your version is : ' . $postgresql_version;
		}
	}

	return $ret;
}
