/**
 * Install PostgreSQL
 * Required if the module adds programs to other modules
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Audit module
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
SELECT 1, 'Audit/AuditLog.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Audit/AuditLog.php'
    AND profile_id=1);


/**
 * Add module tables
 */

/**
 * Audit Log table
 */
--
-- Name: sql_audit_log; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_audit_log() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'audit_log') THEN
    RAISE NOTICE 'Table "audit_log" already exists.';
    ELSE
        CREATE TABLE audit_log (
            id serial PRIMARY KEY,
            syear numeric(4,0),
            username varchar(100),
            profile varchar(30),
            url varchar(2000),
            query_type varchar(50),
            data text,
            created_at timestamp DEFAULT current_timestamp
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_audit_log();
DROP FUNCTION create_table_audit_log();
