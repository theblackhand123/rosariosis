
/**********************************************************************
 install_fr.sql file
 Optional, to translate texts to French
 - Add Student_ID_Card templates
***********************************************************************/

/*********************************************************
 Add Student_ID_Card template
**********************************************************/
--
-- Data for Name: templates; Type: TABLE DATA;
--

UPDATE templates
SET template='<h3>__FULL_NAME__</h3>
<p>Né(e) le&nbsp;: __STUDENT_200000004__</p>
<p>Niveau scolaire&nbsp;: __GRADE_ID__</p>
<p>Année&nbsp;: __SCHOOL_YEAR__</p>'
WHERE modname='Student_ID_Card/StudentIDCard.php';
