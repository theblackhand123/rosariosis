<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the Reports module
 *
 * @package Reports module
 */

$menu['Reports']['admin'] = [
	'title' => _( 'Reports' ),
	'default' => 'Reports/Calculations.php', // Program loaded by default when menu opened.
	'Reports/Calculations.php' => dgettext( 'Reports', 'Calculations' ),
	'Reports/CalculationsReports.php' => dgettext( 'Reports', 'Calculations Reports' ),
	'Reports/SavedReports.php' => dgettext( 'Reports', 'Saved Reports' ),
];

$menu_reports_RET = DBGet( "SELECT ID,TITLE
	FROM saved_reports
	ORDER BY TITLE" );

// Add Saved Reports.
foreach ( (array) $menu_reports_RET as $report )
{
	$menu['Reports']['admin'][ 'Reports/RunReport.php&id=' . $report['ID'] ] = $report['TITLE'];
}
