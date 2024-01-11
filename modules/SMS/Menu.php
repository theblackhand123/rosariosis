<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the SMS module
 *
 * @package SMS module
 */

// Menu entries for the SMS module.
if ( empty( $menu['SMS'] ) ) // If Premium module loaded first, menu already set.
{
	$menu['SMS']['admin'] = [ // Admin menu.
		'title' => dgettext( 'SMS', 'SMS' ),
		'default' => 'SMS/Send.php', // Program loaded by default when menu opened.
		'SMS/Send.php' => dgettext( 'SMS', 'Send' ),
		'SMS/Outbox.php' => dgettext( 'SMS', 'Outbox' ),
		1 => _( 'Setup' ),
		'SMS/Configuration.php' => _( 'Configuration' ),
	];

	$menu['SMS']['teacher'] = [ // Teacher menu
		'title' => dgettext( 'SMS', 'SMS' ),
		'default' => 'SMS/Send.php', // Program loaded by default when menu opened.
		'SMS/Send.php' => dgettext( 'SMS', 'Send' ),
		'SMS/Outbox.php' => dgettext( 'SMS', 'Outbox' ),
	];
}
