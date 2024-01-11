<?php
/**
 * API Authentication endpoint.
 * Simply POST usertoken and receive back the access_token on success.
 *
 * Original file:
 * @link https://github.com/mevdschee/php-api-auth/blob/master/auth.php
 *
 * Modified to not only handle interactive auth (through redirects & redirect_uri param),
 * but direct auth sending back token in JSON: `{ access_token: "XXXX" }`
 *
 * @package REST API plugin
 */

require_once 'auth-config.inc.php';

/**
 * Get User tokens from DB table program_user_config
 * And check if matches with param.
 *
 * @since 10.1 SQL limit user tokens to users in default school year, & user profile != No Access
 *
 * @param string $usertoken User token.
 *
 * @return bool True if User token matches with one from DB.
 */
function RosarioAPILogin( $usertoken )
{
    global $DefaultSyear;

    require_once '../../database.inc.php';

    // Test if plugin Active first.
    $result = db_query( "SELECT 1
        FROM config
        WHERE TITLE='PLUGINS'
        AND CONFIG_VALUE LIKE '%\"REST_API\";b:1%'" );

    $plugin_active = db_fetch_row( $result );

    if ( $result === false
        || ! $plugin_active )
    {
        return false;
    }

    // Get User Tokens for default School Year only, & user profile != No Access.
    $result = db_query( "SELECT VALUE
        FROM program_user_config
        WHERE TITLE='USER_TOKEN'
        AND PROGRAM='REST_API'
        AND USER_ID IN(SELECT STAFF_ID
            FROM staff
            WHERE SYEAR='" . $DefaultSyear . "'
            AND PROFILE<>'none')" );

    $config_user_tokens = db_fetch_row( $result );

    if ( $result === false
        || ! $config_user_tokens )
    {
        return false;
    }

    return in_array( $usertoken, $config_user_tokens );
}

function generateToken($subject, $audience, $issuer, $time, $ttl, $algorithm, $secret)
{
    $algorithms = [
        'HS256' => 'sha256',
        'HS384' => 'sha384',
        'HS512' => 'sha512',
        'RS256' => 'sha256',
        'RS384' => 'sha384',
        'RS512' => 'sha512',
    ];
    $header = [];
    $header['typ'] = 'JWT';
    $header['alg'] = $algorithm;
    $token = [];
    $token[0] = rtrim(strtr(base64_encode(json_encode((object) $header)), '+/', '-_'), '=');
    $claims['sub'] = $subject;
    $claims['aud'] = $audience;
    $claims['iss'] = $issuer;
    $claims['iat'] = $time;
    $claims['exp'] = $time + $ttl;
    $token[1] = rtrim(strtr(base64_encode(json_encode((object) $claims)), '+/', '-_'), '=');
    if (!isset($algorithms[$algorithm])) {
        return false;
    }
    $hmac = $algorithms[$algorithm];
    $data = "$token[0].$token[1]";
    switch ($algorithm[0]) {
        case 'H':
            $signature = hash_hmac($hmac, $data, $secret, true);
            break;
        case 'R':
            $signature = (openssl_sign($data, $signature, $secret, $hmac) ? $signature : '');
            break;
    }
    $token[2] = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    return implode('.', $token);
}

function redirect($url)
{
    header('Location: ' . $url, true, 302);
}

// FJ add sendJson.
function sendJson( $data )
{
    header( 'Content-Type: application/json' );

    echo json_encode( $data );
}

function serve($file)
{
    echo file_get_contents($file);
}

// FJ add $method param.
function handleGet($config, $session, $method = 'redirect')
{
    if (empty($session)) {
        if ( $method === 'redirect' ) {
            serve('login.html');
        } else {
            sendJson( [ 'error' => 'Empty session' ] );
        }
    } else {
        $token = getToken($config, $session);

        if ( $method === 'redirect' ) {
            redirect(generateTokenUrl($token));
        } else {
            sendJson( [ 'access_token' => $token ] );
        }
   }
}

function getSecure()
{
    return isset($_SERVER['HTTPS']) && !in_array(strtolower($_SERVER['HTTPS']), ['off', 'no']);
}

function getFullUrl()
{
    return (getSecure() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function getToken($config, $session)
{
    $time = getConfig($config, 'time', time());
    $ttl = getConfig($config, 'ttl', 5);
    $algorithm = getConfig($config, 'algorithm', 'HS256');
    $secret = getConfig($config, 'secret', '');
    $subject = isset( $session['usertoken'] ) ? $session['usertoken'] : '';
    $audience = $config['audience'];
    $issuer = getFullUrl();
    $token = generateToken($subject, $audience, $issuer, $time, $ttl, $algorithm, $secret);

    return $token;
}

function generateTokenUrl($token)
{
    $redirectUri = getConfig($config, 'redirectUri', '');

    return $redirectUri . '?access_token=' . $token;
}

// FJ add $method param.
function handlePost($config, &$session, $usertoken, $method = 'redirect')
{
    $validate = getConfig($config, 'validate', function ($usertoken) {return false;});
    $valid = call_user_func($validate, $usertoken);
    if (!$valid) {
        if ( $method === 'redirect' ) {
            serve('login.html');
        } else {
            sendJson( [ 'error' => 'Invalid token' ] );
        }
    } else {
        session_regenerate_id();
        $session['usertoken'] = $usertoken;
        $token = getToken($config, $session);

        if ( $method === 'redirect' ) {
            redirect(generateTokenUrl($token));
        } else {
            sendJson( [ 'access_token' => $token ] );
        }
    }
}

function getConfigArray($config, $key, $default)
{
    return array_filter(array_map('trim', explode(',', getConfig($config, $key, $default))));
}

function getConfig($config, $key, $default)
{
    return isset($config[$key]) ? $config[$key] : $default;
}

// FJ add auth without redirect_uri (interactive), returning JSON directly.
function main($config)
{
    session_start();

    $clientId = isset($_GET['client_id']) ? $_GET['client_id'] : 'default';
    $audience = isset($_GET['audience']) ? $_GET['audience'] : 'api.php';
    $redirectUri = isset($_GET['redirect_uri']) ? $_GET['redirect_uri'] : '';
    if (isset($config[$clientId][$audience])) {
        $config = $config[$clientId][$audience];
        $config['clientId'] = $clientId;
        $config['audience'] = $audience;
        $redirects = getConfigArray($config, 'redirects', '');
        if (in_array($redirectUri, $redirects)) {
            $config['redirectUri'] = $redirectUri;
        }
        if (isset($config['redirectUri'])) {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    handleGet($config, $_SESSION);
                    break;
                case 'POST':
                    handlePost($config, $_SESSION, $_POST['usertoken']);
                    break;
            }
        } else {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    handleGet($config, $_SESSION, 'json');
                    break;
                case 'POST':
                    handlePost($config, $_SESSION, $_POST['usertoken'], 'json');
                    break;
            }
        }
    } else {
        echo "Could not find configuration: $clientId / $audience";
    }
}
