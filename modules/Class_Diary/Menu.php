<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the Class_Diary module
 *
 * @package Class Diary module
 */

// Add a Menu entry to the Attendance module.
if ( ! empty( $RosarioModules['Attendance'] )
	&& empty( $RosarioModules['Class_Diary_Premium'] ) ) // Verify Attendance module is activated & Premium module is not activated.
{
	$menu['Attendance']['admin'][] = dgettext( 'Class_Diary', 'Class Diary' );
	$menu['Attendance']['admin']['Class_Diary/Diaries.php'] = dgettext( 'Class_Diary', 'Read' );

	$menu['Attendance']['parent'][] = dgettext( 'Class_Diary', 'Class Diary' );
	$menu['Attendance']['parent']['Class_Diary/Diaries.php'] = dgettext( 'Class_Diary', 'Read' );

	$menu['Attendance']['teacher'][] = dgettext( 'Class_Diary', 'Class Diary' );
	$menu['Attendance']['teacher']['Class_Diary/Read.php'] = dgettext( 'Class_Diary', 'Read' );
	$menu['Attendance']['teacher']['Class_Diary/Write.php'] = dgettext( 'Class_Diary', 'Write' );
}
