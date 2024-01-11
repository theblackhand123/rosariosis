
/**********************************************************************
 install_mysql.sql file
 Required as the module adds programs to other modules
 - Add profile exceptions for the module to appear in the menu
 - Add Certificate templates
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
SELECT 1, 'Certificate/CertificateEnrollment.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Certificate/CertificateEnrollment.php'
    AND profile_id=1);


/*********************************************************
 Add Certificate template
**********************************************************/
--
-- Data for Name: templates; Type: TABLE DATA;
--

INSERT INTO templates (modname, staff_id, template)
SELECT 'Certificate/CertificateEnrollment.php', 0, '<h1 style="text-align: center;">CERTIFICATE OF ENROLLMENT</h1>
<p>&nbsp;</p>
<p>I the undersigned, __SCHOOL_PRINCIPAL__ certify that __FULL_NAME__, born on __STUDENT_200000004__, is enrolled as a student in __GRADE_ID__ grade of __SCHOOL_TITLE__ for the __SCHOOL_YEAR__ school year.</p>
<p>&nbsp;</p>
<p>[drag and drop signature image here]</p>
<p><strong>__SCHOOL_PRINCIPAL__</strong></p>
<p>Principal of School</p>'
FROM DUAL
WHERE NOT EXISTS (SELECT modname
    FROM templates
    WHERE modname='Certificate/CertificateEnrollment.php'
    AND staff_id=0);
