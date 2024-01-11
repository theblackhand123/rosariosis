<?php
/**
 * Gradebook Grades Import functions
 *
 * @package Grades Import module
 * @subpackage includes
 */

/**
 * Convert Excel file to CSV
 * Only 1st sheet!
 * (Deletes Excel file)
 * Or, simply convert CSV to UTF8!
 *
 * @uses SimpleXLS class
 * @uses SimpleXLSX class
 *
 * @param string $import_file_path Excel file path.
 *
 * @return string CSV file path.
 */
function ConvertExcelToCSV( $import_file_path )
{
	$excel_extensions = [ '.csv', '.xls', '.xlsx' ];

	$file_ext = mb_strtolower( mb_strrchr( $import_file_path, '.' ) );

	if ( ! in_array( $file_ext, $excel_extensions ) )
	{
		// Not an Excel file.
		return $import_file_path;
	}


	if ( $file_ext === '.csv' )
	{
		$csv_file_path = $import_file_path;

		$csv_text = file_get_contents( $csv_file_path );

		/**
		 * Check if CSV is encoded in ISO-8859-1 or windows-1252.
		 *
		 * @todo How could we support other encodings?
		 */
		$is_windows1252_or_iso88591 = mb_check_encoding( $csv_text, 'ISO-8859-1' ) ||
			mb_check_encoding( $csv_text, 'windows-1252' );

		$encoding = mb_detect_encoding( $csv_text, mb_detect_order(), true );

		if ( $encoding !== 'UTF-8' && $is_windows1252_or_iso88591 )
		{
			/**
			 * Convert to UTF8
			 * Fix PHP8.2 utf8_encode() function deprecated
			 *
			 * @link https://stackoverflow.com/questions/7979567/php-convert-any-string-to-utf-8-without-knowing-the-original-character-set-or
			 */
			$csv_text_utf8 = iconv(
				mb_detect_encoding( $csv_text, [ 'ISO-8859-1', 'windows-1252' ], true ),
				'UTF-8',
				$csv_text
			);

			file_put_contents( $csv_file_path, $csv_text_utf8 );
		}

		return $csv_file_path;
	}

	require_once 'modules/Grades_Import/classes/SimpleXLS/SimpleXLS.php';
	require_once 'modules/Grades_Import/classes/SimpleXLSX/SimpleXLSX.php';

	$excel_class = $file_ext === '.xlsx' ? 'Shuchkin\\SimpleXLSX' : 'SimpleXLS';

	$excel = $excel_class::parseFile( $import_file_path, ROSARIO_DEBUG );

	if ( ! $excel )
	{
		ErrorMessage( [ sprintf(
			dgettext( 'Grades_Import', 'Excel error reading file %s: %s. Please convert the file to CSV format.' ),
			basename( $import_file_path ),
			$excel_class::parseError()
		) ], 'fatal' );
	}

	$csv_output = '';

	$rows = $excel->rows();

	foreach ( $rows as $row )
	{
		$formatted_row = array_map( function( $value )
		{
			/**
			 * Security: no need to prevent CSV Injection via formulas here
			 * as CSV file is not intended to be opened in Excel or LibreOffice Calc
			 */
			return '"' . str_replace( '"', '""', $value ) . '"';
		}, $row );

		$csv_output .= implode( ',', $formatted_row ) . "\n";
	}

	$csv_file_path = mb_substr(
		$import_file_path,
		0,
		mb_strrpos( $import_file_path, '.' )
	) . '.csv';

	file_put_contents( $csv_file_path, $csv_output );

	unlink( $import_file_path );

	return $csv_file_path;
}


/**
 * Detect delimiter of cells.
 *
 * @param string $name CSV file path.
 *
 * @return array Return detected delimiter.
 */
function DetectCSVDelimiter( $name )
{
	$delimiters = [
		';' => 0,
		',' => 0,
	];

	$handle = fopen( $name, 'r' );

	$first_line = fgets( $handle );

	fclose( $handle );

	foreach ( $delimiters as $delimiter => &$count )
	{
		$count = count( str_getcsv( $first_line, $delimiter ) );
	}

	return array_search( max( $delimiters ), $delimiters );
}



/**
 * Get CSV column name from number
 *
 * @param int $num Column number.
 *
 * @return string Column letter (eg.: "AB")
 */
function GetCSVColumnNameFromNumber( $num )
{
	$numeric = $num % 26;

	$letter = chr( 65 + $numeric );

	$num2 = intval( $num / 26 );

	if ( $num2 > 0 )
	{
		return GetCSVColumnNameFromNumber( $num2 - 1 ) . $letter;
	}
	else
	{
		return $letter;
	}
}


/**
 * Get CSV columns
 *
 * @param  string $csv_file_path  CSV file path.
 *
 * @return array  $csv_columns    CSV columns, eg.: "AB: Teacher Name".
 */
function GetCSVColumns( $csv_file_path )
{
	$csv_handle = fopen( $csv_file_path, 'r' );

	if ( ! $csv_handle )
	{
		return [];
	}

	// Get 1st CSV row, columns delimited by comma (,).
	$csv_columns = fgetcsv( $csv_handle, 0, DetectCSVDelimiter( $csv_file_path ) );

	fclose( $csv_handle );

	$max = count( $csv_columns );

	for ( $i = 0; $i < $max; $i++ )
	{
		// Add column name before value.
		$csv_columns[ $i ] = GetCSVColumnNameFromNumber( $i ) . ': ' . $csv_columns[ $i ];
	}

	return $csv_columns;
}

/**
 * Import Gradebook Grades from CSV
 *
 * @param string $csv_file_path Full path to CSV file.
 *
 * @return int Number of imported grades.
 */
function GradebookGradesCSVImport( $csv_file_path )
{
	global $i,
		$warning;

	$csv_handle = fopen( $csv_file_path, 'r' );

	if ( ! $csv_handle
		|| ! isset( $_REQUEST['values'] ) )
	{
		return 0;
	}

	$row = 0;

	$lines = [];

	$columns_values = my_array_flip( $_REQUEST['values'] );

	$delimiter = DetectCSVDelimiter( $csv_file_path );

	// Get CSV row.
	while ( ( $data = fgetcsv( $csv_handle, 0, $delimiter ) ) !== false )
	{
		// Trim.
		$data = array_map( 'trim', $data );

		// Import first row? (generally column names).
		if ( $row === 0 && ! $_REQUEST['import-first-row'] )
		{
			$row++;

			continue;
		}

		// For each column.
		for ( $col = 0, $col_max = count( $data ); $col < $col_max; $col++ )
		{
			if ( isset( $columns_values[ $col ] ) )
			{
				foreach ( (array) $columns_values[ $col ] as $field )
				{
					$lines[ $row ][ $field ] = $data[ $col ];
				}
			}
		}

		$row++;
	}

	// Sanitize input: Remove HTML tags.
	array_rwalk( $lines, 'strip_tags' );

	// var_dump( $lines ); exit;

	$max = count( $lines );

	$i = $grades_imported = 0;

	// Import first row? (generally column names).
	if ( ! $_REQUEST['import-first-row'] )
	{
		$max++;

		$i++;
	}

	for ( ; $i < $max; $i++ )
	{
		$grade_sql = [];

		$grade = $lines[ $i ];

		// INSERT Grades.
		foreach ( (array) $grade as $assignment_id => $points )
		{
			if ( strpos( $assignment_id, 'ASSIGNMENT_' ) === false )
			{
				continue;
			}

			$assignment_id = str_replace( 'ASSIGNMENT_', '', $assignment_id );

			$grade['ASSIGNMENT_ID'] = $assignment_id;

			$grade['POINTS'] = _checkPoints( $points );

			// INSERT Student.
			$grade['STUDENT_ID'] = _getExistingStudentID( $grade );

			if ( ! _checkRequired( $grade ) )
			{
				if ( ! $grade['STUDENT_ID'] )
				{
					// Skip all Assignments.
					break;
				}

				continue;
			}

			if ( ! _checkEnrolledStudentID(
					$grade['STUDENT_ID'],
					UsercoursePeriod(),
					$grade['ASSIGNMENT_ID'] ) )
			{
				break;
			}

			// INSERT or UPDATE Gradebook Grade.
			if ( _getGradebookGradeID( $grade ) )
			{
				$grades_imported++;
			}
		}
	}

	fclose( $csv_handle );

	return $grades_imported;
}

/**
 * Import Final Grades from CSV
 *
 * @param string $csv_file_path Full path to CSV file.
 *
 * @return int Number of imported grades.
 */
function FinalGradesCSVImport( $csv_file_path )
{
	global $i,
		$warning;

	$csv_handle = fopen( $csv_file_path, 'r' );

	if ( ! $csv_handle
		|| ! isset( $_REQUEST['values'] ) )
	{
		return 0;
	}

	$row = 0;

	$lines = [];

	$columns_values = my_array_flip( $_REQUEST['values'] );

	$delimiter = DetectCSVDelimiter( $csv_file_path );

	// Get CSV row.
	while ( ( $data = fgetcsv( $csv_handle, 0, $delimiter ) ) !== false )
	{
		// Trim.
		$data = array_map( 'trim', $data );

		// Import first row? (generally column names).
		if ( $row === 0 && ! $_REQUEST['import-first-row'] )
		{
			$row++;

			continue;
		}

		// For each column.
		for ( $col = 0, $col_max = count( $data ); $col < $col_max; $col++ )
		{
			if ( isset( $columns_values[ $col ] ) )
			{
				foreach ( (array) $columns_values[ $col ] as $field )
				{
					$lines[ $row ][ $field ] = $data[ $col ];
				}
			}
		}

		$row++;
	}

	// Sanitize input: Remove HTML tags.
	array_rwalk( $lines, 'strip_tags' );

	// var_dump( $lines ); exit;

	$max = count( $lines );

	$i = $grades_imported = 0;

	// Import first row? (generally column names).
	if ( ! $_REQUEST['import-first-row'] )
	{
		$max++;

		$i++;
	}

	$letter_or_percent = $_REQUEST['letter_or_percent'];

	for ( ; $i < $max; $i++ )
	{
		$grade_sql = [];

		$grade = $lines[ $i ];

		// INSERT Grades.
		foreach ( (array) $grade as $mp_id => $points )
		{
			if ( strpos( $mp_id, 'MARKING_PERIOD_' ) === false )
			{
				continue;
			}

			$mp_id = str_replace( 'MARKING_PERIOD_', '', $mp_id );

			$grade['MARKING_PERIOD_ID'] = $mp_id;

			$grade['POINTS'] = _checkGrade( $points, $letter_or_percent );

			// INSERT Student.
			$grade['STUDENT_ID'] = _getExistingStudentID( $grade );

			if ( ! _checkRequired( $grade ) )
			{
				continue;
			}

			if ( ! _checkEnrolledStudentID(
					$grade['STUDENT_ID'],
					UsercoursePeriod(),
					false,
					$grade['MARKING_PERIOD_ID'] ) )
			{
				break;
			}

			// INSERT or UPDATE Final Grade.
			if ( _getFinalGradeID( $grade, $letter_or_percent ) )
			{
				$grades_imported++;
			}
		}
	}

	fclose( $csv_handle );

	return $grades_imported;
}


/**
 * Check Required columns for missing data.
 *
 * Local function
 *
 * @see CSVImport()
 *
 * @param  array $fields Fields.
 *
 * @return string false if incomplete.
 */
function _checkRequired( $fields )
{
	global $warning,
		$i;

	// Student ID name cannot be empty.
	if ( ! $fields['STUDENT_ID'] )
	{
		$warning[] = 'Row #' . ( $i + 1 ) . ': ' .
			dgettext( 'Grades_Import', 'No student found.' );

		return false;
	}

	// Points cannot be empty.
	if ( ! $fields['POINTS'] )
	{
		if ( $fields['POINTS'] === false )
		{
			$warning[] = 'Row #' . ( $i + 1 ) . ': ' .
				dgettext( 'Grades_Import', 'No points found.' );
		}

		// No error if points are empty, just skip.
		return false;
	}

	return true;
}


/**
 * Check if Student is Enrolled in Course Period
 * - when the Assignment was due (Gradebook Grades)
 * - when the Marking Period ended (Final Grades)
 * - or is enrolled but inactive (Include Inactive Students checked)
 *
 * @param  int $student_id       Student ID.
 * @param  int $course_period_id Course Period ID.
 * @param  int $assignment_id    Assignment ID, for Gradebook Grades (optional).
 * @param  int $mp_id            Marking Period ID, for Final Grades (optional).
 *
 * @return bool                  False if not enrolled, else true.
 */
function _checkEnrolledStudentID( $student_id, $course_period_id, $assignment_id = false, $mp_id = false )
{
	global $warning,
		$i;

	if ( $assignment_id )
	{
		$date = DBGetOne( "SELECT DUE_DATE
			FROM gradebook_assignments
			WHERE ASSIGNMENT_ID='" . (int) $assignment_id . "'" );

		$mp_id = DBGetOne( "SELECT MARKING_PERIOD_ID
		FROM gradebook_assignments
		WHERE ASSIGNMENT_ID='" . (int) $assignment_id . "'" );
	}

	if ( empty( $date ) )
	{
		$date = DBDate();

		if ( $date > GetMP( $mp_id, 'END_DATE' ) )
		{
			$date = GetMP( $mp_id, 'END_DATE' );
		}
	}

	$where_active_sql = '';

	if ( empty( $_REQUEST['include_inactive'] ) )
	{
		$where_active_sql = " AND '" . $date . "'>=START_DATE
			AND ('" . $date . "'<=END_DATE OR END_DATE IS NULL)";
	}

	// Fix SQL student enrolled: check Schedule MP.
	$is_student_enrolled = (bool) DBGetOne( "SELECT STUDENT_ID
		FROM schedule
		WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		" . $where_active_sql . "
		AND STUDENT_ID='" . (int) $student_id . "'
		AND MARKING_PERIOD_ID IN(" . GetAllMP( GetMP( $mp_id, 'MP' ), $mp_id ) . ")" );

	if ( ! $is_student_enrolled )
	{
		$warning[] = 'Row #' . ( $i + 1 ) . ': ' .
			dgettext( 'Grades_Import', 'Student not scheduled in course Period.' );

		return false;
	}

	return true;
}


/**
 * Check Points
 *
 * @param  string $points Points.
 *
 * @return false if not points, else formatted points
 */
function _checkPoints( $points )
{
	if ( $points === '' )
	{
		return '';
	}

	if ( $points === '*' )
	{
		// Excuse Student.
		return '-1';
	}

	// Warning: convert European float format.
	$points = str_replace( ',', '.', $points );

	// Remove non numeric characters.
	$points = preg_replace( '/[^0-9.]/', '', $points );

	if ( ! $points )
	{
		return false;
	}

	return $points;
}

/**
 * Check Grade (Report Cards)
 *
 * @param  string $grade Letter or Percent Grade.
 *
 * @return false if not a valid grade, else formatted grade
 */
function _checkGrade( $grade, $letter_or_percent )
{
	if ( $grade === '' )
	{
		return '';
	}

	if ( $letter_or_percent === 'percent' )
	{
		// Warning: convert European float format.
		$grade = str_replace( ',', '.', $grade );

		// Remove non numeric characters.
		$grade = preg_replace( '/[^0-9.]/', '', $grade );
	}
	else
	{
		$grade = trim( $grade );
	}

	if ( ! $grade )
	{
		return false;
	}

	return $grade;
}


/**
 * Get Student ID, if exists, enrolled in school
 * Identify student by username or first name / last name
 * - when the Assignment was due (Gradebook Grades)
 * - when the Marking Period ended (Final Grades)
 * - or was enrolled but inactive (Include Inactive Students checked)
 *
 * @param  array $fields Fields array from CSV.
 *
 * @return int           0 if not enrolled, else Student ID.
 */
function _getExistingStudentID( $fields )
{
	if ( ! empty( $fields['MARKING_PERIOD_ID'] ) )
	{
		$mp_id = $fields['MARKING_PERIOD_ID'];
	}

	if ( ! empty( $fields['ASSIGNMENT_ID'] ) )
	{
		$date = DBGetOne( "SELECT DUE_DATE
			FROM gradebook_assignments
			WHERE ASSIGNMENT_ID='" . (int) $fields['ASSIGNMENT_ID'] . "'" );

		$mp_id = DBGetOne( "SELECT MARKING_PERIOD_ID
		FROM gradebook_assignments
		WHERE ASSIGNMENT_ID='" . (int) $fields['ASSIGNMENT_ID'] . "'" );
	}

	if ( empty( $date ) )
	{
		$date = DBDate();

		if ( $date > GetMP( $mp_id, 'END_DATE' ) )
		{
			$date = GetMP( $mp_id, 'END_DATE' );
		}
	}

	$where_active_sql = '';

	if ( empty( $_REQUEST['include_inactive'] ) )
	{
		$where_active_sql = " AND ('" . $date . "'>=ssm.START_DATE
			AND (ssm.END_DATE IS NULL OR '" . $date . "'<=ssm.END_DATE ))";
	}

	if ( $_REQUEST['student_identify'] === 'STUDENT_ID'
		|| $_REQUEST['student_identify'] === 'USERNAME' )
	{
		if ( empty( $fields[ $_REQUEST['student_identify'] ] ) )
		{
			// No Student ID or Username...
			return 0;
		}

		// Get Student ID by Student ID or Username.
		$where_sql = $_REQUEST['student_identify'] === 'STUDENT_ID' ?
			"s.STUDENT_ID='" . (int) $fields['STUDENT_ID'] . "'" :
			"s.USERNAME='" . $fields['USERNAME'] . "'";

		return (int) DBGetOne( "SELECT s.STUDENT_ID
		FROM students s
		JOIN student_enrollment ssm ON (ssm.STUDENT_ID=s.STUDENT_ID AND ssm.SYEAR='" . UserSyear() . "'
		" . $where_active_sql . "
		AND ssm.SCHOOL_ID='" . UserSchool() . "')
		WHERE " . $where_sql );
	}

	// Identify Student by Name.
	$first_name = explode( ' ', $fields['FIRST_NAME'] );

	$first_name = $first_name[0];

	$last_name = $fields['LAST_NAME'];

	// Get Student ID where First and Last Names match.
	return (int) DBGetOne( "SELECT s.STUDENT_ID
		FROM students s
		JOIN student_enrollment ssm ON (ssm.STUDENT_ID=s.STUDENT_ID AND ssm.SYEAR='" . UserSyear() . "'
		" . $where_active_sql . "
		AND ssm.SCHOOL_ID='" . UserSchool() . "')
		WHERE LOWER(s.LAST_NAME)='" . DBEscapeString( mb_strtolower( $last_name ) ) . "'
		AND LOWER(s.FIRST_NAME) LIKE '" . DBEscapeString( mb_strtolower( $first_name ) ) . "%'" );
}


/**
 * Insert in Database
 *
 * Local function
 *
 * @see CSVImport()
 *
 * @param  array $fields Fields.
 *
 * @return string SQL INSERT
 */
function _insert( $fields, $table )
{
	if ( ! in_array(
		$table,
		[ 'gradebook_grades', 'student_report_card_grades' ]
	) )
	{
		return '';
	}

	// INSERT lines.
	$sql = "INSERT INTO " . DBEscapeIdentifier( $table ) . " ";

	$columns = $values = '';

	foreach ( $fields as $field => $value )
	{
		if ( ! empty( $value )
			|| $value == '0' )
		{
			$columns .= DBEscapeIdentifier( $field ) . ',';

			$values .= "'" . DBEscapeString( $value ) . "',";
		}
	}

	$sql .= '(' . mb_substr( $columns, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ');';

	return $sql;
}


/**
 * Update in Database
 *
 * Local function
 *
 * @see CSVImport()
 *
 * @param  array $fields Fields.
 *
 * @return string SQL UPDATE
 */
function _update( $fields, $table, $where_fields )
{
	if ( ! in_array(
		$table,
		[ 'gradebook_grades', 'student_report_card_grades' ]
	) )
	{
		return '';
	}

	// UPDATE lines.
	$sql = "UPDATE " . DBEscapeIdentifier( $table ) . " SET ";

	$values = '';

	foreach ( $fields as $field => $value )
	{
		if ( ! empty( $value )
			|| $value == '0' )
		{
			$values .= DBEscapeIdentifier( $field ) . "='" . DBEscapeString( $value ) . "',";
		}
	}

	$sql .= mb_substr( $values, 0, -1 ) . " WHERE TRUE ";

	$where_sql = '';

	foreach ( $where_fields as $field => $value )
	{
		if ( ! empty( $value )
			|| $value == '0' )
		{
			$where_sql .= " AND " . DBEscapeIdentifier( $field ) . "='" . DBEscapeString( $value ) . "'";
		}
	}

	$sql .= $where_sql . ";";

	return $sql;
}



/**
 * Insert or update Gradebook Grade.
 *
 * Local function
 *
 * @see CSVImport()
 *
 * @param  array $fields Gradebook Grade Fields.
 *
 * @return bool
 */
function _getGradebookGradeID( $fields )
{
	static $i = 0;

	$grade = $fields;

	foreach ( $grade as $assignment_id => $points )
	{
		if ( $assignment_id !== 'ASSIGNMENT_ID'
			&& strpos( $assignment_id, 'ASSIGNMENT_' ) !== false )
		{
			unset( $grade[ $assignment_id ] );
		}
	}

	unset( $grade['USERNAME'] );

	unset( $grade['FIRST_NAME'] );

	unset( $grade['LAST_NAME'] );

	$grade['COURSE_PERIOD_ID'] = UserCoursePeriod();

	$grade_exists = DBGetOne( "SELECT 1 AS GRADE_EXISTS
		FROM gradebook_grades
		WHERE COURSE_PERIOD_ID='" . (int) $grade['COURSE_PERIOD_ID'] . "'
		AND ASSIGNMENT_ID='" . (int) $grade['ASSIGNMENT_ID'] . "'
		AND STUDENT_ID='" . (int) $grade['STUDENT_ID'] . "'" );

	if ( $grade_exists )
	{
		$where_grade = [
			'COURSE_PERIOD_ID' => $grade['COURSE_PERIOD_ID'],
			'ASSIGNMENT_ID' => $grade['ASSIGNMENT_ID'],
			'STUDENT_ID' => $grade['STUDENT_ID'],
		];

		$sql = _update(
			[ 'POINTS' => $grade['POINTS'] ],
			'gradebook_grades',
			$where_grade
		);
	}
	else
	{
		$sql = _insert( $grade, 'gradebook_grades' );
	}

	if ( ! $sql )
	{
		return false;
	}

	DBQuery( $sql );

	return true;
}

/**
 * Insert or update Final Grade.
 *
 * Local function
 *
 * @see CSVImport()
 *
 * @param  array $fields Final Grade Fields.
 *
 * @return bool
 */
function _getFinalGradeID( $fields, $letter_or_percent )
{
	static $grades_RET = null,
		$courses_RET = [];

	global $i,
		$warning;

	require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';
	require_once 'ProgramFunctions/_makePercentGrade.fnc.php';

	$grade = $fields;

	$course_period_id = UserCoursePeriod();

	if ( ! $grades_RET )
	{
		$grade_scale_id = DBGetOne( "SELECT GRADE_SCALE_ID
				FROM course_periods
				WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'" );

		$grades_RET = DBGet( "SELECT rcg.ID,rcg.TITLE,rcg.GPA_VALUE AS WEIGHTED_GP,
			rcg.UNWEIGHTED_GP,gs.GP_SCALE,gs.GP_PASSING_VALUE
			FROM report_card_grades rcg, report_card_grade_scales gs
			WHERE rcg.grade_scale_id = gs.id
			AND rcg.SYEAR='" . UserSyear() . "'
			AND rcg.SCHOOL_ID='" . UserSchool() . "'
			AND rcg.GRADE_SCALE_ID='" . (int) $grade_scale_id . "'
			ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER IS NULL,rcg.SORT_ORDER", [], [ 'TITLE' ] );
	}

	if ( empty( $courses_RET[ $grade['MARKING_PERIOD_ID'] ] ) )
	{
		$course_RET = DBGet( "SELECT cp.COURSE_ID,c.TITLE AS COURSE_NAME,cp.TITLE,
			cp.GRADE_SCALE_ID,credit('" . (int) $course_period_id . "','" . (int) $grade['MARKING_PERIOD_ID'] . "') AS CREDITS,
			DOES_CLASS_RANK AS CLASS_RANK,c.CREDIT_HOURS,cp.MARKING_PERIOD_ID
			FROM course_periods cp,courses c
			WHERE cp.COURSE_ID=c.COURSE_ID
			AND cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "'" );

		$courses_RET[ $grade['MARKING_PERIOD_ID'] ] = $course_RET;
	}
	else
	{
		$course_RET = $courses_RET[ $grade['MARKING_PERIOD_ID'] ];
	}

	foreach ( $grade as $mp_id => $points )
	{
		if ( $mp_id !== 'MARKING_PERIOD_ID'
			&& strpos( $mp_id, 'MARKING_PERIOD_' ) !== false )
		{
			unset( $grade[ $mp_id ] );
		}
	}

	unset( $grade['USERNAME'] );

	unset( $grade['FIRST_NAME'] );

	unset( $grade['LAST_NAME'] );

	if ( $letter_or_percent === 'percent' )
	{
		$percent = $grade['POINTS'];

		if ( ! is_numeric( $percent ) )
		{
			$percent = (float) $percent;
		}

		if ( $percent > 999.9 )
		{
			$percent = '999.9';
		}
		elseif ( $percent < 0 )
		{
			$percent = '0';
		}

		$grade_id = _makeLetterGrade( $percent / 100, $course_period_id, 0, 'ID' );

		$letter = _makeLetterGrade( $percent / 100, $course_period_id, 0, 'TITLE' );
	}
	else
	{
		if ( isset( $grades_RET[ $grade['POINTS'] ] ) )
		{
			$grade_id = $grades_RET[ $grade['POINTS'] ][1]['ID'];

			$letter = $grade['POINTS'];
		}
		else
		{
			$grade_no_comma = str_replace( ',', '.', $grade['POINTS'] );

			$grade_float = (string) (float) $grade_no_comma;

			$grade_float_comma = str_replace( '.', ',', $grade_float );

			if ( isset( $grades_RET[ $grade_no_comma ] ) )
			{
				$grade_id = $grades_RET[ $grade_no_comma ][1]['ID'];

				$letter = $grade_no_comma;
			}
			elseif ( isset( $grades_RET[ $grade_float ] ) )
			{
				$grade_id = $grades_RET[ $grade_float ][1]['ID'];

				$letter = $grade_float;
			}
			elseif ( isset( $grades_RET[ $grade_float_comma ] ) )
			{
				$grade_id = $grades_RET[ $grade_float_comma ][1]['ID'];

				$letter = $grade_float_comma;
			}
			else
			{
				$warning[] = 'Row #' . ( $i + 1 ) . ': ' .
					sprintf( dgettext( 'Grades_Import', 'Grade not found: %s' ), $grade['POINTS'] );

				return false;
			}
		}

		$percent = _makePercentGrade( $grade_id, $course_period_id );
	}

	unset( $grade['POINTS'] );

	$grade['SYEAR'] = UserSyear();

	$grade['SCHOOL_ID'] = UserSchool();

	$grade['REPORT_CARD_GRADE_ID'] = $grade_id;

	$grade['GRADE_LETTER'] = $letter;

	$grade['GRADE_PERCENT'] = $percent;

	$grade['COURSE_PERIOD_ID'] = $course_period_id;

	$grade['WEIGHTED_GP'] = $grades_RET[$letter][1]['WEIGHTED_GP'];

	// FJ add precision to year weighted GPA if not year course period.

	if ( GetMP( $grade['MARKING_PERIOD_ID'], 'MP' ) === 'FY'
		&& $course_RET[1]['MARKING_PERIOD_ID'] !== 'FY' )
	{
		$grade['WEIGHTED_GP'] = $percent / 100 * $grades_RET[$letter][1]['GP_SCALE'];
	}

	$grade['UNWEIGHTED_GP'] = $grades_RET[$letter][1]['UNWEIGHTED_GP'];

	$grade['GP_SCALE'] = $grades_RET[$letter][1]['GP_SCALE'];

	$gp_passing = $grades_RET[$letter][1]['GP_PASSING_VALUE'];

	$grade['COURSE_TITLE'] = DBEscapeString( $course_RET[1]['COURSE_NAME'] );

	$grade['CREDIT_ATTEMPTED'] = $course_RET[1]['CREDITS'];

	$grade['CREDIT_EARNED'] = ( (float) $grade['WEIGHTED_GP'] && $grade['WEIGHTED_GP'] >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' );

	$grade_exists_id = DBGetOne( "SELECT ID
		FROM student_report_card_grades
		WHERE COURSE_PERIOD_ID='" . (int) $grade['COURSE_PERIOD_ID'] . "'
		AND MARKING_PERIOD_ID='" . (int) $grade['MARKING_PERIOD_ID'] . "'
		AND STUDENT_ID='" . (int) $grade['STUDENT_ID'] . "'" );

	if ( $grade_exists_id )
	{
		$sql = _update(
			$grade,
			'student_report_card_grades',
			[ 'ID' => (int) $grade_exists_id ]
		);
	}
	else
	{
		$sql = _insert( $grade, 'student_report_card_grades' );
	}

	if ( ! $sql )
	{
		return false;
	}

	DBQuery( $sql );

	return true;
}

function _makeCheckboxInput( $column, $value, $title, $array = 'values' )
{
	return CheckboxInput( $value, $array . '[' . $column . ']', $title, '', true );
}



function _makeSelectInput( $column, $options, $title, $extra = '', $select2 = true, $array = 'values' )
{
	if (  ! $select2 )
	{
		return SelectInput( '', $array . '[' . $column . ']', $title, $options, 'N/A', $extra );
	}

	// @since RosarioSIS 10.7 Use Select2 input instead of Chosen, fix overflow issue.
	$select_input_function = function_exists( 'Select2Input' ) ? 'Select2Input' : 'ChosenSelectInput';

	return $select_input_function( '', $array . '[' . $column . ']', $title, $options, 'N/A', $extra );
}


/**
 * My array_flip()
 * Handles multiple occurrences of a value
 *
 * @param  array $array Input array.
 *
 * @return array        Flipped array.
 */
function my_array_flip( $array )
{
	$flipped = [];

	foreach ( $array as $key => $value )
	{
		$flipped[ $value ][] = $key;
	}

	return $flipped;
}

