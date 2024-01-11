<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the Audit module
 * - Add Menu entries to other modules
 *
 * @package Audit module
 */

/**
 * Use dgettext() function instead of _() for Module specific strings translation
 * see locale/README file for more information.
 */
$module_name = dgettext( 'Audit', 'Audit' );

// Menu entries for the School Setup module.
$menu['School_Setup']['admin']['Audit/AuditLog.php'] = dgettext( 'Audit', 'Audit Log' );
