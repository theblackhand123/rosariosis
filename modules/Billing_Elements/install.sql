/**
 * Install PostgreSQL
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
SELECT 1, 'Billing_Elements/Elements.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/Elements.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Billing_Elements/Elements.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/Elements.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Billing_Elements/Elements.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/Elements.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Billing_Elements/MassAssignElements.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/MassAssignElements.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Billing_Elements/StudentElements.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/StudentElements.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Billing_Elements/StudentElements.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/StudentElements.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Billing_Elements/StudentElements.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/StudentElements.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Billing_Elements/CategoryBreakdown.php', 'Y', 'Y'
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

CREATE OR REPLACE FUNCTION create_table_billing_elements() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname=CURRENT_SCHEMA()
        AND tablename='billing_elements') THEN
    RAISE NOTICE 'Table "billing_elements" already exists.';
    ELSE
        CREATE TABLE billing_elements (
            id serial PRIMARY KEY,
            syear numeric(4,0) NOT NULL,
            school_id integer NOT NULL,
            category_id integer NOT NULL,
            ref varchar(50),
            title text NOT NULL,
            amount numeric(14,2) NOT NULL,
            description text,
            grade_level integer, -- @deprecated SQL GRADE_LEVEL column, since 11.0 use GRADE_LEVELS instead
            grade_levels text,
            course_period_id integer,
            rollover char(1),
            created_at timestamp DEFAULT current_timestamp,
            updated_at timestamp,
            FOREIGN KEY (school_id, syear) REFERENCES schools(id, syear)
        );

        CREATE TRIGGER set_updated_at
            BEFORE UPDATE ON billing_elements
            FOR EACH ROW EXECUTE PROCEDURE set_updated_at();
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_billing_elements();
DROP FUNCTION create_table_billing_elements();



--
-- Name: billing_elements_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_billing_elements_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='billing_elements_ind'
        AND n.nspname=CURRENT_SCHEMA
    ) THEN
        CREATE INDEX billing_elements_ind ON billing_elements USING btree (syear, school_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_billing_elements_ind();
DROP FUNCTION create_index_billing_elements_ind();


/**
 * Categories table
 */
--
-- Name: categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_billing_categories() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'billing_categories') THEN
    RAISE NOTICE 'Table "billing_categories" already exists.';
    ELSE
        CREATE TABLE billing_categories (
            id serial PRIMARY KEY,
            school_id integer NOT NULL,
            title text NOT NULL,
            sort_order numeric,
            color varchar(255)
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_billing_categories();
DROP FUNCTION create_table_billing_categories();


/**
 * Billing Student Elements table
 */
--
-- Name: billing_student_elements; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_billing_student_elements() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname=CURRENT_SCHEMA()
        AND tablename='billing_student_elements') THEN
    RAISE NOTICE 'Table "billing_student_elements" already exists.';
    ELSE
        CREATE TABLE billing_student_elements (
            id serial PRIMARY KEY,
            -- syear numeric(4,0) NOT NULL,
            -- school_id integer NOT NULL,
            student_id integer NOT NULL REFERENCES students(student_id),
            element_id integer NOT NULL REFERENCES billing_elements(id),
            fee_id integer NOT NULL REFERENCES billing_fees(id) ON DELETE CASCADE,
            created_at timestamp DEFAULT current_timestamp,
            updated_at timestamp
            -- FOREIGN KEY (school_id, syear) REFERENCES schools(id, syear)
        );

        CREATE TRIGGER set_updated_at
            BEFORE UPDATE ON billing_student_elements
            FOR EACH ROW EXECUTE PROCEDURE set_updated_at();
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_billing_student_elements();
DROP FUNCTION create_table_billing_student_elements();

