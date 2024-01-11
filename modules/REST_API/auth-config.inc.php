<?php

require_once '../../config.inc.php';

require_once 'includes/common.fnc.php';

RESTAPISession();

main([
	'default' => [
		'api.php' => [
			// Should be defined in config.inc.php.
			'secret' => ( defined( 'ROSARIO_REST_API_SECRET' ) ? ROSARIO_REST_API_SECRET : 'defaultPassphrase' ),
			// Always redirect to same URL, if need be.
			'redirects' => ( isset( $_GET['redirect_uri'] ) ? $_GET['redirect_uri'] : '' ),
			'validate' => 'RosarioAPILogin',
		],
	],
]);
