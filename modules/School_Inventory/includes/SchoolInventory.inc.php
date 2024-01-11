<?php
/**
 * School Inventory include
 *
 * @package School Inventory module
 */

echo '<form action="' . ( function_exists( 'URLEscape' ) ?
	URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=' . $_REQUEST['category_id'] .
		'&category_type=' . $_REQUEST['category_type'] .
		'&modfunc=save' ) :
	_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=' . $_REQUEST['category_id'] .
		'&category_type=' . $_REQUEST['category_type'] .
		'&modfunc=save' ) ) . '" method="POST" enctype="multipart/form-data">';

$category_types = [
	'CATEGORY' => _( 'Category' ),
	'STATUS' => _( 'Status' ),
	'LOCATION' => dgettext( 'School_Inventory', 'Location' ),
	// 'WORK_ORDER' => _( 'Work Order' ),
	'USER_ID' => dgettext( 'School_Inventory', 'Person' ),
];

if ( isset( $_REQUEST['category_id'] )
	&& isset( $_REQUEST['category_type'] )
	&& ( ( array_key_exists( $_REQUEST['category_type'], $category_types )
			&& GetSICategory( $_REQUEST['category_id'], $_REQUEST['category_type'] ) )
		|| $_REQUEST['category_id'] === '-1' ) )
{
	if ( array_key_exists( $_REQUEST['category_type'], $category_types ) )
	{
		// Display Category title in header.
		$category = GetSICategory( $_REQUEST['category_id'], $_REQUEST['category_type'] );

		$category_header = $category_types[ $category['CATEGORY_TYPE'] ] . ': <b>' .
			$category['TITLE'] . '</b>';
	}
	else
	{
		// All items.
		$category_header = '<b>' . dgettext( 'School_Inventory', 'All Items' ) . '</b>';
	}

	$back_link = PreparePHP_SELF(
		array_rwalk( $_GET, 'strip_tags' ),
		[ 'category_id', 'category_type', 'modfunc' ]
	);

	DrawHeader(
		'<a href="' . $back_link . '">&laquo; ' . dgettext( 'School_Inventory', 'Back' ) . '</a>',
		SubmitButton()
	);

	DrawHeader( $category_header );

	// Display items, filtered by that category.
	$items_RET = GetSIItemsByCategory( $_REQUEST['category_id'], $_REQUEST['category_type'] );

	// Display list.
	$columns = [
		'TITLE' => _( 'Name' ),
		'QUANTITY' => dgettext( 'School_Inventory', 'Quantity' ),
		'FILE' => _( 'File' ),
		'COMMENTS' => _( 'Comments' ),
	];

	$columns += $category_types;

	$LO_options = [ 'add' => true ];

	$link = [];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=remove&category_id=' . $_REQUEST['category_id'] .
		'&category_type=' . $_REQUEST['category_type'];

	$link['remove']['variables'] = [ 'item_id' => 'ITEM_ID' ];

	$link['add']['html'] = [
		'TITLE' => MakeSITextInput( '', 'TITLE' ),
		'QUANTITY' => MakeSINumberInput( '', 'QUANTITY' ),
		'FILE' => MakeSIFileInput( '', 'FILE' ),
		'COMMENTS' => MakeSITextInput( '', 'COMMENTS' ),
		'CATEGORY' => MakeSICategorySelect( '', 'CATEGORY' ),
		'STATUS' => MakeSICategorySelect( '', 'STATUS' ),
		'LOCATION' => MakeSICategorySelect( '', 'LOCATION' ),
		'WORK_ORDER' => MakeSICategorySelect( '', 'WORK_ORDER' ),
		'USER_ID' => MakeSICategorySelect( '', 'USER_ID' ),
	];

	ListOutput(
		$items_RET,
		$columns,
		'Item',
		'Items',
		$link,
		[],
		$LO_options
	);
}
else
{
	$all_link = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=-1&category_type=-1';

	if ( ! empty( $_REQUEST['snapshot_id'] ) )
	{
		$all_link .= '&snapshot_id=' . $_REQUEST['snapshot_id'];
	}

	$items_total = GetSIItemsTotal();

	DrawHeader(
		'<b><a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( $all_link ) :
			_myURLEncode( $all_link ) ) . '">' . dgettext( 'School_Inventory', 'All Items' ) .
			'</a></b> (' . $items_total . ')',
		SubmitButton()
	);

	// Display Categories.
	// Categories.

	echo '<div class="st">';

	SICategoryItemsListOutput( 'CATEGORY', 'Category', 'Categories' );

	echo '</div>';

	// Statuses.
	echo '<div class="st">';

	SICategoryItemsListOutput( 'STATUS', 'Status', 'Statuses' );

	echo '</div>';

	// Locations.
	echo '<div class="st">';

	SICategoryItemsListOutput(
		'LOCATION',
		dgettext( 'School_Inventory', 'Location' ),
		dgettext( 'School_Inventory', 'Locations' )
	);

	echo '</div>';

	// Work orders.
	/*echo '<div class="st">';

	SICategoryItemsListOutput( 'WORK_ORDER', 'Work Order', 'Work Orders' );

	echo '</div>';*/

	// Person.
	echo '<div class="st">';

	SICategoryItemsListOutput(
		'USER_ID',
		dgettext( 'School_Inventory', 'Person' ),
		dgettext( 'School_Inventory', 'Persons' )
	);

	echo '</div>';
}

// Submit & Close Form.
echo '<br style="clear: left;" /><div class="center">' .
	SubmitButton() . '</div></form>';
