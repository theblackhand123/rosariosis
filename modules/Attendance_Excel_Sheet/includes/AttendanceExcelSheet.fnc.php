<?php
/**
 * Attendance Excel Sheet functions
 */

function AttendanceExcelSheetLoad( $file_path )
{
	if ( ! file_exists( $file_path ) )
	{
		return false;
	}

	$excel = PHPExcel_IOFactory::load( $file_path );

	$excel->setActiveSheetIndex( 0 );

	return $excel;
}

function AttendanceExcelSheetWriteTeacher( $excel, $teacher )
{
	$excel->getActiveSheet()->setCellValue( 'R7', $teacher['FULL_NAME'] );

	return $excel;
}

function AttendanceExcelSheetWriteCoursePeriod( $excel, $course_period )
{
	$excel->getActiveSheet()->setCellValue( 'AL8', $course_period['ROOM'] );

	$mp_title = GetMP( $course_period['MARKING_PERIOD_ID'], 'TITLE' );

	$excel->getActiveSheet()->setCellValue( 'R11', $mp_title );

	$start_date = GetMP( $course_period['MARKING_PERIOD_ID'], 'START_DATE' );

	$excel->getActiveSheet()->setCellValue( 'D12', $start_date );

	$end_date = GetMP( $course_period['MARKING_PERIOD_ID'], 'END_DATE' );

	$excel->getActiveSheet()->setCellValue( 'R12', $end_date );

	$excel->getActiveSheet()->setCellValue( 'D11', $course_period['SHORT_NAME'] );

	$excel->getActiveSheet()->setCellValue( 'D9', $course_period['COURSE_TITLE'] );

	$excel->getActiveSheet()->setCellValue( 'D7', $course_period['SUBJECT_TITLE'] );

	return $excel;
}

function AttendanceExcelSheetWriteStudent( $excel, $student, $i )
{
	$line_i = $i + 16; // A17 & B17 is first student line.

	$excel->getActiveSheet()->setCellValue( 'A' . $line_i, $i );

	$excel->getActiveSheet()->setCellValue( 'B' . $line_i, $student['FULL_NAME'] );

	return $excel;
}

function AttendanceExcelSheetSaveTmp( $excel, $file_name )
{
	$writer = PHPExcel_IOFactory::createWriter( $excel, 'Excel5' );

	$file_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $file_name;

	try
	{
		$writer->save( $file_path );
	}
	catch ( PHPExcel_Writer_Exception $e )
	{
		return false;
	}

	return $file_path;
}


function AttendanceExcelSheetDownload( $excel_sheets )
{
	if ( ! $excel_sheets )
	{
		return false;
	}

	if ( count( $excel_sheets ) === 1 )
	{
		$output = file_get_contents( $excel_sheets[0] );

		// Download file.
		header( "Cache-Control: public" );
		header( "Content-Type: application/vnd.ms-excel" );
		header( "Content-Length: " . strlen( $output ) );
		header( "Content-Disposition: inline; filename=\"" . basename( $excel_sheets[0] ) . "\"\n" );

		echo $output;

		return true;
	}

	$zip_file_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'AttendanceSheets.zip';

	// Make zip with all sheets.
	$zip_ok = AttendanceExcelSheetCreateZip(
		$excel_sheets,
		$zip_file_path
	);

	if ( ! $zip_ok )
	{
		return false;
	}

	// File name.
	$file_name = date( 'Y-m-d_His' ) . '_' . basename(  $zip_file_path );

	// Download file.
	header( "Cache-Control: public" );
	header( "Content-Type: application/zip" );
	header( "Content-Length: " . filesize( $zip_file_path ) );
	header( "Content-Disposition: inline; filename=\"" . $file_name . "\"\n" );

	ob_clean();
	flush();
	readfile( $zip_file_path );exit;

	return true;
}

/**
 * Create zip file
 *
 * @link https://davidwalsh.name/create-zip-php
 *
 * @param  array   $files       [description]
 * @param  string  $destination [description]
 * @param  boolean $overwrite   [description]
 * @return [type]               [description]
 */
function AttendanceExcelSheetCreateZip( $files = [], $destination = '' )
{
	if ( ! class_exists( 'ZipArchive' ) )
	{
		// Error PHP zip extension required to create .zip file.
		$error = [
			dgettext( 'Timetable_Import', 'PHP zip extension is required. Please enable it.' )
		];

		ErrorMessage( $error, 'fatal' );
	}

	//vars
	$valid_files = [];
	//if files were passed in...
	if(is_array($files)) {
		//cycle through each file
		foreach($files as $file) {
			//make sure the file exists
			if(file_exists($file)) {
				$valid_files[] = $file;
			}
		}
	}
	//if we have good files...
	if(count($valid_files)) {
		//create the archive
		$zip = new ZipArchive();
		if($zip->open($destination, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE ) !== true) {
			d($destination);
			return false;
		}
		//add the files
		foreach($valid_files as $file) {
			$zip->addFile($file,basename($file));
		}
		//debug
		//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

		//close the zip -- done!
		$zip->close();

		//check to make sure the file exists
		return file_exists($destination);
	}
	else
	{
		return false;
	}
}
