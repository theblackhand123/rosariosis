<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the Semester Rollover module
 * - Add Menu entries to other modules
 *
 * @package Semester Rollover module
 */

/**
 * Use dgettext() function instead of _() for Module specific strings translation
 * see locale/README file for more information.
 */
$module_name = dgettext( 'Semester_Rollover', 'Semester Rollover' );

// Menu entries for the School Setup module.
$menu['School_Setup']['admin']['Semester_Rollover/SemesterRolloverStudents.php'] = dgettext( 'Semester_Rollover', 'Semester Rollover Students' );
