/**
 * Install SQL
 * - Add program config options if any (to every schools)
 *
 * @package Automatic Attendance
 */

/**
 * config Table
 *
 * syear: school year (school may have various years in DB)
 * school_id: may exists various schools in DB
 * program: convention is plugin name, for ex.: 'email_smtp'
 * title: for ex.: 'AUTOMATIC_ATTENDANCE_[your_config]'
 * value: string
 */
--
-- Data for Name: config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO config (school_id, title, config_value)
SELECT DISTINCT sch.id, 'AUTOMATIC_ATTENDANCE_CRON_DAY', CURRENT_DATE - INTERVAL '1 DAY'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='AUTOMATIC_ATTENDANCE_CRON_DAY');

INSERT INTO config (school_id, title, config_value)
SELECT DISTINCT sch.id, 'AUTOMATIC_ATTENDANCE_CRON_HOUR', '2359'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='AUTOMATIC_ATTENDANCE_CRON_HOUR');
