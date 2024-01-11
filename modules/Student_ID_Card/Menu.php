<?php
/**
 * Menu.php file
 * Required
 * - Add Menu entries to other modules
 *
 * @package Student ID Card module
 */

// Use dgettext() function instead of _() for Module specific strings translation.
// See locale/README file for more information.

// Add a Menu entry to the Students module.
if ( $RosarioModules['Students'] ) // Verify Students module is activated.
{
	$menu['Students']['admin']['Student_ID_Card/StudentIDCard.php'] = dgettext( 'Student_ID_Card', 'Student ID Card' );

	$menu['Students']['parent']['Student_ID_Card/StudentIDCard.php'] = dgettext( 'Student_ID_Card', 'Student ID Card' );
}
