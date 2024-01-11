<?php

require_once '../../config.inc.php';

require_once 'includes/common.fnc.php';

RESTAPISession();

$config_options = [
	'driver' => ( ! empty( $DatabaseType ) && $DatabaseType === 'mysql' ? 'mysql' : 'pgsql' ),
	'address' => $DatabaseServer,
	'username' => $DatabaseUsername,
	'password' => $DatabasePassword,
	'database' => $DatabaseName,
	'controllers' => 'records,openapi,columns',
	// @link https://github.com/mevdschee/php-crud-api/tree/master#middleware
	'middlewares' => 'cors,authorization,sanitation,jwtAuth,pageLimits',
	// @link https://github.com/mevdschee/php-crud-api#prevent-database-scraping
	'pageLimits.records' => 1000,
	// @link https://github.com/mevdschee/php-crud-api#jwt-authentication
	'jwtAuth.secret' => ( defined( 'ROSARIO_REST_API_SECRET' ) ? ROSARIO_REST_API_SECRET : 'defaultPassphrase' ),
	// Do not allow update operations for PASSWORD column (students & staff tables mainly).
	// @link https://github.com/mevdschee/php-crud-api/tree/master#authorizing-tables-columns-and-records
	'authorization.columnHandler' => function ($operation, $tableName, $columnName) {
		if ( $operation !== 'update' ) {
			return true;
		}
		return $columnName !== 'password';
	},
	// Sanitize input: strip HTML tags.
	// @todo Edge cases: find a solution to allow for HTML fields such as templates?
	// @link https://github.com/mevdschee/php-crud-api/tree/master#sanitizing-input
	'sanitation.handler' => function ($operation, $tableName, $column, $value) {
		return strip_tags( $value );
	},
	'openApiBase' => '{"info":{"title":"RosarioSIS REST API","version":"1.0"}}',
];

if ( ! empty( $DatabasePort ) )
{
	$config_options['port'] = $DatabasePort;
}

$config = new Tqdev\PhpCrudApi\Config( $config_options );

$request = Tqdev\PhpCrudApi\RequestFactory::fromGlobals();
$api = new Tqdev\PhpCrudApi\Api($config);
$response = $api->handle($request);
Tqdev\PhpCrudApi\ResponseUtils::output($response);
