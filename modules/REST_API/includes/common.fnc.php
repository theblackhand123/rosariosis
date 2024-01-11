<?php
/**
 * Common functions
 *
 * @package REST API plugin
 */

/**
 * Call before session_start()
 * Proper PHP session & cookie initialization.
 *
 * @param string $session_name Session name. Defaults to 'RosarioSIS_REST_API'.
 *
 * @return string Session name.
 */
function RESTAPISession( $session_name = 'RosarioSIS_REST_API' )
{
	session_name( $session_name );

	// @link http://php.net/manual/en/session.security.php
	$cookie_path = dirname( $_SERVER['SCRIPT_NAME'] ) === DIRECTORY_SEPARATOR ?
		'/' : dirname( $_SERVER['SCRIPT_NAME'] ) . '/';

	// Fix #316 CSRF security issue set cookie samesite to strict.
	// @link https://www.php.net/manual/en/function.session-set-cookie-params.php#125072
	$cookie_samesite = 'Strict';

	// Cookie secure flag for https.
	$cookie_https_only = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) ||
		( isset( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] == 443 );

	if ( PHP_VERSION_ID < 70300 )
	{
		// PHP version < 7.3.
		session_set_cookie_params(
			0,
			$cookie_path . '; samesite=' . $cookie_samesite,
			'',
			$cookie_https_only,
			true
		);
	}
	else
	{
		session_set_cookie_params( [
			'lifetime' => 0,
			'path' => $cookie_path,
			'domain' => '',
			'secure' => $cookie_https_only,
			'httponly' => true,
			'samesite' => $cookie_samesite,
		] );
	}

	return $session_name;
}
