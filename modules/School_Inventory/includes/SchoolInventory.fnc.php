<?php
/**
 * School Inventory functions
 *
 * @package School Inventory module
 */

/**
 * Get Category by ID and type, for current school.
 *
 * @example GetSICategory( $category_id, $category_type );
 *
 * @param string $category_id   Category ID.
 * @param string $category_type Category Type.
 *
 * @return array Array with category details. Empty if not found.
 */
function GetSICategory( $category_id, $category_type )
{
	static $categories = [];

	$category_id = (int) $category_id;

	if ( $category_id < -1 )
	{
		return [];
	}

	if ( $category_id < 1
		&& ! $category_type )
	{
		return [];
	}

	// N/A or All.
	if ( $category_id < 1 )
	{
		return [
			'CATEGORY_ID' => $category_id,
			'SCHOOL_ID' => UserSchool(),
			'TITLE' => ( $category_id < 0 ? _( 'All' ) : _( 'N/A' ) ),
			'CATEGORY_TYPE' => $category_type,
			'SORT_ORDER' => ( $category_id < 0 ? 0 : '' ),
		];
	}

	if ( isset( $categories[ $category_id ] ) )
	{
		return $categories[ $category_id ];
	}

	$table = 'school_inventory_categories';

	$where_sql = '';

	if ( ! empty( $_REQUEST['snapshot_id'] ) )
	{
		$table = 'school_inventory_snapshot_categories';

		$where_sql = " AND SNAPSHOT_ID='" . (int) $_REQUEST['snapshot_id'] . "'";
	}

	$categories[ $category_id ] = DBGet( "SELECT CATEGORY_ID,SCHOOL_ID,TITLE,CATEGORY_TYPE,SORT_ORDER
		FROM " . DBEscapeIdentifier( $table ) . "
		WHERE CATEGORY_ID='" . (int) $category_id . "'
		AND SCHOOL_ID='" . UserSchool() . "'" . $where_sql );

	$categories[ $category_id ] = $categories[ $category_id ][1];

	return $categories[ $category_id ];
}


/**
 * Get Categories by type, for current school.
 *
 * @example $statuses = GetSICategories( 'STATUS' );
 *
 * @param string  $type     Category Type: CATEGORY|STATUS|LOCATION|WORK_ORDER|USER_ID.
 *
 * @return array  Categories details belonging to this type + All & N/A options.
 */
function GetSICategories( $category_type )
{
	static $categories = [];

	if ( ! $category_type )
	{
		return [];
	}

	if ( isset( $categories[ $category_type ] ) )
	{
		return $categories[ $category_type ];
	}

	// Reactivate custom REMOVE column if N/A / All options.
	/*$functions = array(
		'REMOVE' => 'MakeSICategoryRemove',
	);*/

	$table = 'school_inventory_categories';

	$from_count_sql = 'school_inventory_categoryxitem sicxi,school_inventory_items sii';

	$where_sql = $where_count_sql = $select_sql = '';

	if ( ! empty( $_REQUEST['snapshot_id'] ) )
	{
		$table = 'school_inventory_snapshot_categories';

		$where_sql = " AND SNAPSHOT_ID='" . (int) $_REQUEST['snapshot_id'] . "'";

		$from_count_sql = 'school_inventory_snapshot_categoryxitem sicxi,school_inventory_snapshot_items sii';

		$where_count_sql = " AND sicxi.SNAPSHOT_ID='" . (int) $_REQUEST['snapshot_id'] . "'
			AND sii.SNAPSHOT_ID='" . (int) $_REQUEST['snapshot_id'] . "'";

		$select_sql = "SNAPSHOT_ID,";
	}

	$categories_RET = DBGet( "SELECT CATEGORY_ID,SCHOOL_ID,
		'' AS REMOVE,TITLE,CATEGORY_TYPE,SORT_ORDER," . $select_sql .
		// (SELECT SUM(sii.QUANTITY) for Total of quantities, not Items.
		"(SELECT COUNT(sii.ITEM_ID)
			FROM " . $from_count_sql . "
			WHERE sicxi.CATEGORY_TYPE=sic.CATEGORY_TYPE
			AND sic.CATEGORY_ID=sicxi.CATEGORY_ID
			AND sicxi.ITEM_ID=sii.ITEM_ID
			AND sii.SCHOOL_ID='" . UserSchool() . "'" . $where_count_sql . ") AS TOTAL
		FROM " . DBEscapeIdentifier( $table ) . " sic
		WHERE CATEGORY_TYPE='" . $category_type . "'
		AND SCHOOL_ID='" . UserSchool() . "'" . $where_sql . "
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

	/*$total = $total_na = '0';

	foreach ( (array) $categories_RET as $category )
	{
		$total += $category['TOTAL'];
	}

	// All option.
	$all_option = array(
		'CATEGORY_ID' => '-1',
		'CATEGORY_TYPE' => $category_type,
		'SCHOOL_ID' => UserSchool(),
		'TITLE' => _( 'All' ),
		'SORT_ORDER' => '0',
		'TOTAL' => $total,
	);

	// N/A Total.
	$total_na_RET = DBGet( "SELECT COUNT(DISTINCT sii.ITEM_ID) AS TOTAL_NA
		FROM school_inventory_categoryxitem sicxi,school_inventory_items sii
		WHERE NOT EXISTS(SELECT sicxi.ITEM_ID
			FROM school_inventory_categoryxitem sicxi
			WHERE sicxi.CATEGORY_TYPE='" . $category_type . "'
			AND sicxi.ITEM_ID=sii.ITEM_ID)
		AND sicxi.ITEM_ID=sii.ITEM_ID
		AND sii.SCHOOL_ID='" . UserSchool() . "'" );

	$total_na = $total_na_RET[1]['TOTAL_NA'];

	// N/A option.
	$na_option = array(
		'CATEGORY_ID' => '',
		'CATEGORY_TYPE' => $category_type,
		'SCHOOL_ID' => UserSchool(),
		'TITLE' => _( 'N/A' ),
		'SORT_ORDER' => '',
		'TOTAL' => $total_na,
	);

	$categories[ $category_type ] = array();

	if ( $total
		&& count( (array) $categories_RET ) > 1 )
	{
		// Add All option.
		$categories[ $category_type ][0] = $all_option;

		$categories[ $category_type ] += (array) $categories_RET;

		// Start with keyf 1.
		array_unshift( $categories[ $category_type ], null );

		unset( $categories[ $category_type ][0] );

		// Add N/A option.
		$categories[ $category_type ][] = $na_option;
	}
	elseif ( $categories_RET )
	{
		$categories[ $category_type ] += (array) $categories_RET;

		// Add N/A option.
		$categories[ $category_type ][] = $na_option;
	}
	else
	{
		$categories[ $category_type ][1] = $na_option;
	}*/

	$categories[ $category_type ] = $categories_RET;

	// var_dump($categories[ $category_type ]);
	return $categories[ $category_type ];
}


/**
 * Get Items by Category by ID and type, for current school.
 *
 * @example GetSIItemsByCategory( $category_id, $category_type );
 *
 * @param string $category_id   Category ID.
 * @param string $category_type Category Type.
 *
 * @return array Array with category items. Empty if not found.
 */
function GetSIItemsByCategory( $category_id, $category_type )
{
	$category_id = (int) $category_id;

	if ( $category_id < -1
		|| ( $category_id > -1
			&& ! $category_type ) )
	{
		return [];
	}

	$functions = [
		'TITLE' => 'MakeSITextInput',
		'QUANTITY' => 'MakeSINumberInput',
		'FILE' => 'MakeSIFileInput',
		'COMMENTS' => 'MakeSITextInput',
		'CATEGORY' => 'MakeSICategorySelect',
		'STATUS' => 'MakeSICategorySelect',
		'LOCATION' => 'MakeSICategorySelect',
		'WORK_ORDER' => 'MakeSICategorySelect',
		'USER_ID' => 'MakeSICategorySelect',
	];

	$sql_category_where = "sicxi.CATEGORY_ID='" . (int) $category_id . "'
		AND sicxi.CATEGORY_TYPE='" . $category_type . "'
		AND sicxi.ITEM_ID=sii.ITEM_ID";

	if ( ! $category_id )
	{
		// N/A.
		$sql_category_where = "NOT EXISTS(SELECT sicxi.ITEM_ID
			FROM school_inventory_categoryxitem sicxi
			WHERE sicxi.CATEGORY_TYPE='" . $category_type . "'
			AND sicxi.ITEM_ID=sii.ITEM_ID)
			AND sicxi.ITEM_ID=sii.ITEM_ID";
	}
	elseif ( $category_id === -1
		&& $category_type === '-1' )
	{
		// All Items, all categories.
		$sql_category_where = "TRUE";
	}
	elseif ( $category_id === -1 )
	{
		// All.
		$sql_category_where = "sicxi.CATEGORY_TYPE='" . $category_type . "'
			AND sicxi.ITEM_ID=sii.ITEM_ID";
	}

	$table_sub = 'school_inventory_categoryxitem';

	$from_sql = 'school_inventory_items sii,school_inventory_categoryxitem sicxi';

	$where_sub_sql = '';

	if ( ! empty( $_REQUEST['snapshot_id'] ) )
	{
		$table_sub = 'school_inventory_snapshot_categoryxitem';

		$where_sub_sql = " AND SNAPSHOT_ID='" . (int) $_REQUEST['snapshot_id'] . "'";

		$from_sql = 'school_inventory_snapshot_items sii,school_inventory_snapshot_categoryxitem sicxi';

		$sql_category_where .= " AND sicxi.SNAPSHOT_ID='" . (int) $_REQUEST['snapshot_id'] . "'
			AND sii.SNAPSHOT_ID='" . (int) $_REQUEST['snapshot_id'] . "'";
	}

	/**
	 * Fix SQL error SELECT DISTINCT, ORDER BY expressions must appear in select list
	 * when ORDER BY sii.SORT_ORDER IS NULL,sii.SORT_ORDER
	 *
	 * @link https://stackoverflow.com/questions/12693089/pgerror-select-distinct-order-by-expressions-must-appear-in-select-list
	 */
	$category_items_RET = DBGet( "SELECT DISTINCT sii.ITEM_ID,SCHOOL_ID,TITLE,QUANTITY,FILE,COMMENTS,SORT_ORDER,
		(SELECT CATEGORY_ID
			FROM " . DBEscapeIdentifier( $table_sub ) . "
			WHERE sii.ITEM_ID=ITEM_ID
			AND CATEGORY_TYPE='CATEGORY'" . $where_sub_sql . "
			LIMIT 1) AS CATEGORY,
		(SELECT CATEGORY_ID
			FROM " . DBEscapeIdentifier( $table_sub ) . "
			WHERE sii.ITEM_ID=ITEM_ID
			AND CATEGORY_TYPE='STATUS'" . $where_sub_sql . "
			LIMIT 1) AS STATUS,
		(SELECT CATEGORY_ID
			FROM " . DBEscapeIdentifier( $table_sub ) . "
			WHERE sii.ITEM_ID=ITEM_ID
			AND CATEGORY_TYPE='LOCATION'" . $where_sub_sql . "
			LIMIT 1) AS LOCATION,
		(SELECT CATEGORY_ID
			FROM " . DBEscapeIdentifier( $table_sub ) . "
			WHERE sii.ITEM_ID=ITEM_ID
			AND CATEGORY_TYPE='WORK_ORDER'" . $where_sub_sql . "
			LIMIT 1) AS WORK_ORDER,
		(SELECT CATEGORY_ID
			FROM " . DBEscapeIdentifier( $table_sub ) . "
			WHERE sii.ITEM_ID=ITEM_ID
			AND CATEGORY_TYPE='USER_ID'" . $where_sub_sql . "
			LIMIT 1) AS USER_ID
		FROM " . $from_sql . "
		WHERE " . $sql_category_where .  "
		AND sii.SCHOOL_ID='" . UserSchool() . "'
		ORDER BY sii.SORT_ORDER,sii.TITLE", $functions );

	return $category_items_RET;
}


/**
 * Get items total, for the current school.
 *
 * @return int Total.
 */
function GetSIItemsTotal()
{
	$table = 'school_inventory_items';

	$where_sql = '';

	if ( ! empty( $_REQUEST['snapshot_id'] ) )
	{
		$table = 'school_inventory_snapshot_items';

		$where_sql = " AND SNAPSHOT_ID='" . (int) $_REQUEST['snapshot_id'] . "'";
	}

	$items_total = DBGetOne( "SELECT COUNT(ITEM_ID) AS TOTAL
		FROM " . DBEscapeIdentifier( $table ) . "
		WHERE SCHOOL_ID='" . UserSchool() . "'" . $where_sql );

	return (int) $items_total;
}


/**
 * Output list of items belonging to a category.
 *
 * @example SICategoryItemsListOutput( 'STATUS', 'Status', 'Statuses' );
 *
 * @uses ListOutput()
 * @uses GetSICategories()
 *
 * @param string $category_type Category type.
 * @param string $singular      Category type name, singular.
 * @param string $plural        Category type name, plural.
 */
function SICategoryItemsListOutput( $category_type, $singular, $plural )
{
	$categories_RET = GetSICategories( $category_type );

	// Display list.
	$columns = [
		/*'REMOVE' => '',*/
		'TITLE' => _( $singular ),
		'TOTAL' => _( 'Total' ),
	];

	$LO_options = [
		'save' => false,
		'search' => false,
		'add' => true,
		'responsive' => false,
	];

	$link = [];

	$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'];
	$link['TITLE']['variables'] = [
		'category_id' => 'CATEGORY_ID',
		'category_type' => 'CATEGORY_TYPE',
	];

	if ( ! empty( $_REQUEST['snapshot_id'] ) )
	{
		$link['TITLE']['variables']['snapshot_id'] = 'SNAPSHOT_ID';
	}

	$link['add']['html'] = [
		'TITLE' => MakeSITextInput( '', 'TITLE', $category_type ),
	];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
	$link['remove']['variables'] = [ 'category_id' => 'CATEGORY_ID' ];

	ListOutput(
		$categories_RET,
		$columns,
		$singular,
		$plural,
		$link,
		[],
		$LO_options
	);
}


function MakeSITextInput( $value, $column, $category_type = '' )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ITEM_ID'] ) )
	{
		$id = $THIS_RET['ITEM_ID'];
	}
	elseif ( ! empty( $THIS_RET['CATEGORY_ID'] ) )
	{
		$id = $THIS_RET['CATEGORY_ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $column === 'TITLE' )
	{
		$extra = 'size=15 maxlength=100';

		if ( $id !== 'new' )
		{
			$extra .= ' required';
		}
		elseif ( $category_type )
		{
			$id .= $category_type;
		}
	}
	elseif ( $column === 'COMMENTS' )
	{
		$extra = 'size=25 maxlength=1000';

	}
	elseif ( $column === 'SORT_ORDER' )
	{
		$extra = ' type="number" min="-9999" max="9999"';
	}
	else
	{
		$extra = 'size=10 maxlength=255';
	}

	$return = TextInput( $value, 'values[' . $id . '][' . $column . ']', '', $extra );

	if ( $column === 'COMMENTS'
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] )
		&& mb_strlen( (string) $value ) > 60 )
	{
		// Comments length > 60 chars, responsive table ColorBox.
		$return = '<div id="divSchoolInventoryComment' . $id . '" class="rt2colorBox">' .
			$return . '</div>';
	}

	return $return;
}


function MakeSINumberInput( $value, $column )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ITEM_ID'] ) )
	{
		$id = $THIS_RET['ITEM_ID'];
	}
	elseif ( ! empty( $THIS_RET['CATEGORY_ID'] ) )
	{
		$id = $THIS_RET['CATEGORY_ID'];
	}
	else
	{
		$id = 'new';
	}

	$extra = 'type="number"';

	if ( $column === 'QUANTITY' )
	{
		$extra .= ' min="0" step="0.01" max="999999999"';

		if ( $id !== 'new'
			&& $value < 1 )
		{
			// Quantity in red if < 1.
			$value = [ $value, '<span style="color: red">' . $value . '</span>' ];
		}
	}

	return TextInput( $value, 'values[' . $id . '][' . $column . ']', '', $extra );
}


function MakeSICategorySelect( $value, $column )
{
	global $THIS_RET;

	$return = '';

	if ( ! empty( $THIS_RET['ITEM_ID'] ) )
	{
		$id = $THIS_RET['ITEM_ID'];

		$name = 'values[' . $id . '][' . $column . '_WAS_NA]';

		// If N/A, add an hidden *_WAS_NA field
		// to know value should be inserted instead of updated!
		$return .= '<input type="hidden" value="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( ( $value ? '' : 'Y' ) ) : htmlspecialchars( ( $value ? '' : 'Y' ), ENT_QUOTES ) ) .
			'" name="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $name ) : htmlspecialchars( $name, ENT_QUOTES ) ) . '" />';
	}
	else
	{
		$id = 'new';

		// Preselect current categorie's value.
		if ( $column === $_REQUEST['category_type']
			&& $_REQUEST['category_id'] > 0 )
		{
			$value = $_REQUEST['category_id'];
		}
	}

	$extra = 'style="max-width: 180px"';

	$options = GetSICategoryOptions( $column );

	// Do not use Chosen inside ListOutput!
	// @since RosarioSIS 10.7 Use Select2 input instead of Chosen, fix overflow issue.
	$select_input_function = function_exists( 'Select2Input' ) && count( $options ) > 3 ? 'Select2Input' : 'SelectInput';

	return $return . $select_input_function(
		$value,
		'values[' . $id . '][' . $column . ']',
		'',
		$options,
		'N/A',
		$extra,
		$id !== 'new'
	);
}


/**
 * Get Categories by type, for current school.
 *
 * @example $options = GetSICategoryOptions( $column );
 *
 * @param string  $type     Category Type: CATEGORY|STATUS|LOCATION|WORK_ORDER|USER_ID.
 *
 * @return array  Categories details belonging to this type.
 */
function GetSICategoryOptions( $category_type )
{
	static $categories = [];

	if ( ! $category_type )
	{
		return [];
	}

	if ( isset( $categories[ $category_type ] ) )
	{
		return $categories[ $category_type ];
	}

	$table = 'school_inventory_categories';

	$where_sql = '';

	if ( ! empty( $_REQUEST['snapshot_id'] ) )
	{
		$table = 'school_inventory_snapshot_categories';

		$where_sql = " AND SNAPSHOT_ID='" . (int) $_REQUEST['snapshot_id'] . "'";
	}

	$categories_RET = DBGet( "SELECT CATEGORY_ID,TITLE
		FROM " . DBEscapeIdentifier( $table ) . "
		WHERE CATEGORY_TYPE='" . $category_type . "'
		AND SCHOOL_ID='" . UserSchool() . "'" . $where_sql . "
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

	foreach ( (array) $categories_RET as $category )
	{
		$value = $category['TITLE'];

		if ( empty( $_REQUEST['LO_save'] ) )
		{
			// Truncate value to 50 chars.
			$value = mb_strlen( $value ) <= 50 ?
				$value :
				[ $value, '<span title="' . AttrEscape( $value ) . '">' . mb_substr( $value, 0, 47 ) . '...</span>' ];
		}

		$categories[ $category_type ][ $category['CATEGORY_ID'] ] = $value;
	}

	return isset( $categories[ $category_type ] ) ? $categories[ $category_type ] : [];
}


function MakeSIFileInput( $value, $name )
{
	global $THIS_RET, $FileUploadsPath;

	if ( empty( $THIS_RET['ITEM_ID'] ) )
	{
		$file_input_html = FileInput(
			'FILE',
			'',
			'style="width: 230px; padding: 0;"'
		);

		$button = button( 'add' );

		return InputDivOnclick( 'FILE', $file_input_html, $button, '' );
	}

	if ( ! $value
		|| ! empty( $_REQUEST['LO_save'] ) )
	{
		return $value;
	}

	$image_exts = [ '.png', '.gif', '.jpg' ];

	$file_ext = mb_substr( $value, -4 );

	if ( in_array( $file_ext, $image_exts ) )
	{
		$photo = '<img src="' . URLEscape( $value ) . '" style="max-width: 290px" />';

		$button = '<img src="assets/themes/' . Preferences( 'THEME' ) .
			'/btn/visualize.png" class="button bigger" />';

		// It is a photo. Add Tip message.
		return MakeTipMessage( $photo, dgettext( 'School_Inventory', 'Photo' ), $button );
	}

	// It is a document. Download.
	$button = '<img src="assets/themes/' . Preferences( 'THEME' ) .
		'/btn/download.png" class="button bigger" />';

	$title = str_replace( $FileUploadsPath . 'SchoolInventory/', '', $value );

	return '<a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( $value ) :
		_myURLEncode( $value ) ) .
		'" title="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $title ) : htmlspecialchars( $title, ENT_QUOTES ) ) .
		'" target="_blank">' . $button . '</a>';
}


function MakeSICategoryRemove( $value, $column )
{
	global $THIS_RET;

	if ( ! AllowEdit() )
	{
		return '';
	}

	if ( empty( $THIS_RET['CATEGORY_ID'] )
		|| $THIS_RET['CATEGORY_ID'] < 1 )
	{
		return '';
	}

	return button(
		'remove',
		'',
		'"' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&modfunc=remove&category_id=' . $THIS_RET['CATEGORY_ID'] ) :
			_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&modfunc=remove&category_id=' . $THIS_RET['CATEGORY_ID'] ) ) . '"'
	);
}
