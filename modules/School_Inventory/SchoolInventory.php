<?php
/**
 * School Inventory
 *
 * @package School Inventory module
 */

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/TipMessage.fnc.php';
require_once 'includes/SchoolInventory.fnc.php';

$_REQUEST['category_id'] = issetVal( $_REQUEST['category_id'], '' );
$_REQUEST['category_type'] = issetVal( $_REQUEST['category_type'], '' );

DrawHeader( ProgramTitle() );

// Save.
if ( $_REQUEST['modfunc'] === 'save'
	&& $_REQUEST['values']
	&& $_POST['values']
	&& AllowEdit() )
{
	$table = 'school_inventory_categories';

	$id_column = 'CATEGORY_ID';

	if ( $_REQUEST['category_type'] !== '' )
	{
		$table = 'school_inventory_items';

		$id_column = 'ITEM_ID';
	}

	$category_columns = [
		'CATEGORY',
		'STATUS',
		'LOCATION',
		'WORK_ORDER',
		'USER_ID',
	];

	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		if ( isset( $columns['QUANTITY'] )
			&& ! is_numeric( $columns['QUANTITY'] ) )
		{
			// Sanitize quantity.
			$columns['QUANTITY'] = 1;
		}

		if ( $id !== 'new'
			&& mb_strpos( $id, 'new' ) === false )
		{
			$sql = "UPDATE " . DBEscapeIdentifier( $table ) . " SET ";

			$go = false;

			foreach ( (array) $columns as $column => $value )
			{
				if ( $table === 'school_inventory_items'
					&& ( in_array( $column, $category_columns )
						|| mb_strpos( $column, '_WAS_NA' ) !== false ) )
				{
					continue;
				}

				$go = true;

				$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) .
				" WHERE " . DBEscapeIdentifier( $id_column ) . "='" . $id . "'";

			if ( $go )
			{
				DBQuery( $sql );
			}

			if ( $table === 'school_inventory_items' )
			{
				$categories_sql = '';

				// Relate Item to Categories.
				foreach ( (array) $category_columns as $category_col )
				{
					if ( ! isset( $columns[ $category_col ] ) )
					{
						continue;
					}

					$category_where_sql = "ITEM_ID='" . (int) $id . "'
							AND CATEGORY_TYPE='" . $category_col . "'";

					if ( $columns[ $category_col ] )
					{
						// Check if was N/A.
						if ( $columns[ $category_col . '_WAS_NA' ] )
						{
							// Insert.
							$categories_sql .= "INSERT INTO school_inventory_categoryxitem
								(CATEGORY_ID,ITEM_ID,CATEGORY_TYPE)
								values('" . $columns[ $category_col ] . "','" . $id . "','" . $category_col . "');";
						}
						else
						{
							// Update.
							$categories_sql .= "UPDATE school_inventory_categoryxitem SET
								CATEGORY_ID='" . (int) $columns[ $category_col ] . "'
								WHERE " . $category_where_sql . ";";
						}
					}
					elseif ( ! $columns[ $category_col . '_WAS_NA' ] )
					{
						// Delete.
						$categories_sql .= "DELETE FROM school_inventory_categoryxitem
							WHERE " . $category_where_sql . ";";
					}
				}

				if ( $categories_sql )
				{
					DBQuery( $categories_sql );
				}
			}
		}
		// New: check for Title.
		elseif ( $columns['TITLE'] )
		{
			if ( $table === 'school_inventory_categories' )
			{
				// If category, extract category type from id.
				$columns['CATEGORY_TYPE'] = mb_substr( $id, 3 );
			}

			$sql = "INSERT INTO " . DBEscapeIdentifier( $table ) . " ";

			$fields = 'SCHOOL_ID,';

			$values = "'" . UserSchool() . "',";

			if ( $table === 'school_inventory_items'
				&& ! empty( $_FILES['FILE']['name'] ) )
			{
				$file_ext = mb_strtolower( mb_strrchr( $_FILES['FILE']['name'], '.' ) );

				$file_name_no_ext = no_accents( mb_substr(
					$_FILES['FILE']['name'],
					0,
					mb_strrpos( $_FILES['FILE']['name'], '.' )
				) );

				// @since RosarioSIS 11.0 Add microseconds to filename format to make it harder to predict.
				$file_name_no_ext .= '_' . date( 'Y-m-d_His' ) . '.' . substr( (string) microtime(), 2, 6 );

				// @since 10.3 Fix security issue, unset any FILE column first.
				$columns['FILE'] = '';

				if ( in_array( $file_ext, [ '.jpg', '.jpeg', '.png', '.gif' ] ) )
				{
					// Photo.
					$columns['FILE'] = ImageUpload(
						'FILE',
						[ 'width' => 600, 'height' => 600 ],
						$FileUploadsPath . 'SchoolInventory/',
						[],
						'',
						$file_name_no_ext
					);
				}
				else
				{
					// Document.
					$columns['FILE'] = FileUpload(
						'FILE',
						$FileUploadsPath . 'SchoolInventory/',
						FileExtensionWhiteList(),
						0,
						$error,
						'',
						$file_name_no_ext
					);
				}

				// Fix SQL error when quote in uploaded file name.
				$columns['FILE'] = DBEscapeString( $columns['FILE'] );
			}

			$go = 0;
			foreach ( (array) $columns as $column => $value)
			{
				if ( $table === 'school_inventory_items'
					&& in_array( $column, $category_columns ) )
				{
					continue;
				}

				if ( ! empty( $value )
					|| $value === '0' )
				{
					$fields .= DBEscapeIdentifier( $column ) . ',';
					$values .= "'" . $value . "',";
					$go = true;
				}
			}

			$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

			if ( $go )
			{
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
			}

			// TODO.
			if ( $go
				&& $table === 'school_inventory_items' )
			{
				// Relate Item to Categories.
				$insert_cat_sql = '';

				foreach ( (array) $category_columns as $category_col )
				{
					if ( isset( $columns[ $category_col ] )
						&& $columns[ $category_col ] )
					{
						$insert_cat_sql .= "('" . $id . "','" .
							$columns[ $category_col ] . "','" . $category_col . "'),";
					}
				}

				if ( $insert_cat_sql )
				{
					DBQuery( "INSERT INTO school_inventory_categoryxitem
						(ITEM_ID,CATEGORY_ID,CATEGORY_TYPE) values " .
						mb_substr( $insert_cat_sql, 0, -1 ) );
				}
			}
		}

		// $error[] = _( 'Please enter a valid Sort Order.' );
		// $note[] = dgettext( 'School_Inventory', 'The school inventory has been updated.' );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( isset( $_REQUEST['item_id'] ) )
	{
		if ( DeletePrompt( _( 'Item' ) ) )
		{
			// Uploaded file?
			$file = DBGetOne( "SELECT FILE
				FROM school_inventory_items
				WHERE ITEM_ID='" . (int) $_REQUEST['item_id'] . "'
				AND FILE IS NOT NULL" );

			if ( $file )
			{
				// Delete file.
				// Do not Delete file since it may still be referenced in SNAPSHOT!
				//@unlink( $file );
			}

			$delete_sql = "DELETE FROM school_inventory_items
				WHERE ITEM_ID='" . (int) $_REQUEST['item_id'] . "'
				AND SCHOOL_ID='" . UserSchool() . "';";

			$delete_sql .= "DELETE FROM school_inventory_categoryxitem
				WHERE ITEM_ID='" . (int) $_REQUEST['item_id'] . "';";

			DBQuery( $delete_sql );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'item_id' ] );
		}
	}
	elseif ( isset( $_REQUEST['category_id'] )
		&& $_REQUEST['category_id'] > 0 )
	{
		if ( DeletePrompt( _( 'Category' ) ) )
		{
			$delete_sql = "DELETE FROM school_inventory_categories
				WHERE CATEGORY_ID='" . (int) $_REQUEST['category_id'] . "'
				AND SCHOOL_ID='" . UserSchool() . "';";

			$delete_sql .= "DELETE FROM school_inventory_categoryxitem
				WHERE CATEGORY_ID='" . (int) $_REQUEST['category_id'] . "';";

			DBQuery( $delete_sql );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'category_id' ] );
		}
	}
}

// Display errors if any.
echo ErrorMessage( $error );

// Display notes if any.
echo ErrorMessage( $note, 'note' );


// Display Search screen or Student list.
if ( empty( $_REQUEST['modfunc'] ) )
{
	require_once 'modules/School_Inventory/includes/SchoolInventory.inc.php';
}

