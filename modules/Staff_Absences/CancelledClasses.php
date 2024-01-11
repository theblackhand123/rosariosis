<?php
/**
 * Cancelled Classes
 *
 * @package Staff Absences module
 */

require_once 'modules/Staff_Absences/includes/common.fnc.php';
require_once 'modules/Staff_Absences/includes/StaffAbsences.fnc.php';
require_once 'modules/Staff_Absences/includes/CancelledClasses.fnc.php';

// @deprecated since 2.0.
require_once 'modules/Staff_Absences/includes/Update.inc.php';

DrawHeader( ProgramTitle() );

// Set start date.
$start_date = RequestedDate( 'start', empty( $_REQUEST['start'] ) ? DBDate() : $_REQUEST['start'] );

// Set end date.
$end_date = RequestedDate( 'end', issetVal( $_REQUEST['end'] ) );

$_REQUEST['expanded_view'] = issetVal( $_REQUEST['expanded_view'], 'false' );

if ( ! $_REQUEST['modfunc'] )
{
	$sql_where = " AND a.START_DATE>='" . $start_date . "'";

	if ( $end_date )
	{
		$sql_where .= " AND a.START_DATE<='" . $end_date . ' 23:59:59' . "'";
	}

	if ( User( 'SCHOOLS' )
		&& trim( User( 'SCHOOLS' ), ',' ) )
	{
		// Restrict Search All Schools to user schools.
		$sql_schools_like = explode( ',', trim( User( 'SCHOOLS' ), ',' ) );

		$sql_schools_like = implode( ",' IN s.SCHOOLS)>0 OR position(',", $sql_schools_like );

		$sql_schools_like = "position('," . $sql_schools_like . ",' IN s.SCHOOLS)>0";

		$sql_where .= " AND (s.SCHOOLS IS NULL OR " . $sql_schools_like . ") ";
	}

	$functions = [
		'FULL_NAME' => 'StaffAbsenceMakeName',
	];

	$LO_columns = [
		'FULL_NAME' => _( 'User' ),
		'STAFF_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
		'DATE' => _( 'Date' ),
		'SHORT_NAME' => _( 'Course Period' ),
		'PERIOD' => _( 'Period' ),
		'LENGTH' => _( 'Minutes' ),
	];

	if ( isset( $_REQUEST['expanded_view'] )
		&& $_REQUEST['expanded_view'] === 'true' )
	{
		// Expanded View: Add Staff Absence Fields.
		$fields_RET = DBGet( "SELECT ID,TITLE,TYPE
			FROM staff_absence_fields
			WHERE TYPE NOT IN('files','textarea')
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

		foreach ( (array) $fields_RET as $field )
		{
			$field_key = 'CUSTOM_' . $field['ID'];

			$functions[ $field_key ] = makeFieldTypeFunction( $field['TYPE'] );

			if ( $functions[ $field_key ] === 'DeCodeds' )
			{
				$functions[ $field_key ] = 'StaffAbsencesDeCodeds';
			}

			$LO_columns[ $field_key ] = ParseMLField( $field['TITLE'] );
		}
	}

	// CANCELLED CLASSES.
	$cancelled_periods_RET = DBGet( "SELECT a.*,a.STAFF_ID AS FULL_NAME,sacp.COURSE_PERIOD_ID,
		cp.SHORT_NAME,cp.CALENDAR_ID,cpsp.DAYS,cpsp.PERIOD_ID,sp.TITLE AS PERIOD,sp.LENGTH,sp.BLOCK
		FROM staff_absences a,staff s,staff_absence_course_periods sacp,
		course_periods cp,course_period_school_periods cpsp,school_periods sp
		WHERE a.SYEAR='" . UserSyear() . "'
		AND s.SYEAR=a.SYEAR
		AND a.STAFF_ID=s.STAFF_ID
		AND a.ID=sacp.STAFF_ABSENCE_ID
		AND sacp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
		AND cpsp.COURSE_PERIOD_ID=sacp.COURSE_PERIOD_ID
		AND sp.PERIOD_ID=cpsp.PERIOD_ID" . $sql_where .
		" ORDER BY a.START_DATE,cp.SHORT_NAME,sp.SORT_ORDER IS NULL,sp.SORT_ORDER",
		$functions
	);

	$cancelled_classes = [];

	$i = 1;

	foreach ( (array) $cancelled_periods_RET as $cancelled_period )
	{
		$cancelled_days = StaffAbsenceCancelledPeriodDays(
			mb_substr( $cancelled_period['START_DATE'], 0, 10 ),
			mb_substr( $cancelled_period['END_DATE'], 0, 10 ),
			$cancelled_period['DAYS'],
			$cancelled_period['BLOCK'],
			$cancelled_period['CALENDAR_ID']
		);

		foreach ( (array) $cancelled_days as $cancelled_day )
		{
			$cancelled_classes[ $i ] = $cancelled_period;

			if ( ! empty( $_REQUEST['LO_save'] )
				|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				$cancelled_classes[ $i ]['DATE'] = $cancelled_day;
			}
			else
			{
				$cancelled_classes[ $i ]['DATE'] = ProperDate( $cancelled_day );
			}

			$i++;
		}
	}

	if ( User( 'PROFILE' ) === 'teacher' )
	{
		$_ROSARIO['allow_edit'] = true;
	}

	$url = 'Modules.php?modname=' . $_REQUEST['modname'] . '&expanded_view=' . $_REQUEST['expanded_view'];

	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( $url ) :
		_myURLEncode( $url ) ) . '" method="GET">';

	if ( ! isset( $_REQUEST['expanded_view'] )
		|| $_REQUEST['expanded_view'] !== 'true' )
	{
		$expanded_view_header = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'expanded_view' => 'true' ] ) . '">' .
		_( 'Expanded View' ) . '</a>';
	}
	else
	{
		$expanded_view_header = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'expanded_view' => 'false' ] ) . '">' .
		_( 'Original View' ) . '</a>';
	}

	DrawHeader( $expanded_view_header );

	DrawHeader(
		_( 'From' ) . ' ' . DateInput( $start_date, 'start', '', false, false ) . ' - ' .
		_( 'To' ) . ' ' . DateInput( $end_date, 'end', '', false, true ) .
		Buttons( _( 'Go' ) )
	);

	echo '</form>';

	if ( User( 'PROFILE' ) === 'teacher' )
	{
		$_ROSARIO['allow_edit'] = false;
	}

	ListOutput(
		$cancelled_classes,
		$LO_columns,
		dgettext( 'Staff_Absences', 'Cancelled Class' ),
		dgettext( 'Staff_Absences', 'Cancelled Classes' )
	);
}
