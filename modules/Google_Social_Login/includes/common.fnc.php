<?php
/**
 * Common functions
 *
 * @package Google Social Login plugin
 */

function GoogleSocialLoginProviderName()
{
	return 'Google';
}

function GoogleSocialLoginIsSet()
{
	if ( ! Config( 'GOOGLE_SOCIAL_LOGIN_CLIENT_ID' )
		|| ! Config( 'GOOGLE_SOCIAL_LOGIN_CLIENT_SECRET' ) )
	{
		return false;
	}

	return true;
}

// No query string!
function GoogleSocialLoginCurrentPageURL()
{
	if ( function_exists( 'RosarioURL' ) )
	{
		return RosarioURL( 'script' );
	}

	$page_url = 'http://';

	if ( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' )
		|| ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' )
		|| ( isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ) )
	{
		// Fix detect https inside Docker or behind reverse proxy.
		$page_url = 'https://';
	}

	$page_url .= $_SERVER['SERVER_NAME'];

	if ( $_SERVER['SERVER_PORT'] != '80'
		&& $_SERVER['SERVER_PORT'] != '443' )
	{
		$page_url .= ':' . $_SERVER['SERVER_PORT'];
	}

	$page_url .= $_SERVER['SCRIPT_NAME'];

	return $page_url;
}

function GoogleSocialLoginProviderFatalError( $error )
{
	echo '<b>Error:</b> ' . $error;

	exit;
}

function GoogleSocialLoginGetUsername( $user_email )
{
	$user_email_escaped = DBEscapeString( $user_email );

	// Search Staff first.
	$username = DBGetOne( "SELECT USERNAME
		FROM staff
		WHERE SYEAR='" . Config( 'SYEAR' ) . "'
		AND (UPPER(USERNAME)=UPPER('" . $user_email_escaped . "')
			OR UPPER(EMAIL)=UPPER('" . $user_email_escaped . "'))" );

	if ( $username )
	{
		return $username;
	}

	// Search Students.
	$student_email_field_id = Config( 'STUDENTS_EMAIL_FIELD' );

	$student_email_field_sql = '';

	if ( $student_email_field_id )
	{
		$student_email_field = $student_email_field_id === 'USERNAME' ?
			's.USERNAME' :
			's.CUSTOM_' . (int) $student_email_field_id;

		$student_email_field_sql = "OR UPPER(" . $student_email_field . ")=UPPER('" . $user_email_escaped . "')";
	}

	$username = DBGetOne( "SELECT s.USERNAME
		FROM students s,student_enrollment se
		WHERE se.STUDENT_ID=s.STUDENT_ID
		AND se.SYEAR='" . Config( 'SYEAR' ) . "'
		AND CURRENT_DATE>=se.START_DATE
		AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)
		AND (UPPER(s.USERNAME)=UPPER('" . $user_email_escaped . "')
			" . $student_email_field_sql . ")" );

	// Check blocked_ prefix (Students > Remove Access).
	$username_prefix_add = Config( 'REMOVE_ACCESS_USERNAME_PREFIX_ADD' );

	if ( $username_prefix_add
		&& $username
		&& mb_strpos( $username, $username_prefix_add ) === 0 )
	{
		// Remove prefix so blocked Student cannot login.
		$username = str_replace( $username_prefix_add, '', $username );
	}

	return (string) $username;
}

function GoogleSocialLoginPostLoginForm( $username, $password )
{
	if ( $username === '' )
	{
		// Send a non existing but non empty username anyway so we display a login error on index.php.
		$username = ' ';
	}
	?>
	<form id="google-social-login" action="../../../index.php" method="post">
		<input type="hidden" name="USERNAME" id="USERNAME" value="<?php echo ( function_exists( 'AttrEscape' ) ? AttrEscape( $username ) : htmlspecialchars( $username, ENT_QUOTES ) ); ?>" />
		<input type="hidden" name="PASSWORD" id="PASSWORD" value="<?php echo ( function_exists( 'AttrEscape' ) ? AttrEscape( $password ) : htmlspecialchars( $password, ENT_QUOTES ) ); ?>" />
	</form>
	<script>
		document.getElementById('google-social-login').submit();
	</script>
	<?php
}

function GoogleSocialLoginCheckToken( $token, $email )
{
	global $error;

	require 'plugins/Google_Social_Login/classes/vendor/autoload.php';

	$token = unserialize( $token );

	if ( empty( $token ) )
	{
		return false;
	}

	$provider = new League\OAuth2\Client\Provider\Google([
		'clientId'     => Config( 'GOOGLE_SOCIAL_LOGIN_CLIENT_ID' ),
		'clientSecret' => Config( 'GOOGLE_SOCIAL_LOGIN_CLIENT_SECRET' ),
		'redirectUri'  => GoogleSocialLoginCurrentPageURL(),
		'hostedDomain' => Config( 'GOOGLE_SOCIAL_LOGIN_HOSTED_DOMAIN' ), // optional; used to restrict access to users on your G Suite/Google Apps for Business accounts.
	]);

	try {
		// We got an access token, let's now get the owner details
		$owner_details = $provider->getResourceOwner( $token );
	}
	catch ( Exception $e )
	{
		// Failed to get user details.
		$error[] = sprintf(
			dgettext( 'Google_Social_Login', 'Google Social Login: Failed to get user details. %s' ),
			$e->getMessage()
		);

		return false;
	}

	$user_email = $owner_details->getEmail();

	return $user_email === $email;
}
