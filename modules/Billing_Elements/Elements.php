<?php
/**
 * Billing Elements
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'modules/Billing_Elements/includes/Update.inc.php';
require_once 'modules/Billing_Elements/includes/common.fnc.php';
require_once 'modules/Billing_Elements/includes/Elements.fnc.php';

DrawHeader( ProgramTitle() );

$_REQUEST['category_id'] = issetVal( $_REQUEST['category_id'] );
$_REQUEST['id'] = issetVal( $_REQUEST['id'] );

if ( AllowEdit()
	&& isset( $_POST['tables'] )
	&& is_array( $_POST['tables'] ) )
{
	$table = issetVal( $_REQUEST['table'] );

	if ( ! in_array( $table, [ 'billing_categories', 'billing_elements' ] ) )
	{
		// Security: SQL prevent INSERT or UPDATE on any table
		$table = '';

		$_REQUEST['tables'] = [];
	}

	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		if ( isset( $columns['DESCRIPTION'] ) )
		{
			$columns['DESCRIPTION'] = DBEscapeString( SanitizeHTML( $_POST['tables'][ $id ]['DESCRIPTION'] ) );
		}

		if ( isset( $columns['GRADE_LEVELS'] ) )
		{
			// @deprecated SQL GRADE_LEVEL column, since 11.0 use GRADE_LEVELS instead
			$grade_levels = implode( ',', $columns['GRADE_LEVELS'] );

			$columns['GRADE_LEVELS'] = '';

			if ( $grade_levels )
			{
				$columns['GRADE_LEVELS'] = ',' . $grade_levels;
			}
		}

		// FJ fix SQL bug invalid sort order.
		if ( empty( $columns['SORT_ORDER'] )
			|| is_numeric( $columns['SORT_ORDER'] ) )
		{
			// FJ added SQL constraint TITLE is not null.
			if ( ! isset( $columns['TITLE'] )
				|| ! empty( $columns['TITLE'] ) )
			{
				if ( isset( $columns['AMOUNT'] )
					&& ! is_numeric( $columns['AMOUNT'] ) )
				{
					$error[] = _( 'Please enter a valid Amount.' );

					continue;
				}

				// Update Element / Category.
				if ( $id !== 'new' )
				{
					if ( isset( $columns['CATEGORY_ID'] )
						&& $columns['CATEGORY_ID'] != $_REQUEST['category_id'] )
					{
						$_REQUEST['category_id'] = $columns['CATEGORY_ID'];
					}

					$sql = 'UPDATE ' . DBEscapeIdentifier( $table ) . ' SET ';

					foreach ( (array) $columns as $column => $value )
					{
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					}

					$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";

					$go = true;
				}
				// New Element / Category.
				// New: check for Title & Amount.
				elseif ( $columns['TITLE'] )
				{
					$sql = 'INSERT INTO ' . DBEscapeIdentifier( $table ) . ' ';

					// New Element.
					if ( $table === 'billing_elements' )
					{
						if ( isset( $columns['CATEGORY_ID'] ) )
						{
							$_REQUEST['category_id'] = $columns['CATEGORY_ID'];

							unset( $columns['CATEGORY_ID'] );
						}

						$fields = 'CATEGORY_ID,SYEAR,';

						$values = "'" . $_REQUEST['category_id'] . "','" . UserSyear() . "',";
					}
					// New Category.
					elseif ( $table === 'billing_categories' )
					{
						$fields = "";

						$values = "";
					}

					// School.
					$fields .= 'SCHOOL_ID,';

					$values .= "'" . UserSchool() . "',";

					$go = false;

					foreach ( (array) $columns as $column => $value )
					{
						if ( ! empty( $value )
							|| $value == '0' )
						{
							$fields .= DBEscapeIdentifier( $column ) . ',';

							$values .= "'" . $value . "',";

							$go = true;
						}
					}

					$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';
				}

				if ( $go )
				{
					DBQuery( $sql );

					if ( $id === 'new' )
					{
						if ( $table === 'billing_elements' )
						{
							if ( function_exists( 'DBLastInsertID' ) )
							{
								$_REQUEST['id'] = DBLastInsertID();
							}
							else
							{
								// @deprecated since RosarioSIS 9.2.1.
								$_REQUEST['id'] = DBGetOne( "SELECT LASTVAL();" );
							}
						}
						elseif ( $table === 'billing_categories' )
						{
							if ( function_exists( 'DBLastInsertID' ) )
							{
								$_REQUEST['category_id'] = DBLastInsertID();
							}
							else
							{
								// @deprecated since RosarioSIS 9.2.1.
								$_REQUEST['category_id'] = DBGetOne( "SELECT LASTVAL();" );
							}
						}
					}
				}
			}
			else
				$error[] = _( 'Please fill in the required fields' );
		}
		else
			$error[] = _( 'Please enter valid Numeric data.' );
	}

	// Unset tables & redirect URL.
	RedirectURL( [ 'tables' ] );
}

// Delete Element / Category.
if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( intval( $_REQUEST['id'] ) > 0 )
	{
		if ( DeletePrompt( dgettext( 'Billing_Elements', 'Element' ) ) )
		{
			$delete_sql = "DELETE FROM billing_student_elements
				WHERE ELEMENT_ID='" . (int) $_REQUEST['id'] . "';";

			$delete_sql .= "DELETE FROM billing_elements
				WHERE ID='" . (int) $_REQUEST['id'] . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "';";

			DBQuery( $delete_sql );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}
	elseif ( isset( $_REQUEST['category_id'] )
		&& intval( $_REQUEST['category_id'] ) > 0
		&& ! BillingCategoryHasElements( $_REQUEST['category_id'] ) )
	{
		if ( DeletePrompt( dgettext( 'Billing_Elements', 'Category' ) ) )
		{
			DBQuery( "DELETE FROM billing_categories
				WHERE ID='" . (int) $_REQUEST['category_id'] . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			// Unset modfunc & category ID redirect URL.
			RedirectURL( [ 'modfunc', 'category_id' ] );
		}
	}
}

// Purchase Element (Student or its Parents).
if ( $_REQUEST['modfunc'] === 'purchase'
	&& BillingElementCanPurchase( $_REQUEST['id'], UserStudentID() ) )
{
	$_ROSARIO['allow_edit'] = true;

	$comments_input = TextInput(
		'',
		'purchase_comments',
		dgettext( 'Billing_Elements', 'Leave a comment' ),
		'maxlength="1000" size="25"'
	);

	if ( BillingElementStudentAlreadyEnrolled( $_REQUEST['id'], UserStudentID() ) )
	{
		$warning_confirm[] = dgettext( 'Billing_Elements', 'You are already enrolled in this course.' );

		$comments_input = ErrorMessage( $warning_confirm, 'warning' ) . $comments_input;
	}

	$confirm_ok = Prompt(
		'Confirm',
		dgettext( 'Billing_Elements', 'Do you want to purchase that element?' ),
		$comments_input
	);

	$_ROSARIO['allow_edit'] = false;

	if ( $confirm_ok )
	{
		$purchase_ok = BillingElementPurchase( $_REQUEST['id'], UserStudentID() );

		if ( $purchase_ok )
		{
			$student_balance = BillingElementsStudentBalance( UserStudentID() );

			$note[] = button( 'check' ) . '&nbsp;' .
				sprintf(
					dgettext( 'Billing_Elements', 'This element was purchased. New balance: %s' ),
					Currency( $student_balance )
				);

			$purchase_enrolled = BillingElementCourseEnroll( $_REQUEST['id'], UserStudentID() );

			if ( $purchase_enrolled )
			{
				$note[] = dgettext( 'Billing_Elements', 'You have been enrolled in the course.' );
			}

			// Hook.
			do_action( 'Billing_Elements/Elements.php|purchase_element' );
		}

		RedirectURL( 'modfunc' );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	if ( User( 'PROFILE' ) !== 'admin'
		&& ! empty( $_REQUEST['id'] ) )
	{
		if ( empty( $purchase_enrolled )
			&& BillingElementStudentAlreadyEnrolled( $_REQUEST['id'], UserStudentID() ) )
		{
			if ( User( 'PROFILE' ) === 'student' )
			{
				$warning[] = dgettext( 'Billing_Elements', 'You are already enrolled in this course.' );
			}
			else
			{
				$warning[] = dgettext( 'Billing_Elements', 'You child is already enrolled in this course.' );
			}
		}

		if ( BillingElementGradeLevelRestricted( $_REQUEST['id'], UserStudentID() ) )
		{
			if ( User( 'PROFILE' ) === 'student' )
			{
				$warning[] = dgettext( 'Billing_Elements', 'You are not enrolled in the right Grade Level to purchase this element.' );
			}
			else
			{
				$warning[] = dgettext( 'Billing_Elements', 'You child is not enrolled in the right Grade Level to purchase this element.' );
			}
		}
		elseif ( empty( $purchase_ok )
			&& ! BillingElementHasFunds( $_REQUEST['id'], UserStudentID() ) )
		{
			$warning[] = sprintf(
				dgettext( 'Billing_Elements', 'You do not have sufficient funds to purchase this element. Balance: %s' ),
				Currency( BillingElementsStudentBalance( UserStudentID() ) )
			);
		}
	}

	if ( empty( $purchase_enrolled )
		&& BillingElementCoursePeriodFull( $_REQUEST['id'] ) )
	{
		$warning[] = dgettext( 'Billing_Elements', 'There are no available seats in this course.' );
	}

	echo ErrorMessage( $error );

	echo ErrorMessage( $warning, 'warning' );

	echo ErrorMessage( $note, 'note' );

	$RET = [];

	// ADDING & EDITING FORM.
	if ( ! empty( $_REQUEST['id'] )
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = BillingGetElement( $_REQUEST['id'] );

		if ( ! $RET )
		{
			RedirectURL( [ 'id', 'category_id' ] );
		}
		else
		{
			$title = $RET['TITLE'];

			// Set Category ID if not set yet.
			if ( empty( $_REQUEST['category_id'] ) )
			{
				$_REQUEST['category_id'] =  $RET['CATEGORY_ID'];
			}
		}
	}
	elseif ( ! empty( $_REQUEST['category_id'] )
		&& $_REQUEST['category_id'] !== 'new'
		&& empty( $_REQUEST['id'] ) )
	{
		$RET = DBGet( "SELECT ID AS CATEGORY_ID,TITLE,SORT_ORDER
			FROM billing_categories
			WHERE ID='" . (int) $_REQUEST['category_id'] . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );

		if ( ! $RET )
		{
			RedirectURL( 'category_id' );
		}
		else
		{
			$RET = $RET[1];

			$title = $RET['TITLE'];
		}
	}
	elseif ( ! empty( $_REQUEST['id'] )
		&& $_REQUEST['id'] === 'new' )
	{
		$title = dgettext( 'Billing_Elements', 'New Element' );

		$RET['ID'] = 'new';

		$RET['CATEGORY_ID'] = isset( $_REQUEST['category_id'] ) ? $_REQUEST['category_id'] : null;
	}
	elseif ( $_REQUEST['category_id'] === 'new' )
	{
		$title = dgettext( 'Billing_Elements',  'New Category' );

		$RET['CATEGORY_ID'] = 'new';
	}

	echo BillingGetElementsForm(
		$title,
		$RET,
		isset( $extra_fields ) ? $extra_fields : []
	);

	if ( ! empty( $_REQUEST['id'] )
		&& $_REQUEST['id'] !== 'new' )
	{
		if ( User( 'PROFILE' ) === 'admin' )
		{
			$assignations = BillingElementAssignations( $_REQUEST['id'] );

			echo DrawHeader(
				sprintf(
					dgettext( 'Billing_Elements',  '%d assigned' ),
					$assignations
				),
				BillingElementAssignButton( $_REQUEST['id'] )
			);
		}
		elseif ( BillingElementCanPurchase( $_REQUEST['id'], UserStudentID() ) )
		{
			// Parent or Student, purchase Element.
			$purchased = BillingElementPurchased( $_REQUEST['id'], UserStudentID() );

			echo DrawHeader(
				sprintf(
					dgettext( 'Billing_Elements',  '%d purchases' ),
					$purchased
				),
				BillingElementPurchaseButton( $_REQUEST['id'], UserStudentID() )
			);
		}
	}

	$where_not_empty_sql = '';

	if ( User( 'PROFILE' ) !== 'admin' )
	{
		$where_not_empty_sql = " AND EXISTS(SELECT 1
			FROM billing_elements
			WHERE CATEGORY_ID=billing_categories.ID)";
	}

	// CATEGORIES.
	$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM billing_categories
		WHERE SCHOOL_ID='" . UserSchool() . "'" . $where_not_empty_sql .
		" ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

	// DISPLAY THE MENU.
	echo '<div class="st">';

	BillingElementsMenuOutput( $categories_RET, $_REQUEST['category_id'] );

	echo '</div>';

	// DOCUMENTS.
	if ( ! empty( $_REQUEST['category_id'] )
		&& $_REQUEST['category_id'] !=='new'
		&& $categories_RET )
	{
		$elements_RET = DBGet( "SELECT ID,TITLE,REF
			FROM billing_elements
			WHERE CATEGORY_ID='" . (int) $_REQUEST['category_id'] . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY TITLE" );

		echo '<div class="st">';

		BillingElementsMenuOutput( $elements_RET, $_REQUEST['id'], $_REQUEST['category_id'] );

		echo '</div>';
	}
}


