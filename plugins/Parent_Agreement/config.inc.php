<?php
/**
 * Plugin configuration interface
 *
 * @package Parent Agreement
 */

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['Parent_Agreement']
	|| $_REQUEST['modfunc'] !== 'config' )
{
	$error[] = _( 'You\'re not allowed to use this program!' );

	echo ErrorMessage( $error, 'fatal' );
}

// Note: no need to call ProgramTitle() here!

if ( ! empty( $_REQUEST['save'] ) )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] )
		&& AllowEdit() )
	{
		// Update config table.
		$updated = false;

		$_REQUEST['values']['config']['PARENT_AGREEMENT_TEXT'] = DBEscapeString( SanitizeHTML( $_POST['values']['config']['PARENT_AGREEMENT_TEXT'] ) );

		foreach ( (array) $_REQUEST['values']['config'] as $column => $value )
		{
			$config_exists = DBGetOne( "SELECT 1 FROM config
				WHERE TITLE='" . $column . "'
				AND SCHOOL_ID='0'" );

			if ( $config_exists )
			{
				Config( $column, $value );
			}
			else
			{
				// Insert value (does not exist), always in School 0!
				DBQuery( "INSERT INTO config (CONFIG_VALUE,TITLE,SCHOOL_ID)
					VALUES('" . $value . "','" . $column . "','0')" );
			}

			$updated = true;
		}

		if ( $updated )
		{
			$note[] = button( 'check' ) . '&nbsp;' .
				_( 'The plugin configuration has been modified.' );
		}
	}

	// Unset save & values & redirect URL.
	RedirectURL( [ 'save', 'values' ] );
}

if ( empty( $_REQUEST['save'] ) )
{
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Parent_Agreement&save=true' ) . '" method="POST">';

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	PopTable(
		'header',
		dgettext( 'Parent_Agreement', 'Parent Agreement' )
	);

	// Agreement title.
	echo '<table><tr><td>' . TextInput(
		Config( 'PARENT_AGREEMENT_TITLE' ),
		'values[config][PARENT_AGREEMENT_TITLE]',
		_( 'Title' ),
		'maxlength=255 required'
	) . '</td></tr>';

	// Agreement text.
	echo '<tr><td>' . TinyMCEInput(
		Config( 'PARENT_AGREEMENT_TEXT' ),
		'values[config][PARENT_AGREEMENT_TEXT]',
		_( 'Text' ),
		'required'
	) . '</td></tr></table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton() . '</div></form>';
}
