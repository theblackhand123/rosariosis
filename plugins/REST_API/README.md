# REST API plugin

![screenshot](https://gitlab.com/francoisjacquet/REST_API/raw/master/screenshot.png?inline=false)

https://gitlab.com/francoisjacquet/REST_API/

Version 10.2 - August, 2023

Author François Jacquet

License MIT

## Description

This RosarioSIS plugin provides a REST API (Application Programming Interface).
Access and explore the API using the example client. Authentication is done with JWT (JSON Web Token).

It is based on the [PHP-CRUD-API](https://github.com/mevdschee/php-crud-api) package, see `README_API.md` for more info.
Please check your server meets the minimum requirements below.

Translated in French & Spanish.

Note: to prevent database scraping, pages are limited to 1000 records.

Note 2: a user token becomes invalid when the admin user who created it has his account deleted or switched to the "No Access" profile.

## Content

Plugin Configuration
- API URL
- Authentication URL
- Example client. For example, enter `records/access_log` in the "API Call" to list (GET) the `access_log` table entries.
- User token

## Install

Copy the `REST_API/` folder (if named `REST_API-master`, rename it) and its content inside the `plugins/` folder of RosarioSIS.

Go to _School > Configuration > Plugins_ and click "Activate".

Enter the plugin _Configuration_ and click "Save" to actually save the generated _User Token_.

Edit your `config.in.php` file and add the following line (change the passphrase):
```php
define( 'ROSARIO_REST_API_SECRET', 'thisIsMyPassphraseChangeMe' );
```

Requires RosarioSIS 5.0+, PHP **7.0**+, PostgreSQL **9.1**+ or MySQL **5.6**+
