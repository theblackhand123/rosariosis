<?php

require_once 'ProgramFunctions/FileUpload.fnc.php';

require_once 'modules/Attendance_Excel_Sheet/classes/PHPExcel/IOFactory.php';
require_once 'modules/Attendance_Excel_Sheet/includes/AttendanceExcelSheet.fnc.php';
require_once 'modules/Scheduling/includes/ClassSearchWidget.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( empty( $_REQUEST['cp_arr'] ) )
	{
		BackPrompt( _( 'You must choose at least one course period.' ) );
	}

	$cp_list = "'" . implode( "','", $_REQUEST['cp_arr'] ) . "'";

	$course_periods_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,cp.TITLE,cp.TEACHER_ID,cp.MARKING_PERIOD_ID,
		cp.MP,cp.ROOM,cp.SHORT_NAME,c.TITLE AS COURSE_TITLE,cs.TITLE AS SUBJECT_TITLE
		FROM course_periods cp,courses c,course_subjects cs
		WHERE cp.COURSE_PERIOD_ID IN (" . $cp_list . ")
		AND c.COURSE_ID=cp.COURSE_ID
		AND cs.SUBJECT_ID=c.SUBJECT_ID
		ORDER BY cp.SHORT_NAME,cp.TITLE" );

	$teachers_RET = DBGet( "SELECT STAFF_ID,LAST_NAME,FIRST_NAME," . DisplayNameSQL() . " AS FULL_NAME
		FROM staff
		WHERE STAFF_ID IN (SELECT TEACHER_ID
			FROM course_periods
			WHERE COURSE_PERIOD_ID IN (" . $cp_list . "))", [], [ 'STAFF_ID' ] );

	//echo '<pre>'; var_dump($teachers_RET); echo '</pre>';

	// Load XLS template.
	$excel_sheet = AttendanceExcelSheetLoad( 'modules/Attendance_Excel_Sheet/AttendanceSheet.xls' );

	//d($excel_sheet);exit;

	if ( ! $excel_sheet )
	{
		BackPrompt( dgettext( 'Attendance_Excel_Sheet', 'Error: AttendanceSheet.xls file not found.' ) );
	}

	$excel_sheets = [];

	$no_students_backprompt = true;

	foreach ( (array) $course_periods_RET as $course_period )
	{
		$course_period_id = $course_period['COURSE_PERIOD_ID'];
		$teacher_id = $course_period['TEACHER_ID'];

		if ( ! $teacher_id )
		{
			continue;
		}

		$_SESSION['UserCoursePeriod'] = $course_period_id;

		$extra = [
			'SELECT_ONLY' => "s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME",
			'ORDER_BY' => 's.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME',
			'MP' => $course_period['MARKING_PERIOD_ID'],
			'MPTable' => $course_period['MP'],
		];

		$extra['WHERE'] = " AND s.STUDENT_ID IN
		(SELECT STUDENT_ID
		FROM schedule
		WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND '" . DBDate() . "'>=START_DATE
		AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL))";

		$RET = GetStuList( $extra );

		//echo '<pre>'; var_dump($RET); echo '</pre>';

		if ( empty( $RET ) )
		{
			continue;
		}

		$no_students_backprompt = false;

		$teacher = $teachers_RET[$teacher_id][1];

		$excel_sheet_fill = AttendanceExcelSheetWriteTeacher( $excel_sheet, $teacher );

		$excel_sheet_fill = AttendanceExcelSheetWriteCoursePeriod( $excel_sheet_fill, $course_period );

		$i = 1;

		foreach ( (array) $RET as $student )
		{
			$excel_sheet_fill = AttendanceExcelSheetWriteStudent( $excel_sheet_fill, $student, $i++ );
		}

		$excel_sheet_path = AttendanceExcelSheetSaveTmp(
			$excel_sheet_fill,
			'AttendanceSheet_' . no_accents( $course_period['SHORT_NAME'] ) . '.xls'
		);

		if ( ! $excel_sheet_path )
		{
			BackPrompt( dgettext( 'Attendance_Excel_Sheet', 'Error: Cannot save temporary Excel sheet.' ) );
		}

		$excel_sheets[] = $excel_sheet_path;
	}

	if ( $no_students_backprompt )
	{
		BackPrompt( _( 'No Students were found.' ) );
	}

	AttendanceExcelSheetDownload( $excel_sheets );

	exit;
}

if ( ! $_REQUEST['modfunc']
	|| $_REQUEST['modfunc'] === 'list' )
{
	DrawHeader( ProgramTitle() );

	if ( User( 'PROFILE' ) !== 'admin' )
	{
		$_REQUEST['modfunc'] = 'list';
	}

	$extra = issetVal( $extra, [] );

	if ( $_REQUEST['modfunc'] === 'list' )
	{
		echo '<form action="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&_ROSARIO_PDF=true' ) :
			_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&_ROSARIO_PDF=true' ) ) . '" method="POST">';

		$extra['header_right'] = Buttons( dgettext( 'Attendance_Excel_Sheet', 'Create Attendance Sheet for Selected Course Periods' ) );

		$extra['extra_header_left'] = '<table>';


		if ( User( 'PROFILE' ) === 'admin' || User( 'PROFILE' ) === 'teacher' )
		{
			$extra['extra_header_left'] .= '<tr><td colspan="3"><label><input type="checkbox" name="include_inactive" value="Y"> ' . _( 'Include Inactive Students' ) . '</label></td></tr>';
		}

		$extra['extra_header_left'] .= '</table>';
	}

	ClassSearchWidget( $extra );

	if ( $_REQUEST['modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . Buttons( dgettext( 'Attendance_Excel_Sheet', 'Create Attendance Sheet for Selected Course Periods' ) ) . '</div>';
		echo '</form>';
	}
}
