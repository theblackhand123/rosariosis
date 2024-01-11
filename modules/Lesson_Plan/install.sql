/**
 * Install PostgreSQL
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Lesson Plan module
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
SELECT 1, 'Lesson_Plan/LessonPlans.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Lesson_Plan/LessonPlans.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Lesson_Plan/LessonPlans.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Lesson_Plan/LessonPlans.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Lesson_Plan/LessonPlans.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Lesson_Plan/LessonPlans.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Lesson_Plan/Read.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Lesson_Plan/Read.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Lesson_Plan/AddLesson.php', 'Y', NULL
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

CREATE OR REPLACE FUNCTION create_table_lesson_plan_lessons() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname=CURRENT_SCHEMA()
        AND tablename='lesson_plan_lessons') THEN
    RAISE NOTICE 'Table "lesson_plan_lessons" already exists.';
    ELSE
        CREATE TABLE lesson_plan_lessons (
            id serial PRIMARY KEY,
            course_period_id integer NOT NULL,
            title text NOT NULL,
            on_date date NOT NULL,
            location text,
            length_minutes integer,
            lesson_number varchar(50),
            data text,
            created_at timestamp DEFAULT current_timestamp,
            updated_at timestamp,
            FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id)
        );

        CREATE TRIGGER set_updated_at
            BEFORE UPDATE ON lesson_plan_lessons
            FOR EACH ROW EXECUTE PROCEDURE set_updated_at();
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_lesson_plan_lessons();
DROP FUNCTION create_table_lesson_plan_lessons();



--
-- Name: messages_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_lesson_plan_lessons_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='lesson_plan_lessons_ind'
        AND n.nspname=CURRENT_SCHEMA
    ) THEN
        CREATE INDEX lesson_plan_lessons_ind ON lesson_plan_lessons USING btree (course_period_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_lesson_plan_lessons_ind();
DROP FUNCTION create_index_lesson_plan_lessons_ind();


/**
 * Items table
 */
--
-- Name: lesson_plan_items; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_lesson_plan_items() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname=CURRENT_SCHEMA()
        AND tablename='lesson_plan_items') THEN
    RAISE NOTICE 'Table "lesson_plan_items" already exists.';
    ELSE
        CREATE TABLE lesson_plan_items (
            id serial PRIMARY KEY,
            lesson_id integer NOT NULL,
            data text NOT NULL,
            created_at timestamp DEFAULT current_timestamp,
            updated_at timestamp,
            FOREIGN KEY (lesson_id) REFERENCES lesson_plan_lessons(id)
        );

        CREATE TRIGGER set_updated_at
            BEFORE UPDATE ON lesson_plan_items
            FOR EACH ROW EXECUTE PROCEDURE set_updated_at();
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_lesson_plan_items();
DROP FUNCTION create_table_lesson_plan_items();



--
-- Name: lesson_plan_items_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_lesson_plan_items_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='lesson_plan_items_ind'
        AND n.nspname=CURRENT_SCHEMA
    ) THEN
        CREATE INDEX lesson_plan_items_ind ON lesson_plan_items USING btree (lesson_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_lesson_plan_items_ind();
DROP FUNCTION create_index_lesson_plan_items_ind();
