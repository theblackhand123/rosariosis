/**********************************************************************
 install_es.sql file
 Optional, to translate texts to Spanish
 - Add templates
***********************************************************************/

/*********************************************************
 Add Email template
**********************************************************/
--
-- Data for Name: templates; Type: TABLE DATA;
--

UPDATE templates
SET template='Buenos días,

__FULL_NAME__ estará ausente del __START_DATE__ al __END_DATE__.
Razón: __STAFF_ABSENCE_2__'
WHERE modname='Staff_Absences/AddAbsence.php';


--
-- Data for Name: staff_absence_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE staff_absence_fields
SET TITLE='Tipo',
SELECT_OPTIONS='Enfermedad
Vacaciones'
WHERE TITLE='Type';

UPDATE staff_absence_fields
SET TITLE='Razón'
WHERE TITLE='Reason';
