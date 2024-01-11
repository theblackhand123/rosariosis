<?php
/**
 * Common functions
 *
 * @package Class Diary module
 */

/**
 * Get Course Period Title
 *
 * @param int $cp_id Course Period ID.
 *
 * @return string Course Period Title.
 */
function ClassDiaryCoursePeriodTitle( $cp_id )
{
	$cp_title = DBGetOne( "SELECT TITLE
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

	return $cp_title;
}

/**
 * Get Subject Title
 *
 * @param int $cp_id Course Period ID.
 *
 * @return string Subject Title.
 */
function ClassDiarySubjectTitle( $cp_id )
{
	$subject_title = DBGetOne( "SELECT cs.TITLE
		FROM course_periods cp,courses c,course_subjects cs
		WHERE cp.COURSE_PERIOD_ID='" . (int) $cp_id . "'
		AND cp.COURSE_ID=c.COURSE_ID
		AND c.SUBJECT_ID=cs.SUBJECT_ID" );

	return $subject_title;
}

if ( ! function_exists( 'ClassDiaryWriteInputs' ) )
{
	/**
	 * Get Write message inputs HTML
	 *
	 * @param int $cp_id Course Period ID.
	 *
	 * @return string Write message inputs HTML.
	 */
	function ClassDiaryWriteInputs( $cp_id )
	{
		$inputs = TinyMCEInput(
			'',
			'message',
			dgettext( 'Class_Diary', 'Entry' )
		);

		return $inputs;
	}
}

if ( ! function_exists( 'ClassDiarySaveEntry' ) )
{
	/**
	 * Save Class Diary entry
	 *
	 * @param string $entry_id Entry ID or 'new'.
	 * @param int    $cp_id    Course Period ID.
	 * @param array  $data     Entry data, associative array: name, message...
	 *
	 * @return bool True on success.
	 */
	function ClassDiarySaveEntry( $entry_id, $cp_id, $data )
	{
		if ( ! $entry_id
			|| ! $cp_id
			|| ! $data )
		{
			return false;
		}

		if ( $entry_id === 'new' )
		{
			// Save message.
			DBQuery( "INSERT INTO class_diary_messages (COURSE_PERIOD_ID,DATA)
				VALUES('" . UserCoursePeriod() . "','" . DBEscapeString( json_encode( $data ) ) . "');" );
		}
		else
		{
			DBQuery( "UPDATE class_diary_messages
				SET COURSE_PERIOD_ID='" . UserCoursePeriod() . "',
				DATA='" . DBEscapeString( json_encode( $data ) ) . "'
				WHERE ID='" . (int) $entry_id . "'" );
		}

		return true;
	}
}

if ( ! function_exists( 'ClassDiaryDeleteEntry' ) )
{
	/**
	 * Delete Class Diary entry
	 *
	 * @param int $entry_id Entry ID.
	 * @param int $cp_id Course Period ID.
	 *
	 * @return bool True on success.
	 */
	function ClassDiaryDeleteEntry( $entry_id, $cp_id )
	{
		if ( ! $entry_id
			|| ! $cp_id )
		{
			return false;
		}

		DBQuery( "DELETE FROM class_diary_messages
			WHERE ID='" . (int) $entry_id . "'
			AND COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

		return true;
	}
}

/**
 * Get Class Diary entries from DB
 *
 * @param int $cp_id Course Period ID.
 *
 * @return array Class Diary entries.
 */
function ClassDiaryGetEntries( $cp_id )
{
	$entries = DBGet( "SELECT ID,DATA,COURSE_PERIOD_ID,CREATED_AT
		FROM class_diary_messages
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'
		ORDER BY CREATED_AT DESC" );

	return $entries;
}

if ( ! function_exists( 'ClassDiaryDisplayEntry' ) )
{
	/**
	 * Format Class Diary entry HTML for display
	 *
	 * @param array $entry Class Diary entry from DB.
	 *
	 * @return string Entry HTML for display.
	 */
	function ClassDiaryDisplayEntry( $entry )
	{
		$data = json_decode( $entry['DATA'], true );

		$date = ProperDateTime( $entry['CREATED_AT'], 'short' );

		$message = '<div style="max-width: 1024px">' . $data['message'] . '</div>';

		$entry_html = $message .
			FormatInputTitle( $date, '', false, '' );

		return $entry_html;
	}
}

/**
 * Check Student is enrolled in Course Period
 *
 * @param int $student_id Student ID.
 * @param int $cp_id      Course Period ID.
 *
 * @return bool True if Student is enrolled in Course Period.
 */
function ClassDiaryCheckStudentCoursePeriod( $student_id, $cp_id )
{
	$has_cp_id = DBGetOne( "SELECT 1
		FROM schedule s
		WHERE s.STUDENT_ID='" . (int) $student_id . "'
		AND s.COURSE_PERIOD_ID='" . (int) $cp_id . "'
		AND s.START_DATE<=CURRENT_DATE
		AND (s.END_DATE IS NULL OR s.END_DATE>=CURRENT_DATE)
		AND s.SCHOOL_ID='" . UserSchool() . "'
		AND s.SYEAR='" . UserSyear() . "'"
	);

	return (bool) $has_cp_id;
}

/**
 * Get Class diaries for student, to display list
 *
 * @param int $student_id Student ID.
 *
 * @return array Class diaries for student from DB.
 */
function ClassDiaryGetStudentDiaries( $student_id )
{
	$diaries = DBGet( "SELECT cs.TITLE AS SUBJECT,cp.COURSE_PERIOD_ID,cp.TITLE,cp.COURSE_PERIOD_ID AS ENTRIES_COUNT,
		cp.COURSE_PERIOD_ID AS LAST_ENTRY_DATE,cp.COURSE_PERIOD_ID AS READ_LINK
		FROM course_periods cp,courses c,course_subjects cs,schedule s
		WHERE s.STUDENT_ID='" . (int) $student_id . "'
		AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
		AND EXISTS(SELECT 1 FROM class_diary_messages cdm
			WHERE cdm.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)
		AND s.START_DATE<=CURRENT_DATE
		AND (s.END_DATE IS NULL OR s.END_DATE>=CURRENT_DATE)
		AND s.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
		AND s.SCHOOL_ID='" . UserSchool() . "'
		AND s.SYEAR='" . UserSyear() . "'
		AND c.COURSE_ID=cp.COURSE_ID
		AND cs.SUBJECT_ID=c.SUBJECT_ID
		ORDER BY cs.SORT_ORDER IS NULL,cs.SORT_ORDER,c.TITLE,cp.TITLE",
		[
			'ENTRIES_COUNT' => 'ClassDiaryMakeEntriesCount',
			'LAST_ENTRY_DATE' => 'ClassDiaryMakeLastEntryDate',
			'READ_LINK' => 'ClassDiaryMakeReadLink',
		]
	);

	return $diaries;
}

/**
 * Get Class diaries for admin (current school), to display list
 *
 * @return array Class diaries for admin from DB.
 */
function ClassDiaryGetDiaries()
{
	$diaries = DBGet( "SELECT cs.TITLE AS SUBJECT,cp.COURSE_PERIOD_ID,cp.TITLE,cp.COURSE_PERIOD_ID AS ENTRIES_COUNT,
		cp.COURSE_PERIOD_ID AS LAST_ENTRY_DATE,cp.COURSE_PERIOD_ID AS READ_LINK
		FROM course_periods cp,courses c,course_subjects cs
		WHERE EXISTS(SELECT 1 FROM class_diary_messages cdm
			WHERE cdm.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)
		AND cp.SCHOOL_ID='" . UserSchool() . "'
		AND cp.SYEAR='" . UserSyear() . "'
		AND c.COURSE_ID=cp.COURSE_ID
		AND cs.SUBJECT_ID=c.SUBJECT_ID
		ORDER BY cs.SORT_ORDER IS NULL,cs.SORT_ORDER,c.TITLE,cp.TITLE",
		[
			'ENTRIES_COUNT' => 'ClassDiaryMakeEntriesCount',
			'LAST_ENTRY_DATE' => 'ClassDiaryMakeLastEntryDate',
			'READ_LINK' => 'ClassDiaryMakeReadLink',
		]
	);

	return $diaries;
}

/**
 * Make Class Diary entries count
 *
 * DBGet() function hook
 *
 * @param int    $value  Course Period ID.
 * @param string $column DB column.
 *
 * @return int Class Diary entries count
 */
function ClassDiaryMakeEntriesCount( $value, $column = 'ENTRIES_COUNT' )
{
	$entries_count = DBGetOne( "SELECT COUNT(ID) AS ENTRIES_COUNT
		FROM class_diary_messages
		WHERE COURSE_PERIOD_ID='" . (int) $value . "'" );

	return $entries_count;
}

/**
 * Make Class Diary last entry date
 *
 * DBGet() function hook
 *
 * @param int    $value  Course Period ID.
 * @param string $column DB column.
 *
 * @return string Class Diary last entry date
 */
function ClassDiaryMakeLastEntryDate( $value, $column = 'LAST_ENTRY_DATE' )
{
	$last_entry_date = DBGetOne( "SELECT CREATED_AT
		FROM class_diary_messages
		WHERE COURSE_PERIOD_ID='" . (int) $value . "'
		ORDER BY CREATED_AT DESC
		LIMIT 1" );

	return ProperDateTime( $last_entry_date, 'short' );
}

/**
 * Make Class Diary Read link
 *
 * DBGet() function hook
 *
 * @param int    $value  Course Period ID.
 * @param string $column DB column.
 *
 * @return string Class Diary Read link
 */
function ClassDiaryMakeReadLink( $value, $column = 'READ_LINK' )
{
	$read_url = PreparePHP_SELF(
		[],
		[],
		[ 'cp_id' => $value ]
	);

	$read_link = '<a href="' . $read_url . '">' . dgettext( 'Class_Diary', 'Read' ) . '</a>';

	return $read_link;
}
