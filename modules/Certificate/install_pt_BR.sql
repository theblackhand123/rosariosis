
/**********************************************************************
 install_pt_BR.sql file
 Optional, to translate texts to Brazilian Portuguese
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
<p>Eu, __SCHOOL_PRINCIPAL__, certifico que o aluno __FULL_NAME__, nascido em&nbsp; __STUDENT_200000004__, est&aacute; matriculado como aluno na __GRADE_ID__ da __SCHOOL_TITLE__, para o ano letivo __SCHOOL_YEAR__.</p>
<p>&nbsp;</p>
<p>[arraste e solte a imagem da assinatura aqui]</p>
<p><strong>__SCHOOL_PRINCIPAL__</strong></p>
<p>Diretor</p>'
WHERE modname='Certificate/CertificateEnrollment.php';
