<?php
/**
 * Plugin configuration interface
 *
 * @package Convert Names To Titlecase
 */

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['Convert_Names_To_Titlecase']
	|| $_REQUEST['modfunc'] !== 'config' )
{
	$error[] = _( 'You\'re not allowed to use this program!' );

	echo ErrorMessage( $error, 'fatal' );
}

// Note: no need to call ProgramTitle() here!

if ( ! empty( $_REQUEST['convert'] ) )
{
	_convertToTitleCase(
		[ 'students', 'staff' ],
		[ 'FIRST_NAME', 'LAST_NAME', 'MIDDLE_NAME' ]
	);

	$note[] = _( 'Done.' );

	// Unset convert & redirect URL.
	RedirectURL( 'convert' );
}

if ( empty( $_REQUEST['convert'] )
	&& empty( $_REQUEST['remove'] ) )
{
	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Convert_Names_To_Titlecase&convert=true' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Convert_Names_To_Titlecase&convert=true' ) ) . '" method="POST">';

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	PopTable(
		'header',
		dgettext( 'Convert_Names_To_Titlecase', 'Convert Names To Titlecase' )
	);

	echo '<p>' . dgettext( 'Convert_Names_To_Titlecase', 'Convert user and student names to titlecase (first letter of each word to uppercase).' ) . '</p>';

	echo '<br /><div class="center">' . SubmitButton( dgettext( 'Convert_Names_To_Titlecase', 'Convert' ) ) . '</div>';

	PopTable( 'footer' );

	echo '</form>';
}

/**
 * Convert to Titlecase
 * Local function
 *
 * @uses INITCAP PostgreSQL function
 * @link https://www.postgresql.org/docs/current/functions-string.html
 *
 * @param  array $tables  DB table names.
 * @param  array $columns Column names.
 * @return bool False if no tables or no columns, else true.
 */
function _convertToTitleCase( $tables, $columns )
{
	if ( empty( $tables ) || empty( $columns ) )
	{
		return false;
	}

	$columns_sql = [];

	foreach ( (array) $columns as $column )
	{
		$columns_sql[] = DBEscapeIdentifier( $column ) . "=INITCAP(" . DBEscapeIdentifier( $column ) . ")";
	}

	$convert_sql = '';

	foreach ( (array) $tables as $table )
	{
		$convert_sql .= "UPDATE " . DBEscapeIdentifier( $table ) . " SET " .
			implode( ',', $columns_sql ) . ";";
	}

	DBQuery( $convert_sql );

	return true;
}
