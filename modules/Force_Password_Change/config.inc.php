<?php
/**
 * Plugin configuration interface
 *
 * @package Force Password Change
 */

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['Force_Password_Change']
	|| $_REQUEST['modfunc'] !== 'config' )
{
	$error[] = _( 'You\'re not allowed to use this program!' );

	echo ErrorMessage( $error, 'fatal' );
}

// Note: no need to call ProgramTitle() here!

if ( ! empty( $_REQUEST['reset'] ) )
{
	// Save Config value.
	Config( 'FORCE_PASSWORD_CHANGE_USERNAMES', ',' );

	$note[] = _( 'Reset' );

	// Unset reset & redirect URL.
	RedirectURL( 'reset' );
}

if ( empty( $_REQUEST['reset'] )
	&& empty( $_REQUEST['remove'] ) )
{
	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Force_Password_Change&reset=true' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Force_Password_Change&reset=true' ) ) . '" method="POST">';

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	PopTable(
		'header',
		dgettext( 'Force_Password_Change', 'Force Password Change' )
	);

	echo '<p>' . dgettext( 'Force_Password_Change', 'Reset the list of users who have already changed their password.' ) . '</p>';

	echo '<br /><div class="center">' . SubmitButton( _( 'Reset' ) ) . '</div>';

	PopTable( 'footer' );

	echo '</form>';
}
