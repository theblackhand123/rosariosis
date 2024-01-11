<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the Lesson_Plan module
 *
 * @package Lesson Plan module
 */

// Add a Menu entry to the Scheduling module.
if ( ! empty( $RosarioModules['Scheduling'] )
	&& empty( $RosarioModules['Lesson_Plan_Premium'] ) ) // Verify Scheduling module is activated & Premium module is not activated.
{
	$menu['Scheduling']['admin'][] = dgettext( 'Lesson_Plan', 'Lesson Plan' );
	$menu['Scheduling']['admin']['Lesson_Plan/LessonPlans.php'] = dgettext( 'Lesson_Plan', 'Read' );

	$menu['Scheduling']['parent'][] = dgettext( 'Lesson_Plan', 'Lesson Plan' );
	$menu['Scheduling']['parent']['Lesson_Plan/LessonPlans.php'] = dgettext( 'Lesson_Plan', 'Read' );

	$menu['Scheduling']['teacher'][] = dgettext( 'Lesson_Plan', 'Lesson Plan' );
	$menu['Scheduling']['teacher']['Lesson_Plan/Read.php'] = dgettext( 'Lesson_Plan', 'Read' );
	$menu['Scheduling']['teacher']['Lesson_Plan/AddLesson.php'] = dgettext( 'Lesson_Plan', 'Add Lesson' );
}
