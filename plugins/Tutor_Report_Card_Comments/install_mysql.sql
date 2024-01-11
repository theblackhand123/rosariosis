/**
 * Install MySQL
 * - Add program config options if any (to every schools)
 *
 * @package Tutor Report Card Comments
 */

/**
 * Student Field category (tab)
 * Include plugins/Tutor_Report_Card_Comments/Student.inc.php file.
 */
INSERT INTO student_field_categories
VALUES (NULL, 'Tutor Comments|en_US.utf8:Tutor Comments|fr_FR.utf8:Commentaires du Tuteur|es_ES.utf8:Comentarios del Tutor|pt_BR.utf8:Comentários do tutor', NULL, NULL, 'Tutor_Report_Card_Comments/Student', NULL, NULL);

SELECT LAST_INSERT_ID() INTO @sfc_id;

/**
 * Profile exceptions
 * Give access to tab to Admins and Teachers only.
 */
INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
SELECT '1',CONCAT('Students/Student.php&category_id=', @sfc_id),'Y','Y';

INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
SELECT '2',CONCAT('Students/Student.php&category_id=', @sfc_id),'Y','Y';


--
-- Name: student_mp_tutor_report_card_comments; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS student_mp_tutor_report_card_comments (
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    syear numeric(4,0) NOT NULL,
    marking_period_id integer NOT NULL,
    FOREIGN KEY (marking_period_id) REFERENCES school_marking_periods(marking_period_id),
    comment longtext,
    tutor_name text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (student_id, syear, marking_period_id)
);
