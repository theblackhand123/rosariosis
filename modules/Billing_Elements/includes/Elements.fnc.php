<?php
/**
 * Billing_Elements functions
 *
 * @package Billing_Elements module
 */


/**
 * Get Element or Element Category Form
 *
 * @example echo GetElementsForm( $title, $RET );
 *
 * @example echo GetElementsForm(
 *              $title,
 *              $RET,
 *              null,
 *              array( 'text' => _( 'Text' ), 'textarea' => _( 'Long Text' ) )
 *          );
 *
 * @uses DrawHeader()
 * @uses MakeElementType()
 *
 * @param  string $title                 Form Title.
 * @param  array  $RET                   Element or Element Category Data.
 * @param  array  $extra_fields Extra fields for Element Category.
 * @param  array  $type_options          Associative array of Element Types (optional). Defaults to null.
 *
 * @return string Element or Element Category Form HTML
 */
function BillingGetElementsForm( $title, $RET, $extra_fields = [], $type_options = null )
{
	$id = issetVal( $RET['ID'] );

	$category_id = issetVal( $RET['CATEGORY_ID'] );

	if ( empty( $id )
		&& empty( $category_id ) )
	{
		return '';
	}

	$new = $id === 'new' || $category_id === 'new';

	$action = 'Modules.php?modname=' . $_REQUEST['modname'];

	if ( $category_id
		&& $category_id !== 'new' )
	{
		$action .= '&category_id=' . $category_id;
	}

	if ( $id
		&& $id !== 'new' )
	{
		$action .= '&id=' . $id;
	}

	if ( $id )
	{
		$full_table = 'billing_elements';
	}
	else
	{
		$full_table = 'billing_categories';
	}

	$action .= '&table=' . $full_table;

	$form = '<form action="' . ( function_exists( 'URLEscape' ) ? URLEscape( $action ) : _myURLEncode( $action ) ) . '" method="POST" enctype="multipart/form-data">';

	$allow_edit = AllowEdit();

	$div = $allow_edit;

	$delete_button = '';

	$can_delete_element = $id && $id !== 'new' && DBTransDryRun( "DELETE FROM billing_elements
		WHERE ID='" . (int) $id . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "';" );

	if ( $allow_edit
		&& ! $new
		&& ( $can_delete_element || ! BillingCategoryHasElements( $category_id ) ) )
	{
		$delete_URL = ( function_exists( 'URLEscape' ) ?
			URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] . '&modfunc=delete&category_id=' . $category_id . '&id=' . $id ) :
			_myURLEncode( "Modules.php?modname=" . $_REQUEST['modname'] . '&modfunc=delete&category_id=' . $category_id . '&id=' . $id ) );

		$onclick_link = 'ajaxLink(' . json_encode( $delete_URL ) . ');';

		$delete_button = '<input type="button" value="' .
			( function_exists( 'AttrEscape' ) ? AttrEscape( _( 'Delete' ) ) : htmlspecialchars( _( 'Delete' ), ENT_QUOTES ) ) .
			'" onclick="' .
			( function_exists( 'AttrEscape' ) ? AttrEscape( $onclick_link ) : htmlspecialchars( $onclick_link, ENT_QUOTES ) ) . '" /> ';
	}

	ob_start();

	DrawHeader( $title, $delete_button . SubmitButton() );

	$form .= ob_get_clean();

	$header = '<table class="width-100p valign-top fixed-col cellpadding-5"><tr class="st">';

	if ( $id )
	{
		// FJ element name required.
		$header .= '<td>' . TextInput(
			issetVal( $RET['TITLE'] ),
			'tables[' . $id . '][TITLE]',
			_( 'Title' ),
			'required maxlength=1000' .
			( empty( $RET['TITLE'] ) ? ' size=30' : '' ),
			$div
		) . '</td>';

		$amount_value = issetVal( $RET['AMOUNT'], '' );

		$amount_value = User( 'PROFILE' ) !== 'admin' ? Currency( $amount_value ) : $amount_value;

		$header .= '<td>' . TextInput(
			$amount_value,
			'tables[' . $id . '][AMOUNT]',
			_( 'Amount' ),
			'type="number" step="0.01" max="999999999999" min="-999999999999" required',
			$div
		) . '</td>';

		$header .= '</tr><tr class="st">';

		$header .= '<td colspan="2">' . TinyMCEInput(
			issetVal( $RET['DESCRIPTION'] ),
			'tables[' . $id . '][DESCRIPTION]',
			_( 'Description' )
		) . '</td>';

		$header .= '</tr><tr class="st">';

		$grade_levels_RET = DBGet( "SELECT ID,TITLE,SHORT_NAME
			FROM school_gradelevels
			WHERE SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

		$grade_level_options = [];

		foreach ( (array) $grade_levels_RET as $grade_level )
		{
			$grade_level_options[ $grade_level['ID'] ] = $grade_level['TITLE'];
		}

		// @deprecated SQL GRADE_LEVEL column, since 11.0 use GRADE_LEVELS instead
		$grade_level_values = [];

		if ( ! empty( $RET['GRADE_LEVELS'] ) )
		{
			$grade_level_values = explode( ',', trim( $RET['GRADE_LEVELS'], ',' ) );
		}

		$select_input_function = function_exists( 'Select2Input' ) ? 'Select2Input' : 'ChosenSelectInput';

		$header .= '<td>' . $select_input_function(
			$grade_level_values,
			'tables[' . $id . '][GRADE_LEVELS][]',
			_( 'Grade Levels' ),
			$grade_level_options,
			( User( 'PROFILE' ) === 'admin' ? 'N/A' : _( 'All' ) ),
			'multiple'
		) . '</td>';

		if ( User( 'PROFILE' ) === 'admin'
			|| ! empty( $RET['COURSE_PERIOD_ID'] ) )
		{
			$course_options = BillingElementsCourseOptions();

			$header .= '<td>' . SelectInput(
				issetVal( $RET['COURSE_PERIOD_ID'] ),
				'tables[' . $id . '][COURSE_PERIOD_ID]',
				_( 'Course Period' ),
				$course_options,
				'N/A',
				'group style="max-width: 280px"'
			) . '</td>';

			$header .= '</tr><tr class="st">';
		}

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$header .= '<td>' . CheckboxInput(
				( ! array_key_exists( 'ROLLOVER', $RET ) ? 'Y' : $RET['ROLLOVER'] ),
				'tables[' . $id . '][ROLLOVER]',
				_( 'Rollover' ),
				'',
				! array_key_exists( 'ROLLOVER', $RET )
			) . '</td>';

			$header .= '</tr><tr class="st">';
		}

		// REFERENCE (can be changed, not required).
		$header .= '<td' . ( ! $category_id ? ' colspan="2"' : '' ) . '>' . TextInput(
			issetVal( $RET['REF'] ),
			'tables[' . $id . '][REF]',
			dgettext( 'Billing_Elements', 'Reference' ),
			'maxlength=50',
			$div
		) . '</td>';

		if ( User( 'PROFILE' ) === 'admin'
			&& $category_id )
		{
			// CATEGORIES.
			$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
				FROM billing_categories
				WHERE SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

			foreach ( (array) $categories_RET as $category )
			{
				$categories_options[ $category['ID'] ] = $category['TITLE'];
			}

			$header .= '<td>' . SelectInput(
				$RET['CATEGORY_ID'] ? $RET['CATEGORY_ID'] : $category_id,
				'tables[' . $id . '][CATEGORY_ID]',
				_( 'Category' ),
				$categories_options,
				false,
				'required'
			) . '</td></tr>';
		}

		$header .= '<tr class="st">';

		// Extra Fields.
		if ( ! empty( $extra_fields ) )
		{
			$header .= '<tr><td colspan="2"><hr /></td></tr><tr class="st">';

			$i = 0;

			foreach ( (array) $extra_fields as $extra_field )
			{
				if ( $i && $i % 2 === 0 )
				{
					$header .= '</tr><tr class="st">';
				}

				$colspan = 1;

				if ( $i === ( count( $extra_fields ) - 1 ) )
				{
					$colspan = abs( ( $i % 2 ) - 2 );
				}

				$header .= '<td colspan="' . $colspan . '">' . $extra_field . '</td>';

				$i++;
			}

			$header .= '</tr>';
		}

		$header .= '</table>';
	}
	// Elements Category Form.
	else
	{
		$title = isset( $RET['TITLE'] ) ? $RET['TITLE'] : '';

		// Title element.
		$header .= '<td>' . TextInput(
			$title,
			'tables[' . $category_id . '][TITLE]',
			_( 'Title' ),
			'required maxlength=255' . ( empty( $title ) ? ' size=20' : '' )
		) . '</td>';

		if ( User( 'PROFILE' ) === 'admin' )
		{
			// Sort Order element.
			$header .= '<td>' . TextInput(
				( isset( $RET['SORT_ORDER'] ) ? $RET['SORT_ORDER'] : '' ),
				'tables[' . $category_id . '][SORT_ORDER]',
				_( 'Sort Order' ),
				' type="number" min="-9999" max="9999"'
			) . '</td>';
		}

		// Extra Fields.
		if ( ! empty( $extra_fields ) )
		{
			$i = 2;

			foreach ( (array) $extra_fields as $extra_field )
			{
				if ( $i % 3 === 0 )
				{
					$header .= '</tr><tr class="st">';
				}

				$colspan = 1;

				if ( $i === ( count( $extra_fields ) + 1 ) )
				{
					$colspan = abs( ( $i % 3 ) - 3 );
				}

				$header .= '<td colspan="' . $colspan . '">' . $extra_field . '</td>';

				$i++;
			}
		}

		$header .= '</tr></table>';
	}

	ob_start();

	DrawHeader( $header );

	$form .= ob_get_clean();

	$form .= '</form>';

	return $form;
}


/**
 * Outputs Elements or Categories Menu
 *
 * @example ElementsMenuOutput( $elements_RET, $_REQUEST['id'], $_REQUEST['category_id'] );
 * @example ElementsMenuOutput( $categories_RET, $_REQUEST['category_id'] );
 *
 * @uses ListOutput()
 *
 * @param array  $RET         Categories (ID, TITLE, SORT_ORDER columns) or Elements (+ REF column) RET.
 * @param string $id          Element Category ID or Element ID.
 * @param string $category_id Element Category ID (optional). Defaults to '0'.
 */
function BillingElementsMenuOutput( $RET, $id, $category_id = '0' )
{
	if ( $RET
		&& $id
		&& $id !== 'new' )
	{
		foreach ( (array) $RET as $key => $value )
		{
			if ( $value['ID'] == $id )
			{
				$RET[ $key ]['row_color'] = Preferences( 'HIGHLIGHT' );
			}
		}
	}

	$LO_options = [ 'save' => false, 'search' => false, 'responsive' => false ];

	if ( ! $category_id )
	{
		$LO_columns = [
			'TITLE' => _( 'Category' ),
		];
	}
	else
	{
		$LO_columns = [
			'TITLE' => dgettext( 'Billing_Elements', 'Element' ),
			'REF' => dgettext( 'Billing_Elements', 'Reference' ),
		];
	}

	$LO_link = [];

	$LO_link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'];

	if ( $category_id )
	{
		$LO_link['TITLE']['link'] .= '&category_id=' . $category_id;
	}

	$LO_link['TITLE']['variables'] = [ ( ! $category_id ? 'category_id' : 'id' ) => 'ID' ];

	$LO_link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=';

	$LO_link['add']['link'] .= $category_id ? $category_id . '&id=new' : 'new';

	// Fix Teacher cannot add new Billing Elements / not displaying Elements total.
	$tmp_allow_edit = false;

	if ( ! $category_id )
	{
		ListOutput(
			$RET,
			$LO_columns,
			dgettext( 'Billing_Elements', 'Element Category' ),
			dgettext( 'Billing_Elements', 'Categories' ),
			$LO_link,
			[],
			$LO_options
		);
	}
	else
	{
		$LO_options['search'] = true;

		ListOutput(
			$RET,
			$LO_columns,
			dgettext( 'Billing_Elements', 'Element' ),
			dgettext( 'Billing_Elements', 'Elements' ),
			$LO_link,
			[],
			$LO_options
		);
	}
}


/**
 * Category has Elements?
 *
 * @param int $category_id Elements Category ID.
 *
 * @return bool True if Category has Elements.
 */
function BillingCategoryHasElements( $category_id )
{
	if ( (string) (int) $category_id != $category_id
		|| $category_id < 1 )
	{
		return false;
	}

	$category_has_elements = DBGet( "SELECT 1
		FROM billing_elements
		WHERE CATEGORY_ID='" . (int) $category_id . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		LIMIT 1" );

	return (bool) $category_has_elements;
}

/**
 * Element Assignations Number
 *
 * @param int $element_id Element ID.
 *
 * @return int Element Assignations Number
 */
function BillingElementAssignations( $element_id )
{
	return (int) DBGetOne( "SELECT COUNT(1) AS ASSIGNATIONS
		FROM billing_student_elements
		WHERE ELEMENT_ID='" . (int) $element_id . "'" );
}

/**
 * Element Purchased Times
 *
 * @param int $element_id Element ID.
 * @param int $student_id Student ID.
 *
 * @return int Element Purchased Times
 */
function BillingElementPurchased( $element_id, $student_id )
{
	return (int) DBGetOne( "SELECT COUNT(1) AS PURCHASED
		FROM billing_student_elements
		WHERE ELEMENT_ID='" . (int) $element_id . "'
		AND STUDENT_ID='" . (int) $student_id . "'" );
}

/**
 * Assign Button HTML
 *
 * @param int $element_id element ID.
 *
 * @return string Assign Button HTML or empty if cannot access Mass assign Element program.
 */
function BillingElementAssignButton( $element_id )
{
	$modname = 'Billing_Elements/MassAssignElements.php';

	if ( ! AllowEdit( $modname ) )
	{
		return '';
	}

	$element = BillingGetElement( $element_id );

	$assign_button = '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $modname .
			'&element_id=' . $element_id . '&grade_levels=' . $element['GRADE_LEVELS'] ) :
		_myURLEncode( 'Modules.php?modname=' . $modname .
			'&element_id=' . $element_id . '&grade_levels=' . $element['GRADE_LEVELS'] ) ) . '" method="GET">';

	$assign_button .= SubmitButton( dgettext( 'Billing_Elements', 'Assign' ), '', '' );

	$assign_button .= '</form>';

	return $assign_button;
}

/**
 * Purchase Button HTML
 *
 * @param int $element_id element ID.
 * @param int $student_id Student ID.
 *
 * @return string Purchase Button HTML or empty if cannot purchase Element.
 */
function BillingElementPurchaseButton( $element_id, $student_id )
{
	$modname = 'Billing_Elements/StudentElements.php';

	if ( ! BillingElementCanPurchase( $element_id, $student_id ) )
	{
		return '';
	}

	$element = BillingGetElement( $element_id );

	$purchase_button = '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&category_id=' . $element['CATEGORY_ID'] . '&id=' . $element_id . '&modfunc=purchase' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&category_id=' . $element['CATEGORY_ID'] . '&id=' . $element_id . '&modfunc=purchase' ) ) . '" method="GET">';

	$purchase_button .= Buttons( dgettext( 'Billing_Elements', 'Purchase' ) );

	$purchase_button .= '</form>';

	return $purchase_button;
}

/**
 * Can Purchase Billing Element
 *
 * @param int $element_id element ID.
 * @param int $student_id Student ID.
 *
 * @return bool False if cannot access Student Elements program, or Not in right Grade Level, or Course Period is Full or not sufficient funds.
 */
function BillingElementCanPurchase( $element_id, $student_id )
{
	if ( ! AllowUse( 'Billing_Elements/StudentElements.php' ) )
	{
		return false;
	}

	return ! BillingElementGradeLevelRestricted( $element_id, $student_id )
		&& ! BillingElementCoursePeriodFull( $element_id )
		&& BillingElementHasFunds( $element_id, $student_id );
}

/**
 * Is Billing Element Grade Level restricted for Student?
 *
 * @param int $element_id element ID.
 * @param int $student_id Student ID.
 *
 * @return bool True if Not in right Grade Level, else false.
 */
function BillingElementGradeLevelRestricted( $element_id, $student_id )
{
	$element = BillingGetElement( $element_id );

	if ( ! $element )
	{
		return true;
	}

	if ( $element['GRADE_LEVELS'] )
	{
		$student_grade_level = DBGetOne( "SELECT GRADE_ID
			FROM student_enrollment
			WHERE STUDENT_ID='" . (int) $student_id . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY START_DATE DESC
			LIMIT 1" );

		// @deprecated SQL GRADE_LEVEL column, since 11.0 use GRADE_LEVELS instead
		$grade_levels = [];

		if ( ! empty( $element['GRADE_LEVELS'] ) )
		{
			$grade_levels = explode( ',', trim( $element['GRADE_LEVELS'], ',' ) );
		}

		if ( ! $student_grade_level
			|| ! in_array( $student_grade_level, $grade_levels ) )
		{
			// No Grade Level or Grade Level not in Element Grade Levels.
			return true;
		}
	}

	return false;
}

/**
 * Has Student Sufficient funds to purchase Billing Element?
 *
 * @param int $element_id element ID.
 * @param int $student_id Student ID.
 *
 * @return bool True if has funds, else false.
 */
function BillingElementHasFunds( $element_id, $student_id )
{
	$element = BillingGetElement( $element_id );

	if ( ! $element )
	{
		return false;
	}

	if ( $element['AMOUNT'] <= 0 )
	{
		// Element is free or has negative amount.
		return true;
	}

	$student_balance = BillingElementsStudentBalance( $student_id );

	if ( ROSARIO_DEBUG && function_exists( 'd' ) )
	{
		d( $student_balance, $element['AMOUNT'] );
	}

	return ( $student_balance - $element['AMOUNT'] ) >= 0;
}

function BillingElementsStudentBalance( $student_id )
{
	return DBGetOne( "SELECT coalesce((SELECT sum(p.AMOUNT)
			FROM billing_payments p
			WHERE p.STUDENT_ID='" . (int) $student_id . "'
			AND p.SYEAR='" . UserSyear() . "'),0) -
		coalesce((SELECT sum(f.AMOUNT)
			FROM billing_fees f
			WHERE f.STUDENT_ID='" . (int) $student_id . "'
			AND f.SYEAR='" . UserSyear() . "'),0)" );
}

/**
 * Purchase Billing Element (Student or its Parents)
 *
 * @param int $element_id element ID.
 * @param int $student_id Student ID.
 *
 * @return bool False if element not found, else true.
 */
function BillingElementPurchase( $element_id, $student_id )
{
	$element = BillingGetElement( $element_id );

	if ( ! $element )
	{
		return false;
	}

	$insert_sql = "INSERT INTO billing_fees (STUDENT_ID,TITLE,AMOUNT,SYEAR,SCHOOL_ID,ASSIGNED_DATE,DUE_DATE,COMMENTS";

	if ( version_compare( ROSARIO_VERSION, '11.2', '>=' ) )
	{
		// @since RosarioSIS 11.2 Add CREATED_BY column to billing_fees table
		$insert_sql .= ',CREATED_BY';
	}

	$title = $element['TITLE'];

	if ( $element['REF'] )
	{
		$title = $element['REF'] . ' - ' . $title;
	}

	$insert_sql .= ") VALUES('" . $student_id . "',
		'" . DBEscapeString( $title ) . "','" . $element['AMOUNT'] . "',
		'" . UserSyear() . "','" . UserSchool() . "','" . DBDate() . "',NULL,
		'" . $_REQUEST['purchase_comments'] . "'";

	if ( version_compare( ROSARIO_VERSION, '11.2', '>=' ) )
	{
		// @since RosarioSIS 11.2 Add CREATED_BY column to billing_fees table
		$insert_sql .= ",'" . DBEscapeString( User( 'NAME' ) ) . "'";
	}

	$insert_sql .= ");";

	DBQuery( $insert_sql );

	if ( function_exists( 'DBLastInsertID' ) )
	{
		$fee_id = DBLastInsertID();
	}
	else
	{
		// @deprecated since RosarioSIS 9.2.1.
		$fee_id = DBGetOne( "SELECT LASTVAL();" );
	}

	$insert_sql = "INSERT INTO billing_student_elements (STUDENT_ID,ELEMENT_ID,FEE_ID)
		VALUES('" . $student_id . "','" . $element_id . "','" . $fee_id . "');";

	DBQuery( $insert_sql );

	return true;
}


/**
 * Course (Period) options
 * Grouped by Subject TITLE
 *
 * @return array Course (Period) options grouped by Subject TITLE.
 */
function BillingElementsCourseOptions()
{
	$course_periods_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,cp.TITLE,c.TITLE AS COURSE_TITLE,cs.TITLE AS SUBJECT_TITLE
		FROM course_periods cp,courses c,course_subjects cs
		WHERE cp.SYEAR='" . UserSyear() . "'
		AND cp.SCHOOL_ID='" . UserSchool() . "'
		AND cp.COURSE_ID=c.COURSE_ID
		AND c.SUBJECT_ID=cs.SUBJECT_ID
		ORDER BY cs.SORT_ORDER IS NULL,cs.SORT_ORDER,c.TITLE", [], [ 'SUBJECT_TITLE' ] );

	$cp_options = [];

	foreach ( $course_periods_RET as $subject_title => $course_periods )
	{
		$cp_options[ $subject_title ] = [];

		foreach ( $course_periods as $course_period )
		{
			$cp_title = $course_period['COURSE_TITLE'] . ' - ' . $course_period['TITLE'];

			$cp_options[ $subject_title ][ $course_period['COURSE_PERIOD_ID'] ] = $cp_title;
		}
	}

	return $cp_options;
}

/**
 * Enroll Student in Course Period after Purchase.
 *
 * @param int $element_id element ID.
 * @param int $student_id Student ID.
 *
 * @return bool False if student not enrolled, else true.
 */
function BillingElementCourseEnroll( $element_id, $student_id )
{
	$element = BillingGetElement( $element_id );

	if ( ! $element
		|| ! $element['COURSE_PERIOD_ID'] )
	{
		return false;
	}

	if ( BillingElementStudentAlreadyEnrolled( $element_id, $student_id ) )
	{
		return false;
	}

	$cp_RET = DBGet( "SELECT COURSE_ID,MP,MARKING_PERIOD_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $element['COURSE_PERIOD_ID'] . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	DBQuery( "INSERT INTO schedule
		(SYEAR,SCHOOL_ID,STUDENT_ID,COURSE_ID,COURSE_PERIOD_ID,MP,MARKING_PERIOD_ID,START_DATE)
		VALUES('" . UserSyear() . "','" . UserSchool() . "','" . $student_id . "',
		'" . $cp_RET[1]['COURSE_ID'] . "','" . $element['COURSE_PERIOD_ID'] . "','" . $cp_RET[1]['MP'] . "',
		'" . $cp_RET[1]['MARKING_PERIOD_ID'] . "','" . DBDate() . "')" );

	return true;
}


/**
 * Student Already Enrolled in Course Period?
 *
 * @param int $element_id element ID.
 * @param int $student_id Student ID.
 *
 * @return bool True if student already enrolled, else false.
 */
function BillingElementStudentAlreadyEnrolled( $element_id, $student_id )
{
	$element = BillingGetElement( $element_id );

	if ( ! $element
		|| ! $element['COURSE_PERIOD_ID'] )
	{
		return false;
	}

	// Check student is not already enrolled in CP.
	$already_enrolled = DBGetOne( "SELECT 1 AS ALREADY_ENROLLED
		FROM schedule
		WHERE STUDENT_ID='" . (int) $student_id . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND COURSE_PERIOD_ID='" . (int) $element['COURSE_PERIOD_ID'] . "'
		AND END_DATE IS NULL OR END_DATE>CURRENT_DATE;" );

	return (bool) $already_enrolled;
}


/**
 * Is Course Period Full? (No available seats)
 *
 * @param int $element_id element ID.
 *
 * @return bool True if course period full, else false.
 */
function BillingElementCoursePeriodFull( $element_id )
{
	require_once 'modules/Scheduling/includes/calcSeats0.fnc.php';

	$element = BillingGetElement( $element_id );

	if ( ! $element
		|| ! $element['COURSE_PERIOD_ID'] )
	{
		return false;
	}

	$course_period_RET = DBGet( "SELECT COURSE_ID,COURSE_PERIOD_ID,TITLE,MP,MARKING_PERIOD_ID,CALENDAR_ID,TOTAL_SEATS
	FROM course_periods cp
	WHERE COURSE_PERIOD_ID='" . (int) $element['COURSE_PERIOD_ID'] . "'
	AND SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'" );

	if ( empty( $course_period_RET[1]['TOTAL_SEATS'] ) )
	{
		return false;
	}

	$course_period = $course_period_RET[1];

	// Check student is not already enrolled in CP.
	$available_seats = $course_period['TOTAL_SEATS'] - calcSeats0( $course_period );

	return $available_seats <= 0;
}

/**
 * Enroll Student in Moodle Course
 *
 * @param int $element_id Element ID.
 * @param int $student_id Student ID.
 *
 * @return bool True if student enrolled, else false.
 */
function BillingElementMoodleCourseEnroll( $element_id, $student_id )
{
	if ( ! BillingElementStudentAlreadyEnrolled( $element_id, $student_id ) )
	{
		// Student not enrolled, element is not tied to a Course Period.
		return false;
	}

	$element = BillingGetElement( $_REQUEST['id'] );

	if ( empty( $element['COURSE_PERIOD_ID'] ) )
	{
		return false;
	}

	// Check URL is responding with cURL.
	$functionname = 'enrol_manual_enrol_users';

	/**
	 * @param $response
	 */
	function enrol_manual_enrol_users_response( $response )
	{
		return ! empty( $response );
	}

	//then, convert variables for the Moodle object:
	/*
	list of (
		object {
			roleid int   //Role to assign to the user
			userid int   //The user that is going to be enrolled
			courseid int   //The course to enrol the user role in
			timestart int  Optionnel //Timestamp when the enrolment start
			timeend int  Optionnel //Timestamp when the enrolment end
			suspend int  Optionnel //set to 1 to suspend the enrolment
		}

	XML-RPC (PHP structure)

	[enrolments] =>
    Array
        (
        [0] =>
            Array
                (
                [roleid] => int
                [userid] => int
                [courseid] => int
                [timestart] => int
                [timeend] => int
                [suspend] => int
                )
        )
	)*/

	// Student's roleid = student = 5.
	$roleid = 5;

	// Get the Moodle user ID.
	// @deprecated since 6.0 use MoodleXRosarioGet().
	$userid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='" . $student_id . "'
		AND " . DBEscapeIdentifier( 'column' ) . "='student_id'" );

	if ( empty( $userid ) )
	{
		return false;
	}

	// Gather the Moodle course ID.
	// @deprecated since 6.0 use MoodleXRosarioGet().
	$courseid = (int) DBGetOne( "SELECT moodle_id
		FROM moodlexrosario
		WHERE rosario_id='" . $element['COURSE_PERIOD_ID'] . "'
		AND " . DBEscapeIdentifier( 'column' ) . "='course_period_id'" );

	if ( empty( $courseid ) )
	{
		return false;
	}

	// Convert YYYY-MM-DD to timestamp.
	$timestart = strtotime( DBDate() );

	$enrolments = [
		'roleid' => $roleid,
		'userid' => $userid,
		'courseid' => $courseid,
		'timestart' => $timestart,
	];

	$object = [ 'enrolments' => $enrolments ];

	$return = moodle_xmlrpc_call( $functionname, $object );

	return $return;
}

/**
 * Enroll Student in Iomad Course
 *
 * @param int $element_id Element ID.
 * @param int $student_id Student ID.
 *
 * @return bool True if student enrolled, else false.
 */
function BillingElementIomadCourseEnroll( $element_id, $student_id )
{
	require_once 'plugins/Iomad/includes/common.fnc.php';

	if ( ! BillingElementStudentAlreadyEnrolled( $element_id, $student_id ) )
	{
		// Student not enrolled, element is not tied to a Course Period.
		return false;
	}

	$element = BillingGetElement( $element_id );

	return IomadCourseAssignUser( $element['COURSE_PERIOD_ID'], $student_id, 'student_id', DBDate() );
}
