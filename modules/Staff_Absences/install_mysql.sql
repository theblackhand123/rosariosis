/**
 * Install MySQL
 * Required if the module adds programs to other modules
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Staff Absences module
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
SELECT 1, 'Staff_Absences/AddAbsence.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/AddAbsence.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Staff_Absences/AddAbsence.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/AddAbsence.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Staff_Absences/Absences.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/Absences.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Staff_Absences/Absences.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/Absences.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Staff_Absences/AbsenceBreakdown.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/AbsenceBreakdown.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Staff_Absences/AbsenceFields.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/AbsenceFields.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Staff_Absences/CancelledClasses.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/CancelledClasses.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Staff_Absences/CancelledClasses.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/CancelledClasses.php'
    AND profile_id=2);


/**
 * Add module tables
 */

/**
 * Staff Absences table
 */
--
-- Name: staff_absences; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS staff_absences (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    staff_id integer NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    start_date timestamp NOT NULL,
    end_date timestamp NOT NULL,
    custom_1 text, -- Type.
    custom_2 text, -- Reason.
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp,
    created_by integer NOT NULL
);


/**
 * Add module tables
 */

/**
 * Staff Absence Fields table
 */
--
-- Name: staff_absence_fields; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS staff_absence_fields (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type varchar(10) NOT NULL,
    title text NOT NULL,
    sort_order numeric,
    select_options text,
    required varchar(1),
    default_selection text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


/**
 * Staff Absence Course Periods table
 */
--
-- Name: staff_absence_course_periods; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS staff_absence_course_periods (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    staff_absence_id integer NOT NULL,
    FOREIGN KEY (staff_absence_id) REFERENCES staff_absences(id),
    course_period_id integer NOT NULL,
    FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id)
);


--
-- Data for Name: staff_absence_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO staff_absence_fields VALUES (NULL, 'select', 'Type', 1, 'Sick
Vacation', 'Y', NULL, NULL, NULL);
INSERT INTO staff_absence_fields VALUES (NULL, 'text', 'Reason', 2, NULL, NULL, NULL, NULL, NULL);


--
-- Data for Name: templates; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO templates (modname, staff_id, template)
SELECT 'Staff_Absences/AddAbsence.php', 0, 'Hello,

__FULL_NAME__ will be absent from __START_DATE__ to __END_DATE__.
Reason: __STAFF_ABSENCE_2__'
FROM DUAL
WHERE NOT EXISTS (SELECT modname
    FROM templates
    WHERE modname='Staff_Absences/AddAbsence.php'
    AND staff_id=0);
