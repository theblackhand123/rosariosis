<?php
/**
 * Plugin configuration interface
 *
 * @package Assignment Max Points
 */

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['Assignment_Max_Points']
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
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Assignment_Max_Points&save=true' ) . '" method="POST">';

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
			dgettext( 'Assignment_Max_Points', 'Assignment Max Points %s' ),
			$school_title
		)
	);

	echo '<table class="width-100p"><tr><td>' . TextInput(
		Config( 'ASSIGNMENT_MAX_POINTS' ),
		'values[config][ASSIGNMENT_MAX_POINTS]',
		dgettext( 'Assignment_Max_Points', 'Assignment Max Points' ),
		'type="number" min="1" max="9999"',
		false
	) . '</td></tr></table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton() . '</div></form>';
}
