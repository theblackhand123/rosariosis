<?php
/**
 * Google Identity Provider
 *
 * @link https://github.com/thephpleague/oauth2-google
 *
 * @package Google Social Login plugin
 */

use League\OAuth2\Client\Provider\Google;

// Back to root.
chdir( '../../..' );

require_once 'plugins/Google_Social_Login/includes/common.fnc.php';

if ( ! empty( $_GET['state'] )
	&& ! empty( $_GET['code'] ) )
{
	$redirect_url = GoogleSocialLoginCurrentPageURL();

	$redirect_url .= '?google-social-login-state=' . urlencode( $_GET['state'] ) .
		'&google-social-login-code=' . urlencode( $_GET['code'] );

	// Redirect to this same page before starting session!
	// Fix PHP session is lost due to samesite Strict cookie. Redirection is done in Javascript.
	?>
	<script>window.location.href = <?php echo json_encode( $redirect_url ); ?>;</script>
	<?php
	exit;
}

require_once 'plugins/Google_Social_Login/classes/vendor/autoload.php';
require_once 'Warehouse.php';

// Check Google is configured as a provider first.
if ( ! GoogleSocialLoginIsSet() )
{
	$error = sprintf(
		dgettext( 'Google_Social_Login', 'The "%s" login provider is not properly configured.' ),
		GoogleSocialLoginProviderName()
	);

	GoogleSocialLoginProviderFatalError( $error );
}

$provider = new Google([
	'clientId'     => Config( 'GOOGLE_SOCIAL_LOGIN_CLIENT_ID' ),
	'clientSecret' => Config( 'GOOGLE_SOCIAL_LOGIN_CLIENT_SECRET' ),
	'redirectUri'  => GoogleSocialLoginCurrentPageURL(),
	'hostedDomain' => Config( 'GOOGLE_SOCIAL_LOGIN_HOSTED_DOMAIN' ), // optional; used to restrict access to users on your G Suite/Google Apps for Business accounts.
]);

if ( ! empty( $_GET['error'] ) )
{
	// Got an error, probably user denied access.
	$error = sprintf(
		dgettext( 'Google_Social_Login', 'Google Social Login: %s' ),
		htmlspecialchars( $_GET['error'], ENT_QUOTES, 'UTF-8' )
	);

	GoogleSocialLoginProviderFatalError( $error );
}
elseif ( empty( $_GET['code'] )
	&& empty( $_GET['google-social-login-code'] ) )
{
	// If we don't have an authorization code then get one.
	$auth_url = $provider->getAuthorizationUrl();

	$_SESSION['google-social-login-state'] = $provider->getState();

	// var_dump($_SESSION, $auth_url); exit;

	header( 'Location: ' . $auth_url );

	exit;
}
elseif ( empty( $_GET['google-social-login-state'] )
	|| ( $_GET['google-social-login-state'] !== $_SESSION['google-social-login-state'] ) )
{
	// State is invalid, possible CSRF attack in progress.
	unset( $_SESSION['google-social-login-state'] );

	$error = dgettext( 'Google_Social_Login', 'Google Social Login: Invalid state' );

	GoogleSocialLoginProviderFatalError( $error );
}
else
{

	try {
		// Try to get an access token (using the authorization code grant).
		$token = $provider->getAccessToken( 'authorization_code', [
			'code' => $_GET['google-social-login-code']
		]);
	}
	catch ( League\OAuth2\Client\Provider\Exception\IdentityProviderException $e )
	{
		// Failed to get access token.
		$error = sprintf(
			dgettext( 'Google_Social_Login', 'Google Social Login: Failed to get access token. %s' ),
			$e->getMessage()
		);

		GoogleSocialLoginProviderFatalError( $error );
	}

	// Optional: Now you have a token you can look up a users profile data.
	try {
		// We got an access token, let's now get the owner details
		$owner_details = $provider->getResourceOwner( $token );
	}
	catch ( Exception $e )
	{
		// Failed to get user details.
		$error = sprintf(
			dgettext( 'Google_Social_Login', 'Google Social Login: Failed to get user details. %s' ),
			$e->getMessage()
		);

		GoogleSocialLoginProviderFatalError( $error );
	}

	// Use these details to create a new profile.
	// printf( 'Hello %s!', $owner_details->getEmail() );

	// Use this to interact with an API on the users behalf.
	// echo $token->getToken();

	// Use this to get a new access token if the old one expires.
	// echo $token->getRefreshToken();

	// Unix timestamp at which the access token expires.
	// echo $token->getExpires();

	$user_email = $owner_details->getEmail();

	$username = GoogleSocialLoginGetUsername( $user_email );

	$password = 'google-social-login-password';

	if ( $username )
	{
		$_SESSION['google-social-login-token'] = serialize( $token );

		$_SESSION['google-social-login-email'] = $user_email;
	}

	unset( $_SESSION['google-social-login-state'] );

	GoogleSocialLoginPostLoginForm( $username, $password );
}
