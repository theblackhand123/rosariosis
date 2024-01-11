<?php
/**
 * Diaries List
 *
 * @package Class Diary module
 */

require_once 'modules/Class_Diary/includes/common.fnc.php';

DrawHeader( dgettext( 'Class_Diary', 'Class Diary' ) . ' &mdash; ' . ProgramTitle() );

// List Diaries for User.
if ( User( 'PROFILE' ) === 'student' || User( 'PROFILE' ) === 'parent' )
{
	$diaries = ClassDiaryGetStudentDiaries( UserStudentID() );
}
else
{
	// Admin.
	$diaries = ClassDiaryGetDiaries();
}

$LO_columns = [
	'SUBJECT' => _( 'Subject' ),
	'TITLE' => _( 'Course Period' ),
	'ENTRIES_COUNT' => dgettext( 'Class_Diary', 'Entries' ),
	'LAST_ENTRY_DATE' => dgettext( 'Class_Diary', 'Last Entry' ),
	'READ_LINK' => dgettext( 'Class_Diary', 'Read' ),
];

ListOutput(
	$diaries,
	$LO_columns,
	dgettext( 'Class_Diary', 'Class Diary' ),
	dgettext( 'Class_Diary', 'Class Diaries' )
);
