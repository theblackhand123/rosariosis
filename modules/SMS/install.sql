/**
 * Install PostgreSQL
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package SMS module
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
SELECT 1, 'SMS/Send.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='SMS/Send.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'SMS/Outbox.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='SMS/Outbox.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'SMS/Send.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='SMS/Send.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'SMS/Outbox.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='SMS/Outbox.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'SMS/Configuration.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='SMS/Configuration.php'
    AND profile_id=1);



/**
 * Add module tables
 */


/**
 * SMS table
 */
--
-- Name: sms; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_sms() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname=CURRENT_SCHEMA()
        AND tablename='sms') THEN
    RAISE NOTICE 'Table "sms" already exists.';
    ELSE
        CREATE TABLE sms (
            id serial PRIMARY KEY,
            syear numeric(4,0) NOT NULL,
            school_id integer NOT NULL,
            staff_id integer REFERENCES staff(staff_id),
            recipients text,
            data text,
            created_at timestamp DEFAULT current_timestamp,
            updated_at timestamp,
            FOREIGN KEY (school_id, syear) REFERENCES schools(id, syear)
        );

        CREATE TRIGGER set_updated_at
            BEFORE UPDATE ON sms
            FOR EACH ROW EXECUTE PROCEDURE set_updated_at();
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_sms();
DROP FUNCTION create_table_sms();



--
-- Name: sms_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_sms_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='sms_ind'
        AND n.nspname=CURRENT_SCHEMA
    ) THEN
        CREATE INDEX sms_ind ON sms USING btree (syear, school_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_sms_ind();
DROP FUNCTION create_index_sms_ind();
