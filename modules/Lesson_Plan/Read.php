<?php
/**
 * Read
 *
 * @package Lesson Plan module
 */

require_once 'modules/Lesson_Plan/includes/common.fnc.php';

if ( empty( $_ROSARIO['DrawHeader'] )
	|| $_ROSARIO['DrawHeader'] !== 'header2' )
{
	// Fix ProgramTitle() before DeletePrompt for comment in Premium module Read.php!
	DrawHeader( dgettext( 'Lesson_Plan', 'Lesson Plan' ) . ' &mdash; ' . ProgramTitle() );
}

$cp_id = User( 'PROFILE' ) === 'teacher' ? UserCoursePeriod() : $_REQUEST['cp_id'];

if ( ( User( 'PROFILE' ) === 'student' || User( 'PROFILE' ) === 'parent' )
	&& ! LessonPlanCheckStudentCoursePeriod( UserStudentID(), $cp_id ) )
{
	// Student is not enrolled in CP!
	$cp_id = 0;
}

// If running as a teacher program then rosario[allow_edit] will already be set according to admin permissions.
if ( User( 'PROFILE' ) === 'teacher'
	&& ! isset( $_ROSARIO['allow_edit'] ) )
{
	$_ROSARIO['allow_edit'] = true;
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( DeletePrompt( dgettext( 'Lesson_Plan', 'Lesson and its Parts' ) ) )
	{
		$delete_ok = LessonPlanDeleteEntry( $_REQUEST['entry_id'], $cp_id );

		if ( $delete_ok )
		{
			$note[] = dgettext( 'Lesson_Plan', 'The lesson has been removed from the plan.' );
		}

		// Unset modfunc, message & redirect URL.
		RedirectURL( [ 'modfunc', 'entry_id' ] );
	}
}

if ( ! $cp_id )
{
	if ( User( 'PROFILE' ) === 'teacher' )
	{
		$error[] = _( 'No courses assigned to teacher.' );
	}
	else
	{
		$error[] = _( 'No courses found' );
	}

	echo ErrorMessage( $error, 'fatal' );
}

if ( ! $_REQUEST['modfunc'] )
{
	// Set start date.
	$start_date = RequestedDate( 'start', date( 'Y-m-d', strtotime( '-1 days' ) ) );

	// Set end date.
	$end_date = RequestedDate( 'end', date( 'Y-m-d', strtotime( '+7 days' ) ) );

	$lessons = LessonPlanGetEntries( $cp_id );

	if ( ! $lessons )
	{
		$warning[] = sprintf(
			_( 'No %s were found.' ),
			mb_strtolower( dngettext( 'Lesson_Plan', 'Lesson', 'Lessons', 0 ) )
		);
	}

	echo ErrorMessage( $error );

	echo ErrorMessage( $warning, 'warning' );

	echo ErrorMessage( $note, 'note' );

	if ( User( 'PROFILE' ) !== 'teacher'
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		DrawHeader(
			'<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] ) . '">« ' . _( 'Back' ) . '</a>'
		);
	}

	DrawHeader(
		LessonPlanSubjectTitle( $cp_id ),
		LessonPlanCoursePeriodTitle( $cp_id )
	);

	echo '<form method="GET" action="' . PreparePHP_SELF( [], [
		'month_start',
		'day_start',
		'year_start',
		'month_end',
		'day_end',
		'year_end',
	] ) . '">';

	DrawHeader( _( 'Timeframe' ) . ': ' . PrepareDate( $start_date, '_start', false ) . ' ' .
		_( 'to' ) . ' ' . PrepareDate( $end_date, '_end', false ) . ' ' . Buttons( _( 'Go' ) ) );

	echo '</form>';

	// Extra headers for Premium module.
	if ( ! empty( $lesson_plan_extra_headers ) )
	{
		foreach ( $lesson_plan_extra_headers as $extra_header )
		{
			DrawHeader( $extra_header );
		}
	}

	foreach ( $lessons as $lesson )
	{
		$entry_id = $lesson['ID'];

		$delete_button = '';

		if ( AllowEdit()
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			$delete_url = PreparePHP_SELF(
				[],
				[ 'delete_cancel' ],
				[ 'modfunc' => 'delete', 'entry_id' => $entry_id ]
			);

			$delete_button = button( 'remove', '', '"' . $delete_url . '"' );
		}

		echo '<br />';

		DrawHeader(
			'<table class="width-100p valign-top fixed-col">' . LessonPlanDisplayEntry( $lesson ) . '</table>',
			$delete_button
		);

		$items = LessonPlanGetItems( $entry_id );

		if ( $items )
		{
			LessonPlanItemListOutput( $entry_id, $items );
		}
	}
}
