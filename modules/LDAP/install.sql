/**
 * Install SQL
 * - Add program config options if any (to every schools)
 *
 * @package LDAP plugin
 */

/**
 * config Table
 *
 * syear: school year (school may have various years in DB)
 * school_id: may exists various schools in DB
 * program: convention is plugin name, for ex.: 'ldap'
 * title: for ex.: 'LDAP_[your_config]'
 * value: string
 */
--
-- Data for Name: config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO config (school_id, title, config_value)
SELECT 0, 'LDAP_SERVER_URI', NULL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='LDAP_SERVER_URI');

INSERT INTO config (school_id, title, config_value)
SELECT 0, 'LDAP_USER_BASE_DN', NULL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='LDAP_USER_BASE_DN');

INSERT INTO config (school_id, title, config_value)
SELECT 0, 'LDAP_IS_ACTIVE_DIRECTORY', NULL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='LDAP_IS_ACTIVE_DIRECTORY');

INSERT INTO config (school_id, title, config_value)
SELECT 0, 'LDAP_USERNAME', NULL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='LDAP_USERNAME');

INSERT INTO config (school_id, title, config_value)
SELECT 0, 'LDAP_PASSWORD', NULL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='LDAP_PASSWORD');
