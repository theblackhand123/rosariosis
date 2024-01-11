<?php
/**
 * Billing Elements common functions
 *
 * @package Billing Elements module
 */

/**
 * Get Element details from DB
 *
 * @param int     $element_id Element ID.
 * @param boolean $reset      Reset flag to reset cache.
 *
 * @return array Element details.
 */
function BillingGetElement( $element_id, $reset = false )
{
	static $elements = [];

	if ( (string) (int) $element_id != $element_id
		|| $element_id < 1 )
	{
		return [];
	}

	if ( isset( $elements[ $element_id ] )
		&& ! $reset )
	{
		return $elements[ $element_id ];
	}

	$element_RET = DBGet( "SELECT ID,CATEGORY_ID,TITLE,REF,AMOUNT,
		DESCRIPTION,GRADE_LEVELS,COURSE_PERIOD_ID,ROLLOVER,CREATED_AT,UPDATED_AT,
		CONCAT(coalesce(NULLIF(CONCAT(REF,' - '),' - '),''),TITLE) AS REF_TITLE,
		(SELECT TITLE
			FROM billing_categories
			WHERE ID=CATEGORY_ID) AS CATEGORY_TITLE
		FROM billing_elements
		WHERE ID='" . (int) $element_id . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	$elements[ $element_id ] = ( ! $element_RET ? [] : $element_RET[1] );

	return $elements[ $element_id ];
}

/**
 * Get all Elements from DB for current School and Year.
 *
 * @return array Elements.
 */
function BillingGetElements()
{
	$elements_RET = DBGet( "SELECT be.ID,be.TITLE,be.REF,be.AMOUNT,be.DESCRIPTION,GRADE_LEVELS,ROLLOVER,bc.TITLE AS CATEGORY
		FROM billing_elements be,billing_categories bc
		WHERE be.SYEAR='" . UserSyear() . "'
		AND be.SCHOOL_ID='" . UserSchool() . "'
		AND be.CATEGORY_ID=bc.ID
		ORDER BY bc.SORT_ORDER IS NULL,bc.SORT_ORDER,CATEGORY,be.REF,be.TITLE" );

	return $elements_RET;
}

/**
 * JS json encoded list of Elements for Select input.
 *
 * @param array $billing_elements Elements array from DB.
 *
 * @return string Json encoded list of Elements.
 */
function BillingElementSelectJSList( $billing_elements )
{
	$elements = [];

	foreach ( (array) $billing_elements as $element )
	{
		$element_title = $element['REF'] ?
			$element['REF'] . ' - ' . $element['TITLE'] :
			$element['TITLE'];

		$elements[ $element['ID'] ] = [
			'AMOUNT' => $element['AMOUNT'],
			'TITLE' => $element_title,
		];
	}

	return json_encode( $elements );
}

/**
 * Javascript to Fill Title and Amount fields from select input.
 *
 * @param string $title_input_id  Title input ID.
 * @param string $amount_input_id Amount input ID.
 *
 * @return string Javascript for billingElementSelectFillTitleAmount() function.
 */
function BillingElementSelectFillTitleAmountJS( $title_input_id, $amount_input_id )
{
	?>
	<script>
		var billingElementSelectFillTitleAmount = function( element_id ) {
			var $title = $( '#' + <?php echo json_encode( $title_input_id ); ?> ),
				$amount = $( '#' + <?php echo json_encode( $amount_input_id ); ?> );

			if ( element_id <= 0 ) {
				$title.val( '' );
				$amount.val( '' );

				return;
			}

			var element = billingElements[ element_id ];

			$title.val( element['TITLE'] );
			$amount.val( element['AMOUNT'] );
		};
	</script>
	<?php
}

/**
 * Javascript to Fill Title and Amount fields from select input.
 *
 * @return string Javascript for billingElementSelectFillTitleAmount() function.
 */
function BillingElementSelectFillTitleAmountMultipleJS()
{
	?>
	<script>
		var billingElementSelectFillTitleAmount = function( element_id, input_number ) {
			var $title = $( '#title' + input_number ),
				$amount = $( '#amount' + input_number );

			if ( element_id <= 0 ) {
				 $( '#elements_id' + input_number ).val( '' );
				$title.val( '' );
				$amount.val( '' );

				return;
			}

			var element = billingElements[ element_id ];

			$title.val( element['TITLE'] );
			$amount.val( element['AMOUNT'] );
		};

		billingElementSelectFillTitleAmount( <?php echo json_encode(
			issetVal( $_REQUEST['element_id'] )
		); ?>, 1 );
	</script>
	<?php
}

/**
 * Javascript to add new Element inputs
 *
 * @since 3.0 Assign multiple Elements at once
 *
 * @return string Javascript for billingElementAddNew() function.
 */
function BillingElementAddNewJS()
{
	?>
	<script>
		var billingElementAddNew = function( i ) {
			var elementInputsClone = $( '<div>' ).append( $('#element_inputs' + i).clone() ).html(),
				j = i + 1;

			var elementInputsNew = elementInputsClone.replace( new RegExp( "\\[" + i + "\\]", 'g' ), '[' + j + ']' )
				.replace( new RegExp( 'element_inputs' + i, 'g' ), 'element_inputs' + j )
				.replace( new RegExp( 'elements_id' + i, 'g' ), 'elements_id' + j )
				.replace( new RegExp( 'title' + i, 'g' ), 'title' + j )
				.replace( new RegExp( 'amount' + i, 'g' ), 'amount' + j )
				.replace( new RegExp( 'comments' + i, 'g' ), 'comments' + j )
				.replace( new RegExp( 'Select' + i, 'g' ), 'Select' + j ) // Due date inputs.
				.replace( new RegExp( 'trigger' + i, 'g' ), 'trigger' + j ); // Due date icon.

			$( '<hr />' + elementInputsNew ).insertAfter('#element_inputs' + i);

			billingElementSelectFillTitleAmount( 0, j );

			// Setup new calendar JS on icon click.
			JSCalendarSetup();
		};
	</script>
	<?php
}

/**
 * Elements select options
 *
 * @param array $billing_elements Elements array from DB.
 *
 * @return array Select options.
 */
function BillingElementSelectOptions( $billing_elements )
{
	$element_options = [];

	foreach ( (array) $billing_elements as $element )
	{
		if ( ! isset( $element_options[ $element['CATEGORY'] ] ) )
		{
			$element_options[ $element['CATEGORY'] ] = [];
		}

		$element_title = $element['REF'] ?
			$element['REF'] . ' - ' . $element['TITLE'] :
			$element['TITLE'];

		$element_options[ $element['CATEGORY'] ][ $element['ID'] ] = $element_title;
	}

	return $element_options;
}
