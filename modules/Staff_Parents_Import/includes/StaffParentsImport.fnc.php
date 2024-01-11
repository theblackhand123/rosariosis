<?php
/**
 * Staff and Parents Import functions
 *
 * @package Staff and Parents Import module
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

	require_once 'modules/Staff_Parents_Import/classes/SimpleXLS/SimpleXLS.php';
	require_once 'modules/Staff_Parents_Import/classes/SimpleXLSX/SimpleXLSX.php';

	$excel_class = $file_ext === '.xlsx' ? 'Shuchkin\\SimpleXLSX' : 'SimpleXLS';

	$excel = $excel_class::parseFile( $import_file_path, ROSARIO_DEBUG );

	if ( ! $excel )
	{
		ErrorMessage( [ sprintf(
			dgettext( 'Staff_Parents_Import', 'Excel error reading file %s: %s. Please convert the file to CSV format.' ),
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
 * @return array  $csv_columns    CSV columns, eg.: "AB: User Name".
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


function CSVImport( $csv_file_path )
{
	global $i,
		$note;

	$csv_handle = fopen( $csv_file_path, 'r' );

	if ( ! $csv_handle
		|| ! isset( $_REQUEST['values'] ) )
	{
		return 0;
	}

	$row = 0;

	$users = $enrollment = [];

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
					$users[ $row ][ $field ] = $data[ $col ];
				}
			}
		}

		$row++;
	}

	/*$enrollment = $_REQUEST['enrollment'];

	$enrollment['START_DATE'] = RequestedDate(
		$_REQUEST['year_enrollment']['START_DATE'],
		$_REQUEST['month_enrollment']['START_DATE'],
		$_REQUEST['day_enrollment']['START_DATE']
	);*/

	// Sanitize input: Remove HTML tags.
	array_rwalk( $users, 'strip_tags' );
	// array_rwalk( $enrollment, 'strip_tags' );

	//var_dump( $users, $enrollment ); //exit;

	$max = count( $users );

	$i = $staff_parents_imported = $notifications_sent = 0;

	// Import first row? (generally column names).
	if ( ! $_REQUEST['import-first-row'] )
	{
		$max++;

		$i++;
	}

	for ( ; $i < $max; $i++ )
	{
		$user_sql = [];

		$user = $users[ $i ];

		if ( ! _checkUser( $user ) )
		{
			continue;
		}

		// Get current School Year.
		$user['SYEAR'] = UserSyear();

		$user['PROFILE'] = _getUserProfile(
			isset( $user['PROFILE'] ) ? $user['PROFILE'] : $_REQUEST['values']['PROFILE']
		);

		$user['PROFILE_ID'] = _getUserProfileID( $user['PROFILE'] );

		// INSERT Enrollment.
		// $user_sql[] = _insertUserEnrollment( $user['STAFF_ID'], $enrollment );

		// INSERT User.
		$user_sql[] = _insertUser( $user );

		DBQuery( implode( '', $user_sql ) );

		// Get next Staff ID.
		$user['STAFF_ID'] = _getUserID( $user );

		$staff_parents_imported++;

		if ( ! empty( $_REQUEST['send_notification'] )
			&& ! empty( $user['EMAIL'] )
			&& ! empty( $user['PASSWORD'] ) )
		{
			$notification_sent = SendNotificationNewUserAccount( $user['STAFF_ID'], $user['EMAIL'], $user['PASSWORD'] );

			if ( $notification_sent )
			{
				$notifications_sent++;
			}
		}
	}

	fclose( $csv_handle );

	if ( ! empty( $_REQUEST['send_notification'] )
		&& $staff_parents_imported )
	{
		$note[] = sprintf(
			dgettext( 'Staff_Parents_Import', '%d notifications sent.' ),
			$notifications_sent
		);
	}

	return $staff_parents_imported;
}



/**
 * Check for existing User
 * Existing First & Last name
 * or Username
 *
 * Local function
 *
 * @see CSVImport()
 *
 * @param  array $user_fields User Fields.
 *
 * @return string false if incomplete or existing user.
 */
function _checkUser( $user_fields )
{
	global $warning,
		$i;

	// First & Last name cannot be empty.
	if ( ! $user_fields['FIRST_NAME']
		|| ! $user_fields['LAST_NAME'] )
	{
		$warning[] = 'Row #' . ( $i + 1 ) . ': ' .
			dgettext( 'Staff_Parents_Import', 'No names were found.' );

		return false;
	}

	// Or Username.
	if ( isset( $user_fields['USERNAME'] )
		&& $user_fields['USERNAME'] )
	{
		$existing_username = DBGet( "SELECT 'exists'
			FROM staff
			WHERE USERNAME='" . DBEscapeString( $user_fields['USERNAME'] ) . "'
			AND SYEAR='" . UserSyear() ."'
			UNION SELECT 'exists'
			FROM students
			WHERE USERNAME='" . DBEscapeString( $user_fields['USERNAME'] ) . "'" );

		if ( $existing_username )
		{
			$warning[] = 'Row #' . ( $i + 1 ) . ': ' .
				_( 'A user with that username already exists. Choose a different username and try again.' );

			return false;
		}
	}

	/*$user = DBGet( "SELECT STAFF_ID
		FROM staff
		WHERE FIRST_NAME='" . $user_fields['FIRST_NAME'] . "'
		AND LAST_NAME='" . $user_fields['LAST_NAME'] . "'" );

	if ( $user )
	{
		$user_id = $user[1]['STAFF_ID'];
	}*/

	return true;
}


/**
 * Get last inserted Staff ID.
 *
 * Local function
 *
 * @see CSVImport()
 *
 * @return string User ID
 */
function _getUserID()
{
	if ( function_exists( 'DBLastInsertID' ) )
	{
		$user_id = DBLastInsertID();
	}
	else
	{
		// @deprecated since RosarioSIS 9.2.1.
		$user_id = DBGetOne( "SELECT LASTVAL();" );
	}

	return $user_id;
}


/**
 * Get User Profile
 *
 * Local function
 *
 * Accepts the translated Profile title (from CSV file column).
 *
 * @see CSVImport()
 *
 * @param  string $profile_title User Profile title: admin, teacher, parent or none.
 *
 * @return string User Profile.
 */
function _getUserProfile( $profile_title )
{
	// Requested User Profile key?
	if ( mb_strpos( $profile_title, 'KEY_' ) !== false )
	{
		$profile_id = str_replace( 'KEY_', '', $profile_title );
	}
	// Try to deduce Profile from its Title.
	elseif ( $profile_title )
	{
		$profile_options = [
			'admin' => _( 'Administrator' ),
			'teacher' => _( 'Teacher' ),
			'parent' => _( 'Parent' ),
			'none' => _( 'No Access' )
		];

		if ( in_array( mb_strtolower( $profile_title ), array_keys( $profile_options ) ) )
		{
			$profile_id = mb_strtolower( $profile_title );
		}
		elseif ( in_array( $profile_title, $profile_options ) )
		{
			foreach ( (array) $profile_options as $profile_option_id => $profile_option )
			{
				if ( $profile_option === $profile_title )
				{
					$profile_id = $profile_option_id;
				}
			}
		}
	}

	if ( ! isset( $profile_id ) )
	{
		// Do NOT fail, default to No Access profile.
		$profile_id = 'none';
	}

	return $profile_id;
}



/**
 * Get User Profile ID
 *
 * Local function
 *
 * @see _getUserProfile()
 *
 * @param  string $profile_title User Profile title: admin, teacher, parent or none.
 *
 * @return string User Profile ID.
 */
function _getUserProfileID( $profile_title )
{
	// Defaults to No Access.
	$profile_id = '';

	if ( $profile_title === 'admin' )
	{
		$profile_id = '1';
	}
	elseif ( $profile_title === 'teacher' )
	{
		$profile_id = '2';
	}
	elseif ( $profile_title === 'parent' )
	{
		$profile_id = '3';
	}

	return $profile_id;
}


/**
 * Insert User in Database
 *
 * Local function
 *
 * @see CSVImport()
 *
 * @param  array $user_fields User Fields.
 *
 * @return string SQL INSERT
 */
function _insertUser( $user_fields )
{
	static $custom_fields_RET = null;

	if ( ! $custom_fields_RET )
	{
		$custom_fields_RET = DBGet( "SELECT ID,TYPE
			FROM staff_fields
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );
	}

	// INSERT users.
	$sql = "INSERT INTO staff ";

	if ( isset( $user_fields['PASSWORD'] )
		&& $user_fields['PASSWORD'] != '' )
	{
		$user_fields['PASSWORD'] = encrypt_password( $user_fields['PASSWORD'] );
	}

	$fields = $values = '';

	foreach ( $user_fields as $field => $value )
	{
		if ( ! empty( $value )
			|| $value == '0' )
		{
			$field_type = isset( $custom_fields_RET[ str_replace( 'CUSTOM_', '', $field ) ] ) ?
				$custom_fields_RET[ str_replace( 'CUSTOM_', '', $field ) ][1]['TYPE'] : null;

			// Check field type.
			if ( ( $value = _checkFieldType( $value, $field_type ) ) === false )
			{
				continue;
			}

			if ( function_exists( 'DBEscapeIdentifier' ) ) // RosarioSIS 3.0+.
			{
				$fields .= DBEscapeIdentifier( $field ) . ',';
			}
			else
			{
				$fields .= '"' . mb_strtolower( $field ) . '",';
			}

			$values .= "'" . DBEscapeString( $value ) . "',";
		}
	}

	$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ');';

	return $sql;
}


function _checkFieldType( $value, $field_type )
{
	global $error,
		$i;

	// Check text and apparented fields.
	if ( $field_type  == 'text'
		|| $field_type  == 'exports'
		|| $field_type  == 'select'
		|| $field_type  == 'autos' )
	{
		// If string length > 1000, strip.
		return mb_strlen( $value ) > 1000 ? mb_substr( $value, 0, 1000 ) : $value;
	}
	// Check textarea fields.
	elseif ( $field_type  == 'textarea' )
	{
		// If string length > 50000, strip.
		return mb_strlen( $value ) > 50000 ? mb_substr( $value, 0, 50000 ) : $value;
	}
	// Check numeric fields.
	elseif ( $field_type  == 'numeric'
		&& ( $value = _checkNumeric( $value ) ) === false )
	{
		$error[] = 'Row #' . ( $i + 1 ) . ':' .
			_( 'Please enter valid Numeric data.' );

		return false;
	}
	// Check dates.
	elseif ( $field_type == 'date'
		&& ( $value = _checkDate( $value ) ) === false )
	{
		$error[] = 'Row #' . ( $i + 1 ) . ':' .
			_( 'Some dates were not entered correctly.' );

		return false;
	}
	// Check checkbox.
	elseif ( $field_type == 'radio' )
	{
		// Return nothing if anything different than Y!
		return mb_strtolower( $value ) === mb_strtolower( 'Y' ) ? 'Y' : '';
	}
	// Check multiple.
	elseif ( $field_type == 'multiple' )
	{
		return _checkMultiple( $value );
	}
	// TODO: codeds?

	return $value;
}



/**
 * Check Multiple
 *
 * @param  string $multiple Multiple.
 *
 * @return Formatted Multiple:
 */
function _checkMultiple( $multiple )
{
	if ( $multiple === '' )
	{
		return '';
	}

	$separator = _detectMultipleSeparator( $multiple );

	$multiple = str_replace( $separator, '||', $multiple );

	return '||' . $multiple . '||';
}



/**
 * Detect separator of multiple values.
 * Allowed separators: semi-colons (;) and pipes (|)
 *
 * @param string $multiple Multiple value.
 *
 * @return array Return detected separator.
 */
function _detectMultipleSeparator( $multiple )
{
	$separators = [
		';' => 0,
		'|' => 0,
	];

	foreach ( $separators as $separator => &$count )
	{
		$count = count( str_getcsv( $multiple, $separator ) );
	}

	return array_search( max( $separators ), $separators );
}




/**
 * Check Date
 *
 * @param  string $date Date.
 *
 * @return false if not a date, else ISO formatted Date
 */
function _checkDate( $date )
{
	if ( $date === '' )
	{
		return '';
	}

	if ( ! strtotime( $date ) )
	{
		return false;
	}

	return date( 'Y-m-d', strtotime( $date ) );
}


/**
 * Check Numeric
 *
 * @uses _parseFloat()
 * @uses _tofloat()
 *
 * @param  string $numeric Numeric.
 *
 * @return false if not a numeric, else Formatted Numeric
 */
function _checkNumeric( $numeric )
{
	if ( $numeric === '' )
	{
		return '';
	}

	if ( ! is_numeric( $numeric ) )
	{
		$numeric = _formatLongInteger( $numeric );
	}

	if ( ! is_numeric( $numeric ) )
	{
		$numeric = _tofloat( $numeric );
	}

	if ( $numeric === '' )
	{
		return false;
	}

	// Respect format: NUMERIC(20,2).
	if ( strlen( substr( $numeric, 0, strrpos( $numeric, '.' ) ) ) > 20 )
	{
		return false;
	}

	return $numeric;
}


/**
 * Floatval pro:
 * Takes the last comma or dot (if any) to make a clean float,
 * ignoring thousand separator, currency or any other letter
 *
 * @link http://php.net/manual/en/function.floatval.php#114486
 *
 * @param  string $float Float string.
 *
 * @return float         Parsed Float
 */
function _tofloat( $num )
{
	$dotPos = strrpos($num, '.');

	$commaPos = strrpos($num, ',');

	$sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
		((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

	if ( ! $sep )
	{
		return floatval( preg_replace( "/[^0-9]/", "", $num ) );
	}

	return floatval(
		preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
		preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
	);
}


/**
 * Format long integer exponential notation
 *
 * @link http://stackoverflow.com/questions/4964059/convert-exponential-to-a-whole-number-in-php
 *
 * @param  string $long_int Long integer.
 *
 * @return string           Formatted long integer (no E+XX)
 */
function _formatLongInteger( $long_int )
{
	if ( mb_stripos( $long_int, 'E+' ) !== false )
	{
		// Ex.: 1.234E+12
		return number_format( (float) $long_int, 0, '.', '' );
	}

	return $long_int;
}



function _makeCheckboxInput( $column, $value, $title, $array = 'values' )
{
	return CheckboxInput( $value, $array . '[' . $column . ']', $title, '', true );
}



function _makeDateInput( $column, $title, $allow_na, $array = 'values' )
{
	return DateInput( DBDate(), $array . '[' . $column . ']', $title, false, $allow_na );
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


function _makeFieldTypeTooltip( $type, $extra_text = '' )
{
	$type_labels = [
		'select' => _( 'Pull-Down' ),
		'autos' => _( 'Auto Pull-Down' ),
		'text' => _( 'Text' ),
		'radio' => _( 'Checkbox' ),
		'exports' => _( 'Export Pull-Down' ),
		'numeric' => _( 'Number' ),
		'multiple' => _( 'Select Multiple from Options' ),
		'date' => _( 'Date' ),
		'textarea' => _( 'Long Text' ),
	];

	$label = $type_labels[ $type ];

	$tooltip_text = $label;

	switch ( $type )
	{
		case 'text':
		case 'textarea':
		case 'numeric':
		case 'textarea':
		case 'select':
		case 'autos':
		case 'exports':

		break;

		case 'date':

			// $tooltip_text .= ': <span class="custom-date-format">' . _( 'YYYY-MM-DD' ) . '</span>';
		break;


		case 'radio':

			$tooltip_text .= ': <span class="custom-checkbox-format">Y</span>';
		break;
	}

	return $tooltip_text ? '<div class="tooltip"><i>' . $tooltip_text . $extra_text . '</i></div>' : '';
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

