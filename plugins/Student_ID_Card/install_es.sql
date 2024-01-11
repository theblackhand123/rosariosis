
/**********************************************************************
 install_es.sql file
 Optional, to translate texts to Spanish
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
<p>Nacido/a el: __STUDENT_200000004__</p>
<p>Grado: __GRADE_ID__</p>
<p>Año: __SCHOOL_YEAR__</p>'
WHERE modname='Student_ID_Card/StudentIDCard.php';
