/**
 * Install MySQL
 * - Add program config options if any (to every schools)
 *
 * @package Force Password Change
 */

/**
 * config Table
 *
 * syear: school year (school may have various years in DB)
 * school_id: may exists various schools in DB
 * program: convention is plugin name, for ex.: 'force_password_change'
 * title: for ex.: 'FORCE_PASSWORD_CHANGE_[your_config]'
 * value: string
 */
--
-- Data for Name: config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO config (school_id, title, config_value)
SELECT 0, 'FORCE_PASSWORD_CHANGE_USERNAMES', ','
FROM DUAL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='FORCE_PASSWORD_CHANGE_USERNAMES');
