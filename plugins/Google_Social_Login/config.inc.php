<?php
/**
 * Plugin configuration interface
 *
 * @package Google Social Login
 */

require_once 'plugins/Google_Social_Login/includes/common.fnc.php';

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['Google_Social_Login']
	|| $_REQUEST['modfunc'] !== 'config' )
{
	$error[] = _( 'You\'re not allowed to use this program!' );

	echo ErrorMessage( $error, 'fatal' );
}

// Note: no need to call ProgramTitle() here!

if ( ! empty( $_REQUEST['save'] ) )
{
	if ( ! empty( $_REQUEST['values']['config'] )
		&& $_POST['values']
		&& AllowEdit() )
	{
		foreach ( (array) $_REQUEST['values']['config'] as $column => $value )
		{
			// Update config value.
			Config( $column, $value );
		}

		$note[] = button( 'check' ) . '&nbsp;' . _( 'The plugin configuration has been modified.' );
	}

	// Unset save & values & redirect URL.
	RedirectURL( [ 'save', 'values' ] );
}

if ( empty( $_REQUEST['save'] )
	&& empty( $_REQUEST['remove'] ) )
{
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Google_Social_Login&save=true' ) . '" method="POST">';

	DrawHeader( '', SubmitButton( _( 'Save' ) ) );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	PopTable(
		'header',
		dgettext( 'Google_Social_Login', 'Google Social Login' )
	);

	echo '<table class="width-100p"><tr><td>' . TextInput(
		Config( 'GOOGLE_SOCIAL_LOGIN_CLIENT_ID' ),
		'values[config][GOOGLE_SOCIAL_LOGIN_CLIENT_ID]',
		dgettext( 'Google_Social_Login', 'Client ID' ),
		'size="63" required placeholder="123456789123-pu4d0jp6ceohcec0aqfru46mfnh742pa.apps.googleusercontent.com"'
	) . '</td></tr>';

	echo '<tr><td>' . TextInput(
		Config( 'GOOGLE_SOCIAL_LOGIN_CLIENT_SECRET' ),
		'values[config][GOOGLE_SOCIAL_LOGIN_CLIENT_SECRET]',
		dgettext( 'Google_Social_Login', 'Client Secret' ),
		'size="30" required placeholder="12_nzjrPCl3e0iThx12345EoB"'
	) . '</td></tr>';

	echo '<tr><td>' . TextInput(
		Config( 'GOOGLE_SOCIAL_LOGIN_HOSTED_DOMAIN' ),
		'values[config][GOOGLE_SOCIAL_LOGIN_HOSTED_DOMAIN]',
		dgettext( 'Google_Social_Login', 'Hosted Domain' ),
		'size="30" placeholder="mydomain.com"'
	) . '</td></tr>';

	$redirect_uri = GoogleSocialLoginCurrentPageURL();

	$redirect_uri = str_replace( 'Modules.php', 'plugins/Google_Social_Login/provider/Google.php', $redirect_uri );

	echo '<tr><td>' . NoInput(
		'<code>' . $redirect_uri . '</code>',
		dgettext( 'Google_Social_Login', 'Redirect URI' )
	) . '</td></tr>';

	echo '</table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div></form>';
}
