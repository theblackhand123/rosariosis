<?php
/**
 * Timetable Import functions
 *
 * @package Timetable Import module
 * @subpackage includes
 */

use plugins\Grades_Import\classes\SimpleXLS\SimpleXLS;
use plugins\Grades_Import\classes\SimpleXLSX\SimpleXLSX;

if ( ! function_exists( 'CoursePeriodOptionInputs' ) )
{
	// Compatibility for RosarioSIS < 4.9.
	require_once 'modules/Timetable_Import/includes/Courses.fnc.php';
}

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

	require_once 'modules/Timetable_Import/classes/SimpleXLS/SimpleXLS.php';
	require_once 'modules/Timetable_Import/classes/SimpleXLSX/SimpleXLSX.php';

	$excel_class = $file_ext === '.xlsx' ? 'plugins\\Grades_Import\\classes\\SimpleXLSX\\SimpleXLSX' : 'SimpleXLS';

	$excel = $excel_class::parseFile( $import_file_path, ROSARIO_DEBUG );

	if ( ! $excel )
	{
		ErrorMessage( [ sprintf(
			dgettext( 'Timetable_Import', 'Excel error reading file %s: %s. Please convert the file to CSV format.' ),
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
 * Delete existing Courses and Course Periods
 * + Fix SQL error Foreign key: delete Gradebook Assignments.
 * + Fix SQL error Foreign key: delete Attendance Period.
 * + Fix SQL error Foreign key: delete Class Diary messages.
 */
function _deleteCourses()
{
	global $DatabaseType;

	DBQuery( "DELETE FROM course_period_school_periods
		WHERE COURSE_PERIOD_ID IN(SELECT COURSE_PERIOD_ID
			FROM course_periods
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "')" );

	DBQuery( "DELETE FROM gradebook_assignments
		WHERE COURSE_PERIOD_ID IN(SELECT COURSE_PERIOD_ID
			FROM course_periods
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "')
		OR COURSE_ID IN(SELECT COURSE_ID
			FROM courses
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "')" );

	DBQuery( "DELETE FROM gradebook_assignment_types
		WHERE COURSE_ID IN(SELECT COURSE_ID
			FROM courses
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "')" );

	DBQuery( "DELETE FROM gradebook_grades
		WHERE COURSE_PERIOD_ID IN(SELECT COURSE_PERIOD_ID
			FROM course_periods
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "')" );

	DBQuery( "DELETE FROM attendance_period
		WHERE COURSE_PERIOD_ID IN(SELECT COURSE_PERIOD_ID
			FROM course_periods
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "')" );


	$class_diary_messages_table_exists = DBGetOne( "SELECT 1
		FROM information_schema.tables
		WHERE table_schema=" . ( $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
		AND table_name='class_diary_messages';" );

	if ( $class_diary_messages_table_exists )
	{
		DBQuery( "DELETE FROM class_diary_messages
			WHERE COURSE_PERIOD_ID IN(SELECT COURSE_PERIOD_ID
				FROM course_periods
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "')" );
	}

	$delete_sql[] = "DELETE FROM schedule
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'";

	$delete_sql[] = "DELETE FROM schedule_requests
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'";

	$delete_sql[] = "DELETE FROM course_periods
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'";

	$delete_sql[] = "DELETE FROM courses
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'";

	$delete_queries = implode( ';', $delete_sql );

	DBQuery( $delete_queries );
}


function CSVImport( $csv_file_path )
{
	global $i;

	$csv_handle = fopen( $csv_file_path, 'r' );

	if ( ! $csv_handle
		|| ! isset( $_REQUEST['values'] ) )
	{
		return 0;
	}

	if ( ! empty( $_REQUEST['delete-courses'] ) )
	{
		_deleteCourses();
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

	$i = $course_periods_imported = 0;

	// Import first row? (generally column names).
	if ( ! $_REQUEST['import-first-row'] )
	{
		$max++;

		$i++;
	}

	$old_course_period = [ 'ACTIVITY_ID' => 0 ];

	for ( ; $i < $max; $i++ )
	{
		$course_period_sql = [];

		$course_period = $lines[ $i ];

		if ( ! _checkRequired( $course_period ) )
		{
			continue;
		}

		if ( _isNewCoursePeriod( $old_course_period, $course_period ) )
		{
			// INSERT Teacher.
			$course_period['TEACHER_ID'] = _getTeacherID( $course_period );

			// INSERT Subject.
			$course_period['SUBJECT_ID'] = _getSubjectID( $course_period );

			// INSERT Course.
			$course_period['COURSE_ID'] = _getCourseID( $course_period );

			// INSERT Period.
			$course_period['PERIOD_ID'] = _getPeriodID( $course_period );

			// INSERT Course Period.
			$course_period['COURSE_PERIOD_ID'] = _getCoursePeriodID( $course_period );

			$course_periods_imported++;
		}
		else
		{
			$course_period['TEACHER_ID'] = $old_course_period['TEACHER_ID'];
			$course_period['SUBJECT_ID'] = $old_course_period['SUBJECT_ID'];
			$course_period['COURSE_ID'] = $old_course_period['COURSE_ID'];

			// INSERT Period.
			$course_period['PERIOD_ID'] = _getPeriodID( $course_period );

 			$course_period['COURSE_PERIOD_ID'] = $old_course_period['COURSE_PERIOD_ID'];
			$course_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'] = $old_course_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'];
		}

		// INSERT School Period Course Period.
		$course_period['COURSE_PERIOD_SCHOOL_PERIODS_ID'] = _getCoursePeriodSchoolPeriodsID( $course_period );

		$old_course_period = $course_period;
	}

	fclose( $csv_handle );

	return $course_periods_imported;
}

function _isNewCoursePeriod( $old_course_period, $course_period )
{
	if ( ! empty( $old_course_period['ACTIVITY_ID'] ) && ! empty( $course_period['ACTIVITY_ID'] ) )
	{
		if ( $old_course_period['ACTIVITY_ID'] == $course_period['ACTIVITY_ID'] )
		{
			return false;
		}

		return true;
	}

	if ( isset( $old_course_period['SUBJECT'] )
		&& $old_course_period['SUBJECT'] === $course_period['SUBJECT']
		&& $old_course_period['GRADE_LEVELS'] === $course_period['GRADE_LEVELS']
		&& $old_course_period['TEACHER'] === $course_period['TEACHER']
		&& ( empty( $course_period['ROOM'] ) || $old_course_period['ROOM'] === $course_period['ROOM'] ) )
	{
		return false;
	}

	return true;
}

function _getCoursePeriodSchoolPeriodsID( $fields )
{
	$cpsp = [
		'COURSE_PERIOD_ID' => $fields['COURSE_PERIOD_ID'],
		'PERIOD_ID' => $fields['PERIOD_ID'],
	];

	$day = _getDay( $fields['DAY'] );

	if ( ! $day )
	{
		return 0;
	}

	$cpsp = _getExistingCPSP( $cpsp );

	if ( empty( $cpsp['COURSE_PERIOD_SCHOOL_PERIODS_ID'] ) )
	{
		$cpsp['DAYS'] = $day;

		$sql = _insert( $cpsp, 'course_period_school_periods' );

		DBQuery( $sql );

		if ( function_exists( 'DBLastInsertID' ) )
		{
			$cpsp_id = DBLastInsertID();
		}
		else
		{
			// @deprecated since RosarioSIS 9.2.1.
			$cpsp_id = DBGetOne( "SELECT LASTVAL();" );
		}
	}
	else
	{
		$cpsp_id = $cpsp['COURSE_PERIOD_SCHOOL_PERIODS_ID'];

		if ( strpos( $cpsp['DAYS'], $day ) !== false )
		{
			$days = $cpsp['DAYS'] . $day;

			DBQuery( "UPDATE course_period_school_periods
				SET DAYS='" . $days . "'
				WHERE COURSE_PERIOD_SCHOOL_PERIODS_ID='" . (int) $cpsp_id . "'" );
		}
	}

	$title_add = CoursePeriodSchoolPeriodsTitlePartGenerate( $cpsp_id, $fields['COURSE_PERIOD_ID'], $cpsp );

	$current_cp = DBGet( "SELECT TITLE,MARKING_PERIOD_ID,SHORT_NAME
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $fields['COURSE_PERIOD_ID'] . "'" );

	$base_title = mb_substr(
		$current_cp[1]['TITLE'],
		mb_strpos(
			$current_cp[1]['TITLE'],
			( GetMP( $current_cp[1]['MARKING_PERIOD_ID'], 'MP' ) != 'FY' ?
				GetMP( $current_cp[1]['MARKING_PERIOD_ID'], 'SHORT_NAME' ) :
				$current_cp[1]['SHORT_NAME'] )
		)
	);

	$title = $title_add . $base_title;

	DBQuery( "UPDATE course_periods
		SET TITLE='" . $title . "'
		WHERE COURSE_PERIOD_ID='" . (int) $fields['COURSE_PERIOD_ID'] . "'" );

	return $cpsp_id;
}


function _getExistingCPSP( $fields )
{
	$course_period_id = $fields['COURSE_PERIOD_ID'];

	$period_id = $fields['PERIOD_ID'];

	$cpsp_RET = DBGet( "SELECT COURSE_PERIOD_SCHOOL_PERIODS_ID,COURSE_PERIOD_ID,
		PERIOD_ID,DAYS,CREATED_AT,UPDATED_AT
		FROM course_period_school_periods
		WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND PERIOD_ID='" . (int) $period_id . "'" );

	if ( ! $cpsp_RET )
	{
		return $fields;
	}

	return $cpsp_RET[1];
}


function _getDay( $day )
{
	$days_convert = [
		_( 'Sunday' ) => 'U',
		_( 'Monday' ) => 'M',
		_( 'Tuesday' ) => 'T',
		_( 'Wednesday' ) => 'W',
		_( 'Thursday' ) => 'H',
		_( 'Friday' ) => 'F',
		_( 'Saturday' ) => 'S',
	];

	$days_convert_english = [
		'Sunday' => 'U',
		'Monday' => 'M',
		'Tuesday' => 'T',
		'Wednesday' => 'W',
		'Thursday' => 'H',
		'Friday' => 'F',
		'Saturday' => 'S',
	];

	if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
	{
		$days_convert = [
			_( 'Sunday' ) => '7',
			_( 'Monday' ) => '1',
			_( 'Tuesday' ) => '2',
			_( 'Wednesday' ) => '3',
			_( 'Thursday' ) => '4',
			_( 'Friday' ) => '5',
			_( 'Saturday' ) => '6',
		];

		$days_convert_english = [
			'Sunday' => '7',
			'Monday' => '1',
			'Tuesday' => '2',
			'Wednesday' => '3',
			'Thursday' => '4',
			'Friday' => '5',
			'Saturday' => '6',
		];
	}

	if ( isset( $days_convert[ $day ] ) )
	{
		return $days_convert[ $day ];
	}

	if ( isset( $days_convert_english[ $day ] ) )
	{
		return $days_convert_english[ $day ];
	}

	$day = ucfirst( mb_strtolower( $day ) );

	if ( isset( $days_convert[ $day ] ) )
	{
		return $days_convert[ $day ];
	}

	if ( isset( $days_convert_english[ $day ] ) )
	{
		return $days_convert_english[ $day ];
	}
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

	// First & Last name cannot be empty.
	if ( ! $fields['TEACHER'] )
	{
		$warning[] = 'Row #' . ( $i + 1 ) . ': ' .
			dgettext( 'Timetable_Import', 'No teacher found.' );

		return false;
	}

	// Subject cannot be empty.
	if ( ! $fields['SUBJECT'] )
	{
		$warning[] = 'Row #' . ( $i + 1 ) . ': ' .
			dgettext( 'Timetable_Import', 'No subject found.' );

		return false;
	}

	// Day cannot be empty.
	if ( ! $fields['DAY'] )
	{
		$warning[] = 'Row #' . ( $i + 1 ) . ': ' .
			dgettext( 'Timetable_Import', 'No day found.' );

		return false;
	}

	// Period cannot be empty.
	if ( ! $fields['PERIOD_ID']
		&& $fields['PERIOD_ID'] !== '0' )
	{
		$warning[] = 'Row #' . ( $i + 1 ) . ': ' .
			dgettext( 'Timetable_Import', 'No period found.' );

		return false;
	}

	// Student sets / grade levels cannot be empty.
	if ( ! $fields['GRADE_LEVELS'] )
	{
		$warning[] = 'Row #' . ( $i + 1 ) . ': ' .
			dgettext( 'Timetable_Import', 'No student sets / grade levels found.' );

		return false;
	}

	return true;
}


function _getTeacherInfo( $name )
{
	// FET multiple Teachers separated by a +.
	$names = explode( '+', $name );

	$name = $names[0];

	$first_last = explode( ' ', $name );

	$i = (int) round( count( $first_last ) / 2, 0, PHP_ROUND_HALF_UP );

	$first = implode( ' ', array_slice( $first_last, 0, $i ) );

	$last = implode( ' ', array_slice( $first_last, $i ) );

	if ( empty( $last ) )
	{
		// Fix SQL error when no LAST_NAME.
		$last = _( 'None' );
	}

	$info = [
		'FULL_NAME' => $name,
		'FIRST_NAME' => $first,
		'LAST_NAME' => $last,
	];

	return $info;
}

/**
 * Get Teacher ID.
 * Insert Teacher if not found existing one.
 *
 * Local function
 *
 * @see CSVImport()
 *
 * @param  array $fields Teacher Fields.
 *
 * @return string Teacher ID
 */
function _getTeacherID( $fields )
{
	$teacher = _getTeacherInfo( $fields['TEACHER'] );

	$user_id = _getExistingTeacherID( $teacher );

	if ( ! $user_id )
	{
		$teacher['SYEAR'] = UserSyear();

		$teacher['PROFILE'] = 'teacher';

		$teacher['PROFILE_ID'] = '2';

		unset( $teacher['FULL_NAME'] );

		$sql = _insert( $teacher, 'staff' );

		DBQuery( $sql );

		if ( function_exists( 'DBLastInsertID' ) )
		{
			$user_id = DBLastInsertID();
		}
		else
		{
			// @deprecated since RosarioSIS 9.2.1.
			$user_id = DBGetOne( "SELECT LASTVAL();" );
		}
	}

	return $user_id;
}


function _getExistingTeacherID( $fields )
{
	$full_name = $fields['FULL_NAME'];

	$first_last = $fields['FIRST_NAME'] . ' ' . $fields['LAST_NAME'];

	$id = DBGetOne( "SELECT STAFF_ID
		FROM staff
		WHERE PROFILE='teacher'
		AND SYEAR='" . UserSyear() . "'
		AND (LOWER(CONCAT(FIRST_NAME, ' ', LAST_NAME))=LOWER('" . DBEscapeString( $first_last ) . "')
			OR LOWER(" . DisplayNameSQL() . ")=LOWER('" . DBEscapeString( $full_name ) . "'))" );

	return (int) $id;
}


/**
 * Insert Teacher in Database
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
		[ 'staff', 'course_subjects', 'courses', 'course_periods', 'school_periods', 'course_period_school_periods' ]
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
 * Get Subject ID.
 * Insert Subject if not found existing one.
 *
 * Local function
 *
 * @see CSVImport()
 *
 * @param  array $fields Subject Fields.
 *
 * @return string Subject ID
 */
function _getSubjectID( $fields )
{
	$subject = [ 'TITLE' => $fields['SUBJECT'] ];

	$subject_id = _getExistingSubjectID( $subject );

	if ( ! $subject_id )
	{
		$subject['SYEAR'] = UserSyear();

		$subject['SCHOOL_ID'] = UserSchool();

		$sql = _insert( $subject, 'course_subjects' );

		DBQuery( $sql );

		if ( function_exists( 'DBLastInsertID' ) )
		{
			$subject_id = DBLastInsertID();
		}
		else
		{
			// @deprecated since RosarioSIS 9.2.1.
			$subject_id = DBGetOne( "SELECT LASTVAL();" );
		}
	}

	return $subject_id;
}



function _getExistingSubjectID( $fields )
{
	$title = $fields['TITLE'];

	$id = DBGetOne( "SELECT SUBJECT_ID
		FROM course_subjects
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND LOWER(TITLE)=LOWER('" . DBEscapeString( $title ) . "')" );

	return (int) $id;
}



/**
 * Get Course ID.
 * Insert Course.
 *
 * Local function
 *
 * @see CSVImport()
 *
 * @param  array $fields Course Fields.
 *
 * @return string Course ID
 */
function _getCourseID( $fields )
{
	$short_name = mb_substr( $fields['SUBJECT'], 0, 4 ) . ' ' . $fields['GRADE_LEVELS'];

	if ( mb_strlen( $short_name ) > 23 )
	{
		$short_name = mb_substr( $short_name, 0, 23 );
	}

	$title = $fields['SUBJECT'] . ' ' . $fields['GRADE_LEVELS'];

	if ( mb_strlen( $title > 100 ) )
	{
		// Fix SQL error value too long for type character varying(100)
		$title = mb_substr( $title, 0, 100 );
	}

	$course = [
		'TITLE' => $title,
		'SHORT_NAME' => $short_name,
	];

	$course_id = _getExistingCourseID( $course );

	if ( ! $course_id )
	{
		$course['SUBJECT_ID'] = $fields['SUBJECT_ID'];

		$course['SYEAR'] = UserSyear();

		$course['SCHOOL_ID'] = UserSchool();

		$sql = _insert( $course, 'courses' );

		DBQuery( $sql );

		if ( function_exists( 'DBLastInsertID' ) )
		{
			$course_id = DBLastInsertID();
		}
		else
		{
			// @deprecated since RosarioSIS 9.2.1.
			$course_id = DBGetOne( "SELECT LASTVAL();" );
		}
	}

	return $course_id;
}



function _getExistingCourseID( $fields )
{
	$title = $fields['TITLE'];

	$id = DBGetOne( "SELECT COURSE_ID
		FROM courses
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND LOWER(TITLE)=LOWER('" . DBEscapeString( $title ) . "')" );

	return (int) $id;
}



/**
 * Get Period ID.
 * Insert Period if not found existing one.
 *
 * Local function
 *
 * @see CSVImport()
 *
 * @param  array $fields Period Fields.
 *
 * @return string Period ID
 */
function _getPeriodID( $fields )
{
	$period = [ 'TITLE' => $fields['PERIOD_ID'] ];

	$period_id = _getExistingPeriodID( $period );

	if ( ! $period_id )
	{
		$period['SYEAR'] = UserSyear();

		$period['SCHOOL_ID'] = UserSchool();

		$sql = _insert( $period, 'school_periods' );

		DBQuery( $sql );

		if ( function_exists( 'DBLastInsertID' ) )
		{
			$period_id = DBLastInsertID();
		}
		else
		{
			// @deprecated since RosarioSIS 9.2.1.
			$period_id = DBGetOne( "SELECT LASTVAL();" );
		}
	}

	return $period_id;
}



function _getExistingPeriodID( $fields )
{
	$title = $fields['TITLE'];

	/**
	 * SQL TRIM() both compatible with PostgreSQL and MySQL.
	 *
	 * @link https://www.sqltutorial.org/sql-string-functions/sql-trim/
	 */
	$id = DBGetOne( "SELECT PERIOD_ID
		FROM school_periods
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND (LOWER(TITLE)=LOWER('" . DBEscapeString( $title ) . "')
			OR LOWER(SHORT_NAME)=LOWER('" . DBEscapeString( $title ) . "')
			OR TRIM(TRAILING ':' FROM TRIM(TRAILING '0' FROM TITLE))=TRIM(TRAILING ':' FROM TRIM(TRAILING '0' FROM '" . DBEscapeString( $title ) . "')))" ); // 14:00 == 14.

	// Period: try matching "04" (Short Name) with "4", if Period is int.
	if ( ! $id
		&& (string) (int) $title === $title )
	{
		$id = DBGetOne( "SELECT PERIOD_ID
		FROM school_periods
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND TRIM(LEADING '0' FROM SHORT_NAME)='" . DBEscapeString( $title ) . "'" ); // 04 == 4.
	}

	return (int) $id;
}



/**
 * Get Course Period ID.
 * Insert Course Period.
 *
 * Local function
 *
 * @see CSVImport()
 *
 * @param  array $fields Course Period Fields.
 *
 * @return string Course Period ID
 */
function _getCoursePeriodID( $fields )
{
	static $i = 0,
		$last_name = '';

	$name = mb_substr( $fields['SUBJECT'], 0, 4 ) . ' ' . $fields['GRADE_LEVELS'];

	if ( mb_strlen( $name ) > 23 )
	{
		$name = mb_substr( $name, 0, 23 );
	}

	if ( $name !== $last_name )
	{
		$i = 0;
	}

	$last_name = $name;

	$course_period = [
		'SHORT_NAME' => $name . ' ' . ++$i,
	];

	$course_period['COURSE_ID'] = $fields['COURSE_ID'];

	$course_period['SYEAR'] = UserSyear();

	$course_period['SCHOOL_ID'] = UserSchool();

	$course_period['TEACHER_ID'] = $fields['TEACHER_ID'];

	$course_period['ROOM'] = ! empty( $fields['ROOM'] ) ? $fields['ROOM'] : '';

	$course_period['TOTAL_SEATS'] = ! empty( $fields['TOTAL_SEATS'] ) ? (int) $fields['TOTAL_SEATS'] : '';

	$course_period['MARKING_PERIOD_ID'] = $_REQUEST['values']['MARKING_PERIOD_ID'];

	if ( GetMP( $course_period['MARKING_PERIOD_ID'], 'MP' ) == 'FY' )
	{
		$course_period['MP'] = 'FY';
	}
	elseif ( GetMP( $course_period['MARKING_PERIOD_ID'], 'MP' ) == 'SEM' )
	{
		$course_period['MP'] = 'SEM';
	}
	else
	{
		$course_period['MP'] = 'QTR';
	}

	$course_period['TITLE'] = CoursePeriodTitleGenerate( 0, $course_period );

	$course_period['TITLE'] = DBEscapeString( $course_period['TITLE'] );

	$course_period['DOES_ATTENDANCE'] = $_REQUEST['cp_options']['DOES_ATTENDANCE'];

	if ( $course_period['DOES_ATTENDANCE'] )
	{
		$tbls = '';

		foreach ( (array) $course_period['DOES_ATTENDANCE'] as $tbl => $y )
		{
			if ( $y == 'Y' )
			{
				$tbls .= ',' . $tbl;
			}
		}

		if ( $tbls )
		{
			$course_period['DOES_ATTENDANCE'] = $tbls . ',';
		}
		else
		{
			$course_period['DOES_ATTENDANCE'] = '';
		}
	}

	$course_period['DOES_HONOR_ROLL'] = $_REQUEST['cp_options']['DOES_HONOR_ROLL'];

	$course_period['DOES_CLASS_RANK'] = $_REQUEST['cp_options']['DOES_CLASS_RANK'];

	$course_period['GENDER_RESTRICTION'] = $_REQUEST['cp_options']['GENDER_RESTRICTION'];

	$course_period['GRADE_SCALE_ID'] = $_REQUEST['cp_options']['GRADE_SCALE_ID'];

	$course_period['CREDITS'] = $_REQUEST['cp_options']['CREDITS'];

	$course_period['CALENDAR_ID'] = $_REQUEST['cp_options']['CALENDAR_ID'];

	$course_period['DOES_BREAKOFF'] = $_REQUEST['cp_options']['DOES_BREAKOFF'];

	$sql = _insert( $course_period, 'course_periods' );

	DBQuery( $sql );

	if ( function_exists( 'DBLastInsertID' ) )
	{
		$course_period_id = DBLastInsertID();
	}
	else
	{
		// @deprecated since RosarioSIS 9.2.1.
		$course_period_id = DBGetOne( "SELECT LASTVAL();" );
	}

	if ( ! isset( $course_period['PARENT_ID'] ) )
	{
		DBQuery( "UPDATE course_periods
			SET PARENT_ID='" . (int) $course_period_id . "'
			WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'" );
	}

	return $course_period_id;
}

function _makeCheckboxInput( $column, $value, $title, $array = 'values' )
{
	return CheckboxInput( $value, $array . '[' . $column . ']', $title, '', true );
}



function _makeSelectInput( $column, $options, $title, $extra = '', $select2 = true, $array = 'values' )
{
	$select_input_function = 'SelectInput';

	if ( $select2 )
	{
		// @since RosarioSIS 10.7 Use Select2 input instead of Chosen, fix overflow issue.
		$select_input_function = function_exists( 'Select2Input' ) ? 'Select2Input' : 'ChosenSelectInput';
	}

	return $select_input_function( '', $array . '[' . $column . ']', $title, $options, 'N/A', $extra . ' style="max-width:280px;"' );
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

