<?php
/**
 * Menu.php file
 *
 * Required
 * - Add Menu entries to other modules
 *
 * @package Attendance Excel Sheet module
 */

// Use dgettext() function instead of _() for Module specific strings translation.
// See locale/README file for more information.
$module_name = dgettext( 'Attendance_Excel_Sheet', 'Attendance Excel Sheet' );

// Add a Menu entry to the Attendance module.
if ( $RosarioModules['Attendance'] ) // Verify Attendance module is activated.
{
	// Place Print Attendance Sheets program before Utilities separator.
	$utilities_pos = array_search( 1, array_keys( $menu['Attendance']['admin'] ) );

	$menu['Attendance']['admin'] = array_merge(
	    array_slice( $menu['Attendance']['admin'], 0, $utilities_pos ),
	    [ 'Attendance_Excel_Sheet/PrintAttendanceSheets.php' => dgettext( 'Attendance_Excel_Sheet', 'Print Attendance Sheets' ) ],
	    array_slice( $menu['Attendance']['admin'], $utilities_pos )
	);
}
