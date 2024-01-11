/**
 * Install PostgreSQL
 * - Add program config options if any (to every schools)
 *
 * @package TinyMCE Record Audio Video plugin
 */

/**
 * config Table
 *
 * syear: school year (school may have various years in DB)
 * school_id: may exists various schools in DB
 * program: convention is plugin name, for ex.: 'tinymce_record_audio_video'
 * title: for ex.: 'TINYMCE_RECORD_AUDIO_VIDEO_[your_config]'
 * value: string
 */
--
-- Data for Name: config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO config (school_id, title, config_value)
SELECT 0, 'TINYMCE_RECORD_AUDIO_VIDEO_TIME_LIMIT', '120';
