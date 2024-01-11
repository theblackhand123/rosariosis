/**
 * Install PostgreSQL
 * - Add program config options if any (to every schools)
 *
 * @package Relatives
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
 * Include plugins/Relatives/Student.inc.php file.
 */
INSERT INTO student_field_categories
VALUES (nextval('student_field_categories_id_seq'), 'Relatives', NULL, NULL, 'Relatives/Student');

/**
 * Profile exceptions
 * Give access to tab to Admins only.
 */
INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
SELECT '1',CONCAT('Students/Student.php&category_id=', currval('student_field_categories_id_seq')),'Y','Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname=CONCAT('Students/Student.php&category_id=', currval('student_field_categories_id_seq'))
    AND profile_id=1);
