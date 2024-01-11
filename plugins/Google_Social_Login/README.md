# Google Social Login plugin

![screenshot](https://gitlab.com/francoisjacquet/Google_Social_Login/raw/master/screenshot.png?inline=false)

https://www.rosariosis.org/plugins/google-social-login/

Version 10.0 - July, 2022

Author François Jacquet

License Gnu GPL v2

Sponsored by Santa Cecilia school, Salvador

## Description

Google Social Login plugin for RosarioSIS. User login using Google as an external identity provider (through the **OAuth 2.0** protocol).

The user clicks the "Login with Google" link on the login screen. He is redirected to the provider login screen. Then, if authentication is successful, RosarioSIS will try to match the user email address with an existing user in RosarioSIS database and log the user in.

Note: only existing users in RosarioSIS can login. They must have a Username.

Note 2: other identity providers are available. Check these pages for [Official](https://oauth2-client.thephpleague.com/providers/league/) and [Community](https://oauth2-client.thephpleague.com/providers/thirdparty/) providers.

**Warning**: even after login out of RosarioSIS, you are still logged in your Google account. If you are on a public computer, go to the Google site and logout.

Translated in [French](https://www.rosariosis.org/fr/plugins/google-social-login/) & [Spanish](https://www.rosariosis.org/es/plugins/google-social-login/).

### Setup

To use Google as a OAuth2.0 provider, you will need a Google client ID and client secret. Please follow the [Google instructions](https://developers.google.com/identity/protocols/OpenIDConnect#registeringyourapp) to create the required credentials.

Add the redirect URI appearing on the plugin _Configuration_ screen.


## Content

Plugin configuration:

- Client ID. Example: `123456789123-pu4d0jp6ceohcec0aqfru46mfnh742pa.apps.googleusercontent.com`.
- Client Secret. Example: `12_nzjrPCl3e0iThx12345EoB`.
- Hosted Domain. Restrict access to users on your G Suite/Google Apps for Business accounts.
- Redirect URI.


## Install

Copy the `Google_Social_Login/` folder (if named `Google_Social_Login-master`, rename it) and its content inside the `plugins/` folder of RosarioSIS.

Go to _School > Configuration > Plugins_ and click "Activate".

Requires: RosarioSIS 7.6+, PHP **7.0+** and the `openssl` PHP extension.
