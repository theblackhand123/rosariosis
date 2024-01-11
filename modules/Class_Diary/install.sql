/**
 * Install PostgreSQL
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Class Diary module
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
SELECT 1, 'Class_Diary/Diaries.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Class_Diary/Diaries.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Class_Diary/Diaries.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Class_Diary/Diaries.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Class_Diary/Diaries.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Class_Diary/Diaries.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Class_Diary/Read.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Class_Diary/Read.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Class_Diary/Write.php', 'Y', NULL
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

CREATE OR REPLACE FUNCTION create_table_class_diary_messages() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname=CURRENT_SCHEMA()
        AND tablename='class_diary_messages') THEN
    RAISE NOTICE 'Table "class_diary_messages" already exists.';
    ELSE
        CREATE TABLE class_diary_messages (
            id serial PRIMARY KEY,
            course_period_id integer NOT NULL,
            data text,
            created_at timestamp DEFAULT current_timestamp,
            updated_at timestamp,
            FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id)
        );

        CREATE TRIGGER set_updated_at
            BEFORE UPDATE ON class_diary_messages
            FOR EACH ROW EXECUTE PROCEDURE set_updated_at();
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_class_diary_messages();
DROP FUNCTION create_table_class_diary_messages();



--
-- Name: messages_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_class_diary_messages_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='class_diary_messages_ind'
        AND n.nspname=CURRENT_SCHEMA
    ) THEN
        CREATE INDEX class_diary_messages_ind ON class_diary_messages USING btree (course_period_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_class_diary_messages_ind();
DROP FUNCTION create_index_class_diary_messages_ind();
