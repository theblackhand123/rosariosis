<?php
/**
 * Room List
 *
 * @package Hostel module
 */

DrawHeader( ProgramTitle() );

$_REQUEST['building_id'] = issetVal( $_REQUEST['building_id'] );
$_REQUEST['include_students'] = issetVal( $_REQUEST['include_students'] );

$buildings_RET = DBGet( "SELECT ID,TITLE
	FROM hostel_buildings
	ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

$building_options = [];

foreach ( (array) $buildings_RET as $building )
{
	$building_options[ $building['ID'] ] = $building['TITLE'];
}

$building_select = SelectInput(
	$_REQUEST['building_id'],
	'building_id',
	'<span class="a11y-hidden">' . dgettext( 'Hostel', 'Building' ) . '</span>',
	$building_options,
	dgettext( 'Hostel', 'All Buildings' ),
	'onchange="ajaxPostForm(this.form,true);"',
	false
);

$include_students_checkbox = CheckboxInput(
	$_REQUEST['include_students'],
	'include_students',
	dgettext( 'Hostel', 'Include Students' ),
	'',
	true,
	'Yes',
	'No',
	false,
	'onchange="ajaxPostForm(this.form,true);"'
);

echo '<form action="' . PreparePHP_SELF( [], [ 'building_id', 'include_students' ] ) . '" method="GET">';

DrawHeader(
	$building_select,
	$include_students_checkbox
);

echo '</form>';

$sql_where_building = '';

if ( $_REQUEST['building_id'] > 0 )
{
	$sql_where_building = " AND hr.BUILDING_ID='" . (int) $_REQUEST['building_id'] . "'";
}

if ( $_REQUEST['include_students'] )
{
	$rooms_RET = DBGet( "SELECT hr.ID,hr.TITLE,hr.CAPACITY,hr.PRICE,
		" . DisplayNameSQL( 's' ) . " AS STUDENTS,s.STUDENT_ID,
		hb.TITLE AS BUILDING_TITLE,CAST(hs.CREATED_AT AS char(10)) AS SINCE
		FROM hostel_rooms hr
		LEFT JOIN hostel_buildings hb ON(hb.ID=hr.BUILDING_ID)
		LEFT JOIN hostel_students hs ON(hs.ROOM_ID=hr.ID)
		LEFT JOIN students s ON(s.STUDENT_ID=hs.STUDENT_ID)
		WHERE TRUE " . $sql_where_building . "
		ORDER BY hb.SORT_ORDER IS NULL,hb.SORT_ORDER,hb.TITLE,hr.TITLE,STUDENTS",
		[ 'PRICE' => 'Currency', 'SINCE' => 'ProperDate', 'STUDENTS' => 'makePhotoTipMessage' ] );
}
else
{
	$rooms_RET = DBGet( "SELECT hr.ID,hr.TITLE,hr.CAPACITY,hr.PRICE,
		(SELECT COUNT(hs.STUDENT_ID)
			FROM hostel_students hs
			WHERE hs.ROOM_ID=hr.ID) AS STUDENTS,
		hb.TITLE AS BUILDING_TITLE
		FROM hostel_rooms hr,hostel_buildings hb
		WHERE hb.ID=hr.BUILDING_ID
		" . $sql_where_building . "
		ORDER BY hb.SORT_ORDER IS NULL,hb.SORT_ORDER,hb.TITLE,hr.TITLE",
		[ 'PRICE' => 'Currency' ] );
}

$LO_columns = [
	'BUILDING_TITLE' => dgettext( 'Hostel', 'Building' ),
	'TITLE' => dgettext( 'Hostel', 'Room' ),
	'CAPACITY' => dgettext( 'Hostel', 'Capacity' ),
	'PRICE' => _( 'Price' ),
	'STUDENTS' => _( 'Students' ),
];

if ( $_REQUEST['include_students'] )
{
	$LO_columns['STUDENTS'] = _( 'Student' );

	$LO_columns['SINCE'] = dgettext( 'Hostel', 'Since' );
}

ListOutput(
	$rooms_RET,
	$LO_columns,
	$_REQUEST['include_students'] ? _( 'Student' ) : dgettext( 'Hostel', 'Room' ),
	$_REQUEST['include_students'] ? _( 'Students' ) : dgettext( 'Hostel', 'Rooms' )
);
