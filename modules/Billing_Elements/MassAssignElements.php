<?php
/**
 * Mass Assign Billing Elements
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'modules/Billing_Elements/includes/Update.inc.php';
require_once 'modules/Billing_Elements/includes/common.fnc.php';
require_once 'modules/Billing_Elements/includes/Elements.fnc.php';

if ( empty( $_REQUEST['tab'] ) )
{
	$_REQUEST['tab'] = '';
}

if ( $_REQUEST['modfunc'] === 'save'
	&& ! $_REQUEST['tab'] )
{
	if ( empty( $_REQUEST['student'] )
		|| ! AllowEdit() )
	{
		$error[] = _( 'You must choose at least one student.' );
	}

	if ( ! $error )
	{
		AddRequestedDates( 'due' );

		// Group SQL inserts.
		$sql = '';

		foreach ( (array) $_REQUEST['student'] as $student_id )
		{
			foreach ( (array) $_REQUEST['elements_id'] as $i => $element_id )
			{
				if ( ! is_numeric( $_REQUEST['amount'][ $i ] ) )
				{
					$error[] = _( 'Please enter a valid Amount.' );

					continue;
				}

				$fee_sql = "INSERT INTO billing_fees (STUDENT_ID,TITLE,AMOUNT,SYEAR,SCHOOL_ID,ASSIGNED_DATE,DUE_DATE,COMMENTS";

				if ( version_compare( ROSARIO_VERSION, '11.2', '>=' ) )
				{
					// @since RosarioSIS 11.2 Add CREATED_BY column to billing_fees table
					$fee_sql .= ',CREATED_BY';
				}

				$fee_sql .= ") VALUES('" . $student_id . "',
					'" . $_REQUEST['title'][ $i ] . "','" . $_REQUEST['amount'][ $i ] . "',
					'" . UserSyear() . "','" . UserSchool() . "','" . DBDate() . "','" . $_REQUEST['due'][ $i ] . "',
					'" . $_REQUEST['comments'][ $i ] . "'";

				if ( version_compare( ROSARIO_VERSION, '11.2', '>=' ) )
				{
					// @since RosarioSIS 11.2 Add CREATED_BY column to billing_fees table
					$fee_sql .= ",'" . DBEscapeString( User( 'NAME' ) ) . "'";
				}

				$fee_sql .= ");";

				DBQuery( $fee_sql );

				if ( function_exists( 'DBLastInsertID' ) )
				{
					$fee_id = DBLastInsertID();
				}
				else
				{
					// @deprecated since RosarioSIS 9.2.1.
					$fee_id = DBGetOne( "SELECT LASTVAL();" );
				}

				$sql .= "INSERT INTO billing_student_elements (STUDENT_ID,ELEMENT_ID,FEE_ID)
					VALUES('" . $student_id . "','" . $element_id . "','" . $fee_id . "');";
			}
		}

		if ( $sql )
		{
			DBQuery( $sql );

			$note[] = button( 'check' ) . '&nbsp;' .
				dgettext( 'Billing_Elements', 'The elements and fees have been added to the selected students.' );
		}
	}

	// Unset modfunc, student, elements_id, amount & redirect URL.
	RedirectURL( [ 'modfunc', 'student', 'elements_id', 'amount' ] );
}

if ( $_REQUEST['modfunc'] === 'save'
	&& $_REQUEST['tab'] === 'by-grade-level' )
{
	if ( empty( $_REQUEST['element'] )
		|| ! AllowEdit() )
	{
		$error[] = dgettext( 'Billing_Elements', 'You must choose at least one element.' );
	}

	if ( ! $error )
	{
		AddRequestedDates( 'due' );

		// Group SQL inserts.
		$sql = '';

		foreach ( (array) $_REQUEST['element'] as $element_id )
		{
			$element = BillingGetElement( $element_id );

			if ( ! $element['GRADE_LEVELS'] )
			{
				continue;
			}

			$grade_levels_in_sql = trim( $element['GRADE_LEVELS'], ',' );

			$where_not_assigned = '';

			if ( $_REQUEST['not_assigned_yet'] === 'Y' )
			{
				$where_not_assigned = " AND s.STUDENT_ID NOT IN(SELECT STUDENT_ID
					FROM billing_student_elements
					WHERE ELEMENT_ID='" . (int) $element_id . "')";
			}

			$students_RET = DBGet( "SELECT s.STUDENT_ID
			FROM students s
			JOIN student_enrollment ssm ON (ssm.STUDENT_ID=s.STUDENT_ID
				AND ssm.SYEAR='" . UserSyear() . "'
				AND ('" . DBDate() . "'>=ssm.START_DATE
					AND (ssm.END_DATE IS NULL
						OR '" . DBDate() . "'<=ssm.END_DATE ))
				AND ssm.SCHOOL_ID='" . UserSchool() . "')
			WHERE ssm.GRADE_ID IN(" . $grade_levels_in_sql . ")" . $where_not_assigned );

			foreach ( (array) $students_RET as $student )
			{
				$student_id = $student['STUDENT_ID'];

				$fee_sql = "INSERT INTO billing_fees (STUDENT_ID,TITLE,AMOUNT,SYEAR,SCHOOL_ID,ASSIGNED_DATE,DUE_DATE)
					VALUES('" . $student_id . "',
					'" . $element['REF_TITLE'] . "','" . $element['AMOUNT'] . "',
					'" . UserSyear() . "','" . UserSchool() . "','" . DBDate() . "','" . issetVal( $_REQUEST['due'][ $element_id ], '' ) . "');";

				DBQuery( $fee_sql );

				if ( function_exists( 'DBLastInsertID' ) )
				{
					$fee_id = DBLastInsertID();
				}
				else
				{
					// @deprecated since RosarioSIS 9.2.1.
					$fee_id = DBGetOne( "SELECT LASTVAL();" );
				}

				$sql .= "INSERT INTO billing_student_elements (STUDENT_ID,ELEMENT_ID,FEE_ID)
					VALUES('" . $student_id . "','" . $element_id . "','" . $fee_id . "');";
			}
		}

		if ( $sql )
		{
			DBQuery( $sql );

			$note[] = button( 'check' ) . '&nbsp;' .
				dgettext( 'Billing_Elements', 'The elements and fees have been added to the selected students.' );
		}
	}

	// Unset modfunc, element & redirect URL.
	RedirectURL( [ 'modfunc', 'element' ] );
}


if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	$by_grade_level_link = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=by-grade-level' ) . '">' . dgettext( 'Billing_Elements', 'Assign Elements by Grade Level' ) . '</a>';

	if (  $_REQUEST['tab'] !== 'by-grade-level'
		&& ( empty( $_REQUEST['search_modfunc'] )
			|| $_REQUEST['search_modfunc'] !== 'list' ) )
	{
		DrawHeader( $by_grade_level_link );
	}

	echo ErrorMessage( $error );

	echo ErrorMessage( $note, 'note' );

	if ( $_REQUEST['tab'] === 'by-grade-level' )
	{
		echo '<form action="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&tab=by-grade-level' ) :
			_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&tab=by-grade-level' ) ) .
		'" method="POST">';

		DrawHeader(
			CheckboxInput(
				'Y',
				'not_assigned_yet',
				dgettext( 'Billing_Elements', 'Only to students who have not been assigned the element yet' ),
				'',
				true
			),
			SubmitButton( dgettext( 'Billing_Elements', 'Assign Selected Elements' ) )
		);

		$columns = [
			'CHECKBOX' => MakeChooseCheckbox( 'required', 'ID', 'element' ),
			'CATEGORY' => _( 'Category' ),
			'REF_TITLE' => dgettext( 'Billing_Elements', 'Element' ),
			'AMOUNT' => _( 'Amount' ),
			'DUE_DATE' => _( 'Due Date' ),
			'GRADE_LEVELS' => _( 'Grade Levels' ),
			'STUDENTS_COUNT' => _( 'Students' ),
		];

		// Only select Elements having Grade Levels.
		$elements_RET = DBGet( "SELECT be.ID,be.TITLE,be.REF,be.AMOUNT,be.DESCRIPTION,GRADE_LEVELS,ROLLOVER,bc.TITLE AS CATEGORY,GRADE_LEVELS AS STUDENTS_COUNT,be.ID AS CHECKBOX,
			CONCAT(coalesce(NULLIF(CONCAT(be.REF,' - '),' - '),''),be.TITLE) AS REF_TITLE,
			be.ID AS DUE_DATE
		FROM billing_elements be,billing_categories bc
		WHERE be.SYEAR='" . UserSyear() . "'
		AND be.SCHOOL_ID='" . UserSchool() . "'
		AND be.CATEGORY_ID=bc.ID
		AND GRADE_LEVELS IS NOT NULL
		ORDER BY bc.SORT_ORDER IS NULL,bc.SORT_ORDER,CATEGORY,be.REF IS NULL,be.REF,be.TITLE", [
			'CHECKBOX' => '_makeChooseElementCheckbox',
			'STUDENTS_COUNT' => '_makeStudentsCount',
			'GRADE_LEVELS' => '_makeGradeLevels',
			'AMOUNT' => 'Currency',
			'DUE_DATE' => '_makeDueDate',
		] );

		/*$tooltip = '<div class="tooltip" style="text-transform: none !important;"><i>' .
			dgettext(
				'Billing_Elements',
				'Students enrolled in Grade Level(s) (students who have not been assigned the element yet).'
			) .
		'</i></div>';*/

		ListOutput(
			$elements_RET,
			$columns,
			dgettext( 'Billing_Elements', 'Element' ),
			dgettext( 'Billing_Elements', 'Elements' )
		);

		echo '<br /><div class="center">' .
			SubmitButton( dgettext( 'Billing_Elements', 'Assign Selected Elements' ) ) .
			'</div></form>';
	}
	else
	{
		if ( ! empty( $_REQUEST['search_modfunc'] )
			&& $_REQUEST['search_modfunc'] === 'list' )
		{
			echo '<form action="' . ( function_exists( 'URLEscape' ) ?
				URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) :
				_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) ) . '" method="POST">';

			DrawHeader(
				'',
				SubmitButton( dgettext( 'Billing_Elements', 'Add Element and Fee to Selected Students' ) )
			);

			echo '<br />';

			PopTable( 'header', dgettext( 'Billing_Elements', 'Element and Fee' ) );

			$billing_elements = BillingGetElements();

			$element_options = BillingElementSelectOptions( $billing_elements );

			$elements_js = BillingElementSelectJSList( $billing_elements );
			?>
			<script>
				var billingElements = <?php echo $elements_js; ?>;
				var inputNumber = 1;
			</script>
			<?php

			echo BillingElementSelectFillTitleAmountMultipleJS();

			echo '<table id="element_inputs1"><tr><td>' . SelectInput(
				issetVal( $_REQUEST['element_id'] ),
				'elements_id[1]',
				dgettext( 'Billing_Elements', 'Element' ),
				$element_options,
				'N/A',
				'required group autocomplete="off" onchange="billingElementSelectFillTitleAmount(this.value,inputNumber);"',
				false
			) . '</td></tr>';

			echo '<tr><td>' . TextInput(
				'',
				'title[1]',
				_( 'Title' ),
				'required size="25"'
			) . '</td></tr>';

			echo '<tr><td>' . TextInput(
				'',
				'amount[1]',
				_( 'Amount' ),
				' type="number" step="0.01" max="999999999999" min="-999999999999" required'
			) . '</td></tr>';

			echo '<tr><td>' . DateInput( '', 'due[1]', _( 'Due Date' ), false ) . '</td></tr>';

			echo '<tr><td>' . TextInput(
				'',
				'comments[1]',
				_( 'Comment' ),
				'maxlength="1000" size="25"'
			) . '</td></tr></table>';

			// @since 3.0 Assign multiple Elements at once.
			echo button(
				'add',
				dgettext( 'Billing_Elements', 'New Element' ),
				'"#!" onclick="billingElementAddNew(inputNumber++);"'
			);

			echo BillingElementAddNewJS();

			PopTable( 'footer' );

			echo '<br />';
		}

		$extra['link'] = [ 'FULL_NAME' => false ];
		$extra['SELECT'] = ",NULL AS CHECKBOX";
		$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];
		$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( 'required', 'STUDENT_ID', 'student' ) ];
		$extra['new'] = true;

		$grade_levels = issetVal( $_REQUEST['grade_levels'] );

		if ( ! empty( $grade_levels ) )
		{
			$grade_levels = explode( ',', trim( $grade_levels, ',' ) );

			if ( count( $grade_levels ) === 1 )
			{
				// Only one Grade Level, do not pass an array to avoid "Advanced Search".
				$grade_levels = reset( $grade_levels );
			}
			else
			{
				// Grade Level ID as key, value not empty.
				$grade_levels = array_flip( $grade_levels );
				$grade_levels = array_fill_keys( array_keys( $grade_levels ), 'not_empty' );
			}
		}

		// Preselect Grade Level(s) if one is assigned to Element.
		$extra['grades'] = $grade_levels;

		// Maintain &document_id param for direct assignation from Elements program.
		$extra['action'] = '&element_id=' . issetVal( $_REQUEST['element_id'] );

		Search( 'student_id', $extra );

		if ( ! empty( $_REQUEST['search_modfunc'] )
			&& $_REQUEST['search_modfunc'] === 'list' )
		{
			echo '<br /><div class="center">' .
				SubmitButton( dgettext( 'Billing_Elements', 'Add Element and Fee to Selected Students' ) ) .
				'</div></form>';
		}
	}
}

function _makeGradeLevels( $value, $column = 'GRADE_LEVELS' )
{
	static $grade_levels_RET = null;

	if ( is_null( $grade_levels_RET ) )
	{
		$grade_levels_RET = DBGet( "SELECT ID,TITLE,SHORT_NAME
			FROM school_gradelevels
			WHERE SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );
	}

	if ( ! $value )
	{
		return '';
	}

	$grade_levels = explode( ',', trim( $value, ',' ) );

	$grade_level_values = [];

	foreach ( (array) $grade_levels_RET as $grade_level )
	{
		if ( in_array( $grade_level['ID'], $grade_levels ) )
		{
			$grade_level_values[] = $grade_level['TITLE'];
		}
	}

	return implode( ', ', $grade_level_values );
}


function _makeStudentsCount( $value, $column = 'STUDENTS_COUNT' )
{
	global $THIS_RET;

	if ( empty( $value )
		|| empty( $THIS_RET['ID'] ) )
	{
		return '';
	}

	$grade_levels_in_sql = trim( $value, ',' );

	$students_count_in_grade_levels = DBGetOne( "SELECT COUNT(s.STUDENT_ID)
	FROM students s
	JOIN student_enrollment ssm ON (ssm.STUDENT_ID=s.STUDENT_ID
		AND ssm.SYEAR='" . UserSyear() . "'
		AND ('" . DBDate() . "'>=ssm.START_DATE
			AND (ssm.END_DATE IS NULL
				OR '" . DBDate() . "'<=ssm.END_DATE ))
		AND ssm.SCHOOL_ID='" . UserSchool() . "')
	WHERE ssm.GRADE_ID IN(" . $grade_levels_in_sql . ")" );

	if ( ! $students_count_in_grade_levels )
	{
		return '0';
	}

	// Count students in Grade Level(s), who have not been assigned the element yet.
	$students_count_not_assigned_yet = DBGetOne( "SELECT COUNT(s.STUDENT_ID)
	FROM students s
	JOIN student_enrollment ssm ON (ssm.STUDENT_ID=s.STUDENT_ID
		AND ssm.SYEAR='" . UserSyear() . "'
		AND ('" . DBDate() . "'>=ssm.START_DATE
			AND (ssm.END_DATE IS NULL
				OR '" . DBDate() . "'<=ssm.END_DATE ))
		AND ssm.SCHOOL_ID='" . UserSchool() . "')
	WHERE ssm.GRADE_ID IN(" . $grade_levels_in_sql . ")
	AND s.STUDENT_ID NOT IN(SELECT STUDENT_ID
		FROM billing_student_elements
		WHERE ELEMENT_ID='" . (int) $THIS_RET['ID'] . "')" );

	if ( $students_count_in_grade_levels !== $students_count_not_assigned_yet )
	{
		return $students_count_in_grade_levels . ' (' .
			sprintf(
				dgettext( 'Billing_Elements', '%d students who have not been assigned the element yet' ),
				$students_count_not_assigned_yet
			) . ')';
	}

	return $students_count_in_grade_levels;
}

function _makeChooseElementCheckbox( $value, $column = 'CHECKBOX' )
{
	global $THIS_RET;

	if ( empty( $value )
		|| empty( $THIS_RET['GRADE_LEVELS'] ) )
	{
		return '';
	}

	$grade_levels_in_sql = trim( $THIS_RET['GRADE_LEVELS'], ',' );

	$students_count_in_grade_levels = DBGetOne( "SELECT COUNT(s.STUDENT_ID)
	FROM students s
	JOIN student_enrollment ssm ON (ssm.STUDENT_ID=s.STUDENT_ID
		AND ssm.SYEAR='" . UserSyear() . "'
		AND ('" . DBDate() . "'>=ssm.START_DATE
			AND (ssm.END_DATE IS NULL
				OR '" . DBDate() . "'<=ssm.END_DATE ))
		AND ssm.SCHOOL_ID='" . UserSchool() . "')
	WHERE ssm.GRADE_ID IN(" . $grade_levels_in_sql . ")" );

	if ( ! $students_count_in_grade_levels )
	{
		return '';
	}

	return MakeChooseCheckbox( $value );
}

function _makeDueDate( $value, $column = 'DUE_DATE' )
{
	return DateInput(
		'',
		'due[' . $value . ']',
		'',
		false
	);
}
