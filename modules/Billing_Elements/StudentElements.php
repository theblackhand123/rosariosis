<?php
/**
 * Student Billing Elements
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'modules/Billing_Elements/includes/Update.inc.php';
require_once 'modules/Billing_Elements/includes/common.fnc.php';
require_once 'modules/Billing_Elements/includes/Elements.fnc.php';
require_once 'modules/Student_Billing/functions.inc.php';

if ( empty( $_REQUEST['print_statements'] ) )
{
	DrawHeader( ProgramTitle() );

	Search( 'student_id', issetVal( $extra ) );
}

if ( ! empty( $_REQUEST['values'] )
	&& $_POST['values']
	&& AllowEdit()
	&& UserStudentID() )
{
	// Add eventual Dates to $_REQUEST['values'].
	AddRequestedDates( 'values', 'post' );

	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		// New: check for Title.
		if ( $columns['TITLE'] )
		{
			if ( ! is_numeric( $columns['AMOUNT'] ) )
			{
				$error[] = _( 'Please enter a valid Amount.' );

				continue;
			}

			$sql = "INSERT INTO billing_fees ";

			$fields = 'STUDENT_ID,SCHOOL_ID,SYEAR,ASSIGNED_DATE,';
			$values = "'" . UserStudentID() . "','" . UserSchool() . "','" . UserSyear() . "','" . DBDate() . "',";

			if ( version_compare( ROSARIO_VERSION, '11.2', '>=' ) )
			{
				// @since RosarioSIS 11.2 Add CREATED_BY column to billing_fees table
				$fields .= 'CREATED_BY,';
				$values .= "'" . DBEscapeString( User( 'NAME' ) ) . "',";
			}

			$go = 0;

			foreach ( (array) $columns as $column => $value )
			{
				if ( ! empty( $value ) || $value == '0' )
				{
					$fields .= DBEscapeIdentifier( $column ) . ',';
					$values .= "'" . $value . "',";
					$go = true;
				}
			}

			$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ');';

			if ( $go )
			{
				DBQuery( $sql );

				if ( function_exists( 'DBLastInsertID' ) )
				{
					$fee_id = DBLastInsertID();
				}
				else
				{
					// @deprecated since RosarioSIS 9.2.1.
					$fee_id = DBGetOne( "SELECT LASTVAL();" );
				}

				DBquery( "INSERT INTO billing_student_elements (STUDENT_ID,ELEMENT_ID,FEE_ID)
					VALUES('" . UserStudentID() . "','" . $_REQUEST['element_id'] . "','" . $fee_id . "');" );
			}
		}
	}

	// Unset values & redirect URL.
	RedirectURL( 'values' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( dgettext( 'Billing_Elements', 'Element and Fee' ) ) )
	{
		$delete_sql = "DELETE FROM billing_fees
			WHERE ID='" . (int) $_REQUEST['id'] . "';";

		$delete_sql .= "DELETE FROM billing_fees
			WHERE WAIVED_FEE_ID='" . (int) $_REQUEST['id'] . "';";

		$delete_sql .= "DELETE FROM billing_student_elements
			WHERE FEE_ID='" . (int) $_REQUEST['id'] . "';";

		DBQuery( $delete_sql );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( UserStudentID()
	&& ! $_REQUEST['modfunc'] )
{
	$functions = [
		'REMOVE' => '_makeElementsRemove',
		'ASSIGNED_DATE' => 'ProperDate',
		'DUE_DATE' => 'ProperDate',
		'AMOUNT' => 'Currency',
	];

	$elements_RET = DBGet( "SELECT '' AS REMOVE,f.ID,f.TITLE,f.ASSIGNED_DATE,
		f.DUE_DATE,f.COMMENTS,f.AMOUNT,f.WAIVED_FEE_ID
		FROM billing_fees f,billing_student_elements bse
		WHERE f.STUDENT_ID='" . UserStudentID() . "'
		AND f.SYEAR='" . UserSyear() . "'
		AND f.WAIVED_FEE_ID IS NULL
		AND f.STUDENT_ID=bse.STUDENT_ID
		AND f.ID=bse.FEE_ID
		ORDER BY f.ASSIGNED_DATE", $functions );

	$i = 1;

	$columns = [];

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] )
		&& $elements_RET )
	{
		$columns = [ 'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>' ];
	}

	$columns += [
		'TITLE' => dgettext( 'Billing_Elements', 'Element and Fee' ),
		'AMOUNT' => _( 'Amount' ),
		'ASSIGNED_DATE' => _( 'Assigned' ),
		'DUE_DATE' => _( 'Due' ),
		'COMMENTS' => _( 'Comment' ),
	];

	$link = [];

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$billing_elements = BillingGetElements();

		$element_options = BillingElementSelectOptions( $billing_elements );

		$elements_js = BillingElementSelectJSList( $billing_elements );
		?>
		<script>
			var billingElements = <?php echo $elements_js; ?>;
		</script>
		<?php

		echo BillingElementSelectFillTitleAmountJS( 'valuesnewTITLE', 'valuesnewAMOUNT' );

		$link['add']['html'] = [
			'REMOVE' => button( 'add' ),
			'TITLE' => SelectInput(
				'',
				'element_id',
				'',
				$element_options,
				'N/A',
				'style="max-width: 200px;" required group autocomplete="off" onchange="billingElementSelectFillTitleAmount(this.value);"'
			) . ' ' . _makeFeesTextInput( '', 'TITLE' ),
			'AMOUNT' => _makeFeesTextInput( '', 'AMOUNT' ),
			'ASSIGNED_DATE' => ProperDate( DBDate() ),
			'DUE_DATE' => _makeFeesDateInput( '', 'DUE_DATE' ),
			'COMMENTS' => _makeFeesTextInput( '', 'COMMENTS' ),
		];
	}

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		echo '<form action="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&student_id=' . UserStudentID() ) :
			_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&student_id=' . UserStudentID() ) ) . '" method="POST">';

		if ( AllowEdit() )
		{
			DrawHeader( '', SubmitButton() );
		}

		$options = [];
	}
	else
	{
		$options = [ 'center' => false ];
	}

	ListOutput( $elements_RET, $columns, 'Fee', 'Fees', $link, [], $options );

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		echo '<div class="center">' . SubmitButton() . '</div>';
	}

	echo '<br />';

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$elements_total = DBGetOne( "SELECT SUM(bf.AMOUNT) AS TOTAL
			FROM billing_fees bf,billing_student_elements bse
			WHERE bf.STUDENT_ID='" . UserStudentID() . "'
			AND bf.SYEAR='" . UserSyear() . "'
			AND bse.STUDENT_ID=bf.STUDENT_ID
			AND bf.ID=bse.FEE_ID" );

		$table = '<table class="align-right"><tr><td>' . dgettext( 'Billing_Elements', 'Total from Elements' ) . ': ' . '</td><td>' . Currency( $elements_total ) . '</td></tr></table>';

		DrawHeader( $table );

		echo '</form>';
	}
}


function _makeElementsRemove( $value, $column )
{
	global $THIS_RET;

	return button(
		'remove',
		_( 'Delete' ),
		'"' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&id=' . $THIS_RET['ID'] . '&student_id=' . UserStudentID() ) :
			_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&id=' . $THIS_RET['ID'] . '&student_id=' . UserStudentID() ) ) . '"'
	);
}
