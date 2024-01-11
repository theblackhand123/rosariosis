/**********************************************************************
 install_fr.sql file
 Optional, to translate texts to French
 - Add templates
***********************************************************************/

/*********************************************************
 Add Email template
**********************************************************/
--
-- Data for Name: templates; Type: TABLE DATA;
--

UPDATE templates
SET template='Bonjour,

__FULL_NAME__ sera absent du __START_DATE__ au __END_DATE__.
Raison : __STAFF_ABSENCE_2__'
WHERE modname='Staff_Absences/AddAbsence.php';


--
-- Data for Name: staff_absence_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE staff_absence_fields
SET TITLE='Type',
SELECT_OPTIONS='Maladie
Vacances'
WHERE TITLE='Type';

UPDATE staff_absence_fields
SET TITLE='Raison'
WHERE TITLE='Reason';
