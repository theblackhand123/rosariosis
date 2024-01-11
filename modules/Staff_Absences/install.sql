/**
 * Install PostgreSQL
 * Required if the module adds programs to other modules
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Staff Absences module
 */

-- Fix #102 error language "plpgsql" does not exist
-- http://timmurphy.org/2011/08/27/create-language-if-it-doesnt-exist-in-postgresql/
--
-- Name: create_language_plpgsql(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION create_language_plpgsql()
RETURNS BOOLEAN AS $$
    CREATE LANGUAGE plpgsql;
    SELECT TRUE;
$$ LANGUAGE SQL;

SELECT CASE WHEN NOT (
    SELECT TRUE AS exists FROM pg_language
    WHERE lanname='plpgsql'
    UNION
    SELECT FALSE AS exists
    ORDER BY exists DESC
    LIMIT 1
) THEN
    create_language_plpgsql()
ELSE
    FALSE
END AS plpgsql_created;

DROP FUNCTION create_language_plpgsql();


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
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/AddAbsence.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Staff_Absences/AddAbsence.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/AddAbsence.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Staff_Absences/Absences.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/Absences.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Staff_Absences/Absences.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/Absences.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Staff_Absences/AbsenceBreakdown.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/AbsenceBreakdown.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Staff_Absences/AbsenceFields.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/AbsenceFields.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Staff_Absences/CancelledClasses.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Staff_Absences/CancelledClasses.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Staff_Absences/CancelledClasses.php', 'Y', null
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

CREATE OR REPLACE FUNCTION create_table_staff_absences() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'staff_absences') THEN
    RAISE NOTICE 'Table "staff_absences" already exists.';
    ELSE
        CREATE TABLE staff_absences (
            id serial PRIMARY KEY,
            syear numeric(4,0) NOT NULL,
            staff_id integer NOT NULL REFERENCES staff(staff_id),
            start_date timestamp NOT NULL,
            end_date timestamp NOT NULL,
            custom_1 text, -- Type.
            custom_2 text, -- Reason.
            created_at timestamp DEFAULT current_timestamp,
            updated_at timestamp,
            created_by integer NOT NULL
        );

        CREATE TRIGGER set_updated_at
            BEFORE UPDATE ON staff_absences
            FOR EACH ROW EXECUTE PROCEDURE set_updated_at();
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_staff_absences();
DROP FUNCTION create_table_staff_absences();


--
-- Name: staff_absences_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_staff_absences_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='staff_absences_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE INDEX staff_absences_ind ON staff_absences (staff_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_staff_absences_ind();
DROP FUNCTION create_index_staff_absences_ind();


/**
 * Staff Absence Fields table
 */
--
-- Name: staff_absence_fields; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_staff_absence_fields() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'staff_absence_fields') THEN
    RAISE NOTICE 'Table "staff_absence_fields" already exists.';
    ELSE
        CREATE TABLE staff_absence_fields (
            id serial PRIMARY KEY,
            type varchar(10) NOT NULL,
            title text NOT NULL,
            sort_order numeric,
            select_options text,
            required varchar(1),
            default_selection text,
            created_at timestamp DEFAULT current_timestamp,
            updated_at timestamp
        );

        CREATE TRIGGER set_updated_at
            BEFORE UPDATE ON staff_absence_fields
            FOR EACH ROW EXECUTE PROCEDURE set_updated_at();
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_staff_absence_fields();
DROP FUNCTION create_table_staff_absence_fields();


/**
 * Staff Absence Course Periods table
 */
--
-- Name: staff_absence_course_periods; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_staff_absence_course_periods() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'staff_absence_course_periods') THEN
    RAISE NOTICE 'Table "staff_absence_course_periods" already exists.';
    ELSE
        CREATE TABLE staff_absence_course_periods (
            id serial PRIMARY KEY,
            staff_absence_id integer NOT NULL REFERENCES staff_absences(id),
            course_period_id integer NOT NULL REFERENCES course_periods(course_period_id)
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_staff_absence_course_periods();
DROP FUNCTION create_table_staff_absence_course_periods();


--
-- Data for Name: staff_absence_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO staff_absence_fields VALUES (NEXTVAL('staff_absence_fields_id_seq'), 'select', 'Type', 1, 'Sick
Vacation', 'Y', NULL);
INSERT INTO staff_absence_fields VALUES (NEXTVAL('staff_absence_fields_id_seq'), 'text', 'Reason', 2, NULL, NULL, NULL);


--
-- Data for Name: templates; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO templates (modname, staff_id, template)
SELECT 'Staff_Absences/AddAbsence.php', 0, 'Hello,

__FULL_NAME__ will be absent from __START_DATE__ to __END_DATE__.
Reason: __STAFF_ABSENCE_2__'
WHERE NOT EXISTS (SELECT modname
    FROM templates
    WHERE modname='Staff_Absences/AddAbsence.php'
    AND staff_id=0);
