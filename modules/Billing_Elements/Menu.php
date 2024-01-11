<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the Billing Elements module
 *
 * @package Billing Elements module
 */

// Menu entries for the Billing Elements module.
$menu['Billing_Elements']['admin'] = [ // Admin menu.
	'title' => dgettext( 'Billing_Elements', 'Billing Elements' ),
	'default' => 'Billing_Elements/Elements.php', // Program loaded by default when menu opened.
	'Billing_Elements/Elements.php' => dgettext( 'Billing_Elements', 'Elements' ),
	'Billing_Elements/MassAssignElements.php' => dgettext( 'Billing_Elements', 'Mass Assign Elements' ),
	'Billing_Elements/StudentElements.php' => dgettext( 'Billing_Elements', 'Student Elements' ),
	1 => _( 'Reports' ),
	'Billing_Elements/CategoryBreakdown.php' => _( 'Category Breakdown' ),
];

$menu['Billing_Elements']['parent'] = [ // Parent & Student menu.
	'title' => dgettext( 'Billing_Elements', 'Store' ),
	'default' => 'Billing_Elements/Elements.php', // Program loaded by default when menu opened.
	'Billing_Elements/Elements.php' => dgettext( 'Billing_Elements', 'Elements' ),
	'Billing_Elements/StudentElements.php' => ( User( 'PROFILE' ) === 'parent' ?
		dgettext( 'Billing_Elements', 'Student Elements' ) : dgettext( 'Billing_Elements', 'My Elements' ) ),
];
