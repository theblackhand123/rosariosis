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
 * @uses Heredoc syntax
 * @see  http://php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc
 *
 * @package Timetable Import module
 * @subpackage Help
 */

// STAFF AND PARENTS IMPORT ---.
if ( User( 'PROFILE' ) === 'admin' ) :

    $help['Timetable_Import/TimetableImport.php'] = '<p>' . _help( '<i>Timetable Import</i> allows you to import a timetable contained in an <b>Excel</b> spreadsheet or a <b>CSV</b> file.', 'Timetable_Import' ) . '</p>
    <p>' . _help( 'First thing, it is recommended to <b>backup your database</b> in case something goes wrong.', 'Timetable_Import' ) . '</p>
    <p>' . _help( 'Then, select the Excel (.xls, .xlsx) or CSV (.csv) file containing your timetable using the "Select CSV or Excel file". Then, click the "Submit" button to upload the file. Please note that if you select an Excel file, only the first spreadsheet will be uploaded.', 'Timetable_Import' ) . '</p>
    <p>' . _help( 'On the next screen, you will be able to associate a column to each Timetable Field. Please note that the fields in <span style="color:red;">red</span> are mandatory. Check the "Import first row" checkbox at the top of the screen if your file\'s first row contains user data instead of column labels. Once you are set, click the "Import Timetable" button.', 'Timetable_Import' ) . '</p>';

endif;
