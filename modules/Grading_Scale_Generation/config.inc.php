<?php
/**
 * Plugin configuration interface
 *
 * @package Grading Scale Generation
 */

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['Grading_Scale_Generation']
	|| $_REQUEST['modfunc'] !== 'config' )
{
	$error[] = _( 'You\'re not allowed to use this program!' );

	echo ErrorMessage( $error, 'fatal' );
}

// Note: no need to call ProgramTitle() here!

if ( ! empty( $_REQUEST['generate'] )
	&& DeletePrompt( _( 'Grading Scale' ), dgettext( 'Grading_Scale_Generation', 'Generate' ) ) )
{
	if ( ! empty( $_REQUEST['values'] )
		&& $_REQUEST['values']['GRADE_MIN'] < $_REQUEST['values']['GRADE_MAX'] )
	{
		$grades = _gradingScaleGetGrades(
			$_REQUEST['values']['GRADE_MIN'],
			$_REQUEST['values']['GRADE_MAX'],
			$_REQUEST['values']['GRADE_STEP']
		);

		if ( $grades )
		{
			$main_scale_id = _gradingScaleGetMainID();

			// Delete existing Grades from Main Grading Scale.
			DBQuery( "DELETE FROM report_card_grades
			WHERE GRADE_SCALE_ID='" . (int) $main_scale_id . "';" );

			$done = _gradingScaleGenerate( $grades );
		}

		if ( $done )
		{
			$note[] = _( 'Done.' );

			$note[] = sprintf(
				dgettext( 'Grading_Scale_Generation', 'Setup your new Grading Scale: %s' ),
				'<a href="Modules.php?modname=Grades/ReportCardGrades.php&tab_id=new">' . _( 'Grading Scales' ) . '</a>'
			);
		}
	}

	// Unset generate & redirect URL.
	RedirectURL( 'generate' );
}

if ( empty( $_REQUEST['generate'] )
	&& empty( $_REQUEST['remove'] ) )
{
	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Grading_Scale_Generation&generate=true' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab=plugins&modfunc=config&plugin=Grading_Scale_Generation&generate=true' ) ) . '" method="POST">';

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	$school_title = '';

	// If more than 1 school, add its title to table title.
	if ( SchoolInfo( 'SCHOOLS_NB' ) > 1 )
	{
		$school_title = SchoolInfo( 'SHORT_NAME' );

		if ( ! $school_title )
		{
			// No short name, get full title.
			$school_title = SchoolInfo( 'TITLE' );
		}

		$school_title = '(' . $school_title . ')';
	}

	PopTable(
		'header',
		dgettext( 'Grading_Scale_Generation', 'Grading Scale Generation' ) . ' ' . $school_title
	);

	echo '<table class="width-100p"><tr class="st">';

	echo '<td>' . TextInput(
		'0',
		'values[GRADE_MIN]',
		dgettext( 'Grading_Scale_Generation', 'Minimum Grade' ),
		'required type="number" min="0" max="10"',
		false
	) . '</td>';

	echo '<td>' . TextInput(
		'10',
		'values[GRADE_MAX]',
		dgettext( 'Grading_Scale_Generation', 'Maximum Grade' ),
		'required type="number" min="0" max="100"',
		false
	) . '</td>';

	$step_options = [
		'1' => '1',
		'0.5' => '0.5',
		'0.25' => '0.25',
		'0.1' => '0.1',
		'0.05' => '0.05',
		'0.01' => '0.01',
	];

	echo '<td>' . SelectInput(
		'0.1',
		'values[GRADE_STEP]',
		dgettext( 'Grading_Scale_Generation', 'Step' ),
		$step_options,
		false,
		'required',
		false
	) . '</td>';

	echo '</tr></table>';

	$warning[] = dgettext( 'Grading_Scale_Generation', 'Current Main Grading Scale will be deleted. Any student grades for the current school year will be lost.' );

	echo ErrorMessage( $warning, 'warning' );

	echo '<br /><div class="center">' . SubmitButton( dgettext( 'Grading_Scale_Generation', 'Generate' ) ) . '</div>';

	PopTable( 'footer' );

	echo '</form>';
}


function _gradingScaleGetGrades( $grade_min, $grade_max, $grade_step )
{
	$grade_min = $grade_min < 0 ? 0 : (int) $grade_min;

	$grade_max = $grade_max > 100 ? 100 : (int) $grade_max;

	$grade_step = $grade_step >= 1 ? 1 : (float) $grade_step;

	if ( $grade_step < 0.01 )
	{
		$grade_step = 0.01;
	}

	$steps = $grade_step === 0.01 ? 100 :
		( $grade_step === 0.05 ? 20 :
			( $grade_step === 0.1 ? 10 :
				( $grade_step === 0.25 ? 4 :
					( $grade_step === 0.5 ? 2 : 1 ) ) ) );

	// Generate all grades.
	$grades = [];

	for ( $i = $grade_max; $i >= $grade_min; $i-- )
	{
		if ( $grade_step === 1
			|| $i === $grade_max )
		{
			$grades[] = $i;

			continue;
		}

		for ( $j = $steps - 1; $j >= 0; $j-- )
		{
			$grades[] = $i + ( 1 * $grade_step * $j );
		}
	}

	if ( ROSARIO_DEBUG )
	{
		var_dump( $grades );
	}

	return $grades;
}

function _gradingScaleGenerate( $grades )
{
	if ( empty( $grades ) )
	{
		return false;
	}

	$grades_sql = '';

	$main_scale_id = _gradingScaleGetMainID();

	$sort_order = 1;

	$grade_max = reset( $grades );

	foreach ( $grades as $i => $grade )
	{
		$sql = 'INSERT INTO report_card_grades ';
		$fields = 'SCHOOL_ID,SYEAR,GRADE_SCALE_ID,';
		$values = "'" . UserSchool() . "','" . UserSyear() . "','" . $main_scale_id . "',";

		// Percent.
		$break_off = (float) number_format( ( ( $grade / $grade_max ) * 100 ), 2, '.', '' );

		$fields .= 'TITLE,SORT_ORDER,GPA_VALUE,BREAK_OFF,UNWEIGHTED_GP';
		$values .= "'" . (float) $grade . "','" . $sort_order . "','" . $grade . "','" . $break_off . "',''";

		$sql .= '(' . $fields . ') values(' . $values . ');';

		$grades_sql .= $sql;

		$sort_order++;
	}

	DBQuery( $grades_sql );

	// @since 1.2 Set Max. Grade as "Scale Value" / GP_SCALE.
	DBQuery( "UPDATE report_card_grade_scales
		SET GP_SCALE='" . $grade_max . "'
		WHERE ID='" . (int) $main_scale_id . "'" );

	return true;
}

function _gradingScaleGetMainID()
{
	return DBGetOne( "SELECT ID
		FROM report_card_grade_scales
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER
		LIMIT 1" );
}
