<?php
/**
 * Saved Reports (Setup)
 *
 * @package Reports
 */

// RosarioSIS 2.9: ajaxLink( 'Side.php' );
$reload_side = '<script>
	var side_link = document.createElement("a");
	side_link.href = "Side.php";
	side_link.target = "menu";
	ajaxLink(side_link);
</script>';

// Save New Report.
if ( $_REQUEST['modfunc'] === 'new'
	&& AllowEdit() )
{
	DBQuery( "INSERT INTO saved_reports (TITLE,STAFF_ID,PHP_SELF,SEARCH_PHP_SELF,SEARCH_VARS)
		values(
			'" . DBEscapeString( dgettext( 'Reports', 'Untitled' ) ) . "',
			'" . User( 'STAFF_ID' ) . "',
			'" . DBEscapeString( PreparePHP_SELF( $_SESSION['_REQUEST_vars'] ) ) . "',
			'',
			'" . /*serialize( $_SESSION['Search_vars'] ) .*/ "')" );

	if ( function_exists( 'DBLastInsertID' ) )
	{
		$report_id = DBLastInsertID();
	}
	else
	{
		// @deprecated since RosarioSIS 9.2.1.
		$report_id = DBGetOne( "SELECT LASTVAL();" );
	}

	// FJ disable Publishing options.
	$modname = 'Reports/RunReport.php&id=' . $report_id;

	// Admin can Use Report.
	DBQuery( "INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
		values('1','" . $modname . "','Y','Y')" );

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );

	// Reload Side.php Menu.
	echo $reload_side;
}

// Update Saved Report.
if ( isset( $_REQUEST['values'] )
	&& isset( $_POST['values'] )
	&& AllowEdit() )
{
	foreach ( (array)$_REQUEST['values'] as $id => $columns )
	{
		$sql = "UPDATE saved_reports SET ";

		foreach ( (array)$columns as $column => $value )
		{
			if ( function_exists( 'DBEscapeIdentifier' ) ) // RosarioSIS 3.0+.
			{
				$escaped_column = DBEscapeIdentifier( $column );
			}
			else
			{
				$escaped_column = '"' . mb_strtolower( $column ) . '"';
			}

			$sql .= $escaped_column . "='" . $value . "',";
		}

		$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";

		DBQuery( $sql );
	}

	// Reload Side.php Menu.
	echo $reload_side;
}

// Add profile exceptions for the Saved Reports to appear in the menu.
if ( isset( $_REQUEST['profiles'] )
	&& isset( $_POST['profiles'] )
	&& AllowEdit() )
{
	$profiles_RET = DBGet( "SELECT ID,TITLE
		FROM user_profiles" );

	$reports_RET = DBGet( "SELECT ID
		FROM saved_reports" );

	foreach( (array)$reports_RET as $report_id )
	{
		$report_id = $report_id['ID'];

		$modname = 'Reports/RunReport.php&id=' . $report_id;

		if ( ! isset( $exceptions_RET[ $report_id ] ) )
		{
			$exceptions_RET[ $report_id ] = DBGet( "SELECT PROFILE_ID,CAN_USE,CAN_EDIT
				FROM profile_exceptions
				WHERE MODNAME='" . $modname . "'", [], [ 'PROFILE_ID' ] );
		}

		foreach ( (array)$profiles_RET as $profile )
		{
			$profile_id = $profile['ID'];

			if ( ! isset( $exceptions_RET[ $report_id ][ $profile_id ] ) )
			{
				DBQuery( "INSERT INTO profile_exceptions (PROFILE_ID,MODNAME)
					values('" . $profile_id . "','" . $modname . "')" );
			}

			if ( ! $_REQUEST['profiles'][ $report_id ][ $profile_id ] )
			{
				DBQuery( "UPDATE profile_exceptions
					SET CAN_USE='N',CAN_EDIT='N'
					WHERE PROFILE_ID='" . (int) $profile_id . "'
					AND MODNAME='" . $modname . "'" );
			}
			else
			{
				DBQuery( "UPDATE profile_exceptions
					SET CAN_USE='Y',CAN_EDIT='Y'
					WHERE PROFILE_ID='" . (int) $profile_id . "'
					AND MODNAME='". $modname . "'" );
			}

			/*if ( ! $_REQUEST['profiles'][ str_replace( '.', '_', $modname ) ] )
			{
				$update_profile = "UPDATE profile_exceptions SET ";

				if ( ! $_REQUEST['can_use'][ str_replace( '.', '_', $modname ) ] )
				{
					$update_profile .= "CAN_USE='N'";
				}

				$update_profile .= " WHERE PROFILE_ID='" . (int) $profile_id . "'
					AND MODNAME='" . $modname . "'";

				DBQuery( $update_profile );
			}*/
		}
	}
}

DrawHeader( ProgramTitle() );

// Remove Saved Report.
if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Saved Report' ) ) )
	{
		$delete_sql = "DELETE FROM saved_reports
			WHERE ID='" . (int) $_REQUEST['id'] . "';";

		$modname = 'Reports/RunReport.php&id=' . $_REQUEST['id'];

		$delete_sql .= "DELETE FROM profile_exceptions
			WHERE MODNAME='" . $modname . "';";

		DBQuery( $delete_sql );

		// Unset modfunc & id & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );

		// Reload Side.php Menu.
		echo $reload_side;
	}
}

// Display Saved Report.
if ( $_REQUEST['modfunc'] !== 'remove' )
{
	$saved_reports_RET = DBGet(
		"SELECT ID,TITLE,PHP_SELF,'' AS PUBLISHING
			FROM saved_reports
			ORDER BY TITLE",
		[
			'TITLE' => '_makeTextInput',
			'PHP_SELF' => '_makeProgram',
			// FJ disable Publishing options.
			//'PUBLISHING' => '_makePublishing',
		]
	);

	$columns = [
		'TITLE' => _( 'Title' ),
		'PHP_SELF' => dgettext( 'Reports', 'Program Title' ),
		// FJ disable Publishing options.
		//'PUBLISHING' => _('Publishing Options' ),
	];

	$link['remove']['link'] = "Modules.php?modname=". $_REQUEST['modname'] . "&modfunc=remove";

	$link['remove']['variables'] = [ 'id' => 'ID' ];

	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) ) . '" method="POST">';

	DrawHeader( '', SubmitButton() );

	ListOutput( $saved_reports_RET, $columns, 'Saved Report', 'Saved Reports', $link );

	echo '<div class="center">' . SubmitButton() . '</div>';

	echo '</form>';
}


/**
 * Make Text Input
 *
 * Local function
 *
 * @global $THIS_RET Current Return value
 *
 * @param  string $value  Value.
 * @param  string $column 'TITLE'.
 *
 * @return string Text Input
 */
function _makeTextInput( $value, $column )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	if ( $value === dgettext( 'Reports', 'Untitled' ) )
	{
		$div = false;
	}
	else
		$div = true;

	$extra = 'maxlength="100"';

	$run_button = '';

	if ( $id !== 'new' )
	{
		$run_button = '<a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=Reports/RunReport.php&id=' . $id ) :
			_myURLEncode( 'Modules.php?modname=Reports/RunReport.php&id=' . $id ) ) .
			'" style="float: right;" title="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( _( 'Run' ) ) : htmlspecialchars( _( 'Run' ), ENT_QUOTES ) ) . '">
			<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/next.png" class="button" /></a>';
	}

	return '<div style="float:left;">' . TextInput(
		$value,
		'values[' . $id . '][' . $column . ']',
		'',
		$extra,
		$div
	) . '</div>' . $run_button;
}


/**
 * Make Program Title
 *
 * Local function
 *
 * @param  string $value  Value.
 * @param  string $column 'PHP_SELF'.
 *
 * @return string ProgramTitle( $modname )
 */
function _makeProgram( $value, $column )
{
	if ( strpos( $value, '&' ) )
	{
		$modname = mb_substr($value, 20, strpos( $value, '&' ) - 20 );
	}
	else
		$modname = mb_substr( $value, 20 );

	return ProgramTitle( $modname );
}


/**
 * Make Publishing options
 *
 * Local function
 *
 * @global $THIS_RET Current Return value
 *
 * @param  string $value  Value.
 * @param  string $column 'PUBLISHING'.
 *
 * @return string Publishing options
 */
function _makePublishing( $value, $column )
{
	global $THIS_RET;

	static $profiles_RET = null,
		$schools_RET;

	if ( ! $profiles_RET )
	{
		$profiles_RET = DBGet( "SELECT ID,TITLE
			FROM user_profiles" );
	}

	$exceptions_RET = DBGet( "SELECT CAN_EDIT,CAN_USE,PROFILE_ID
		FROM profile_exceptions
		WHERE MODNAME='Reports/RunReport.php&id=" . $THIS_RET['ID'] . "'", [], [ 'PROFILE_ID' ] );

	$return = '<table class="cellspacing-0"><tr><td colspan="4"><b>' .
		_( 'Profiles' ) . ': </b></td></tr>';

	$i = 0;

	foreach ( (array) $profiles_RET as $profile )
	{
		$i++;

		$return .= '<td>' .
			CheckboxInput(
				$exceptions_RET[ $profile['ID'] ][1]['CAN_USE'],
				'profiles[' . $THIS_RET['ID'] . '][' . $profile['ID'] . ']',
				$profile['TITLE'],
				'',
				true
			) .
			'</td>';

		if ( $i % 4 == 0
			&& $i !== count( $profiles_RET ) )
		{
			$return .= '</tr><tr>';
		}
	}

	for ( ; $i % 4 != 0; $i++ )
	{
		$return .= '<td></td>';
	}

	/*$return .= '</tr><tr><td colspan="2"><b><a href="#">' .
		_( 'Schools' ) . ': ...</a></b></td>';*/

	$return .= '</tr></table>';

	return $return;
}
