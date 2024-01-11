<?php
/**
 * Gradebook Grades Import
 *  1. Upload CSV or Excel file
 *  2. Associate CSV columns to User Fields
 *  3. Import grades
 *
 * @package Grades Import module
 */

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'modules/Grades_Import/includes/GradebookGradesImport.fnc.php';

if ( ! empty( $_REQUEST['period'] )
	&& function_exists( 'SetUserCoursePeriod' ) )
{
	// @since RosarioSIS 10.9 Set current User Course Period before Secondary Teacher logic.
	SetUserCoursePeriod( $_REQUEST['period'] );
}

if ( ! empty( $_SESSION['is_secondary_teacher'] ) )
{
	// @since 6.9 Add Secondary Teacher: set User to main teacher.
	UserImpersonateTeacher();
}

if ( User( 'PROFILE' ) === 'teacher' )
{
	$_ROSARIO['allow_edit'] = true;
}

if ( empty( $_REQUEST['tab'] ) )
{
	$_REQUEST['tab'] = 'gradebook-grades';
}

$header_title = ProgramTitle();

if ( mb_strpos( $_REQUEST['modname'], 'Users/TeacherPrograms.php' ) !== 0
	|| $_REQUEST['tab'] !== 'final-grades' )
{
	// Do not display UserMP() if admin and importing Final Grades.
	$header_title .= ' - ' . GetMP( UserMP() );
}

DrawHeader( $header_title );


$gradebook_grades_link = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] ) . '">' .
	( $_REQUEST['tab'] !== 'final-grades' ?
	'<b>' . _( 'Gradebook Grades' ) . '</b>' : _( 'Gradebook Grades' ) ) . '</a>';

$final_grades_link = ' | <a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=final-grades' ) . '">' .
	( $_REQUEST['tab'] === 'final-grades' ?
	'<b>' . _( 'Final Grades' ) . '</b>' : _( 'Final Grades' ) ) . '</a>';

DrawHeader( $gradebook_grades_link . $final_grades_link );

// Upload.
if ( $_REQUEST['modfunc'] === 'upload' )
{
	$error = [];

	if ( ! isset( $_SESSION['GradebookGradesImport.php']['csv_file_path'] )
		|| ! $_SESSION['GradebookGradesImport.php']['csv_file_path'] )
	{
		// Save original file name.
		$_SESSION['GradebookGradesImport.php']['original_file_name'] = issetVal( $_FILES['grades-import-file']['name'] );

		// Upload CSV file.
		$grades_import_file_path = FileUpload(
			'grades-import-file',
			sys_get_temp_dir() . DIRECTORY_SEPARATOR, // Temporary directory.
			[ '.csv', '.xls', '.xlsx' ],
			0,
			$error
		);

		if ( empty( $error ) )
		{
			// Convert Excel files to CSV.
			$csv_file_path = ConvertExcelToCSV( $grades_import_file_path );

			// Open file.
			if ( ( fopen( $csv_file_path, 'r' ) ) === false )
			{
				$error[] = dgettext( 'Grades_Import', 'Cannot open file.' );
			}
			else
			{
				$_SESSION['GradebookGradesImport.php']['csv_file_path'] = $csv_file_path;
			}
		}
	}

	if ( $error )
	{
		// @since 3.3.
		RedirectURL( 'modfunc' );
	}
}

if ( $_REQUEST['tab'] === 'final-grades' ) :

	require_once 'modules/Grades_Import/includes/FinalGradesImport.inc.php';

else :

// Import.
if ( $_REQUEST['modfunc'] === 'import' )
{
	// Open file.
	if ( ! isset( $_SESSION['GradebookGradesImport.php']['csv_file_path'] )
		|| fopen( $_SESSION['GradebookGradesImport.php']['csv_file_path'], 'r' ) === false )
	{
		$error[] = dgettext( 'Grades_Import', 'Cannot open file.' );
	}
	else
	{
		// Import grades.
		$grades_imported = GradebookGradesCSVImport( $_SESSION['GradebookGradesImport.php']['csv_file_path'] );

		$grades_imported_txt = sprintf(
			dgettext( 'Grades_Import', '%s grades were imported.' ),
			$grades_imported
		);

		if ( $grades_imported )
		{
			$note[] = button( 'check' ) . '&nbsp;' . $grades_imported_txt;
		}
		else
		{
			$warning[] = $grades_imported_txt;
		}

		// Remove CSV file.
		unlink( $_SESSION['GradebookGradesImport.php']['csv_file_path'] );
	}

	// @since 3.3.
	RedirectURL( 'modfunc' );

	unset( $_SESSION['GradebookGradesImport.php']['csv_file_path'] );
}

// Display error messages.
echo ErrorMessage( $error, 'error' );

// Display warnings.
echo ErrorMessage( $warning, 'warning' );

// Display note.
echo ErrorMessage( $note, 'note' );

if ( UserCoursePeriod() )
{
	// Get Assignments for current Course Period and Quarter.
	$assignments_RET = DBGet( "SELECT a.ASSIGNMENT_ID,a.TITLE,a.POINTS,at.TITLE AS TYPE_TITLE
		FROM gradebook_assignments a,gradebook_assignment_types at
		WHERE a.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (a.COURSE_ID=(SELECT COURSE_ID FROM course_periods WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "') OR a.COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
		AND a.MARKING_PERIOD_ID='" . UserMP() . "'
		AND a.ASSIGNMENT_TYPE_ID=at.ASSIGNMENT_TYPE_ID
		ORDER BY " . DBEscapeIdentifier( Preferences( 'ASSIGNMENT_SORTING', 'Gradebook' ) ) . " DESC",
		[ 'TYPE_TITLE' => '_makeTitle', 'TITLE' => '_makeTitle' ],
		[ 'TYPE_TITLE' ]
	);
}

if ( empty( $assignments_RET ) )
{
	$error = [ dgettext( 'Grades_Import', 'No Assignments were found for current Course Period and Quarter.' ) ];

	echo ErrorMessage( $error );
}
elseif ( ! $_REQUEST['modfunc'] )
{
	/*if ( isset( $_SESSION['GradebookGradesImport.php']['csv_file_path'] ) )
	{
		// Remove CSV file.
		@unlink( $_SESSION['GradebookGradesImport.php']['csv_file_path'] );*/

		unset( $_SESSION['GradebookGradesImport.php']['csv_file_path'] );
	//}

	/**
	 * Adding `'&period=' . UserCoursePeriod()` to the Teacher form URL will prevent the following issue:
	 * If form is displayed for CP A, then Teacher opens a new browser tab and switches to CP B
	 * Then teacher submits the form, data would be saved for CP B...
	 *
	 * Must be used in combination with
	 * `if ( ! empty( $_REQUEST['period'] ) ) SetUserCoursePeriod( $_REQUEST['period'] );`
	 */
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=upload&period=' . UserCoursePeriod() . '&tab=' . $_REQUEST['tab'] ) . '" method="POST" enctype="multipart/form-data">';

	if ( User( 'PROFILE' ) === 'admin'
		&& AllowEdit( 'School_Setup/DatabaseBackup.php' ) )
	{
		DrawHeader( '<a href="Modules.php?modname=School_Setup/DatabaseBackup.php">' .
			_( 'Database Backup' ) . '</a>' );
	}

	DrawHeader( '<input type="file" name="grades-import-file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required title="' .
			( function_exists( 'AttrEscape' ) ? AttrEscape( sprintf( _( 'Maximum file size: %01.0fMb' ), FileUploadMaxSize() ) ) : htmlspecialchars( sprintf( _( 'Maximum file size: %01.0fMb' ), FileUploadMaxSize() ), ENT_QUOTES ) ) . '" />
		<span class="loading"></span>
		<br /><span class="legend-red">' . dgettext( 'Grades_Import', 'Select CSV or Excel file' ) . '</span>' );

	echo '<br /><div class="center">' . SubmitButton( _( 'Submit' ) ) . '</div>';

	echo '</form>';
}
// Uploaded: show import form!
elseif ( $_REQUEST['modfunc'] === 'upload' )
{
	// Get CSV columns.
	$csv_columns = GetCSVColumns( $_SESSION['GradebookGradesImport.php']['csv_file_path'] );

	if ( ! $csv_columns )
	{
		$error = [ 'No columns were found in the uploaded file.' ];

		echo ErrorMessage( $error );
	}
	else
	{
		/**
		 * Adding `'&period=' . UserCoursePeriod()` to the Teacher form URL will prevent the following issue:
		 * If form is displayed for CP A, then Teacher opens a new browser tab and switches to CP B
		 * Then teacher submits the form, data would be saved for CP B...
		 *
		 * Must be used in combination with
		 * `if ( ! empty( $_REQUEST['period'] ) ) SetUserCoursePeriod( $_REQUEST['period'] );`
		 */
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=import&period=' . UserCoursePeriod() . '&tab=' . $_REQUEST['tab'] ) . '" method="POST" class="import-grades-form">';

		$rows_number = file( $_SESSION['GradebookGradesImport.php']['csv_file_path'] );

		$rows_number = count( $rows_number );

		DrawHeader(
			$_SESSION['GradebookGradesImport.php']['original_file_name'] . ': ' .
				sprintf( dgettext( 'Grades_Import', '%s rows' ), $rows_number ),
			SubmitButton(
				dgettext( 'Grades_Import', 'Import Gradebook Grades' ),
				'',
				' class="import-grades-button button-primary"'
			)
		);
		?>
		<script>
		$(function(){
			$('.import-grades-form').submit(function(e){

				e.preventDefault();

				var alertTxt = <?php echo json_encode( dgettext(
						'Grades_Import',
						'Are you absolutely ready to import grades?'
					) ); ?>;

				// Alert.
				if ( ! window.confirm( alertTxt ) ) return false;

				var $buttons = $('.import-grades-button'),
					buttonTxt = $buttons.val(),
					seconds = 5,
					stopButtonHTML = <?php echo json_encode( SubmitButton(
						dgettext( 'Grades_Import', 'Stop' ),
						'',
						'class="stop-button"'
					) ); ?>;

				$buttons.css('pointer-events', 'none').attr('disabled', true).val( buttonTxt + ' ... ' + seconds );

				var countdown = setInterval( function(){
					if ( seconds == 0 ) {
						clearInterval( countdown );
						$('.import-grades-form').off('submit').submit();
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
				dgettext( 'Grades_Import', 'Import first row' ),
				'',
				true
			),
			'<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=upload&tab=' . $_REQUEST['tab'] ) . '">' .
				dgettext( 'Grades_Import', 'Reset form' ) . '</a>'
		);

		echo '<br /><table class="widefat cellspacing-0 center">';

		/**
		 * Gradebook Grades Fields.
		 */
		echo '<tr><td><h4>' . dgettext( 'Grades_Import', 'Gradebook Grades Fields' ) . '</h4></td></tr>';

		// Identify Student by ID or Username or Name.
		?>
		<script>
			var gradesImportIdentifyStudent = function( withId ) {
				var withIds = ['STUDENT_ID', 'USERNAME', 'NAME'];

				for ( max = withIds.length, i = 0; i < max; i++ ) {
					if ( withIds[ i ] === withId ) {
						$( '#' + withIds[ i ] ).show().css('position', 'relative').css('top', 'auto');

						// Enable select & update chosen.
						$( '#' + withIds[ i ] ).find('select').prop('disabled', false).trigger("chosen:updated");

						continue;
					}

					$( '#' + withIds[ i ] ).hide();

					// Disable select & update chosen.
					$( '#' + withIds[ i ] ).find('select').prop('disabled', true).trigger("chosen:updated");
				}
			};
		</script>
		<?php
		$student_identify_options = [
			'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
			'USERNAME' => _( 'Username' ),
			'NAME' => _( 'Name' ),
		];

		echo '<tr><td>' . SelectInput(
			'STUDENT_ID',
			'student_identify',
			dgettext( 'Grades_Import', 'Identify Student' ),
			$student_identify_options,
			false,
			'autocomplete="off" onchange="gradesImportIdentifyStudent(this.value);"',
			false
		) . '</td></tr>';

		echo '<tr id="STUDENT_ID"><td>' .
			_makeSelectInput( 'STUDENT_ID', $csv_columns, sprintf( _( '%s ID' ), Config( 'NAME' ) ), 'required' ) .
		'</td></tr>';

		echo '<tr id="USERNAME" style="position: absolute; top: -1000px"><td>' .
			_makeSelectInput( 'USERNAME', $csv_columns, _( 'Username' ), 'required disabled' ) .
		'</td></tr>';

		echo '<tbody id="NAME" style="position: absolute; top: -1000px"><tr><td>' .
			_makeSelectInput( 'FIRST_NAME', $csv_columns, _( 'First Name' ), 'required disabled' ) .
		'</td></tr>';

		echo '<tr><td>' .
			_makeSelectInput( 'LAST_NAME', $csv_columns, _( 'Last Name' ), 'required disabled' ) .
		'</td></tr></tbody>';

		echo '<tr><td>';

		echo CheckboxInput(
			'',
			'include_inactive',
			_( 'Include Inactive Students' ),
			'',
			true
		);

		echo '</td></tr>';

		/**
		 * Assignments Fields.
		 */
		echo '<tr><td><h4>' . _( 'Assignments' ) . '</h4></td></tr>';

		foreach ( (array) $assignments_RET as $assignment_type => $assignments )
		{
			echo '<tr><td><p><strong>' . $assignments[1]['TYPE_TITLE'] . '</strong></p>';

			foreach ( (array) $assignments as $assignment )
			{
				echo _makeSelectInput( 'ASSIGNMENT_' . $assignment['ASSIGNMENT_ID'], $csv_columns, $assignment['TITLE'], '' ) . '<br />';
			}

			echo '</td></tr>';
		}

		echo '</table>';

		echo '<br /><div class="center">' . SubmitButton(
			dgettext( 'Grades_Import', 'Import Gradebook Grades' ),
			'',
			' class="import-grades-button button-primary"'
		) . '</div></form><br /><br /><br /><br /><br /><br /><br /><br />';
	}
}

/**
 * Make Assignment Title
 * Truncate Assignment title to 36 chars only if has words > 36 chars
 *
 * Local function.
 * GetStuList() DBGet() callback.
 *
 * @since 10.5.2
 *
 * @param  string $value  Title value.
 * @param  string $column Column. Defaults to 'TITLE'.
 *
 * @return string         Assignment title truncated to 36 chars.
 */
function _makeTitle( $value, $column = 'TITLE' )
{
	// Split on spaces.
	$title_words = explode( ' ', $value );

	$truncate = false;

	foreach ( $title_words as $title_word )
	{
		if ( mb_strlen( $title_word ) > 36 )
		{
			$truncate = true;

			break;
		}
	}

	$title = ! $truncate ?
		$value :
		'<span title="' . AttrEscape( $value ) . '">' . mb_substr( $value, 0, 33 ) . '...</span>';

	return $title;
}
endif;
