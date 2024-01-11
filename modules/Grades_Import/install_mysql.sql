
/**********************************************************************
 install_mysql.sql file
 Required as the module adds programs to other modules
 - Add profile exceptions for the module to appear in the menu
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
SELECT 1, 'Users/TeacherPrograms.php&include=Grades_Import/GradebookGradesImport.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Users/TeacherPrograms.php&include=Grades_Import/GradebookGradesImport.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Grades_Import/GradebookGradesImport.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Grades_Import/GradebookGradesImport.php'
    AND profile_id=2);
