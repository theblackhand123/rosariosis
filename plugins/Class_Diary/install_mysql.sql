/**
 * Install MySQL
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Class Diary module
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
SELECT 1, 'Class_Diary/Diaries.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Class_Diary/Diaries.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Class_Diary/Diaries.php', 'Y', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Class_Diary/Diaries.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Class_Diary/Diaries.php', 'Y', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Class_Diary/Diaries.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Class_Diary/Read.php', 'Y', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Class_Diary/Read.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Class_Diary/Write.php', 'Y', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Class_Diary/Write.php'
    AND profile_id=2);


/**
 * Add module tables
 */

/**
 * Messages table
 */
--
-- Name: class_diary_messages; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS class_diary_messages (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    course_period_id integer NOT NULL,
    data longtext,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id)
);
