<?php
/**
 * Plugin configuration interface
 *
 * @package Public Pages
 */

require_once 'plugins/Public_Pages/includes/PublicPages.fnc.php';


// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['Public_Pages']
	|| $_REQUEST['modfunc'] !== 'config' )
{
	$error[] = _( 'You\'re not allowed to use this program!' );

	echo ErrorMessage( $error, 'fatal' );
}

// Note: no need to call ProgramTitle() here!

if ( $_REQUEST['save'] === 'true' )
{
	if ( $_REQUEST['values']['config']
		&& $_POST['values']
		&& AllowEdit() )
	{
		// Update the config table.
		$sql = '';

		if ( isset( $_REQUEST['values']['config'] )
			&& is_array( $_REQUEST['values']['config'] ) )
		{
			foreach ( (array) $_REQUEST['values']['config'] as $column => $value )
			{
				if ( $column === 'PUBLIC_PAGES'
					&& is_array( $value ) )
				{
					$value = '||' . implode( '||', $value ) . '||';
				}

				$sql .= "UPDATE config
					SET CONFIG_VALUE='" . $value . "'
					WHERE TITLE='" . $column . "'
					AND SCHOOL_ID IN('" . UserSchool() . "','0');"; // Save for all schools too.
			}
		}

		if ( $sql != '' )
		{
			DBQuery( $sql );

			$note[] = button( 'check' ) . '&nbsp;' . _( 'The plugin configuration has been modified.' );
		}

		unset( $_ROSARIO['Config'] ); // Update Config var.
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
			'&tab=plugins&modfunc=config&plugin=Public_Pages&save=true' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Public_Pages&save=true' ) ) . '" method="POST">';

	DrawHeader( '', SubmitButton( _( 'Save' ) ) );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	PopTable(
		'header',
		dgettext( 'Public_Pages', 'Public Pages' )
	);

	$options = GetPublicPagesAll();

	echo '<table class="width-100p"><tr><td>' . MultipleCheckboxInput(
		Config( 'PUBLIC_PAGES' ),
		'values[config][PUBLIC_PAGES][]',
		dgettext( 'Public_Pages', 'Public Pages' ),
		$options,
		'',
		false
	) . '</td></tr>';

	echo '</table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div></form>';
}
