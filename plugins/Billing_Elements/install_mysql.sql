/**
 * Install MySQL
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Billing Elements module
 */

-- Fix #102 error language "plpgsql" does not exist
-- http://timmurphy.org/2011/08/27/create-language-if-it-doesnt-exist-in-postgresql/
--
-- Name: create_language_plpgsql(); Type: FUNCTION; Schema: public; Owner: postgres
--

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
SELECT 1, 'Billing_Elements/Elements.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/Elements.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Billing_Elements/Elements.php', 'Y', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/Elements.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Billing_Elements/Elements.php', 'Y', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/Elements.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Billing_Elements/MassAssignElements.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/MassAssignElements.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Billing_Elements/StudentElements.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/StudentElements.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Billing_Elements/StudentElements.php', 'Y', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/StudentElements.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Billing_Elements/StudentElements.php', 'Y', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/StudentElements.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Billing_Elements/CategoryBreakdown.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/CategoryBreakdown.php'
    AND profile_id=1);



/**
 * Add module tables
 */


/**
 * Billing Elements table
 */
--
-- Name: billing_elements; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--
-- Note: Unlinke with PostgreSQL, no need to manually create index for (school_id, syear). MySQL automatically creates it.
--

CREATE TABLE IF NOT EXISTS billing_elements (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    category_id integer NOT NULL,
    ref varchar(50),
    title text NOT NULL,
    amount numeric(14,2) NOT NULL,
    description longtext,
    grade_level integer, -- @deprecated SQL GRADE_LEVEL column, since 11.0 use GRADE_LEVELS instead
    grade_levels text,
    course_period_id integer,
    rollover char(1),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id, syear) REFERENCES schools(id, syear)
);


/**
 * Categories table
 */
--
-- Name: categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS billing_categories (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    title text NOT NULL,
    sort_order numeric,
    color varchar(255)
);


/**
 * Billing Student Elements table
 */
--
-- Name: billing_student_elements; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS billing_student_elements (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    -- syear numeric(4,0) NOT NULL,
    -- school_id integer NOT NULL,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    element_id integer NOT NULL,
    FOREIGN KEY (element_id) REFERENCES billing_elements(id),
    fee_id integer NOT NULL,
    FOREIGN KEY (fee_id) REFERENCES billing_fees(id) ON DELETE CASCADE,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
    -- FOREIGN KEY (school_id, syear) REFERENCES schools(id, syear)
);
