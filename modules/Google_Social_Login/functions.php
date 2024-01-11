<?php
/**
 * Google Social Login plugin
 *
 * @package Google Social Login
 */

add_action( 'index.php|login_form_link', 'GoogleSocialLoginLink' );

function GoogleSocialLoginLink()
{
	require_once 'plugins/Google_Social_Login/includes/common.fnc.php';

	if ( ! GoogleSocialLoginIsSet() )
	{
		return false;
	}

	?>

	<p class="align-right">
		<a href="plugins/Google_Social_Login/provider/Google.php" rel="nofollow">
			<?php echo sprintf(
				dgettext( 'Google_Social_Login', 'Login with %s' ),
				GoogleSocialLoginProviderName()
			); ?>
		</a>
	</p>

	<?php
}

add_action( 'functions/Password.php|match_password', 'GoogleSocialLoginMatchPassword', 3 );

function GoogleSocialLoginMatchPassword( $tag, &$crypted, $plain )
{
	require_once 'plugins/Google_Social_Login/includes/common.fnc.php';

	if ( $plain !== 'google-social-login-password'
		|| empty( $_SESSION['google-social-login-token'] )
		|| empty( $_SESSION['google-social-login-email'] )
		|| ! GoogleSocialLoginIsSet() )
	{
		// No using this plugin...
		// Connection failed for Staff, do not try again for Student.
		remove_action( 'functions/Password.php|match_password', 'GoogleSocialLoginMatchPassword' );

		return false;
	}

	$token = $_SESSION['google-social-login-token'];
	$email = $_SESSION['google-social-login-email'];

	unset( $_SESSION['google-social-login-token'] );
	unset( $_SESSION['google-social-login-email'] );

	// Check token.
	if ( ! GoogleSocialLoginCheckToken( $token, $email ) )
	{
		// Connection failed for Staff, do not try again for Student.
		remove_action( 'functions/Password.php|match_password', 'GoogleSocialLoginMatchPassword' );

		return false;
	}

	$crypted = encrypt_password( $plain );

	return true;
}
