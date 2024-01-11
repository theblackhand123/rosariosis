<?php
/**
 * Functions
 *
 * @package Email SMTP
 */

// Register plugin functions to be hooked.
add_action( 'ProgramFunctions/SendEmail.fnc.php|before_send', 'EmailSMTPTriggered' );

// Triggered function.
function EmailSMTPTriggered( $hook_tag )
{
	global $phpmailer;

	static $email_count = 0;

	if ( ! ( $phpmailer instanceof PHPMailer ) )
	{
		// Not sending email?
		return false;
	}

	$host = Config( 'EMAIL_SMTP_HOST' );

	if ( empty( $host ) )
	{
		// No SMTP host / server configured.
		return false;
	}

	// Get config options.
	$smtp = [
		'EMAIL_SMTP_HOST' => $host,
		'EMAIL_SMTP_PORT' => (int) Config( 'EMAIL_SMTP_PORT' ),
		'EMAIL_SMTP_PORT' => (int) Config( 'EMAIL_SMTP_PORT' ),
		'EMAIL_SMTP_ENCRYPTION' => Config( 'EMAIL_SMTP_ENCRYPTION' ),
		'EMAIL_SMTP_USERNAME' => Config( 'EMAIL_SMTP_USERNAME' ),
		'EMAIL_SMTP_PASSWORD' => Config( 'EMAIL_SMTP_PASSWORD' ),
		'EMAIL_SMTP_FROM' => Config( 'EMAIL_SMTP_FROM' ),
		'EMAIL_SMTP_FROM_NAME' => Config( 'EMAIL_SMTP_FROM_NAME' ),
		'EMAIL_SMTP_PAUSE' => Config( 'EMAIL_SMTP_PAUSE' ),
	];

	if ( defined( 'EMAIL_SMTP_PASSWORD' ) )
	{
		// Password set in the config.inc.php file.
		$smtp['EMAIL_SMTP_PASSWORD'] = EMAIL_SMTP_PASSWORD;
	}

	$phpmailer->IsSMTP();
	// Authentication if Username not empty.
	$phpmailer->SMTPAuth = ! empty( $smtp['EMAIL_SMTP_USERNAME'] );
	// @link https://github.com/PHPMailer/PHPMailer/blob/master/examples/mailing_list.phps
	$phpmailer->SMTPKeepAlive = true; // SMTP connection will not close after each email sent, reduces SMTP overhead.
	$phpmailer->Host = $smtp['EMAIL_SMTP_HOST'];
	$phpmailer->Port = $smtp['EMAIL_SMTP_PORT'];

	if ( $smtp['EMAIL_SMTP_ENCRYPTION'] === 'ssl' )
	{
		// Fix force SSL encryption.
		$phpmailer->SMTPSecure = $smtp['EMAIL_SMTP_ENCRYPTION'];
	}

	$phpmailer->Username = $smtp['EMAIL_SMTP_USERNAME'];
	$phpmailer->Password = $smtp['EMAIL_SMTP_PASSWORD'];
	// SMTP::DEBUG_SERVER (2): as 1, plus responses received from the server (this is the most useful setting).
	// @link https://github.com/PHPMailer/PHPMailer/wiki/SMTP-Debugging
	$phpmailer->SMTPDebug = ( ROSARIO_DEBUG ? 2 : 0 );

	$display_errors = ini_get( 'display_errors' );

	if ( ! $display_errors
		|| mb_strtolower( $display_errors ) === 'off' )
	{
		// Do not display errors, send debug output to error log.
		$phpmailer->Debugoutput = 'error_log';
	}

	if ( filter_var( $smtp['EMAIL_SMTP_FROM'], FILTER_VALIDATE_EMAIL ) )
	{
		// Defaults to rosariosis@yourdomain.com.
		$phpmailer->From = $smtp['EMAIL_SMTP_FROM'];
	}

	if ( ! empty( $smtp['EMAIL_SMTP_FROM_NAME'] ) )
	{
		// Defaults to RosarioSIS.
		$phpmailer->FromName = $smtp['EMAIL_SMTP_FROM_NAME'];
	}

	if ( $smtp['EMAIL_SMTP_PAUSE'] > 0
		&& $email_count > 0 )
	{
		sleep( (int) $smtp['EMAIL_SMTP_PAUSE'] );
	}

	// Reduce SMTP timeout to 30 seconds (default is 300)
	$phpmailer->Timeout = 30;

	$email_count++;

	return true;
}

// @since 8.7 ProgramFunctions/SendEmail.fnc.php|send_error action hook.
add_action( 'ProgramFunctions/SendEmail.fnc.php|send_error', 'EmailSMTPError' );

// Triggered function.
function EmailSMTPError( $hook_tag )
{
	global $phpmailer;

	if ( ! ( $phpmailer instanceof PHPMailer ) )
	{
		// Not sending email?
		return false;
	}

	$host = Config( 'EMAIL_SMTP_HOST' );

	if ( empty( $host ) )
	{
		// No SMTP host / server configured.
		return false;
	}

	// @link https://github.com/PHPMailer/PHPMailer/blob/master/examples/mailing_list.phps
	// Reset the connection to abort sending this message.
	// The loop will continue trying to send to the rest of the list.
	$phpmailer->getSMTPInstance()->reset();

	return true;
}
