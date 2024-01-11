<?php
/**
 * Plugin configuration interface
 *
 * @package Tutor Report Card Comments
 */

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['Tutor_Report_Card_Comments']
	|| $_REQUEST['modfunc'] !== 'config' )
{
	$error[] = _( 'You\'re not allowed to use this program!' );

	echo ErrorMessage( $error, 'fatal' );
}

// Note: no need to call ProgramTitle() here!

if ( isset( $_REQUEST['save'] )
	&& $_REQUEST['save'] === 'true' )
{
	if ( $_REQUEST['values']['config']
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

	if ( function_exists( 'RedirectURL' ) )
	{
		// Unset save & values & redirect URL.
		RedirectURL( [ 'save', 'values' ] );
	}
}

if ( empty( $_REQUEST['save'] )
	&& empty( $_REQUEST['remove'] ) )
{
	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Tutor_Report_Card_Comments&save=true' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Tutor_Report_Card_Comments&save=true' ) ) . '" method="POST">';

	DrawHeader( '', SubmitButton( _( 'Save' ) ) );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	PopTable(
		'header',
		dgettext( 'Tutor_Report_Card_Comments', 'Tutor Report Card Comments' )
	);

	echo '<table class="width-100p"><tr><td>' . CheckboxInput(
		Config( 'TUTOR_REPORT_CARD_COMMENTS_SMALL_FONT_SIZE' ),
		'values[config][TUTOR_REPORT_CARD_COMMENTS_SMALL_FONT_SIZE]',
		dgettext( 'Tutor_Report_Card_Comments', 'Small font size' ),
		'',
		true
	) . '</td></tr>';

	echo '</table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div></form>';
}
