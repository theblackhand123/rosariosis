<?php
/**
 * LDAP plugin
 *
 * @package LDAP
 */

use RosarioSIS\plugins\LDAP;

if ( ! function_exists( 'HasFirstLoginForm' ) )
{
	function HasFirstLoginForm()
	{
		// Disable.
		return false;
	}
}

add_action( 'functions/Password.php|match_password', 'LDAPAuthenticate', 3 );

function LDAPAuthenticate( $tag, &$crypted, $password )
{
	global $username;

	if ( empty( $username )
		|| (string) $password === ''
		|| basename( $_SERVER['PHP_SELF'] ) !== 'index.php' )
	{
		// No username, or not on index.php or password empty: fail.
		return false;
	}

	require_once 'plugins/LDAP/classes/LDAP.php';

	if ( ! LDAP::CheckPHPExtension() )
	{
		// No ldap PHP ext: fail.
		return false;
	}

	$ldap_server_uri = Config( 'LDAP_SERVER_URI' );

	if ( ! $ldap_server_uri )
	{
		return false;
	}

	$options = [
		'user_base_dn' => Config( 'LDAP_USER_BASE_DN' ),
		'is_active_directory' => Config( 'LDAP_IS_ACTIVE_DIRECTORY' ),
		'username' => Config( 'LDAP_USERNAME' ),
		'password' => Config( 'LDAP_PASSWORD' ),
	];

	$ldap = new LDAP( $ldap_server_uri, $options );

	$authenticated = $ldap->Authenticate( $username, $password );

	if ( $authenticated === true )
	{
		$crypted = encrypt_password( $password );

		return true;
	}

	if ( $ldap->last_error === LDAP::ERROR_USER_NOT_BOUND )
	{
		// Empty so we cannot connect with RosarioSIS password... only LDAP.
		$crypted = '';
	}
	elseif ( $ldap->last_error === LDAP::ERROR_CONNECTION )
	{
		// Connection failed for Staff, do not try again for Student.
		remove_action( 'functions/Password.php|match_password', 'LDAPAuthenticate' );
	}

	return false;
}
