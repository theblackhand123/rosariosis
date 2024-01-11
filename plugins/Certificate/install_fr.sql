
/**********************************************************************
 install_fr.sql file
 Optional, to translate texts to French
 - Add Certificate templates
***********************************************************************/

/*********************************************************
 Add Certificate template
**********************************************************/
--
-- Data for Name: templates; Type: TABLE DATA;
--

UPDATE templates
SET template='<h1 style="text-align: center;">CERTIFICAT DE SCOLARIT&Eacute;</h1>
<p>&nbsp;</p>
<p>Madame, Monsieur,</p>
<p>Je soussign&eacute; __SCHOOL_PRINCIPAL__ certifie par la pr&eacute;sente que l&#39;&eacute;tudiant __FULL_NAME__, n&eacute; le&nbsp; __STUDENT_200000004__, est inscrit comme &eacute;l&egrave;ve au niveau __GRADE_ID__ dans notre &eacute;tablissement __SCHOOL_TITLE__, pour l&#39;ann&eacute;e scolaire __SCHOOL_YEAR__.</p>
<p>&nbsp;</p>
<p>[glissez d&eacute;posez l&#39;image de la signature ici]</p>
<p><strong>__SCHOOL_PRINCIPAL__</strong></p>
<p>Directeur</p>'
WHERE modname='Certificate/CertificateEnrollment.php';
