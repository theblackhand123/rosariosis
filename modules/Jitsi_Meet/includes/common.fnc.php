<?php
/**
 * Common functions
 *
 * @package Jisti Meet module
 */

/**
 * Site URL with trailing slash
 *
 * @return string Site URL.
 */
function JitsiMeetSiteURL()
{
	if ( function_exists( 'RosarioURL' ) )
	{
		return RosarioURL();
	}

	$site_url = 'http://';

	if ( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' )
		|| ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' )
		|| ( isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ) )
	{
		// Fix detect https inside Docker or behind reverse proxy.
		$site_url = 'https://';
	}

	$site_url .= $_SERVER['SERVER_NAME'];

	if ( $_SERVER['SERVER_PORT'] != '80'
		&& $_SERVER['SERVER_PORT'] != '443' )
	{
		$site_url .= ':' . $_SERVER['SERVER_PORT'];
	}

	$site_url .= dirname( $_SERVER['SCRIPT_NAME'] ) === DIRECTORY_SEPARATOR ?
		// Add trailing slash.
		'/' : dirname( $_SERVER['SCRIPT_NAME'] ) . '/';

	return $site_url;
}
