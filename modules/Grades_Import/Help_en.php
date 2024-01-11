<?php
/**
 * English Help texts
 *
 * Texts are organized by:
 * - Module
 * - Profile
 *
 * Please use this file as a reference to generate the Gettext [My_Module]_help.po files
 * and translate Help texts to your language.
 * The Catalog should only reference the Help_en.php file
 * and detect the `_help` function / source keyword.
 *
 * @author François Jacquet
 *
 * @package Grades Import module
 * @subpackage Help
 */

// STAFF AND PARENTS IMPORT ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['Users/TeacherPrograms.php&include=Grades_Import/GradebookGradesImport.php'] = '<p>' . _help( '<i>Teacher Programs: Import Grades</i> allows you to import (Gradebook) grades contained in an <b>Excel</b> spreadsheet or a <b>CSV</b> file.', 'Grades_Import' ) . '</p>
	<p>' . _help( 'You can select the teachers\' classes using the pull-down in the upper left corner of the page.', 'Grades_Import' ) . '</p>
	<p>' . _help( 'First thing, it is recommended to <b>backup your database</b> in case something goes wrong.', 'Grades_Import' ) . '</p>
	<p>' . _help( 'Then, select the Excel (.xls, .xlsx) or CSV (.csv) file containing the student Grades using the "Select CSV or Excel file". Then, click the "Submit" button to upload the file. Please note that if you select an Excel file, only the first spreadsheet will be uploaded.', 'Grades_Import' ) . '</p>
	<p>' . _help( 'On the next screen, you will be able to associate columns containing grades (or points) to each Assignment. Identify Students enrolled in the Course Period based on their Student ID, or Username, or First and Last Names. Please note that the fields in <span style="color:red;">red</span> are mandatory. Check the "Import first row" checkbox at the top of the screen if your file\'s first row contains data instead of column labels. Once you are set, click the "Import Gradebook Grades" button.', 'Grades_Import' ) . '</p>';

endif;


if ( User( 'PROFILE' ) === 'teacher' ) :

	$help['Grades_Import/GradebookGradesImport.php'] = '<p>' . _help( '<i>Import Grades</i> allows you to import (Gradebook) grades contained in an <b>Excel</b> spreadsheet or a <b>CSV</b> file.', 'Grades_Import' ) . '</p>
	<p>' . _help( 'You can select the desired class using the pull-down in the left menu.', 'Grades_Import' ) . '</p>
	<p>' . _help( 'Then, select the Excel (.xls, .xlsx) or CSV (.csv) file containing the student Grades using the "Select CSV or Excel file". Then, click the "Submit" button to upload the file. Please note that if you select an Excel file, only the first spreadsheet will be uploaded.', 'Grades_Import' ) . '</p>
	<p>' . _help( 'On the next screen, you will be able to associate columns containing grades (or points) to each Assignment. Identify Students enrolled in the Course Period based on their Student ID, or Username, or First and Last Names. Please note that the fields in <span style="color:red;">red</span> are mandatory. Check the "Import first row" checkbox at the top of the screen if your file\'s first row contains data instead of column labels. Once you are set, click the "Import Gradebook Grades" button.', 'Grades_Import' ) . '</p>';

endif;
