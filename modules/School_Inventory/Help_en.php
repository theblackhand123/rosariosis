<?php
/**
 * English Help texts - School Inventory module
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
 * @package School Inventory module
 */

// SCHOOL INVENTORY ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['School_Inventory/SchoolInventory.php'] = '<p>' . _help( '<i>School Inventory</i> allows you to manage and keep track of your school asset.', 'School_Inventory' ) . '</p>

	<p>' . _help( 'Items can be organized and filtered by category, status, location (for example a class room) and person (for example, the owner or the person in charge or the person the item was lended to).', 'School_Inventory' ) . '</p>

	<p>' . _help( 'To add a category, status, location or person, fill the "+" field at the bottom the list. Then press the "Save" button.', 'School_Inventory' ) . '</p>

	<p>' . _help( 'To consult items in a belonging to a specific category, status, location or person, click on the link in the corresponding list.', 'School_Inventory' ) . '</p>

	<p>' . _help( 'To consult all items, click the "All Items" link at the top of the screen.', 'School_Inventory' ) . '</p>

	<p>' . _help( 'To add an Item, fill the Title, Quantity, Comments fields at the bottom of the lis and select at least a category, status, location or person. Then press the "Save" button.', 'School_Inventory' ) . '</p>';

endif;
