/**********************************************************************
 install_pt_BR.sql file
 Optional, to translate texts to Brazilian portuguese
 - Add templates
***********************************************************************/

/*********************************************************
 Add Email template
**********************************************************/
--
-- Data for Name: templates; Type: TABLE DATA;
--

UPDATE templates
SET template='Bom Dia,

__FULL_NAME__ estará ausente de __START_DATE__ até __END_DATE__.
Motivo: __STAFF_ABSENCE_2__'
WHERE modname='Staff_Absences/AddAbsence.php';


--
-- Data for Name: staff_absence_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE staff_absence_fields
SET TITLE='Tipo',
SELECT_OPTIONS='Doença
Férias'
WHERE TITLE='Type';

UPDATE staff_absence_fields
SET TITLE='Motivo'
WHERE TITLE='Reason';
