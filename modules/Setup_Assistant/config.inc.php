<?php
/**
 * Plugin configuration interface
 *
 * @package Setup Assistant plugin
 */

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['Setup_Assistant']
	|| $_REQUEST['modfunc'] !== 'config' )
{
	$error[] = _( 'You\'re not allowed to use this program!' );

	echo ErrorMessage( $error, 'fatal' );
}

// Note: no need to call ProgramTitle() here!

if ( isset( $_REQUEST['save'])
	&& $_REQUEST['save'] === 'true' )
{
	if ( $_REQUEST['values']['program_config']
		&& $_POST['values']
		&& AllowEdit() )
	{
		// Update the program_config table.
		$sql = '';

		if ( isset( $_REQUEST['values']['program_config'] )
			&& is_array( $_REQUEST['values']['program_config'] ) )
		{
			foreach ( (array) $_REQUEST['values']['program_config'] as $column => $value )
			{
				ProgramConfig( 'setup_assistant', $column, $value );
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


if ( empty( $_REQUEST['save'] )
	&& empty( $_REQUEST['remove'] ) )
{
	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Setup_Assistant&save=true' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Setup_Assistant&save=true' ) ) . '" method="POST">';

	DrawHeader( '', SubmitButton( _( 'Save' ) ) );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	$school_title = '';

	// If more than 1 school, add its title to table title.
	if ( SchoolInfo( 'SCHOOLS_NB' ) > 1 )
	{
		$school_title = SchoolInfo( 'SHORT_NAME' );

		if ( ! $school_title )
		{
			// No short name, get full title.
			$school_title = SchoolInfo( 'TITLE' );
		}

		$school_title = '(' . $school_title . ')';
	}

	PopTable(
		'header',
		sprintf(
			dgettext( 'Setup_Assistant', 'Setup Assistant %s' ),
			$school_title
		)
	);

	$setup_assistant = ProgramConfig( 'setup_assistant' );

	$profiles = [
		'admin' => _( 'Administrator' ),
		'teacher' => _( 'Teacher' ),
		'parent' => _( 'Parent' ),
		'student' => _( 'Student' ),
	];

	echo '<fieldset><legend>' . _( 'Profiles' ) . '</legend>';

	echo '<table class="width-100p">';

	foreach ( $profiles as $profile => $profile_label )
	{
		$profile_key = 'INACTIVE_' . $profile;

		$inactive = ! empty( $setup_assistant[ $profile_key ][1]['VALUE'] ) ? 'Y' : '';

		// Profile checkbox.
		echo '<tr><td>' . CheckboxInput(
			$inactive,
			'values[program_config][' . $profile_key . ']',
			$profile_label,
			'',
			false,
			_( 'Disabled' ),
			dgettext( 'Setup_Assistant', 'Enabled' )
		) . '</td></tr>';

	}

	echo '</table></fieldset>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div></form>';
}
