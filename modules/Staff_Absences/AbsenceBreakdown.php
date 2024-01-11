<?php

require_once 'ProgramFunctions/Charts.fnc.php';

$_REQUEST['field_id'] = issetVal( $_REQUEST['field_id'] );

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

$chart_types = [ 'bar', 'list' ];

// Set Chart Type.
if ( ! isset( $_REQUEST['chart_type'] )
	|| ! in_array( $_REQUEST['chart_type'], $chart_types ) )
{
	$_REQUEST['chart_type'] = 'bar';
}

if ( ! empty( $_REQUEST['field_id'] ) )
{
	$sql_where = "a.STAFF_ID=s.STAFF_ID
		AND a.SYEAR='" . UserSyear() . "'
		AND a.SYEAR=s.SYEAR
		AND a.START_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";

	$sql_where_schools = '';

	if ( User( 'SCHOOLS' )
		&& trim( User( 'SCHOOLS' ), ',' ) )
	{
		// Restrict Search All Schools to user schools.
		$sql_schools_like = explode( ',', trim( User( 'SCHOOLS' ), ',' ) );

		$sql_schools_like = implode( ",' IN s.SCHOOLS)>0 OR position(',", $sql_schools_like );

		$sql_schools_like = "position('," . $sql_schools_like . ",' IN s.SCHOOLS)>0";

		$sql_where_schools = " AND (s.SCHOOLS IS NULL OR " . $sql_schools_like . ") ";

		$sql_where .= $sql_where_schools;
	}

	if ( $_REQUEST['field_id'] === '-1' )
	{
		// Breakdown by Staff.
		$field_RET = DBGet( "SELECT '" . _( 'Staff' ) . "' AS TITLE,'' AS SELECT_OPTIONS,'staff' AS TYPE" );

		// Limit to 15 Staff max?
		$staff_options_RET = DBGet( "SELECT DISTINCT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,
			s.STAFF_ID
			FROM staff s,staff_absences a
			WHERE s.SYEAR='" . UserSyear() . "'
			AND s.SYEAR=a.SYEAR
			AND s.STAFF_ID=a.STAFF_ID
			AND a.START_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'" . $sql_where_schools );

		$field_RET[1]['SELECT_OPTIONS'] = [];

		foreach ( (array) $staff_options_RET as $staff_option )
		{
			$field_RET[1]['SELECT_OPTIONS'][ $staff_option['STAFF_ID'] ] = $staff_option['FULL_NAME'];
		}
	}
	else
	{
		$field_RET = DBGet( "SELECT f.TITLE,f.SELECT_OPTIONS,f.TYPE
			FROM staff_absence_fields f
			WHERE f.ID='" . (int) $_REQUEST['field_id'] . "'" );

		$field_RET[1]['SELECT_OPTIONS'] = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", $field_RET[1]['SELECT_OPTIONS'] ) );
	}

	$months = [
		'1' => mb_substr( _( 'January' ), 0, 4 ),
		'2' => mb_substr( _( 'February' ), 0, 4 ),
		'3' => mb_substr( _( 'March' ), 0, 4 ),
		'4' => mb_substr( _( 'April' ), 0, 4 ),
		'5' => mb_substr( _( 'May' ), 0, 4 ),
		'6' => mb_substr( _( 'June' ), 0, 4 ),
		'7' => mb_substr( _( 'July' ), 0, 4 ),
		'8' => mb_substr( _( 'August' ), 0, 4 ),
		'9' => mb_substr( _( 'September' ), 0, 4 ),
		'10' => mb_substr( _( 'October' ), 0, 4 ),
		'11' => mb_substr( _( 'November' ), 0, 4 ),
		'12' => mb_substr( _( 'December' ), 0, 4 ),
	];

	$start = $_REQUEST['month_start'] * 1;

	$end = ( $_REQUEST['month_end'] * 1 ) + 12 *
		( $_REQUEST['year_end'] - $_REQUEST['year_start'] );

	$sql_count = "SUM(ROUND(CAST((EXTRACT(EPOCH FROM (SELECT END_DATE - START_DATE
		FROM STAFF_ABSENCES
		WHERE ID=a.ID)) / 86400) AS DECIMAL), 1))";

	if ( $DatabaseType === 'mysql' )
	{
		// @since RosarioSIS 9.3 Add MySQL support
		$sql_count = "SUM(ROUND((TIMESTAMPDIFF(SECOND, (SELECT START_DATE
			FROM staff_absences
			WHERE ID=a.ID), (SELECT END_DATE
			FROM staff_absences
			WHERE ID=a.ID)) / 86400), 1))";
	}

	if ( $field_RET[1]['TYPE'] === 'staff' )
	{
		/**
		 * SQL use extract() instead of to_char() for MySQL compatibility
		 *
		 * @since RosarioSIS 9.2.1
		 */
		$totals_RET = DBGet( "SELECT a.STAFF_ID AS TITLE,
			" . $sql_count . " AS COUNT,
			extract(MONTH from a.START_DATE) AS TIMEFRAME
			FROM staff s,staff_absences a
			WHERE " . $sql_where . "
			GROUP BY a.STAFF_ID,TIMEFRAME",
			[],
			[ 'TITLE', 'TIMEFRAME' ]
		);

		$chart['chart_data'][0][0] = '';

		foreach ( (array) $field_RET[1]['SELECT_OPTIONS'] as $option )
		{
			$chart['chart_data'][0][] = $option;
		}

		$index = 0;

		for ( $i = $start; $i <= $end; $i++ )
		{
			$index++;

			// Bugfix data shown in the wrong month.
			$tf = ( $i%12 == 0 ? 12 : $i%12 );

			$chart['chart_data'][ $index ][0] = $months[ (int) $tf ];

			foreach ( (array) $field_RET[1]['SELECT_OPTIONS'] as $staff_id => $option )
			{
				$chart['chart_data'][ $index ][] = issetVal( $totals_RET[ $staff_id ][ $tf ][1]['COUNT'], 0 );
			}
		}
	}
	if ( in_array( $field_RET[1]['TYPE'], [ 'select', 'autos', 'exports' ] ) )
	{
		// Autos & edits pull-down fields.
		if ( $field_RET[1]['TYPE'] === 'autos' )
		{
			// Add values found in current year.
			$options_RET = DBGet( "SELECT DISTINCT a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . ",upper(a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . ") AS SORT_KEY
				FROM staff_absences a
				WHERE a.SYEAR='" . UserSyear() . "'
				AND a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . " IS NOT NULL
				AND a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . " != ''
				ORDER BY SORT_KEY" );

			foreach ( (array) $options_RET as $option )
			{
				if ( ! $field_RET[1]['SELECT_OPTIONS']
					|| ! in_array( $option['CUSTOM_' . intval( $_REQUEST['field_id'] )], $field_RET[1]['SELECT_OPTIONS'] ) )
				{
					$field_RET[1]['SELECT_OPTIONS'][] = $option['CUSTOM_' . intval( $_REQUEST['field_id'] )];
				}
			}
		}

		/**
		 * SQL use extract() instead of to_char() for MySQL compatibility
		 *
		 * @since RosarioSIS 9.2.1
		 */
		$totals_RET = DBGet( "SELECT a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . " AS TITLE,
			" . $sql_count . " AS COUNT,
			extract(MONTH from a.START_DATE) AS TIMEFRAME
			FROM staff s,staff_absences a
			WHERE " . $sql_where . "
			GROUP BY a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . ",TIMEFRAME",
			[],
			[ 'TITLE', 'TIMEFRAME' ]
		);

		$chart['chart_data'][0][0] = '';

		foreach ( (array) $field_RET[1]['SELECT_OPTIONS'] as $option )
		{
			$chart['chart_data'][0][] = $option;
		}

		$index = 0;

		for ( $i = $start; $i <= $end; $i++ )
		{
			$index++;

			// Bugfix data shown in the wrong month.
			$tf = ( $i%12 == 0 ? 12 : $i%12 );

			$chart['chart_data'][ $index ][0] = $months[ (int) $tf ];

			foreach ( (array) $field_RET[1]['SELECT_OPTIONS'] as $option )
			{
				$chart['chart_data'][ $index ][] = issetVal( $totals_RET[ $option ][ $tf ][1]['COUNT'], 0 );
			}
		}
	}
	elseif ( $field_RET[1]['TYPE'] === 'radio' )
	{
		/**
		 * SQL use extract() instead of to_char() for MySQL compatibility
		 *
		 * @since RosarioSIS 9.2.1
		 */
		$totals_RET = DBGet( "SELECT COALESCE(a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . ",'N') AS TITLE,
			" . $sql_count . " AS COUNT,
			extract(MONTH from a.START_DATE) AS TIMEFRAME
			FROM staff s,staff_absences a
			WHERE " . $sql_where . "
			GROUP BY a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . ",TIMEFRAME",
			[],
			[ 'TITLE', 'TIMEFRAME' ]
		);

		$chart['chart_data'][0][0] = '';

		$chart['chart_data'][0][] = _( 'Yes' );
		$chart['chart_data'][0][] = _( 'No' );

		$index = 0;

		for ( $i = $start; $i <= $end; $i++ )
		{
			$index++;

			// Bugfix data shown in the wrong month.
			$tf = ( $i%12 == 0 ? 12 : $i%12 );

			$chart['chart_data'][ $index ][0] = $months[ (int) $tf ];

			$chart['chart_data'][ $index ][] = issetVal( $totals_RET['Y'][ $tf ][1]['COUNT'], 0 );

			$chart['chart_data'][ $index ][] = issetVal( $totals_RET['N'][ $tf ][1]['COUNT'], 0 );
		}
	}
	elseif ( $field_RET[1]['TYPE'] === 'multiple' )
	{
		/**
		 * SQL use extract() instead of to_char() for MySQL compatibility
		 *
		 * @since RosarioSIS 9.2.1
		 */
		$totals_RET = DBGet( "SELECT COALESCE(a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . ",'N') AS TITLE,
			" . $sql_count . " AS COUNT,
			extract(MONTH from a.START_DATE) AS TIMEFRAME
			FROM staff s,staff_absences a
			WHERE " . $sql_where . "
			GROUP BY a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . ",TIMEFRAME" );

		$chart['chart_data'][0][0] = '';

		foreach ( (array) $field_RET[1]['SELECT_OPTIONS'] as $option )
		{
			$chart['chart_data'][0][] = $option;
		}

		foreach ( (array) $totals_RET as $total )
		{
			$total['TITLE'] = explode( "||", trim( $total['TITLE'], '|' ) );

			foreach ( (array) $total['TITLE'] as $option )
			{
				if ( ! isset( $options_count[$total['TIMEFRAME']][ $option ] ) )
				{
					$options_count[$total['TIMEFRAME']][ $option ] = 0;
				}

				$options_count[$total['TIMEFRAME']][ $option ] += $total['COUNT'];
			}
		}

		$index = 0;

		for ( $i = $start; $i <= $end; $i++ )
		{
			$index++;

			// Bugfix data shown in the wrong month.
			$tf = ( $i%12 == 0 ? 12 : $i%12 );

			$chart['chart_data'][ $index ][0] = $months[ (int) $tf ];

			foreach ( (array) $field_RET[1]['SELECT_OPTIONS'] as $option )
			{
				$chart['chart_data'][ $index ][] = issetVal( $options_count[ $tf ][ $option ], 0 );
			}
		}
	}
	elseif ( $field_RET[1]['TYPE'] === 'numeric' )
	{
		/**
		 * SQL use extract() instead of to_char() for MySQL compatibility
		 *
		 * @since RosarioSIS 9.2.1
		 */
		$totals_RET = DBGet( "SELECT a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . " AS TITLE,
			" . $sql_count . " AS COUNT,
			extract(MONTH from a.START_DATE) AS TIMEFRAME
			FROM staff s,staff_absences a
			WHERE " . $sql_where . "
			AND a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . " IS NOT NULL
			GROUP BY a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . ",TIMEFRAME",
			[],
			[ 'TITLE', 'TIMEFRAME' ]
		);

		// Limit to 15 max?
		$numeric_options_RET = DBGet( "SELECT DISTINCT a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . " AS OPTION
			FROM staff s,staff_absences a
			WHERE s.SYEAR='" . UserSyear() . "'
			AND s.SYEAR=a.SYEAR
			AND s.STAFF_ID=a.STAFF_ID
			AND a.START_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
			AND a.CUSTOM_" . intval( $_REQUEST['field_id'] ) . " IS NOT NULL" . $sql_where_schools .
			" ORDER BY OPTION" );

		$field_RET[1]['SELECT_OPTIONS'] = [];

		foreach ( (array) $numeric_options_RET as $numeric_option )
		{
			$field_RET[1]['SELECT_OPTIONS'][] = $numeric_option['OPTION'];
		}

		$chart['chart_data'][0][0] = '';

		foreach ( (array) $field_RET[1]['SELECT_OPTIONS'] as $option )
		{
			$chart['chart_data'][0][] = $option;
		}

		$index = 0;

		for ( $i = $start; $i <= $end; $i++ )
		{
			$index++;

			// Bugfix data shown in the wrong month.
			$tf = ( $i%12 == 0 ? 12 : $i%12 );

			$chart['chart_data'][ $index ][0] = $months[ (int) $tf ];

			foreach ( (array) $field_RET[1]['SELECT_OPTIONS'] as $option )
			{
				$chart['chart_data'][ $index ][] = issetVal( $totals_RET[ $option ][ $tf ][1]['COUNT'], 0 );
			}
		}

		ksort( $chart['chart_data'] );
	}

	// Chart.js charts.
	if ( $_REQUEST['chart_type'] !== 'list' )
	{
		$datacolumns = 0;
		$ticks = [];

		foreach ( (array) $chart['chart_data'] as $chart_data )
		{
			// Ticks
			if ( $datacolumns++ == 0 )
			{
				$jump = true;

				foreach ( $chart_data as $tick )
				{
					if ( $jump )
					{
						$jump = false;
					}
					else
					{
						$ticks[] = $tick;
					}
				}
			}
			else
			{
				$series = true;

				foreach ( (array) $chart_data as $i => $data )
				{
					if ( $series )
					{
						$series = false;

						$series_label = $data;

						// Set series label + ticks
						$chart_data_series[ $series_label ][0] = $ticks;
					}
					else
					{
						$chart_data_series[ $series_label ][1][] = $data;
					}
				}
			}
		}
	}
}


if ( ! $_REQUEST['modfunc'] )
{
	echo '<form action="' . PreparePHP_SELF( $_REQUEST ) . '" method="GET">';

	$fields_RET = DBGet( "SELECT f.ID,f.TITLE,f.SELECT_OPTIONS
		FROM staff_absence_fields f
		WHERE f.TYPE NOT IN ('textarea','text','date','files')
		ORDER BY f.SORT_ORDER IS NULL,f.SORT_ORDER" );

	$select_options = [];

	$select_options['-1'] = _( 'Staff' );

	foreach ( (array) $fields_RET as $field )
	{
		$select_options[$field['ID']] = ParseMLField( $field['TITLE'] );
	}

	$select = SelectInput(
		$_REQUEST['field_id'],
		'field_id',
		'<span class="a11y-hidden">' . _( 'Field' ) . '</span>',
		$select_options,
		dgettext( 'Staff_Absences', 'Please choose a field' ),
		'onchange="ajaxPostForm(this.form,true);"',
		false
	);

	DrawHeader( $select );

	DrawHeader(
		_( 'Report Timeframe' ) . ': ' .
			PrepareDate( $start_date, '_start', false ) . ' &nbsp; ' . _( 'to' ) . ' &nbsp; ' .
			PrepareDate( $end_date, '_end', false ) . ' ' .
			SubmitButton( _( 'Go' ) )
	);

	echo '<br />';

	if ( ! empty( $_REQUEST['field_id'] ) )
	{
		$tabs = [
			[
				'title' => _( 'Column' ),
				'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'bar' ] ),
			],
			[
				'title' => _( 'List' ),
				'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'list' ] ),
			]
		];

		$_ROSARIO['selected_tab'] = PreparePHP_SELF( $_REQUEST );

		PopTable( 'header', $tabs );

		if ( $_REQUEST['chart_type'] === 'list' )
		{
			// IGNORE THE 'Series' RECORD.
			$LO_columns = [ 'TITLE' => _( 'Option' ) ];

			foreach ( (array) $chart['chart_data'] as $timeframe => $values )
			{
				if ( $timeframe != 0 )
				{
					$LO_columns[ $timeframe ] = $values[0];

					unset( $values[0] );

					foreach ( (array) $values as $key => $value )
					{
						$chart_data[ $key ][ $timeframe ] = $value;
					}
				}
				else
				{
					unset( $values[0] );

					foreach ( (array) $values as $key => $value )
					{
						$chart_data[ $key ]['TITLE'] = $value;
					}
				}
			}

			unset( $chart_data[0] );

			$LO_options['responsive'] = false;

			ListOutput( $chart_data, $LO_columns, 'Option', 'Options', [], [], $LO_options );
		}
		// Chart.js charts.
		else
		{
			$chart_title = sprintf( _( '%s Breakdown' ), ParseMLField( $field_RET[1]['TITLE'] ) );

			echo ChartjsChart(
				$_REQUEST['chart_type'],
				$chart_data_series,
				$chart_title
			);
		}

		PopTable( 'footer' );
	}
	echo '</form>';
}
