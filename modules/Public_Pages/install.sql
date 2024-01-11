/**
 * Install PostgreSQL
 * - Add program config options if any (to every schools)
 *
 * @package Public Pages
 */

/**
 * config Table
 *
 * syear: school year (school may have various years in DB)
 * school_id: may exists various schools in DB
 * program: convention is plugin name, for ex.: 'public_pages'
 * title: for ex.: 'PUBLIC_PAGES_[your_config]'
 * value: string
 */
--
-- Data for Name: config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO config (school_id, title, config_value)
SELECT 0, 'PUBLIC_PAGES', '||school||calendar||markingperiods||courses||'
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='PUBLIC_PAGES');

