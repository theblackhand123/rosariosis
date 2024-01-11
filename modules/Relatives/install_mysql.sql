/**
 * Install MySQL
 * - Add program config options if any (to every schools)
 *
 * @package Relatives
 */


/**
 * Student Field category (tab)
 * Include plugins/Relatives/Student.inc.php file.
 */
INSERT INTO student_field_categories
VALUES (NULL, 'Relatives', NULL, NULL, 'Relatives/Student', NULL, NULL);

SET @sfc_id=LAST_INSERT_ID();

/**
 * Profile exceptions
 * Give access to tab to Admins only.
 */
INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
SELECT '1',CONCAT('Students/Student.php&category_id=', @sfc_id),'Y','Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname=CONCAT('Students/Student.php&category_id=', @sfc_id)
    AND profile_id=1);
