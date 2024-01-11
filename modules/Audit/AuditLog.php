<?php
/**
 * Audit Log
 * Display Audit Log records
 *
 * @package Audit
 */

DrawHeader( ProgramTitle() );

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m-d', time() - 60 * 60 * 24 ) );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

if ( $_REQUEST['modfunc'] === 'delete' )
{
	// Prompt before deleting log.
	if ( DeletePrompt( _( 'Audit Log' ) ) )
	{
		DBQuery( 'DELETE FROM audit_log' );

		$note[] = dgettext( 'Audit', 'Audit Log cleared.' );

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

echo ErrorMessage( $note, 'note' );

if ( ! $_REQUEST['modfunc'] )
{
	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] ) ) . '" method="GET">';

	DrawHeader(
		_( 'From' ) . ' ' . DateInput( $start_date, 'start', '', false, false ) . ' - ' .
		_( 'To' ) . ' ' . DateInput( $end_date, 'end', '', false, false ) .
		Buttons( _( 'Go' ) )
	);

	echo '</form>';

	// Format DB data.
	$audit_logs_functions = [
		'URL' => '_makeAuditLogURL', // Add link to URL.
		'PROFILE' => '_makeAuditLogProfile', // Translate profile.
		'USERNAME' => '_makeAuditLogUsername', // Add link to user info.
		'CREATED_AT' => 'ProperDateTime', // Display localized & preferred Date & Time.
		'QUERY_TYPE' => '_makeAuditLogQueryType', // Display DELETE in red, INSERT in green.
		'DATA' => '_makeAuditLogData', // Display SQL data.
	];

	$audit_logs_RET = DBGet( "SELECT
		DISTINCT USERNAME,PROFILE,CREATED_AT,URL,QUERY_TYPE,DATA
		FROM audit_log
		WHERE CREATED_AT >='" . $start_date . "'
		AND CREATED_AT <='" . $end_date . ' 23:59:59' . "'
		ORDER BY CREATED_AT DESC", $audit_logs_functions );

	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete' ) ) . '" method="POST">';

	DrawHeader( '', SubmitButton( _( 'Clear Log' ), '', '' ) );

	ListOutput(
		$audit_logs_RET,
		[
			'CREATED_AT' => _( 'Date' ),
			'USERNAME' => _( 'Username' ),
			'PROFILE' => _( 'User Profile' ),
			'URL' => dgettext( 'Audit', 'URL' ),
			'QUERY_TYPE' => _( 'Type' ),
			'DATA' => '<span class="a11y-hidden">' . _( 'Data' ) . '</span>',
		],
		dgettext( 'Audit', 'Audit record' ),
		dgettext( 'Audit', 'Audit records' ),
		[],
		[],
		[ 'count' => true, 'save' => true ]
	);

	echo '</form>';

	// When clicking on Username, go to Student or User Info. ?>
<script>
	$('.al-username').attr('href', function(){
		var url = 'Modules.php?modname=Users/User.php&search_modfunc=list&';

		if ( $(this).hasClass('student') ) {
			url = url.replace( 'Users/User.php', 'Students/Student.php' ) + 'cust[USERNAME]=';
		} else {
			url += 'username=';
		}

		return url + this.firstChild.data;
	});
</script>
	<?php
}


/**
 * Make URL
 * Add link to URL
 *
 * Local function
 * DBGet callback
 *
 * @param  string $value   Field value.
 * @param  string $name    'URL'.
 *
 * @return string          URL with HTML link.
 */
function _makeAuditLogURL( $value, $column )
{
	if ( ! $value )
	{
		return '';
	}

	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $value;
	}

	// Truncate links > 80 chars.
	$truncated_link = $value;

	if ( mb_strpos( $truncated_link, 'Modules.php' ) )
	{
		// Remove directories before Modules.php.
		$truncated_link = mb_substr( $truncated_link, mb_strpos( $truncated_link, 'Modules.php' ) );
	}

	if ( mb_strlen( $truncated_link ) > 80 )
	{
		$separator = '/.../';
		$separator_length = mb_strlen( $separator );
		$max_length = 80 - $separator_length;
		$start = (int) ( $max_length / 2 );
		$trunc =  mb_strlen( $truncated_link ) - $max_length;
		$truncated_link = substr_replace( $truncated_link, $separator, (int) $start, (int) $trunc );
	}

	return '<a href="' . ( function_exists( 'URLEscape' ) ? URLEscape( $value ) : _myURLEncode( $value ) ) . '" target="_blank">' . $truncated_link . '</a>';
}


/**
 * Make Profile
 * Only for successful logins.
 *
 * Local function
 * DBGet callback
 *
 * @param  string $value   Field value.
 * @param  string $name    'PROFILE'.
 *
 * @return string          Student, Administrator, Teacher, Parent, or No Access.
 */
function _makeAuditLogProfile( $value, $column )
{
	$profile_options = [
		'student' => _( 'Student' ),
		'admin' => _( 'Administrator' ),
		'teacher' => _( 'Teacher' ),
		'parent' => _( 'Parent' ),
		'none' => _( 'No Access' ),
	];

	if ( ! isset( $profile_options[ $value ] ) )
	{
		return '';
	}

	return $profile_options[ $value ];
}


/**
 * Make Username
 * Links to user info page.
 *
 * Local function
 * DBGet callback
 *
 * @param  string $value   Field value.
 * @param  string $name    'USERNAME'.
 *
 * @return string          USername linking to user info page.
 */
function _makeAuditLogUsername( $value, $column )
{
	global $THIS_RET;

	if ( ! $value )
	{
		return '';
	}

	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $value;
	}

	return '<a class="al-username ' .
		( $THIS_RET['PROFILE'] === 'student' ? 'student' : '' ) .
		'" href="#">' . $value . '</a>';
}


/**
 * Make Query Type
 * Display DELETE in red, INSERT in green.
 *
 * Local function
 * DBGet callback
 *
 * @param  string $value   Field value.
 * @param  string $name    'QUERY_TYPE'.
 *
 * @return string          Username linking to user info page.
 */
function _makeAuditLogQueryType( $value, $column )
{
	global $THIS_RET;

	if ( ! $value )
	{
		return '';
	}

	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $value;
	}

	$return = $value;

	if ( $value === 'DELETE' )
	{
		$return = '<span style="color: red;">' . $value . '</a>';
	}
	elseif ( $value === 'INSERT' )
	{
		$return = '<span style="color: green;">' . $value . '</a>';
	}

	return $return;
}


/**
 * Display data
 * Display SQL queries + count if > 1 inside ColorBox.
 *
 * Local function
 * DBGet callback
 *
 * @param  string $value   Field value.
 * @param  string $name    'DATA'.
 *
 * @return string          SQL queries ColorBox.
 */
function _makeAuditLogData( $value, $column )
{
	global $THIS_RET;

	static $i = 1;

	if ( ! $value )
	{
		return '';
	}

	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $value;
	}

	$query_type = $THIS_RET['QUERY_TYPE'];

	// Count queries.
	$count_queries = substr_count( $value, $query_type );

	$count_queries_html = '';

	if ( $count_queries > 1 )
	{
		$query_type_color = _makeAuditLogQueryType( $query_type, '' );

		$count_queries_html = '<p>' . $count_queries . ' ' . $query_type_color . '</p>';
	}

	$return = '<div style="display:none;"><div id="' . $column . $i . '" class="colorboxinline">' .
		$count_queries_html .
		'<pre><code>' . $value . '</code></pre></div></div>';

	$return .= button(
		'visualize',
		'',
		'"#' . $column . $i++ . '" class="colorboxinline"',
		'bigger'
	);

	return $return;
}
