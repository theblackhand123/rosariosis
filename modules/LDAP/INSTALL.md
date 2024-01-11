# Install OpenLDAP

Installation instructions for Debian / Ubuntu Linux OS. Will install OpenLDAP and phpLDAPadmin.

https://computingforgeeks.com/how-to-install-and-configure-openldap-server-on-debian/
https://serverfault.com/questions/98730/how-to-configure-open-ldap-to-work-on-localhost#98752

## PHP ldap extension

```bash
$ sudo apt install php-ldap
```


## Install OpenLDAP
```bash
$ sudo apt -y install slapd ldap-utils
```
Set admin password: `password`.


## Info about server & admin user
```bash
$ sudo slapcat
```
```
dn: dc=local
objectClass: top
objectClass: dcObject
objectClass: organization
o: local
dc: local
structuralObjectClass: organization
entryUUID: f8a3a4ce-7271-1039-87c7-013074665388
creatorsName: cn=admin,dc=local
createTimestamp: 20190923171857Z
entryCSN: 20190923171857.585179Z#000000#000#000000
modifiersName: cn=admin,dc=local
modifyTimestamp: 20190923171857Z

dn: cn=admin,dc=local
objectClass: simpleSecurityObject
objectClass: organizationalRole
cn: admin
description: LDAP administrator
userPassword:: e1NTSEF9WklwdEg5SmRMTnJzcDVxY1h4YldjKy9zMGRoN1owcU0=
structuralObjectClass: organizationalRole
entryUUID: f8a427a0-7271-1039-87c8-013074665388
creatorsName: cn=admin,dc=local
createTimestamp: 20190923171857Z
entryCSN: 20190923171857.588583Z#000000#000#000000
modifiersName: cn=admin,dc=local
modifyTimestamp: 20190923171857Z
```

## Adding a base DN for users and groups

Replace `local` with your actual dc(s), ie: `dc=mydomain,dc=com`:
```
dn: ou=people,dc=local
objectClass: organizationalUnit
ou: people

dn: ou=groups,dc=local
objectClass: organizationalUnit
ou: groups
```
```bash
$ sudo ldapadd -x -D cn=admin,dc=local -W -f basedn.ldif
```


##  Install phpLDAPadmin
http://phpldapadmin.sourceforge.net/wiki/index.php/Installation
https://github.com/leenooks/phpLDAPadmin/releases

Create `config/config.php` by copying example file and customize:
```php
/* Use this array to map attribute names to user friendly names. For example, if
   you don't want to see "facsimileTelephoneNumber" but rather "Fax". */
// $config->custom->appearance['friendly_attrs'] = array();
$config->custom->appearance['friendly_attrs'] = array(
	'facsimileTelephoneNumber' => 'Fax',
	'gid'                      => 'Group',
	'mail'                     => 'Email',
	'telephoneNumber'          => 'Telephone',
	'uid'                      => 'User Name',
	'userPassword'             => 'Password'
);
```
Set server at line 286
```php
$servers->setValue('server','name','local');
```

Login with:
`cn=admin,dc=local` and `password`

### Add a group

Before user, so we have a GID!!
"Posix Group"

Distinguished Name: `cn=students,ou=groups,dc=local`
GID 500
Name: students

### Add a user

Template: Generic: User Account

Distinguished Name: `cn=Francois Jacquet,ou=people,dc=local`
Francois Jacquet (First Last)
User ID: uid = fjacquet
Password: fjacquet
UID Number	1000
GID Number	500 (students)
/home/users/fjacquet
objectClass: inetOrgPerson (structure),posixAccount,top

### Search users

All. Filter: `uid=*`

One. Filter: `uid=fjacquet`

### Search groups

One. Filter: `cn=students`


## Terminology

- **CN** = Common Name
- **OU** = Organizational Unit
- **DC** = Domain Component
- **DN** = Distinguished Name
