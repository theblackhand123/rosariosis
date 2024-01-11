/**
 * Install MySQL
 * - Add program config options if any (to every schools)
 *
 * @package Human Resources
 */



DELIMITER $$
CREATE PROCEDURE add_staff_fields()
BEGIN
    DECLARE sfc_id integer;
    DECLARE sf_id integer;

    /**
     * User Field category (tab)
     * Include modules/Human_Resources/User.inc.php file.
     */
    INSERT INTO staff_field_categories
    VALUES (NULL, 'Qualifications', NULL, NULL, 'Human_Resources/User', 'Y', 'Y', NULL, NULL, NULL, NULL);

    SELECT LAST_INSERT_ID() INTO sfc_id;

    /**
     * Profile exceptions
     * Give access to tab to Admins and Teachers only.
     */
    INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
    SELECT '1',CONCAT('Users/User.php&category_id=', sfc_id),'Y','Y';

    INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
    SELECT '2',CONCAT('Users/User.php&category_id=', sfc_id),'Y',NULL;

    /**
     * Add Supervisor field.
     */
    INSERT INTO staff_fields VALUES (NULL, 'text', 'Supervisor', 1, NULL, sfc_id, NULL, NULL, NULL, NULL);

    SELECT LAST_INSERT_ID() INTO sf_id;

    /**
     * Add Supervisor column.
     *
     * @link https://stackoverflow.com/questions/999200/is-it-possible-to-execute-a-string-in-mysql
     */
    SET @alter_exp = CONCAT('ALTER TABLE staff ADD CUSTOM_', sf_id, ' TEXT;');
    PREPARE alter_query FROM @alter_exp;
    EXECUTE alter_query;
END$$
DELIMITER ;

CALL add_staff_fields();

DROP PROCEDURE add_staff_fields;


/**
 * Add module tables
 */

/**
 * skills table
 */
--
-- Name: skills; Type: TABLE; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS human_resources_skills (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    staff_id integer NOT NULL,
    title text NOT NULL,
    description text,
    created_at timestamp DEFAULT current_timestamp
);

/**
 * education table
 */
--
-- Name: education; Type: TABLE; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS human_resources_education (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    staff_id integer NOT NULL,
    qualification text NOT NULL,
    institute text NOT NULL,
    start_date date,
    completed_on date,
    created_at timestamp DEFAULT current_timestamp
);

/**
 * certifications table
 */
--
-- Name: certifications; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS human_resources_certifications (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    staff_id integer NOT NULL,
    title text NOT NULL,
    institute text NOT NULL,
    granted_on date,
    valid_through date,
    created_at timestamp DEFAULT current_timestamp
);

/**
 * languages table
 */
--
-- Name: languages; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS human_resources_languages (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    staff_id integer NOT NULL,
    title text NOT NULL,
    reading text,
    speaking text,
    writing text,
    understanding text,
    created_at timestamp DEFAULT current_timestamp
);
