<?php
/**
 * Menu.php file
 *
 * Required
 * - Add Menu entries to other modules
 *
 * @package Timetable Import module
 */

// Use dgettext() function instead of _() for Module specific strings translation.
// See locale/README file for more information.
$module_name = dgettext( 'Timetable_Import', 'Timetable Import' );

// Add a Menu entry to the Scheduling module.
if ( $RosarioModules['Scheduling'] ) // Verify Scheduling module is activated.
{
	$menu['Scheduling']['admin'] += [
		'Timetable_Import/TimetableImport.php' => dgettext( 'Timetable_Import', 'Timetable Import' ),
	];
}
