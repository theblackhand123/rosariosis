<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the Staff_Absences module
 * - Add Menu entries to other modules
 *
 * @package Staff_Absences module
 */

/**
 * Use dgettext() function instead of _() for Module specific strings translation
 * see locale/README file for more information.
 */
$module_name = dgettext( 'Staff_Absences', 'Staff Absences' );

// Menu entries for the Staff_Absences module.
$menu['Staff_Absences']['admin'] = [ // Admin menu.
	'title' => dgettext( 'Staff_Absences', 'Staff Absences' ),
	'default' => 'Staff_Absences/Absences.php', // Program loaded by default when menu opened.
	'Staff_Absences/AddAbsence.php' => dgettext( 'Staff_Absences', 'Add Absence' ),
	'Staff_Absences/Absences.php' => dgettext( 'Staff_Absences', 'Absences' ),
	1 => _( 'Reports' ),
	'Staff_Absences/CancelledClasses.php' => dgettext( 'Staff_Absences', 'Cancelled Classes' ),
	'Staff_Absences/AbsenceBreakdown.php' => dgettext( 'Staff_Absences', 'Days Absent Breakdown' ),
	2 => _( 'Setup' ),
	'Staff_Absences/AbsenceFields.php' => dgettext( 'Staff_Absences', 'Absence Fields' ),
];

$menu['Staff_Absences']['teacher'] = [ // Teacher menu.
	'title' => dgettext( 'Staff_Absences', 'Staff Absences' ),
	'default' => 'Staff_Absences/Absences.php', // Program loaded by default when menu opened.
	'Staff_Absences/AddAbsence.php' => dgettext( 'Staff_Absences', 'Add Absence' ),
	'Staff_Absences/Absences.php' => dgettext( 'Staff_Absences', 'My Absences' ),
	1 => _( 'Reports' ),
	'Staff_Absences/CancelledClasses.php' => dgettext( 'Staff_Absences', 'Cancelled Classes' ),
];
