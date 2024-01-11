/**
 * Install PostgreSQL
 * - Add program config options if any (to every schools)
 *
 * @package Tutor Report Card Comments
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
 * Student Field category (tab)
 * Include plugins/Tutor_Report_Card_Comments/Student.inc.php file.
 */
INSERT INTO student_field_categories
VALUES (nextval('student_field_categories_id_seq'), 'Tutor Comments|en_US.utf8:Tutor Comments|fr_FR.utf8:Commentaires du Tuteur|es_ES.utf8:Comentarios del Tutor|pt_BR.utf8:Comentários do tutor', NULL, NULL, 'Tutor_Report_Card_Comments/Student');

/**
 * Profile exceptions
 * Give access to tab to Admins and Teachers only.
 */
INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
SELECT '1','Students/Student.php&category_id='||currval('student_field_categories_id_seq'),'Y','Y';

INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
SELECT '2','Students/Student.php&category_id='||currval('student_field_categories_id_seq'),'Y','Y';


--
-- Name: student_mp_tutor_report_card_comments; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_student_mp_tutor_report_card_comments() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'student_mp_tutor_report_card_comments') THEN
    RAISE NOTICE 'Table "student_mp_tutor_report_card_comments" already exists.';
    ELSE
        CREATE TABLE student_mp_tutor_report_card_comments (
            student_id integer NOT NULL REFERENCES students(student_id),
            syear numeric(4,0) NOT NULL,
            marking_period_id integer NOT NULL REFERENCES school_marking_periods(marking_period_id),
            comment text,
            tutor_name text,
            created_at timestamp DEFAULT current_timestamp,
            updated_at timestamp,
            PRIMARY KEY (student_id, syear, marking_period_id)
        );

        CREATE TRIGGER set_updated_at
            BEFORE UPDATE ON student_mp_tutor_report_card_comments
            FOR EACH ROW EXECUTE PROCEDURE set_updated_at();
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_student_mp_tutor_report_card_comments();
DROP FUNCTION create_table_student_mp_tutor_report_card_comments();
