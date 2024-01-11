/**
 * Install MySQL
 * - Add program config options if any (to every schools)
 *
 * @package Google Social Login plugin
 */

/**
 * config Table
 *
 * syear: school year (school may have various years in DB)
 * school_id: may exists various schools in DB
 * program: convention is plugin name, for ex.: 'googlesociallogin'
 * title: for ex.: 'GOOGLE_SOCIAL_LOGIN_[your_config]'
 * value: string
 */
--
-- Data for Name: config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO config (school_id, title, config_value)
SELECT 0, 'GOOGLE_SOCIAL_LOGIN_CLIENT_ID', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='GOOGLE_SOCIAL_LOGIN_CLIENT_ID');

INSERT INTO config (school_id, title, config_value)
SELECT 0, 'GOOGLE_SOCIAL_LOGIN_CLIENT_SECRET', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='GOOGLE_SOCIAL_LOGIN_CLIENT_SECRET');

INSERT INTO config (school_id, title, config_value)
SELECT 0, 'GOOGLE_SOCIAL_LOGIN_HOSTED_DOMAIN', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='GOOGLE_SOCIAL_LOGIN_HOSTED_DOMAIN');
