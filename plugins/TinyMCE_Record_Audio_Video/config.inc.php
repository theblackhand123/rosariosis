<?php
/**
 * Plugin configuration interface
 *
 * @package TinyMCE Record Audio Video
 */

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['TinyMCE_Record_Audio_Video']
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

if ( empty( $_REQUEST['save'] ) )
{
	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=TinyMCE_Record_Audio_Video&save=true' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=TinyMCE_Record_Audio_Video&save=true' ) ) . '" method="POST">';

	DrawHeader( '', SubmitButton( _( 'Save' ) ) );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	PopTable(
		'header',
		dgettext( 'TinyMCE_Record_Audio_Video', 'TinyMCE Record Audio Video' )
	);

	echo '<table class="width-100p"><tr><td>' . TextInput(
		Config( 'TINYMCE_RECORD_AUDIO_VIDEO_TIME_LIMIT' ),
		'values[config][TINYMCE_RECORD_AUDIO_VIDEO_TIME_LIMIT]',
		dgettext( 'TinyMCE_Record_Audio_Video', 'Time Limit (seconds)' ),
		'type="number" step="1" min="1" max="36000"'
	) . '</td></tr>';

	echo '</table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div></form>';
}
