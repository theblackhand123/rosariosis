# LDAP plugin

![screenshot](https://gitlab.com/francoisjacquet/LDAP/raw/master/screenshot.png?inline=false)

https://www.rosariosis.org/plugins/ldap/

Version 10.3 - December, 2023

Author François Jacquet

License Gnu GPL v2

## Description

LDAP plugin for RosarioSIS. Provides user and student authentication (login) using an LDAP server: [OpenLDAP](http://www.openldap.org/) or [Active Directory](https://en.wikipedia.org/wiki/Active_Directory) (Windows). LDAP (Lightweight Directory Access Protocol) is a centralized directory to share information about users throughout the network.

Translated in [French](https://www.rosariosis.org/fr/plugins/ldap/), [Spanish](https://www.rosariosis.org/es/plugins/ldap/) and Portuguese (Brazil).

### How it works

The LDAP plugin provides basic LDAP authentication. Simply enter your OpenLDAP or Active Directory server details in the plugin Configuration and you are ready to go.
It does not synchronize users and students with your LDAP server. For this see the Premium plugin below.
This means you will have to manually create accounts (and remove access).
Users found on the LDAP server ( **username** match) can login if their password matches the LDAP user one.
Password change or reset from RosarioSIS will have no effect for LDAP users. This being said, users will still be able to login with their RosarioSIS password in case the LDAP server is down.
Other users will login normally using their password set from RosarioSIS.

Overrides the "Force Password Change on First Login" school configuration option.

Note: this plugin was not tested with Active Directory.

### Terminology

- **CN** = Common Name
- **OU** = Organizational Unit
- **DC** = Domain Component
- **DN** = Distinguished Name

## Premium plugin (sponsors are welcome)

- Automatic synchronization (CRON).
- Account creation, on first login: sets RosarioSIS password.
- Map LDAP user groups to User profiles (student, teacher, admin, etc.).
- Account suspension - Drop students.

### CRON / sync

0. No UPDATE. Users can change their information independently from LDAP.
1. CRON to CREATE users after midnight (add to list in Config): username=`uid`, email=`mail`, (first or full) name=`name`, last name (optional).
2. CRON to DELETE (suspend or drop) users after midday.
3. FORCE synchronization button.
4. Set RosarioSIS password in DB on first successful login.

Note: Student accounts will created as _Inactive_. You will have to manually enroll them so they can login.

### Groups

Configure groups:

0. Example group: `admin` => `cn=admin` + `,` + Group base DN => `cn=admin,ou=groups,dc=mydomain,dc=com`
1. List available groups: `cn=*`  + `,` + Group base DN => `cn=*,ou=groups,dc=mydomain,dc=com`
2. Map a Group to a Profile.


## Content

Plugin configuration:

- Is Active Directory?
- LDAP server URI. Examples: `ldap://127.0.0.1:389` or `ldaps://192.168.0.1:636` for LDAP over SSL.
- User base DN. Examples: `CN=Users,DC=mydomain,DC=com` for Active Directory, `ou=people,dc=mydomain,dc=com` for OpenLDAP.
- Check. Server connection and user search.

## Test LDAP server

https://www.forumsys.com/tutorials/integration-how-to/ldap/online-ldap-test-server/

- URI: `ldap://ldap.forumsys.com`
- User Base DN: `dc=example,dc=com`
- Bind DN (Username): `uid=tesla,dc=example,dc=com`
- Password: `password`
- Test command, list tesla users: `ldapsearch -W -h ldap.forumsys.com -D "uid=tesla,dc=example,dc=com" -b "dc=example,dc=com"`


## Install

Copy the `LDAP/` folder (if named `LDAP-master`, rename it) and its content inside the `plugins/` folder of RosarioSIS.

Go to _School > Configuration > Plugins_ and click "Activate".

Requires: RosarioSIS 5.4+ & PHP **ldap** extension.
