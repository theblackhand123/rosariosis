<?php
/**
 * Functions
 *
 * @package Force Password Change
 */

// Register plugin functions to be hooked.
add_action( 'index.php|login_check', 'ForcePasswordChangeLoginCheck', 2 );

// Triggered function.
function ForcePasswordChangeLoginCheck( $tag, $username )
{
	// Add Username to $_SESSION for later use.
	$_SESSION['FORCE_PASSWORD_CHANGE_USERNAME'] = $username;
}

function ForcePasswordChangeCheckUsername( $username )
{
	if ( empty( $username ) )
	{
		return false;
	}

	$usernames = Config( 'FORCE_PASSWORD_CHANGE_USERNAMES' );

	if ( strpos( $usernames, ',' . $username . ',' ) !== false )
	{
		// Username found, password already changed.
		return false;
	}

	// Force password change.
	return true;
}

if ( ! function_exists( 'HasFirstLoginForm' ) )
{
	function HasFirstLoginForm()
	{
		if ( ! User( 'STAFF_ID' ) && empty( $_SESSION['STUDENT_ID'] ) )
		{
			// User or student login failed.
			return false;
		}

		if ( empty( $_SESSION['FORCE_PASSWORD_CHANGE_USERNAME'] )
			|| ! ForcePasswordChangeCheckUsername( $_SESSION['FORCE_PASSWORD_CHANGE_USERNAME'] ) )
		{
			// No username or password already changed.
			return false;
		}

		// Override the "Force Password Change on First Login" school configuration option.
		Config( 'FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN', 'Y_FORCE_PASSWORD_CHANGE_PLUGIN' );

		if ( ! empty( $_POST['first_login']['PASSWORD'] ) )
		{
			// Add Username to list of users who already changed their password.
			ForcePasswordChangeDone( $_SESSION['FORCE_PASSWORD_CHANGE_USERNAME'] );
		}

		return true;
	}
}

function ForcePasswordChangeDone( $username )
{
	$usernames = Config( 'FORCE_PASSWORD_CHANGE_USERNAMES' );

	// Add Username to list of users who already changed their password.
	$usernames .= $username . ',';

	// Save Config value.
	Config( 'FORCE_PASSWORD_CHANGE_USERNAMES', $usernames );
}
