<?php

require_once 'ProgramFunctions/Charts.fnc.php';

$_REQUEST['category_id'] = issetVal( $_REQUEST['category_id'] );

$_REQUEST['total'] = issetVal( $_REQUEST['total'], 'number' );

$_REQUEST['grade_level'] = issetVal( $_REQUEST['grade_level'], '' );

DrawHeader( ProgramTitle() );

$min_date = DBGetOne( "SELECT min(SCHOOL_DATE) AS MIN_DATE
	FROM attendance_calendar
	WHERE SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'" );

if ( ! $min_date )
{
	$min_date = date( 'Y-m' ) . '-01';
}

// Set start date.
$start_date = RequestedDate( 'start', $min_date, 'set' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate(), 'set' );

$chart_types = [ 'bar', 'pie', 'list' ];

// Set Chart Type.
if ( ! isset( $_REQUEST['chart_type'] )
	|| ! in_array( $_REQUEST['chart_type'], $chart_types ) )
{
	$_REQUEST['chart_type'] = 'bar';
}

$chartline = false;

if ( ! empty( $_REQUEST['category_id'] ) )
{
	$count_or_sum_sql = $_REQUEST['total'] === 'number' ? 'COUNT(1)' : 'SUM(AMOUNT)';

	if ( $_REQUEST['grade_level'] )
	{
		$totals_RET = DBGet( "SELECT ssm.GRADE_ID," . $count_or_sum_sql . " AS COUNT
			FROM billing_elements be,billing_student_elements bse
			JOIN student_enrollment ssm ON (ssm.STUDENT_ID=bse.STUDENT_ID
				AND ssm.SYEAR='" . UserSyear() . "'
				AND ('" . $end_date . "'>=ssm.START_DATE
					AND (ssm.END_DATE IS NULL OR '" . $end_date . "'<=ssm.END_DATE ) )
				AND ssm.SCHOOL_ID='" . UserSchool() . "'
				AND ssm.GRADE_ID IS NOT NULL)
			WHERE be.ID=bse.ELEMENT_ID
			AND be.CATEGORY_ID='" . (int) $_REQUEST['category_id'] . "'
			AND be.SYEAR='" . UserSyear() . "'
			AND be.SCHOOL_ID='" . UserSchool() . "'
			AND bse.CREATED_AT BETWEEN '" . $start_date . "' AND '" . $end_date . " 23:59'
			GROUP BY ssm.GRADE_ID
			ORDER BY ssm.GRADE_ID" );
	}
	else
	{
		// Limit charts to 25 elements.
		$limit_sql = $_REQUEST['chart_type'] === 'list' ? ' LIMIT 1000' : ' LIMIT 25';

		$totals_RET = DBGet( "SELECT be.ID,be.TITLE," . $count_or_sum_sql . " AS COUNT
			FROM billing_student_elements bse,billing_elements be
			WHERE be.ID=bse.ELEMENT_ID
			AND be.CATEGORY_ID='" . (int) $_REQUEST['category_id'] . "'
			AND be.SYEAR='" . UserSyear() . "'
			AND be.SCHOOL_ID='" . UserSchool() . "'
			AND bse.CREATED_AT BETWEEN '" . $start_date . "' AND '" . $end_date . " 23:59'
			GROUP BY be.ID
			ORDER BY COUNT DESC,be.REF,be.TITLE" . $limit_sql );
	}

	$chart = [ 'chart_data' => [ 0 => [], 1 => [] ] ];

	foreach ( (array) $totals_RET as $element )
	{
		$title = $_REQUEST['grade_level'] ? strip_tags( GetGrade( $element['GRADE_ID'] ) ) : $element['TITLE'];

		if ( ! $title )
		{
			$title = _( 'N/A' );
		}

		$chart['chart_data'][0][] = $title;

		$chart['chart_data'][1][] = $element['COUNT'];
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo '<form action="' . PreparePHP_SELF( $_REQUEST ) . '" method="GET">';

	$categories_RET = DBGet( "SELECT bc.ID,bc.TITLE
		FROM billing_categories bc
		WHERE EXISTS(SELECT 1 FROM billing_elements be
			WHERE be.SYEAR='" . UserSyear() . "'
			AND be.SCHOOL_ID='" . UserSchool() . "'
			AND be.CATEGORY_ID=bc.ID)
		ORDER BY bc.SORT_ORDER IS NULL,bc.SORT_ORDER" );

	$select_options = [];

	foreach ( (array) $categories_RET as $category )
	{
		$select_options[$category['ID']] = $category['TITLE'];

		if ( ! empty( $_REQUEST['category_id'] )
			&& $category['ID'] === $_REQUEST['category_id'] )
		{
			$category_title = $category['TITLE'];
		}
	}

	$select = SelectInput(
		$_REQUEST['category_id'],
		'category_id',
		'',
		$select_options,
		_( 'Please choose a category' ),
		'autocomplete="off" onchange="ajaxPostForm(this.form,true);"',
		false
	);

	DrawHeader( $select );

	$total_options = [
		'number' => _( 'Number' ),
		'amount' => _( 'Amount' ),
	];

	$total_radio = RadioInput(
		$_REQUEST['total'],
		'total',
		_( 'Total' ),
		$total_options,
		false,
		'autocomplete="off" onchange="ajaxPostForm(this.form,true);"',
		false
	);

	$breakdown_by_grade_level = CheckboxInput(
		$_REQUEST['grade_level'],
		'grade_level',
		dgettext( 'Billing_Elements', 'Breakdown by Grade Level' ),
		'',
		true,
		'Yes',
		'No',
		false,
		'autocomplete="off" onchange="ajaxPostForm(this.form,true);"'
	);

	DrawHeader( $total_radio, $breakdown_by_grade_level );

	DrawHeader(
		_( 'Report Timeframe' ) . ': ' .
			PrepareDate( $start_date, '_start', false ) . ' &nbsp; ' . _( 'to' ) . ' &nbsp; ' .
			PrepareDate( $end_date, '_end', false ) . ' ' .
		SubmitButton( _( 'Go' ) )
	);

	if ( ! empty( $_ROSARIO['SearchTerms'] ) )
	{
		DrawHeader( $_ROSARIO['SearchTerms'] );
	}

	echo '<br />';

	if ( ! empty( $_REQUEST['category_id'] ) )
	{
		if ( $chartline )
		{
			// For Chart Type to bar if Line.
			if ( $_REQUEST['chart_type'] === 'pie' )
			{
				$_REQUEST['chart_type'] = 'bar';
			}

			$tabs = [
				[
					'title' => _( 'Line' ),
					'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'bar' ] ),
				],
				[
					'title' => _( 'List' ),
					'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'list' ] ),
				]
			];
		}
		else
		{
			$tabs = [
				[
					'title' => _( 'Column' ),
					'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'bar' ] ),
				],
				[
					'title' => _( 'Pie' ),
					'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'pie' ] ),
				],
				[
					'title' => _( 'List' ),
					'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'list' ] ),
				]
			];
		}

		$_ROSARIO['selected_tab'] = PreparePHP_SELF( $_REQUEST );

		PopTable( 'header', $tabs );

		if ( $_REQUEST['chart_type'] === 'list' )
		{
			$chart_data = [ '0' => '' ];

			foreach ( (array) $chart['chart_data'][1] as $key => $value )
			{
				$chart_data[] = [ 'TITLE' => $chart['chart_data'][0][ $key ], 'VALUE' => $value ];
			}

			unset( $chart_data[0] );

			$LO_options['responsive'] = false;

			$LO_columns = [
				'TITLE' => ( $_REQUEST['grade_level'] ?
					_( 'Grade Level' ) :
					dgettext( 'Billing_Elements', 'Element' ) ),
				'VALUE' => ( $_REQUEST['total'] === 'number' ?
					dgettext( 'Billing_Elements', 'Number of Elements' ) :
					_( 'Amount' ) ),
			];

			if ( $_REQUEST['grade_level'] )
			{
				ListOutput( $chart_data, $LO_columns, 'Grade Level', 'Grade Levels', [], [], $LO_options );
			}
			else
			{
				ListOutput(
					$chart_data,
					$LO_columns,
					dgettext( 'Billing_Elements', 'Element' ),
					dgettext( 'Billing_Elements', 'Elements' ),
					[],
					[],
					$LO_options
				);
			}
		}
		// Chart.js charts.
		else
		{
			$chart_title = sprintf( _( '%s Breakdown' ), $category_title );

			if ( $_REQUEST['chart_type'] === 'pie' )
			{
				foreach ( (array) $chart['chart_data'][0] as $index => $label )
				{
					if ( ! is_numeric( $chart['chart_data'][1][ $index ] ) )
					{
						continue;
					}

					// Limit label to 30 char max.
					$chart['chart_data'][0][ $index ] = mb_substr( $label, 0, 30 );
				}
			}

			if ( ! function_exists( 'ChartjsChart' ) )
			{
				// @deprecated since 6.0.
				echo jqPlotChart(
					( $chartline ? 'line' : ( $_REQUEST['chart_type'] === 'bar' ? 'column' : $_REQUEST['chart_type'] ) ),
					$chart['chart_data'],
					$chart_title
				);
			}
			else
			{
				echo ChartjsChart(
					$chartline ? 'line' : $_REQUEST['chart_type'],
					$chart['chart_data'],
					$chart_title
				);
			}
		}

		PopTable( 'footer' );
	}

	echo '</form>';
}
