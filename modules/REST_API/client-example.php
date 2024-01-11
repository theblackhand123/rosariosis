<?php
/**
 * Authentication with usertoken.
 * And make a GET API call.
 *
 * @example http://mydomain.com/rosariosis/plugins/REST_API/client-example.php
 *
 * @package REST API plugin
 */

// Load cURL class.
require_once '../../classes/curl.php';

$script_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) .
	'://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

$api_url = str_replace( basename( __FILE__ ), 'api.php', $script_url );

$auth_url = str_replace( basename( __FILE__ ), 'auth.php', $script_url );

if ( empty( $_POST ) )
{
	?>
	<form method="POST">
		<label>
			<input name="usertoken" value="" type="text"
				maxlength="255" pattern=".{32,}" size="30" required /><br />
			User Token
		</label><br /><br />
		<label>
			GET /<input name="call" value="" placeholder="openapi" type="text"
				maxlength="255" size="100" style="max-width: 100%;" /><br />
			API call
		</label><br /><br />
		<input type="submit" />
	</form>
	<?php
	exit;
}

if ( empty( $_POST['usertoken'] ) )
{
	echo 'Error: no usertoken.';

	exit;
}

$curl = new curl;

$response = $curl->post( $auth_url, [ 'usertoken' => $_REQUEST['usertoken'] ] );

$response = _getJson( $response );

if ( empty( $response['access_token'] ) )
{
	var_dump( $response );

	exit;
}

// Path defaults to /openapi.
$request_path = 'openapi';

if ( ! empty( $_POST['call'] ) )
{
	$request_path = $_POST['call'];
}

if ( strpos( $request_path, '/' ) !== 0 )
{
	$request_path = '/' . $request_path;
}

$api_url_path = $api_url . $request_path;

// Send our access_token in the X-Authorization HTTP header.
$curl->setHeader( 'X-Authorization: Bearer ' . $response['access_token'] );

$response = $curl->get( $api_url_path );

header( 'Content-Type: application/json' );

echo $response;

exit;


function _getJson( $data )
{
	$decoded = json_decode( $data, true );

	if ( json_last_error() !== JSON_ERROR_NONE )
	{
		return $data;
	}

	return $decoded;
}
