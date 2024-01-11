
/**********************************************************************
 install.sql file
 Required as the module adds programs to other modules
 - Add profile exceptions for the module to appear in the menu
 - Add Student_ID_Card templates
***********************************************************************/

/*******************************************************
 profile_id:
 	- 0: student
 	- 1: admin
 	- 2: teacher
 	- 3: parent
 modname: should match the Menu.php entries
 can_use: 'Y'
 can_edit: 'Y' or null (generally null for non admins)
*******************************************************/
--
-- Data for Name: profile_exceptions; Type: TABLE DATA;
--

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Student_ID_Card/StudentIDCard.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Student_ID_Card/StudentIDCard.php'
    AND profile_id=1);


/*********************************************************
 Add Student_ID_Card template
**********************************************************/
--
-- Data for Name: templates; Type: TABLE DATA;
--

INSERT INTO templates (modname, staff_id, template)
SELECT 'Student_ID_Card/StudentIDCard.php', 0, '<h3>__FULL_NAME__</h3>
<p>Born: __STUDENT_200000004__</p>
<p>Grade Level: __GRADE_ID__</p>
<p>School Year: __SCHOOL_YEAR__</p>'
WHERE NOT EXISTS (SELECT modname
    FROM templates
    WHERE modname='Student_ID_Card/StudentIDCard.php'
    AND staff_id=0);
