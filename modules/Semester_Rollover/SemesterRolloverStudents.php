<?php

DrawHeader( ProgramTitle() );

$question = dgettext( 'Semester_Rollover', 'Are you sure you want to roll students to the next semester?' );

$warning_msg = ErrorMessage(
	[ sprintf(
		dgettext( 'Semester_Rollover', 'Please check the "%s" for each student and make a %s first.' ),
		_( 'Rolling / Retention Options' ),
		_( 'Database Backup' )
	) ],
	'warning'
);

$semester2_start_date = DBGetOne( "SELECT START_DATE
	FROM school_marking_periods
	WHERE MP='SEM'
	AND SCHOOL_ID='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'
	ORDER BY START_DATE DESC
	LIMIT 1" );

$semester2_day_before = date( 'Y-m-d', ( strtotime( '-1 day' , strtotime( $semester2_start_date ) ) ) );

$enrollment_table = '<table class="widefat center"><tr><td>';

$enrollment_table .= _makeSemesterRolloverEndInput( $semester2_day_before, 'END_DATE' );

$enrollment_table .= FormatInputTitle( _( 'Dropped' ), '', false, '' );

$enrollment_table .= '</td></tr><tr><td>';

// No div in _makeStartInput(), fake new student.
$_REQUEST['student_id'] = 'new';

$THIS_RET['ID'] = 1;

$enrollment_table .= _makeSemesterRolloverStartInput( $semester2_start_date, 'START_DATE' );

$enrollment_table .= FormatInputTitle( dgettext( 'Semester_Rollover', 'Next Semester Start Date' ), '', false, '' );

unset( $_REQUEST['student_id'] );

$enrollment_table .= '</td></tr></table>';

if ( Prompt(
	_( 'Confirm' ) . ' ' . _( 'Rollover' ),
	button( 'help', '', '', 'bigger' ) . '<br /><br />' .
		$question,
	$warning_msg . $enrollment_table
) )
{
	$end_date = RequestedDate(
		'END_DATE',
		$semester2_day_before
	);

	$drop_code = $_REQUEST['DROP_CODE'];

	$start_date = RequestedDate(
		'START_DATE',
		$semester2_start_date
	);

	if ( ! $start_date
		|| ! $end_date )
	{
		$error[] = dgettext( 'Semester_Rollover', 'Please enter valid dates.' );

		echo ErrorMessage( $error, 'fatal' );
	}

	$enrollment_code = $_REQUEST['ENROLLMENT_CODE'];

	// Select current enrollments.
	$current_enrollments_RET = DBGet( "SELECT ID,STUDENT_ID,CALENDAR_ID,NEXT_SCHOOL,GRADE_ID
		FROM student_enrollment
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND END_DATE IS NULL
		AND START_DATE<=CURRENT_DATE
		ORDER BY ID DESC" );

	$sql_drop_students = $sql_enroll_students = '';

	$retain_students = $next_grade_students = $dropped_students = $dropped_no_next_grade_students = $other_school_students = 0;

	$student_id_tmp = '0';

	foreach ( $current_enrollments_RET as $current_enrollment )
	{
		if ( $student_id_tmp === $current_enrollment['STUDENT_ID'] )
		{
			// We accidentally got various enrollment records for the same student, skip.
			continue;
		}

		$student_id_tmp = $current_enrollment['STUDENT_ID'];

		if ( ! $current_enrollment['NEXT_SCHOOL'] )
		{
			// Retain (NEXT_SCHOOL === 0).
			// No need to drop and re-enroll the student in the same Grade Level. The student is skipped.
			$retain_students++;

			continue;
		}

		// Drop Student.
		$sql_drop_students .= "UPDATE student_enrollment
			SET END_DATE='" . $end_date . "',DROP_CODE='" . $drop_code . "'
			WHERE ID='" . (int) $current_enrollment['ID'] . "';";

		$next_grade = $current_enrollment['GRADE_ID'];

		$calendar_id = $current_enrollment['CALENDAR_ID'];

		$school_id = UserSchool();

		$next_school = $current_enrollment['NEXT_SCHOOL'];

		if ( $next_school < 0 )
		{
			// Do not enroll after this school year.
			// Drop student.
			$dropped_students++;

			continue;
		}
		elseif ( $next_school === UserSchool() )
		{
			// Next grade at current school.
			$next_grade = DBGetOne( "SELECT NEXT_GRADE_ID
				FROM school_gradelevels
				WHERE SCHOOL_ID='" . UserSchool() . "'
				AND ID='" . (int) $current_enrollment['GRADE_ID'] . "'" );

			if ( ! $next_grade )
			{
				// No Next Grade set, only Drop.
				$dropped_no_next_grade_students++;

				continue;
			}

			$next_grade_students++;
		}
		elseif ( $next_school > 0 )
		{
			// Other School.
			$school_id = $next_school;

			// Other School, do not set Grade Level.
			$next_grade = '';

			// Other School, do not set Calendar.
			$calendar_id = '';

			$other_school_students++;
		}

		// Enroll Student.
		$sql_enroll_students .= "INSERT INTO student_enrollment
			(SYEAR,SCHOOL_ID,STUDENT_ID,START_DATE,ENROLLMENT_CODE,CALENDAR_ID,NEXT_SCHOOL,GRADE_ID)
			VALUES('" . UserSyear() . "','" . $school_id . "','" .
			$current_enrollment['STUDENT_ID'] . "','" . $start_date . "','" . $enrollment_code . "','" .
			$calendar_id . "','" . $next_school . "','" .
			$next_grade . "');";
	}

	if ( $sql_drop_students )
	{
		DBQuery( $sql_drop_students );
	}

	if ( $sql_enroll_students )
	{
		DBQuery( $sql_enroll_students );
	}

	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] ) ) . '" method="POST">';

	$totals = _( 'Students' ) . ':' . '<ul>';

	$totals .= '<li>' . $next_grade_students . ' - ' . _( 'Next grade at current school' ) . '</li>';

	$totals .= '<li>' . $retain_students . ' - ' . _( 'Retain' ) . '</li>';

	$totals .= '<li>' . $dropped_students . ' - ' . _( 'Dropped' ) . ': ' .
		_(  'Do not enroll after this school year' ) . '</li>';

	$totals .= '<li>' . $dropped_no_next_grade_students . ' - ' . _( 'Dropped' ) . ': ' .
		sprintf( 'No %s were found.', _( 'Next Grade' ) ) . '</li>';

	$totals .= '<li>' . $other_school_students . ' - ' . dgettext( 'Semester_Rollover', 'Other School' ) . '</li>';

	$totals .= '</ul>';

	$note[] = $totals;

	echo ErrorMessage( $note, 'note' );

	echo '<div class="center"><input type="submit" value="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( _( 'OK' ) ) : htmlspecialchars( _( 'OK' ), ENT_QUOTES ) ) . '" /></div></form>';
}


/**
 * Make Enrollment End Date & Code Inputs
 *
 * @global array  $THIS_RET
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Enrollment End Date & Code Inputs
 */
function _makeSemesterRolloverEndInput( $value, $column )
{
	static $drop_codes;

	if ( ! $drop_codes )
	{
		$options_RET = DBGet( "SELECT ID,TITLE AS TITLE
			FROM student_enrollment_codes
			WHERE SYEAR='" . UserSyear() . "'
			AND TYPE='Drop'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

		foreach ( (array) $options_RET as $option )
		{
			$drop_codes[$option['ID']] = $option['TITLE'];
		}
	}

	$div = false;

	return '<div class="nobr">' .
		DateInput(
			$value,
			$column,
			'',
			$div,
			false
		) . ' - ' .
		SelectInput(
			'',
			'DROP_CODE',
			'',
			$drop_codes,
			'N/A',
			'style="max-width:150px;"'
		) .
	'</div>';
}


/**
 * Make Enrollment Start Date & Code Inputs
 *
 * @since 5.4 Enrollment Start: No N/A option if already has Drop date.
 *
 * @global array  $THIS_RET
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Enrollment Start Date & Code Inputs
 */
function _makeSemesterRolloverStartInput( $value, $column )
{
	static $add_codes = [];

	$add = '';

	$na = 'N/A';

	$id = 'new';

	if ( ! $add_codes )
	{
		$options_RET = DBGet( "SELECT ID,TITLE AS TITLE
			FROM student_enrollment_codes
			WHERE SYEAR='" . UserSyear() . "'
			AND TYPE='Add'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

		foreach ( (array) $options_RET as $option )
		{
			$add_codes[$option['ID']] = $option['TITLE'];
		}
	}

	$div = false;

	// FJ remove LO_field.
	return '<div class="nobr">' . $add .
		DateInput(
			$value,
			$column,
			'',
			$div,
			false
		) . ' - ' .
		SelectInput(
			'',
			'ENROLLMENT_CODE',
			'',
			$add_codes,
			$na,
			'style="max-width:150px;"'
		) .
	'</div>';
}
