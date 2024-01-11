<?php

require_once 'plugins/Relatives/includes/Relatives.fnc.php';

if ( ! $_REQUEST['modfunc']
	&& UserStudentID() )
{
	// Unset current student ID temporarily so we can use the Search() function.
	$current_student_id = UserStudentID();

	$_REQUEST['search_modfunc'] = 'list';

	// Fix to search for Siblings in all schools.
	$_REQUEST['_search_all_schools'] = 'Y';

	$_REQUEST['student_id'] = false;

	// Fake Student ID so we still have it in the URLs.
	$_REQUEST['siblings'] = '1&student_id=' . $current_student_id;

	$extra = GetSiblingsSearchExtra( $current_student_id );

	Search( 'student_id', $extra );

	// Unset Fake Student ID.
	$_REQUEST['siblings'] = false;

	// Unset all schools.
	unset( $_REQUEST['_search_all_schools'] );

	$_ROSARIO['SearchTerms'] = '';

	// Set back UserStudentID() & $_REQUEST['student_id'] to current student ID.
	$_SESSION['student_id'] = $_REQUEST['student_id'] = $current_student_id;

	// Unset current staff ID temporarily so we can use the Search() function.
	$current_staff_id = UserStaffID();

	echo '<br />';

	// Parents.
	$extra = GetParentsSearchExtra( $current_student_id );

	Search( 'staff_id', $extra );

	// Set back UserStaffID() & $_REQUEST['staff_id'] to current staff ID.
	$_SESSION['staff_id'] = $current_staff_id;
}
