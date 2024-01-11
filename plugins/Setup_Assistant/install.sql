/**
 * Install PostgreSQL
 * - Add program config options if any (to every schools)
 *
 * @package Setup Assistant
 */

/**
 * program_config Table
 *
 * syear: school year (school may have various years in DB)
 * school_id: may exists various schools in DB
 * program: convention is plugin name, for ex.: 'setup_assistant'
 * title: for ex.: 'SETUP_ASSISTANT_[your_program_config]'
 * value: string
 */
--
-- Data for Name: program_config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO program_config (syear, school_id, program, title, value)
SELECT sch.syear, sch.id, 'setup_assistant', 'INACTIVE_admin', ''
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM program_config
    WHERE title='INACTIVE_admin'
    AND program='setup_assistant');

INSERT INTO program_config (syear, school_id, program, title, value)
SELECT sch.syear, sch.id, 'setup_assistant', 'INACTIVE_teacher', ''
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM program_config
    WHERE title='INACTIVE_teacher'
    AND program='setup_assistant');

INSERT INTO program_config (syear, school_id, program, title, value)
SELECT sch.syear, sch.id, 'setup_assistant', 'INACTIVE_parent', 'Y'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM program_config
    WHERE title='INACTIVE_parent'
    AND program='setup_assistant');

INSERT INTO program_config (syear, school_id, program, title, value)
SELECT sch.syear, sch.id, 'setup_assistant', 'INACTIVE_student', 'Y'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM program_config
    WHERE title='INACTIVE_student'
    AND program='setup_assistant');
