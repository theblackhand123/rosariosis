<?php
/**
 * Menu.php file
 *
 * Required
 * - Add Menu entries to other modules
 *
 * @package Staff and Parents Import module
 */

// Use dgettext() function instead of _() for Module specific strings translation.
// See locale/README file for more information.
$module_name = dgettext( 'Staff_Parents_Import', 'Staff Parents Import' );

// Add a Menu entry to the Users module.
if ( $RosarioModules['Users'] ) // Verify Users module is activated.
{
	// Place Staff and Parents Import program right after Utilities separator.
	$utilities_pos = array_search( 2, array_keys( $menu['Users']['admin'] ) );

	if ( $utilities_pos )
	{
		$menu['Users']['admin'] = array_merge(
			array_slice( $menu['Users']['admin'], 0, $utilities_pos + 1 ),
			[ 'Staff_Parents_Import/StaffParentsImport.php' => dgettext( 'Staff_Parents_Import', 'Staff and Parents Import' ) ],
			array_slice( $menu['Users']['admin'], $utilities_pos + 1 )
		);
	}
	else
	{
		$menu['Users']['admin'] += [
			'Staff_Parents_Import/StaffParentsImport.php' => dgettext( 'Staff_Parents_Import', 'Staff and Parents Import' ),
		];
	}
}
