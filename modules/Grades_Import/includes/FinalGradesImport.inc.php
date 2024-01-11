<?php
/**
 * Final Grades Import
 *  1. Upload CSV or Excel file
 *  2. Associate CSV columns to User Fields
 *  3. Import grades
 *
 * @package Grades Import module
 */


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
		$grades_imported = FinalGradesCSVImport( $_SESSION['GradebookGradesImport.php']['csv_file_path'] );

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

$course_period_id = UserCoursePeriod();


if ( UserCoursePeriod() )
{
	// Get all the MP's associated with the current MP
	$all_mp_ids = explode( "','", trim( GetAllMP( 'PRO', UserMP() ), "'" ) );

	if ( mb_strpos( $_REQUEST['modname'], 'Users/TeacherPrograms.php' ) === 0 )
	{
		$all_mp_ids = explode( "','", trim( GetAllMP( 'FY' ), "'" ) );
	}

	// Get all graded MPs.
	$all_graded_mp_ids = [];

	foreach ( $all_mp_ids as $mp_id )
	{
		if ( ! GetMP( $mp_id, 'DOES_GRADES' ) )
		{
			continue;
		}

		if ( mb_strpos( $_REQUEST['modname'], 'Users/TeacherPrograms.php' ) === 0 )
		{
			// Is admin, can always enter final grades.
			$all_graded_mp_ids[] = $mp_id;
		}

		// Check if Grade Posting open for teacher.
		if ( GetMP( $mp_id, 'POST_START_DATE' ) > DBDate() )
		{
			continue;
		}

		if ( GetMP( $mp_id, 'POST_END_DATE' ) < DBDate() )
		{
			continue;
		}

		$all_graded_mp_ids[] = $mp_id;
	}

	$course_is_graded = DBGetOne( "SELECT GRADE_SCALE_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );
}

if ( empty( $all_graded_mp_ids ) )
{
	$error = [ dgettext( 'Grades_Import', 'No graded marking periods were found.' ) ];

	if ( mb_strpos( $_REQUEST['modname'], 'Users/TeacherPrograms.php' ) !== 0 )
	{
		$error = [ dgettext( 'Grades_Import', 'Marking Period is not currently open for grade posting.' ) ];
	}

	echo ErrorMessage( $error );
}
elseif ( empty( $course_is_graded ) )
{
	$error = [ _( 'You cannot enter grades for this course period.' ) ];

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

	if ( $_REQUEST['modname'] === 'Users/TeacherPrograms.php'
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
				dgettext( 'Grades_Import', 'Import Final Grades' ),
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
		 * Final Grades Fields.
		 */
		echo '<tr><td><h4>' . dgettext( 'Grades_Import', 'Final Grades Fields' ) . '</h4></td></tr>';

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
		 * Final Grades Fields.
		 */
		echo '<tr><td><h4>' . _( 'Final Grades' ) . '</h4></td></tr>';

		echo '<tr><td>';

		echo SelectInput(
			'',
			'letter_or_percent',
			_( 'Grades' ),
			[
				'letter' => _( 'Letter' ),
				'percent' => _( 'Percent' ),
			],
			false
		);

		echo '</td></tr>';

		echo '<tr><td><p><strong>' . _( 'Marking Periods' ) . '</strong></p>';

		foreach ( (array) $all_graded_mp_ids as $mp_id )
		{
			echo _makeSelectInput( 'MARKING_PERIOD_' . $mp_id, $csv_columns, GetMP( $mp_id, 'TITLE' ) ) . '<br />';
		}

		echo '</td></tr>';

		echo '</table>';

		echo '<br /><div class="center">' . SubmitButton(
			dgettext( 'Grades_Import', 'Import Final Grades' ),
			'',
			' class="import-grades-button button-primary"'
		) . '</div></form><br /><br /><br /><br /><br /><br /><br /><br />';
	}
}
