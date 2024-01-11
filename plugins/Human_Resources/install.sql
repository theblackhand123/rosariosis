/**
 * Install SQL
 * - Add program config options if any (to every schools)
 *
 * @package Human Resources
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
 * User Field category (tab)
 * Include modules/Human_Resources/User.inc.php file.
 */
INSERT INTO staff_field_categories
VALUES (nextval('staff_field_categories_id_seq'), 'Qualifications', NULL, NULL, 'Human_Resources/User', 'Y', 'Y');

/**
 * Profile exceptions
 * Give access to tab to Admins and Teachers only.
 */
INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
SELECT '1','Users/User.php&category_id='||currval('staff_field_categories_id_seq'),'Y','Y';

INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
SELECT '2','Users/User.php&category_id='||currval('staff_field_categories_id_seq'),'Y',NULL;

CREATE FUNCTION add_staff_fields() RETURNS void AS $$
BEGIN

    /**
     * Add Supervisor field & column.
     */
    INSERT INTO staff_fields VALUES (NEXTVAL('staff_fields_id_seq'), 'text', 'Supervisor', 1, NULL, currval('staff_field_categories_id_seq'), NULL, NULL);

    EXECUTE 'ALTER TABLE staff ADD CUSTOM_'||currval('staff_fields_id_seq')||' TEXT;';
END
$$ LANGUAGE plpgsql;

SELECT add_staff_fields();

DROP FUNCTION add_staff_fields();


/**
 * Add module tables
 */

/**
 * skills table
 */
--
-- Name: skills; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_human_resources_skills() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'human_resources_skills') THEN
    RAISE NOTICE 'Table "human_resources_skills" already exists.';
    ELSE
        CREATE TABLE human_resources_skills (
            id serial PRIMARY KEY,
            staff_id integer NOT NULL,
            title text NOT NULL,
            description text,
            created_at timestamp DEFAULT current_timestamp
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_human_resources_skills();
DROP FUNCTION create_table_human_resources_skills();

/**
 * education table
 */
--
-- Name: education; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_human_resources_education() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'human_resources_education') THEN
    RAISE NOTICE 'Table "human_resources_education" already exists.';
    ELSE
        CREATE TABLE human_resources_education (
            id serial PRIMARY KEY,
            staff_id integer NOT NULL,
            qualification text NOT NULL,
            institute text NOT NULL,
            start_date date,
            completed_on date,
            created_at timestamp DEFAULT current_timestamp
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_human_resources_education();
DROP FUNCTION create_table_human_resources_education();

/**
 * certifications table
 */
--
-- Name: certifications; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_human_resources_certifications() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'human_resources_certifications') THEN
    RAISE NOTICE 'Table "human_resources_certifications" already exists.';
    ELSE
        CREATE TABLE human_resources_certifications (
            id serial PRIMARY KEY,
            staff_id integer NOT NULL,
            title text NOT NULL,
            institute text NOT NULL,
            granted_on date,
            valid_through date,
            created_at timestamp DEFAULT current_timestamp
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_human_resources_certifications();
DROP FUNCTION create_table_human_resources_certifications();

/**
 * languages table
 */
--
-- Name: languages; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_human_resources_languages() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'human_resources_languages') THEN
    RAISE NOTICE 'Table "human_resources_languages" already exists.';
    ELSE
        CREATE TABLE human_resources_languages (
            id serial PRIMARY KEY,
            staff_id integer NOT NULL,
            title text NOT NULL,
            reading text,
            speaking text,
            writing text,
            understanding text,
            created_at timestamp DEFAULT current_timestamp
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_human_resources_languages();
DROP FUNCTION create_table_human_resources_languages();
