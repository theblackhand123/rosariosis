<?php
/**
 * Timetable Import
 *  1. Upload CSV or Excel file
 *  2. Associate CSV columns to User Fields
 *  3. Import timetable
 *
 * @package Timetable Import module
 */

require_once 'ProgramFunctions/FileUpload.fnc.php';

if ( file_exists( 'modules/Scheduling/includes/Courses.fnc.php' ) )
{
	// @since 4.8.
	require_once 'modules/Scheduling/includes/Courses.fnc.php';
}

require_once 'modules/Timetable_Import/includes/TimetableImport.fnc.php';

DrawHeader( ProgramTitle() ); // Display main header with Module icon and Program title.

// Upload.
if ( $_REQUEST['modfunc'] === 'upload' )
{
	$error = [];

	if ( ! isset( $_SESSION['TimetableImport.php']['csv_file_path'] )
		|| ! $_SESSION['TimetableImport.php']['csv_file_path'] )
	{
		// Save original file name.
		$_SESSION['TimetableImport.php']['original_file_name'] = issetVal( $_FILES['timetable-import-file']['name'] );

		// Upload CSV file.
		$timetable_import_file_path = FileUpload(
			'timetable-import-file',
			sys_get_temp_dir() . DIRECTORY_SEPARATOR, // Temporary directory.
			[ '.csv', '.xls', '.xlsx' ],
			0,
			$error
		);

		if ( empty( $error ) )
		{
			// Convert Excel files to CSV.
			$csv_file_path = ConvertExcelToCSV( $timetable_import_file_path );

			// Open file.
			if ( ( fopen( $csv_file_path, 'r' ) ) === false )
			{
				$error[] = dgettext( 'Timetable_Import', 'Cannot open file.' );
			}
			else
			{
				$_SESSION['TimetableImport.php']['csv_file_path'] = $csv_file_path;
			}
		}
	}

	if ( $error )
	{
		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}
// Import.
elseif ( $_REQUEST['modfunc'] === 'import' )
{
	// Open file.
	if ( ! isset( $_SESSION['TimetableImport.php']['csv_file_path'] )
		|| fopen( $_SESSION['TimetableImport.php']['csv_file_path'], 'r' ) === false )
	{
		$error[] = dgettext( 'Timetable_Import', 'Cannot open file.' );
	}
	else
	{
		// Import timetable.
		$timetable_imported = CSVImport( $_SESSION['TimetableImport.php']['csv_file_path'] );

		$timetable_imported_txt = sprintf(
			dgettext( 'Timetable_Import', '%s Course Periods were imported.' ),
			$timetable_imported
		);

		if ( $timetable_imported )
		{
			$note[] = button( 'check' ) . '&nbsp;' . $timetable_imported_txt;
		}
		else
		{
			$warning[] = $timetable_imported_txt;
		}

		// Remove CSV file.
		unlink( $_SESSION['TimetableImport.php']['csv_file_path'] );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );

	unset( $_SESSION['TimetableImport.php']['csv_file_path'] );
}

// Display error messages.
echo ErrorMessage( $error, 'error' );

// Display warnings.
echo ErrorMessage( $warning, 'warning' );

// Display note.
echo ErrorMessage( $note, 'note' );


if ( ! $_REQUEST['modfunc'] )
{
	/*if ( isset( $_SESSION['TimetableImport.php']['csv_file_path'] ) )
	{
		// Remove CSV file.
		@unlink( $_SESSION['TimetableImport.php']['csv_file_path'] );*/

		unset( $_SESSION['TimetableImport.php']['csv_file_path'] );
	//}

	// Form.
	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=upload' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=upload' ) ) . '" method="POST" enctype="multipart/form-data">';

	if ( AllowEdit( 'School_Setup/DatabaseBackup.php' ) )
	{
		DrawHeader( '<a href="Modules.php?modname=School_Setup/DatabaseBackup.php">' .
			_( 'Database Backup' ) . '</a>' );
	}

	DrawHeader( '<input type="file" name="timetable-import-file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required title="' .
			( function_exists( 'AttrEscape' ) ? AttrEscape( sprintf( _( 'Maximum file size: %01.0fMb' ), FileUploadMaxSize() ) ) : htmlspecialchars( sprintf( _( 'Maximum file size: %01.0fMb' ), FileUploadMaxSize() ), ENT_QUOTES ) ) . '" />
		<span class="loading"></span>
		<br /><span class="legend-red">' . dgettext( 'Timetable_Import', 'Select CSV or Excel file' ) . '</span>' );

	echo '<br /><div class="center">' . SubmitButton( _( 'Submit' ) ) . '</div>';

	echo '</form>';
}
// Uploaded: show import form!
elseif ( $_REQUEST['modfunc'] === 'upload' )
{
	// Get CSV columns.
	$csv_columns = GetCSVColumns( $_SESSION['TimetableImport.php']['csv_file_path'] );

	if ( ! $csv_columns )
	{
		$error = [ 'No columns were found in the uploaded file.' ];

		echo ErrorMessage( $error );
	}
	else
	{
		// Form.
		echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=import' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=import' ) ) . '" method="POST" class="import-timetable-form">';

		$rows_number = file( $_SESSION['TimetableImport.php']['csv_file_path'] );

		$rows_number = count( $rows_number );

		DrawHeader(
			$_SESSION['TimetableImport.php']['original_file_name'] . ': ' .
				sprintf( dgettext( 'Timetable_Import', '%s rows' ), $rows_number ),
			SubmitButton(
				dgettext( 'Timetable_Import', 'Import Timetable' ),
				'',
				' class="import-timetable-button button-primary"'
			)
		);
		?>
		<script>
		$(function(){
			$('.import-timetable-form').submit(function(e){

				e.preventDefault();

				var alertTxt = <?php echo json_encode( dgettext(
						'Timetable_Import',
						'Are you absolutely ready to import timetable? Make sure you have backed up your database!'
					) ); ?>;

				// Alert.
				if ( ! window.confirm( alertTxt ) ) return false;

				var $buttons = $('.import-timetable-button'),
					buttonTxt = $buttons.val(),
					seconds = 5,
					stopButtonHTML = <?php echo json_encode( SubmitButton(
						dgettext( 'Timetable_Import', 'Stop' ),
						'',
						'class="stop-button"'
					) ); ?>;

				$buttons.css('pointer-events', 'none').attr('disabled', true).val( buttonTxt + ' ... ' + seconds );

				var countdown = setInterval( function(){
					if ( seconds == 0 ) {
						clearInterval( countdown );
						$('.import-timetable-form').off('submit').submit();
						return;
					}

					$buttons.val( buttonTxt + ' ... ' + --seconds );
				}, 1000 );

				// Insert stop button.
				$( stopButtonHTML ).click( function(){
					clearInterval( countdown );
					$('.stop-button').remove();
					$buttons.css('pointer-events', '').attr('disabled', false).val( buttonTxt );
					return false;
				}).insertAfter( $buttons );
			});
		});
		</script>
		<?php

		// Import first row? (generally column names).
		DrawHeader( CheckboxInput(
				'',
				'import-first-row',
				dgettext( 'Timetable_Import', 'Import first row' ),
				'',
				true
			),
			'<a href="' . ( function_exists( 'URLEscape' ) ?
				URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=upload' ) :
				_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=upload' ) ) . '">' .
				dgettext( 'Timetable_Import', 'Reset form' ) . '</a>'
		);

		// Delete Courses?.
		DrawHeader(
			CheckboxInput(
				'',
				'delete-courses',
				dgettext( 'Timetable_Import', 'Delete existing Courses and Course Periods' ),
				'',
				true
			)
		);

		echo '<br /><table class="widefat cellspacing-0 center">';

		/**
		 * Timetable Fields.
		 */
		echo '<tr><td><h4>' . dgettext( 'Timetable_Import', 'Timetable Fields' ) . '</h4></td></tr>';

		echo '<tr><td>' .
			_makeSelectInput( 'ACTIVITY_ID', $csv_columns,  dgettext( 'Timetable_Import', 'Activity ID' ) ) .
		'</td></tr>';

		echo '<tr><td>' .
			_makeSelectInput( 'DAY', $csv_columns, _( 'Day' ), 'required' ) .
		'</td></tr>';

		echo '<tr><td>' .
			_makeSelectInput( 'PERIOD_ID', $csv_columns, _( 'Period' ), 'required' ) .
		'</td></tr>';

		echo '<tr><td>' .
			_makeSelectInput(
				'GRADE_LEVELS',
				$csv_columns,
				dgettext( 'Timetable_Import', 'Student Sets' ) . ' / ' . _( 'Grade Levels' ),
				'required'
			) .
		'</td></tr>';

		echo '<tr><td>' .
			_makeSelectInput( 'SUBJECT', $csv_columns, _( 'Subject' ), 'required' ) .
		'</td></tr>';

		echo '<tr><td>' .
			_makeSelectInput( 'TEACHER', $csv_columns, _( 'Teacher' ), 'required' ) .
		'</td></tr>';

		echo '<tr><td>' .
			_makeSelectInput( 'ROOM', $csv_columns, _( 'Room' ) ) .
		'</td></tr>';

		echo '<tr><td>' .
			_makeSelectInput( 'TOTAL_SEATS', $csv_columns, _( 'Seats' ) ) .
		'</td></tr>';

		// @since RosarioSIS 11.1 SQL Use GetFullYearMP() & GetChildrenMP() functions to limit Marking Periods
		$fy_and_children_mp = "'" . GetFullYearMP() . "'";

		if ( GetChildrenMP( 'FY' ) )
		{
			$fy_and_children_mp .= "," . GetChildrenMP( 'FY' );
		}

		$mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,SHORT_NAME," .
			db_case( [ 'MP', "'FY'", "'0'", "'SEM'", "'1'", "'QTR'", "'2'" ] ) . " AS TBL
			FROM school_marking_periods
			WHERE (MP='FY' OR MP='SEM' OR MP='QTR')
			AND MARKING_PERIOD_ID IN(" . $fy_and_children_mp . ")
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY TBL,SORT_ORDER IS NULL,SORT_ORDER" );

		foreach ( (array) $mp_RET as $mp )
		{
			$mp_options[$mp['MARKING_PERIOD_ID']] = $mp['SHORT_NAME'];
		}

		echo '<tr><td>' .
			_makeSelectInput( 'MARKING_PERIOD_ID', $mp_options, _( 'Marking Period' ), 'required', false ) .
		'</td></tr>';

		$cp_inputs = CoursePeriodOptionInputs( [], 'cp_options', true );

		echo '<tr><td>' .
			implode( '</td></tr><tr><td>', $cp_inputs ) .
		'</td></tr>';

		echo '</table>';

		echo '<br /><div class="center">' . SubmitButton(
			dgettext( 'Timetable_Import', 'Import Timetable' ),
			'',
			' class="import-timetable-button button-primary"'
		) . '</div></form><br /><br /><br /><br /><br /><br /><br /><br />';
	}
}
