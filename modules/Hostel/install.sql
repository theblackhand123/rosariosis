/**
 * Install PostgreSQL
 * Required if the module adds programs to other modules
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Hostel module
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
SELECT 1, 'Hostel/Hostel.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Hostel/Hostel.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Hostel/Hostel.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Hostel/Hostel.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Hostel/Hostel.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Hostel/Hostel.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Hostel/RoomList.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Hostel/RoomList.php'
    AND profile_id=1);


/**
 * Add module tables
 */

/**
 * Buildings table
 */
--
-- Name: hostel_buildings; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_hostel_buildings() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'hostel_buildings') THEN
    RAISE NOTICE 'Table "hostel_buildings" already exists.';
    ELSE
        CREATE TABLE hostel_buildings (
            id serial PRIMARY KEY,
            -- school_id integer NOT NULL,
            title text NOT NULL,
            description text,
            sort_order numeric,
            created_at timestamp DEFAULT current_timestamp
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_hostel_buildings();
DROP FUNCTION create_table_hostel_buildings();


/**
 * Hostel Rooms table
 */
--
-- Name: hostel_rooms; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_hostel_rooms() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'hostel_rooms') THEN
    RAISE NOTICE 'Table "hostel_rooms" already exists.';
    ELSE
        CREATE TABLE hostel_rooms (
            id serial PRIMARY KEY,
            -- school_id integer NOT NULL,
            building_id integer NOT NULL REFERENCES hostel_buildings(id),
            title text NOT NULL,
            description text,
            capacity integer NOT NULL,
            price numeric(14,2),
            created_at timestamp DEFAULT current_timestamp
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_hostel_rooms();
DROP FUNCTION create_table_hostel_rooms();


--
-- Name: hostel_rooms_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_hostel_rooms_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='hostel_rooms_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE INDEX hostel_rooms_ind ON hostel_rooms (building_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_hostel_rooms_ind();
DROP FUNCTION create_index_hostel_rooms_ind();


/**
 * Hostel Students table
 */
--
-- Name: hostel_students; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_hostel_students() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'hostel_students') THEN
    RAISE NOTICE 'Table "hostel_students" already exists.';
    ELSE
        CREATE TABLE hostel_students (
            room_id integer NOT NULL REFERENCES hostel_rooms(id),
            student_id integer NOT NULL REFERENCES students(student_id),
            created_at timestamp DEFAULT current_timestamp,
            UNIQUE (room_id, student_id)
       );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_hostel_students();
DROP FUNCTION create_table_hostel_students();
