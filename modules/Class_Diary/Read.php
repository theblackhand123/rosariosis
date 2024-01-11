<?php
/**
 * Teacher Diary
 *
 * @package Class Diary module
 */

require_once 'modules/Class_Diary/includes/common.fnc.php';

if ( empty( $_ROSARIO['DrawHeader'] )
	|| $_ROSARIO['DrawHeader'] !== 'header2' )
{
	// Fix ProgramTitle() before DeletePrompt for comment in Premium module Read.php!
	DrawHeader( dgettext( 'Class_Diary', 'Class Diary' ) . ' &mdash; ' . ProgramTitle() );
}

$cp_id = User( 'PROFILE' ) === 'teacher' ? UserCoursePeriod() : $_REQUEST['cp_id'];

if ( ( User( 'PROFILE' ) === 'student' || User( 'PROFILE' ) === 'parent' )
	&& ! ClassDiaryCheckStudentCoursePeriod( UserStudentID(), $cp_id ) )
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
	if ( DeletePrompt( dgettext( 'Class_Diary', 'Entry' ) ) )
	{
		$delete_ok = ClassDiaryDeleteEntry( $_REQUEST['entry_id'], $cp_id );

		if ( $delete_ok )
		{
			$note[] = dgettext( 'Class_Diary', 'The entry has been removed from the diary.' );
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
	$diary_entries = ClassDiaryGetEntries( $cp_id );

	if ( ! $diary_entries )
	{
		$warning[] = sprintf(
			_( 'No %s were found.' ),
			mb_strtolower( dngettext( 'Class_Diary', 'Diary entry', 'Diary entries', 0 ) )
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
		ClassDiarySubjectTitle( $cp_id ),
		ClassDiaryCoursePeriodTitle( $cp_id )
	);

	// Extra headers for Premium module.
	if ( ! empty( $class_diary_extra_headers ) )
	{
		foreach ( $class_diary_extra_headers as $extra_header )
		{
			DrawHeader( $extra_header );
		}
	}

	foreach ( $diary_entries as $diary_entry )
	{
		$entry_id = $diary_entry['ID'];

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
			ClassDiaryDisplayEntry( $diary_entry ),
			$delete_button
		);
	}
}
