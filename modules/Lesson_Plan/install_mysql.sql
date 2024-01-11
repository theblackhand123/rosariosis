/**
 * Install MySQL
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Lesson Plan module
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
SELECT 1, 'Lesson_Plan/LessonPlans.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Lesson_Plan/LessonPlans.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Lesson_Plan/LessonPlans.php', 'Y', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Lesson_Plan/LessonPlans.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Lesson_Plan/LessonPlans.php', 'Y', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Lesson_Plan/LessonPlans.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Lesson_Plan/Read.php', 'Y', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Lesson_Plan/Read.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Lesson_Plan/AddLesson.php', 'Y', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Lesson_Plan/AddLesson.php'
    AND profile_id=2);


/**
 * Add module tables
 */

/**
 * Lessons table
 */
--
-- Name: lesson_plan_lessons; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS lesson_plan_lessons (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    course_period_id integer NOT NULL,
    title text NOT NULL,
    on_date date NOT NULL,
    location text,
    length_minutes integer,
    lesson_number varchar(50),
    data longtext,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id)
);


/**
 * Items table
 */
--
-- Name: lesson_plan_items; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS lesson_plan_items (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    lesson_id integer NOT NULL,
    data longtext NOT NULL,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (lesson_id) REFERENCES lesson_plan_lessons(id)
);
