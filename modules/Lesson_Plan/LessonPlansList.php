<?php
/**
 * Lesson Plans List
 *
 * @package Lesson Plan module
 */

require_once 'modules/Lesson_Plan/includes/common.fnc.php';

DrawHeader( dgettext( 'Lesson_Plan', 'Lesson Plan' ) . ' &mdash; ' . ProgramTitle() );

// List Lesson Plans for User.
if ( User( 'PROFILE' ) === 'student' || User( 'PROFILE' ) === 'parent' )
{
	$lesson_plans = LessonPlanGetStudentPlans( UserStudentID() );
}
else
{
	// Admin.
	$lesson_plans = LessonPlanGetPlans();
}

$LO_columns = [
	'SUBJECT' => _( 'Subject' ),
	'TITLE' => _( 'Course Period' ),
	'ENTRIES_COUNT' => dgettext( 'Lesson_Plan', 'Entries' ),
	'LAST_ENTRY_DATE' => dgettext( 'Lesson_Plan', 'Last Entry' ),
	'READ_LINK' => dgettext( 'Lesson_Plan', 'Read' ),
];

ListOutput(
	$lesson_plans,
	$LO_columns,
	dgettext( 'Lesson_Plan', 'Lesson Plan' ),
	dgettext( 'Lesson_Plan', 'Lesson Plans' )
);
