<?php
/**
 * Menu.php file
 * Required
 * - Add Menu entries to other modules
 *
 * @package Certificate module
 */

// Use dgettext() function instead of _() for Module specific strings translation.
// See locale/README file for more information.

// Add a Menu entry to the Students module.
if ( $RosarioModules['Students'] ) // Verify Students module is activated.
{
	$menu['Students']['admin']['Certificate/CertificateEnrollment.php'] = dgettext( 'Certificate', 'Certificate of Enrollment' );

	$menu['Students']['parent']['Certificate/CertificateEnrollment.php'] = dgettext( 'Certificate', 'Certificate of Enrollment' );
}
