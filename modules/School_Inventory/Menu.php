<?php
/**
 * Menu.php file
 * Required
 * - Add Menu entries to other modules
 *
 * @package School Inventory module
 */

// Use dgettext() function instead of _() for Module specific strings translation
// See locale/README file for more information.

// Add a Menu entry to the Resources module.
if ( $RosarioModules['Resources'] ) // Verify Resources module is activated.
{
	$menu['Resources']['admin'] += [
		'School_Inventory/SchoolInventory.php' => dgettext( 'School_Inventory', 'School Inventory' ),
		'School_Inventory/InventorySnapshots.php' => dgettext( 'School_Inventory', 'Inventory Snapshots' ),
	];

	$menu['Resources']['teacher'] += [
		'School_Inventory/SchoolInventory.php' => dgettext( 'School_Inventory', 'School Inventory' ),
		'School_Inventory/InventorySnapshots.php' => dgettext( 'School_Inventory', 'Inventory Snapshots' ),
	];
}
