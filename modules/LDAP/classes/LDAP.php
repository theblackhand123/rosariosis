<?php
/**
 * LDAP class
 *
 * @package LDAP plugin
 */

namespace RosarioSIS\plugins;

class LDAP
{
	public $ldap;
	public $user_base_dn;
	public $is_active_directory;
	public $server_uri;
	public $user;
	public $last_error;

	const ERROR_USER_NOT_BOUND = -3;

	const ERROR_CONNECTION = -2;

	const ERROR_USER_NOT_FOUND = -1;

	public function __construct( $server_uri, $options = array() )
	{
		if ( isset( $options['user_base_dn'] ) )
		{
			// $user_base_dn = 'CN=Users,DC=example,DC=com'; for Active Directory.
			// $user_base_dn = 'ou=people,dc=example,dc=com'; for OpenLDAP.
			$this->user_base_dn = $options['user_base_dn'];
		}

		if ( isset( $options['is_active_directory'] ) )
		{
			$this->is_active_directory = $options['is_active_directory'];
		}

		// Username & password are null by default: anonymous bind.
		$username = $password = null;

		if ( isset( $options['username'] ) )
		{
			// Username or Bind DN (Distinguished Name).
			$username = $options['username'];
		}

		if ( isset( $options['password'] ) )
		{
			$password = $options['password'];
		}

		if ( $this->Connect( $server_uri ) )
		{
			$this->Bind( $username, $password );
		}
	}

	// https://stackoverflow.com/questions/171519/authenticating-in-php-using-ldap-through-active-directory#172042
	public function Connect( $server_uri = '' )
	{
		global $error;

		if ( $server_uri )
		{
			$this->server_uri = $server_uri;
		}

		$this->ldap = ldap_connect( $this->server_uri );

		if ( ! $this->ldap )
		{
			$error[] = $this->GetError( 'connect' );

			$this->last_error = $this::ERROR_CONNECTION;

			if ( ROSARIO_DEBUG )
			{
				var_dump( $this->server_uri );
			}

			return false;
		}

		// Timeout 10 sec.
		//ldap_set_option( $this->ldap, LDAP_OPT_TIMELIMIT, 10 );
		ldap_set_option( $this->ldap, LDAP_OPT_NETWORK_TIMEOUT, 10 );

		// Fix Active Directory Operations error.
		ldap_set_option( $this->ldap, LDAP_OPT_REFERRALS, 0 );

		// Fix Protocol error.
		ldap_set_option( $this->ldap, LDAP_OPT_PROTOCOL_VERSION, 3 );

		return true;
	}

	public function Authenticate( $username, $password )
	{
		if ( ! $this->ldap )
		{
			return false;
		}

		// Bind with admin first.
		//$bound = LDAPBind( $this->ldap, 'cn=admin,dc=local', 'password' );

		$this->user = $this->SearchUser( $username );

		if ( ! $this->user )
		{
			if ( ROSARIO_DEBUG )
			{
				var_dump( $this->server_uri, $this->user );
			}

			return false;
		}

		if ( $this->user['count'] < 1 )
		{
			$this->last_error = $this::ERROR_USER_NOT_FOUND;

			// User not found, continue authentication with RosarioSIS password.
			return false;
		}

		$dn = empty( $this->user[0]['dn'] ) ? '' : $this->user[0]['dn'];

		return $this->Bind( $dn, $password );
	}

	public function Bind( $username, $password )
	{
		global $error;

		$bound = @ldap_bind( $this->ldap, $username, $password );

		if ( ! $bound )
		{
			$error[] = $this->GetError( $this->ldap, 'bind' );

			$this->last_error = $this::ERROR_USER_NOT_BOUND;
		}

		return $bound;
	}

	public function Search( $search, $base_dn )
	{
		global $error;

		$result = @ldap_search( $this->ldap, $base_dn, $search );

		if ( ! $result )
		{
			$error[] = $this->GetError();

			$this->last_error = $this::ERROR_USER_NOT_FOUND;

			return array();
		}

		$data = ldap_get_entries( $this->ldap, $result );

		return $data;
	}

	public function SearchUser( $username, $user_base_dn = '' )
	{
		if ( $user_base_dn )
		{
			$this->user_base_dn = $user_base_dn;
		}

		// $username_attribute = 'samaccountname'; for Active Directory.
		// $username_attribute = 'uid'; for OpenLDAP.
		$search = 'uid=' . $username;

		if ( ! empty( $this->is_active_directory ) )
		{
			$search = 'samaccountname=' . $username;
		}

		return $this->Search( $search, $this->user_base_dn );
	}

	// TODO: use DEBUG, or display global $error if testing connection!!
	public function GetError( $type = '' )
	{
		if ( $type === 'bind' )
		{
			if ( ldap_get_option( $this->ldap, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error ) )
			{
				return sprintf(
					dgettext( 'LDAP', 'LDAP authentication: %s.' ),
					$extended_error
				);
			}

			return dgettext( 'LDAP', 'LDAP authentication.' );
		}
		elseif ( $type === 'connect' )
		{
			if ( ! $this->ldap )
			{
				return dgettext( 'LDAP', 'LDAP connection: Syntax.' );
			}
		}

		return sprintf(
			dgettext( 'LDAP', 'LDAP: %s.' ),
			ldap_error( $this->ldap )
		);
	}

	// For config.inc.php test!
	public static function CheckPHPExtension()
	{
		global $error;

		if ( ! extension_loaded( 'ldap' ) )
		{
			$error[] = dgettext( 'LDAP', 'PHP extensions: LDAP plugin relies on the ldap extension. Please install and activate it.' );

			return false;
		}

		return true;
	}
}
