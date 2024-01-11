<?php
/**
 * Plugin configuration interface
 *
 * @package LDAP
 */

require_once 'plugins/LDAP/classes/LDAP.php';

use RosarioSIS\plugins\LDAP;

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['LDAP']
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

if ( ! empty( $_REQUEST['check'] ) )
{
	$options = [
		'user_base_dn' => Config( 'LDAP_USER_BASE_DN' ),
		'is_active_directory' => Config( 'LDAP_IS_ACTIVE_DIRECTORY' ),
		'username' => Config( 'LDAP_USERNAME' ),
		'password' => Config( 'LDAP_PASSWORD' ),
	];

	if ( _LDAPCheckConnection( Config( 'LDAP_SERVER_URI' ), $options ) )
	{
		$note[] = button( 'check' ) . '&nbsp;' . _( 'Test' ) . ': ' . _( 'Success' );
	}
	else
	{
		$error[] = _( 'Test' ) . ': ' . _( 'Fail' );
	}

	// Unset check & redirect URL.
	RedirectURL( 'check' );
}

if ( empty( $_REQUEST['save'] )
	&& empty( $_REQUEST['remove'] ) )
{
	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=LDAP&save=true' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=LDAP&save=true' ) ) . '" method="POST">';

	DrawHeader( '', SubmitButton( _( 'Save' ) ) );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	PopTable(
		'header',
		'LDAP'
	);

	echo '<table class="width-100p"><tr><td>' . CheckboxInput(
		Config( 'LDAP_IS_ACTIVE_DIRECTORY' ),
		'values[config][LDAP_IS_ACTIVE_DIRECTORY]',
		dgettext( 'LDAP', 'Is Active Directory?' )
	) . '</td></tr>';

	echo '<tr><td>' . TextInput(
		Config( 'LDAP_SERVER_URI' ),
		'values[config][LDAP_SERVER_URI]',
		dgettext( 'LDAP', 'LDAP server URI' ),
		'size="30" required placeholder="ldap://127.0.0.1:389"'
	) . '</td></tr>';

	echo '<tr><td>' . TextInput(
		Config( 'LDAP_USER_BASE_DN' ),
		'values[config][LDAP_USER_BASE_DN]',
		dgettext( 'LDAP', 'User base DN' ),
		'size="30" required placeholder="ou=people,dc=mydomain,dc=com"'
	) . '</td></tr>';

	echo '<tr><td>' . TextInput(
		Config( 'LDAP_USERNAME' ),
		'values[config][LDAP_USERNAME]',
		dgettext( 'LDAP', 'Bind DN (Username)' ),
		''
	) . '</td></tr>';

	echo '<tr><td>' . TextInput(
		[ Config( 'LDAP_PASSWORD' ), '********' ],
		'values[config][LDAP_PASSWORD]',
		_( 'Password' ),
		'type="password"'
	) . '</td></tr>';

	echo '</table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton( _( 'Save' ) );

	if ( Config( 'LDAP_SERVER_URI' )
		&& Config( 'LDAP_USER_BASE_DN' ) )
	{
		echo '<input type="button" value="' .
			AttrEscape( _( 'Test' ) ) .
			// Add check param to form's action & remove save.
			'" onclick="ajaxLink(this.form.action.replace(\'&save=true\',\'&check=Y\'));" />';
	}

	echo '</div></form>';
}

function _LDAPCheckConnection( $server_uri, $options )
{
	if ( ! LDAP::CheckPHPExtension() )
	{
		// No ldap PHP ext: fail.
		return false;
	}

	if ( ! $server_uri
		|| ! $options['user_base_dn'] )
	{
		return false;
	}

	$ldap = new LDAP( $server_uri, $options );

	if ( ! empty( $ldap->last_error ) )
	{
		return false;
	}

	$authenticated = $ldap->Authenticate( 'testmebutyouwillnotfindme', 'test' );

	if ( $ldap->last_error === LDAP::ERROR_USER_NOT_FOUND )
	{
		global $error;

		array_pop( $error );
	}

	return $authenticated
		|| ( $ldap->last_error === LDAP::ERROR_USER_NOT_FOUND );
}
