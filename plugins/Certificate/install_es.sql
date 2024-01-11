
/**********************************************************************
 install_es.sql file
 Optional, to translate texts to Spanish
 - Add Certificate templates
***********************************************************************/

/*********************************************************
 Add Certificate template
**********************************************************/
--
-- Data for Name: templates; Type: TABLE DATA;
--

UPDATE templates
SET template='<h1 style="text-align: center;">CERTIFICADO DE MATR&Iacute;CULA</h1>
<p>&nbsp;</p>
<p>El abajo firmante, __SCHOOL_PRINCIPAL__ certifica por la presente que el estudiante __FULL_NAME__, nacido el&nbsp; __STUDENT_200000004__, est&aacute; inscrito como alumno en el grado __GRADE_ID__ de nuestra instituci&oacute;n __SCHOOL_TITLE__, para el a&ntilde;o escolar __SCHOOL_YEAR__.</p>
<p>&nbsp;</p>
<p>[arrastrar y soltar la imagen de la firma ac&aacute;]</p>
<p><strong>__SCHOOL_PRINCIPAL__</strong></p>
<p>Director</p>'
WHERE modname='Certificate/CertificateEnrollment.php';
