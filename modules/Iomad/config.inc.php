<?php
/**
 * Iomad plugin configuration interface
 */

require_once 'plugins/Iomad/includes/common.fnc.php';

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| empty( $RosarioPlugins['Iomad'] )
	|| $_REQUEST['modfunc'] !== 'config' )
{
	$error[] = _( 'You\'re not allowed to use this program!' );

	echo ErrorMessage( $error, 'fatal' );
}

// Note: no need to call ProgramTitle().

if ( ! empty( $_REQUEST['save'] ) )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] )
		&& AllowEdit() )
	{
		// Update config table.
		$updated = false;

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

			// Save Company Course Category if not done yet!
			IomadCompanyCourseCategory( $value );

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

if ( ! empty( $_REQUEST['check'] ) )
{
	if ( ! _validIomadURL() )
	{
		$error[] = _( 'Test' ) . ': ' . _( 'Fail' );
	}
	else
	{
		$note[] = button( 'check' ) . '&nbsp;' . _( 'Test' ) . ': ' . _( 'Success' );
	}

	// Unset save & values & redirect URL.
	RedirectURL( 'check' );
}

if ( empty( $_REQUEST['save'] ) )
{
	// Error if Moodle plugin not activated.
	if ( empty( $RosarioPlugins['Moodle'] )
		|| ! ProgramConfig( 'moodle', 'MOODLE_URL' )
		|| ! ProgramConfig( 'moodle', 'MOODLE_TOKEN' ) )
	{
		$error[] = dgettext( 'Iomad', 'Please activate and configure the Moodle plugin.' );

		echo ErrorMessage( $error, 'fatal' );
	}

	// Check for Companies associated twice.
	$iomad_schools_companies = IomadGetSchoolCompanyID();

	if ( $iomad_schools_companies )
	{
		$iomad_company_ids = [];

		foreach ( $iomad_schools_companies as $school_id => $company_id )
		{
			if ( isset( $iomad_company_ids[ $company_id ] ) )
			{
				$warning[] = dgettext( 'Iomad', 'A single company is associated to various schools.' );

				break;
			}

			$iomad_company_ids[ $company_id ] = true;
		}
	}

	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Iomad&save=true' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Iomad&save=true' ) ) . '" method="POST">';

	DrawHeader( '', SubmitButton() );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $warning, 'warning' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';
	PopTable( 'header', _( 'Iomad' ) );

	echo '<fieldset><legend>' . dgettext( 'Iomad', 'Companies' ) . '</legend><table>';

	// RosarioSIS Schools and Iomad Companies.
	$schools_RET = DBGet( "SELECT ID,TITLE
		FROM schools
		WHERE SYEAR='" . UserSyear() . "'
		ORDER BY ID;" );

	$iomad_companies_options = [];

	$iomad_companies = IomadGetCompanies();

	foreach ( (array) $iomad_companies as $iomad_company )
	{
		$iomad_companies_options[ $iomad_company['id'] ] = $iomad_company['name'];
	}

	foreach ( $schools_RET as $school )
	{
		echo '<tr><td>' . SelectInput(
			Config( 'IOMAD_SCHOOL_' . $school['ID'] ),
			'values[config][IOMAD_SCHOOL_' . $school['ID'] . ']',
			$school['TITLE'],
			$iomad_companies_options
		) .	'</td></tr>';
	}

	echo '</table></fieldset>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton() . '</div></form>';
}
