<?php
/**
 * Outbox program
 *
 * @package SMS module
 */

require_once 'modules/SMS/includes/SMS.fnc.php';
require_once 'modules/SMS/includes/SMSMake.fnc.php';

if ( User( 'PROFILE' ) === 'teacher' )
{
	// Allow Edit if non admin.
	$_ROSARIO['allow_edit'] = true;
}

$program_title_school = '';

if ( SchoolInfo( 'SCHOOLS_NB' ) > 1
	&& ( ! User( 'SCHOOLS' )
		|| strlen( User( 'SCHOOLS' ) ) > 3 ) )
{
	// Mention School after program title.
	$program_title_school = ' <small>' . SchoolInfo( 'TITLE' ) . '</small>';
}

DrawHeader( ProgramTitle() . $program_title_school );

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m-01' ) );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );


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
	$sms_functions = [
		'CREATED_AT' => 'ProperDateTime', // Display localized & preferred Date & Time.
		'STAFF_ID' => 'SMSMakeSender',
		'RECIPIENTS' => 'SMSMakeRecipients',
		'DATA' => 'SMSMakeData',
		'SEND_AGAIN' => 'SMSMakeSendAgain',
	];

	$where_sql = '';

	if ( User( 'PROFILE' ) === 'teacher' )
	{
		$where_sql = " AND STAFF_ID='" . User( 'STAFF_ID' ) . "'";
	}

	$sms_RET = DBGet( "SELECT
		CREATED_AT,STAFF_ID,RECIPIENTS,DATA,ID AS SEND_AGAIN
		FROM sms
		WHERE CREATED_AT>='" . $start_date . "'
		AND CREATED_AT<='" . $end_date . ' 23:59:59' . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" .
		$where_sql .
		" ORDER BY CREATED_AT DESC", $sms_functions );

	ListOutput(
		$sms_RET,
		[
			'CREATED_AT' => _( 'Date' ),
			'STAFF_ID' => dgettext( 'SMS', 'Sender' ),
			'DATA' => dgettext( 'SMS', 'SMS' ),
			'RECIPIENTS' => dgettext( 'SMS', 'Recipients' ),
			'SEND_AGAIN' => '',
		],
		'SMS',
		'SMS',
		[],
		[],
		[ 'count' => true, 'save' => true ]
	);
}
