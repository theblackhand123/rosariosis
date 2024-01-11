<?php
/**
 * Inventory Snapshots
 *
 * @package School Inventory module
 */

require_once 'ProgramFunctions/TipMessage.fnc.php';
require_once 'includes/SchoolInventory.fnc.php';

// @deprecated since 2.0.
require_once 'modules/School_Inventory/includes/Update.inc.php';

$_REQUEST['category_id'] = issetVal( $_REQUEST['category_id'], '' );
$_REQUEST['category_type'] = issetVal( $_REQUEST['category_type'], '' );

DrawHeader( ProgramTitle() );

// Save.
if ( $_REQUEST['modfunc'] === 'save'
	&& $_REQUEST['values']
	&& $_POST['values']
	&& AllowEdit() )
{
	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		// New: check for Title.
		if ( $columns['TITLE'] )
		{
			$sql = "INSERT INTO school_inventory_snapshots ";

			$fields = 'SCHOOL_ID,TITLE';

			$values = "'" . UserSchool() . "','" . $columns['TITLE'] . "'";

			$sql .= '(' . $fields . ') values(' . $values . ')';

			DBQuery( $sql );

			if ( function_exists( 'DBLastInsertID' ) )
			{
				$id = DBLastInsertID();
			}
			else
			{
				// @deprecated since RosarioSIS 9.2.1.
				$id = DBGetOne( "SELECT LASTVAL();" );
			}

			$snapshot_sql = "INSERT INTO school_inventory_snapshot_categories (SNAPSHOT_ID,
			CATEGORY_ID,CATEGORY_TYPE,CATEGORY_KEY,SCHOOL_ID,TITLE,SORT_ORDER,COLOR)
			SELECT '" . $id . "',
			CATEGORY_ID,CATEGORY_TYPE,CATEGORY_KEY,SCHOOL_ID,TITLE,SORT_ORDER,COLOR
			FROM school_inventory_categories
			WHERE SCHOOL_ID='" . UserSchool() . "';";

			$snapshot_sql .= "INSERT INTO school_inventory_snapshot_items (SNAPSHOT_ID,
			ITEM_ID,SCHOOL_ID,TITLE,SORT_ORDER,TYPE,QUANTITY,COMMENTS,FILE,PRICE," . DBEscapeIdentifier( 'DATE' ) . ")
			SELECT '" . $id . "',
			ITEM_ID,SCHOOL_ID,TITLE,SORT_ORDER,TYPE,QUANTITY,COMMENTS,FILE,PRICE," . DBEscapeIdentifier( 'DATE' ) . "
			FROM school_inventory_items
			WHERE SCHOOL_ID='" . UserSchool() . "';";

			$snapshot_sql .= "INSERT INTO school_inventory_snapshot_categoryxitem (SNAPSHOT_ID,
			ITEM_ID,CATEGORY_ID,CATEGORY_TYPE)
			SELECT '" . $id . "',
			sicxi.ITEM_ID,sicxi.CATEGORY_ID,sicxi.CATEGORY_TYPE
			FROM school_inventory_categoryxitem sicxi,school_inventory_items sii,school_inventory_categories sic
			WHERE sii.SCHOOL_ID='" . UserSchool() . "'
			AND sic.SCHOOL_ID=sii.SCHOOL_ID
			AND sii.ITEM_ID=sicxi.ITEM_ID
			AND sic.CATEGORY_ID=sicxi.CATEGORY_ID;";

			DBQuery( $snapshot_sql );
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit()
	&& ! empty( $_REQUEST['id'] ) )
{
	if ( DeletePrompt( dgettext( 'School_Inventory', 'Snapshot' ) ) )
	{
		$delete_sql = "DELETE FROM school_inventory_snapshot_categories
			WHERE SNAPSHOT_ID='" . (int) $_REQUEST['id'] . "'
			AND SCHOOL_ID='" . UserSchool() . "';";

		$delete_sql .= "DELETE FROM school_inventory_snapshot_items
			WHERE SNAPSHOT_ID='" . (int) $_REQUEST['id'] . "'
			AND SCHOOL_ID='" . UserSchool() . "';";

		$delete_sql .= "DELETE FROM school_inventory_snapshot_categoryxitem
			WHERE SNAPSHOT_ID='" . (int) $_REQUEST['id'] . "';";

		$delete_sql .= "DELETE FROM school_inventory_snapshots
			WHERE ID='" . (int) $_REQUEST['id'] . "'
			AND SCHOOL_ID='" . UserSchool() . "';";

		DBQuery( $delete_sql );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

// Display errors if any.
echo ErrorMessage( $error );

// Display notes if any.
echo ErrorMessage( $note, 'note' );


// Display Search screen or Student list.
if ( empty( $_REQUEST['modfunc'] ) )
{
	if ( empty( $_REQUEST['snapshot_id'] ) )
	{
		$snapshots_RET = DBGet( "SELECT ID,TITLE,CREATED_AT
			FROM school_inventory_snapshots
			WHERE SCHOOL_ID='" . UserSchool() . "'
			ORDER BY CREATED_AT DESC",
		[
			'TITLE' => '_makeLink',
			'CREATED_AT' => 'ProperDateTime',
		] );

		$columns = [
			'TITLE' => _( 'Title' ),
			'CREATED_AT' => _( 'Date' ),
		];

		$link['add']['html'] = [
			'TITLE' => _makeTextInput( '', 'TITLE' ),
		];

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
		$link['remove']['variables'] = [ 'id' => 'ID' ];

		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';

		DrawHeader( '', SubmitButton() );

		ListOutput(
			$snapshots_RET,
			$columns,
			dgettext( 'School_Inventory', 'Snapshot' ),
			dgettext( 'School_Inventory', 'Snapshots' ),
			$link
		);

		echo '<div class="center">' . SubmitButton() . '</div></form>';
	}
	else
	{
		$snapshot_title = DBGetOne( "SELECT TITLE
			FROM school_inventory_snapshots
			WHERE ID='" . (int) $_REQUEST['snapshot_id'] . "'" );

		DrawHeader( $snapshot_title );

		// Do not edit Snapshot Items & Categories, read only.
		$_ROSARIO['allow_edit'] = false;

		require_once 'modules/School_Inventory/includes/SchoolInventory.inc.php';
	}
}

function _makeTextInput( $value, $name )
{
	global $THIS_RET;

	$id = 'new';

	$extra = 'maxlength=100';

	return TextInput( $value, 'values[' . $id . '][' . $name . ']', '', $extra );
}

function _makeLink( $value, $name )
{
	global $THIS_RET;

	$id = $THIS_RET['ID'];

	return '<a href="Modules.php?modname=School_Inventory/InventorySnapshots.php&snapshot_id=' . $id . '">' .
		$value . '</a>';
}
