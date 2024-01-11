/**
 * Install MySQL
 * Required if the module adds programs to other modules
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Jitsi_Meet module
 */

/**
 * profile_exceptions Table
 *
 * profile_id:
 * - 0: student
 * - 1: admin
 * - 2: teacher
 * - 3: parent
 * modname: should match the Menu.php entries
 * can_use: 'Y'
 * can_edit: 'Y' or null (generally null for non admins)
 */
--
-- Data for Name: profile_exceptions; Type: TABLE DATA;
--

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Jitsi_Meet/Configuration.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Configuration.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Jitsi_Meet/Rooms.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Rooms.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Jitsi_Meet/Rooms.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Rooms.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Jitsi_Meet/Meet.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Meet.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Jitsi_Meet/Meet.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Meet.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Jitsi_Meet/Meet.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Meet.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Jitsi_Meet/Meet.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Meet.php'
    AND profile_id=0);


/**
 * config Table
 *
 * title: for ex.: 'JITSI_MEET_[your_config]'
 * value: string
 */
--
-- Data for Name: config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_DOMAIN', 'framatalk.org'
FROM DUAL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_DOMAIN');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_TOOLBAR', 'microphone,camera,hangup,desktop,fullscreen,profile,chat,recording,settings,raisehand,videoquality,tileview'
FROM DUAL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_TOOLBAR');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_SETTINGS', 'devices,language'
FROM DUAL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_SETTINGS');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_WIDTH', '100%'
FROM DUAL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_WIDTH');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_HEIGHT', '700'
FROM DUAL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_HEIGHT');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_BRAND_WATERMARK_LINK', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_BRAND_WATERMARK_LINK');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_DISABLE_VIDEO_QUALITY_LABEL', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_DISABLE_VIDEO_QUALITY_LABEL');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_JAAS_APP_ID', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_JAAS_APP_ID');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_JAAS_JWT', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_JAAS_JWT');

/**
 * Add module tables
 */

/**
 * Jitsi_Meet Rooms table
 */
--
-- Name: jitsi_meet_rooms; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS jitsi_meet_rooms (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    -- school_id integer NOT NULL,
    syear numeric(4,0) NOT NULL,
    title text NOT NULL,
    subject text,
    password text,
    start_audio_only char(1),
    students text,
    users text,
    owner_id integer,
    created_at timestamp DEFAULT current_timestamp
);
