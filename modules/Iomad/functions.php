<?php
/**
 * Functions
 *
 * @package Iomad plugin
 */

require_once 'plugins/Iomad/includes/common.fnc.php';
require_once 'plugins/Moodle/client.php';

// Register plugin functions to be hooked.
add_action( 'School_Setup/CopySchool.php|header', 'IomadCopySchoolHeader' );

/**
 * Copy School Header action
 * Adds the "Create company in Iomad" checkbox to the end of the $table_list.
 *
 * @global $table_list
 */
function IomadCopySchoolHeader()
{
	global $RosarioPlugins,
		$table_list;

	if ( ! empty( $RosarioPlugins['Moodle'] )
		&& empty( $_REQUEST['delete_ok'] ) )
	{
		// Prompt display, no go.
		$checkbox = CheckboxInput(
			'Y',
			'iomad_create_company',
			dgettext( 'Iomad', 'Create company in Iomad' ),
			'',
			true
		);

		$table_list[] = $checkbox;
	}
}

// Register plugin functions to be hooked.
add_action( 'School_Setup/CopySchool.php|copy_school', 'IomadCopySchool' );

/**
 * Copy School action
 * Create company.
 *
 * @uses IomadCreateCompany()
 *
 * @return bool
 */
function IomadCopySchool()
{
	global $error;

	if ( empty( $_REQUEST['iomad_create_company'] ) )
	{
		return false;
	}

	$return = IomadCreateCompany();

	if ( $return )
	{
		$company_id = $return;

		IomadCompanyCourseCategory( $company_id, SchoolInfo( 'TITLE' ) );
	}

	if ( $error )
	{
		echo ErrorMessage( $error, 'error' );
	}

	return $return;
}

// Register plugin functions to be hooked.
add_action( 'School_Setup/Schools.php|delete_school', 'IomadDeleteCompany' );

// Register plugin functions to be hooked.
add_action( 'School_Setup/Schools.php|update_school', 'IomadUpdateSchool' );

/**
 * Update School action
 * Edit company Title, Short name or City.
 *
 * @uses IomadEditCompany()
 *
 * @return bool
 */
function IomadUpdateSchool()
{
	global $RosarioPlugins;

	if ( empty( $RosarioPlugins['Moodle'] ) )
	{
		return false;
	}

	if ( ! isset( $_REQUEST['values']['TITLE'] )
		&& ! isset( $_REQUEST['values']['SHORT_NAME'] )
		&& ! isset( $_REQUEST['values']['CITY'] ) )
	{
		// No fields to Edit.
		return false;
	}

	return IomadEditCompany();
}

// Register plugin functions to be hooked.
add_action( 'Users/User.php|create_user', 'IomadCreateUser', 0, 20 ); // After Moodle plugin hook.

/**
 * Create User action
 * Assign companies to user
 *
 * @uses IomadUserAssignCompanies()
 *
 * @return bool
 */
function IomadCreateUser()
{
	global $RosarioPlugins;

	if ( empty( $RosarioPlugins['Moodle'] ) )
	{
		return false;
	}

	if ( ! UserStaffID()
		|| empty( $_REQUEST['staff']['PROFILE'] )
		|| $_REQUEST['staff']['PROFILE'] === 'parent'
		|| $_REQUEST['staff']['PROFILE'] === 'none' )
	{
		// Do not assign No Access / Parent to companies.
		return false;
	}

	$schools = [];

	if ( $_REQUEST['staff']['SCHOOLS'] )
	{
		$schools = trim( $_REQUEST['staff']['SCHOOLS'], ',' );

		$schools = explode( ',', $schools );
	}

	return IomadUserAssignCompanies( $_REQUEST['staff']['PROFILE'], $schools );
}

// Register plugin functions to be hooked.
add_action( 'Users/User.php|update_user', 'IomadUpdateUser', 0, 20 ); // After Moodle plugin hook.

/**
 * Update User action
 * (Un)assign companies to/from user
 *
 * @uses IomadUserUnassignCompanies()
 * @uses IomadUserAssignCompanies()
 *
 * @return bool
 */
function IomadUpdateUser()
{
	global $RosarioPlugins;

	if ( empty( $RosarioPlugins['Moodle'] ) )
	{
		return false;
	}

	if ( User( 'PROFILE' ) !== 'admin'
		|| ! UserStaffID() )
	{
		// Non admin user is editing its self profile.
		return false;
	}

	$user_profile = DBGetOne( "SELECT PROFILE
		FROM staff
		WHERE STAFF_ID='" . UserStaffID() . "'
		AND SYEAR='" . UserSyear() . "'" );

	if ( empty( $user_profile )
		|| $user_profile === 'parent'
		|| $user_profile === 'none' )
	{
		// Do not assign No Access / Parent to companies.
		return false;
	}

	if ( ! isset( $_REQUEST['staff']['SCHOOLS'] )
		&& ! isset( $_REQUEST['moodle_create_user'] ) ) // Do not check empty as Moodle sets it to false.
	{
		// Schools were not edited.
		return false;
	}

	$schools = [];

	$schools_RET = DBGetOne( "SELECT SCHOOLS
		FROM staff
		WHERE STAFF_ID='" . UserStaffID() . "'
		AND SYEAR='" . UserSyear() . "'" );

	if ( $schools_RET )
	{
		$schools = trim( $schools_RET, ',' );

		$schools = explode( ',', $schools );
	}

	IomadUserUnassignCompanies( $user_profile, $schools );

	return IomadUserAssignCompanies( $user_profile, $schools );
}


// Register plugin functions to be hooked.
add_action( 'Students/Student.php|create_student', 'IomadCreateStudent', 0, 20 ); // After Moodle plugin hook.

/**
 * Create Student action
 * Assign company to student
 *
 * @uses IomadUserAssignCompanies()
 *
 * @return bool
 */
function IomadCreateStudent()
{
	global $RosarioPlugins;

	if ( empty( $RosarioPlugins['Moodle'] ) )
	{
		return false;
	}

	if ( ! UserStudentID() )
	{
		return false;
	}

	$school_id = DBGetOne( "SELECT SCHOOL_ID
		FROM student_enrollment
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'
		LIMIT 1" );

	if ( ! $school_id )
	{
		// Student is not enrolled, inactive.
		return false;
	}

	$schools = [ $school_id ];

	return IomadUserAssignCompanies( 'student', $schools );
}

// Register plugin functions to be hooked.
add_action( 'Students/Student.php|update_student', 'IomadUpdateStudent', 0, 20 ); // After Moodle plugin hook.

/**
 * Update Student action
 * (Un)assign company to/from student
 *
 * @uses IomadUserUnassignCompanies()
 * @uses IomadUserAssignCompanies()
 *
 * @return bool
 */
function IomadUpdateStudent()
{
	global $RosarioPlugins;

	if ( empty( $RosarioPlugins['Moodle'] ) )
	{
		return false;
	}

	if ( User( 'PROFILE' ) !== 'admin'
		|| ! UserStudentID() )
	{
		// Non admin user is editing the profile.
		return false;
	}

	if ( empty( $_POST['month_values']['student_enrollment'] )
		&& empty( $_POST['values']['student_enrollment'] )
		&& empty( $_POST['month_values']['STUDENT_ENROLLMENT'] )
		&& empty( $_POST['values']['STUDENT_ENROLLMENT'] ) ) // Compat with RosarioSIS 9.3-.
	{
		// Schools were not edited.
		return false;
	}

	// Get Last enrolled school ID, where not Dropped yet.
	$school_id = DBGetOne( "SELECT SCHOOL_ID
		FROM student_enrollment
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND END_DATE IS NULL
		AND DROP_CODE IS NULL
		ORDER BY START_DATE DESC,ID
		LIMIT 1" );

	$schools = $school_id ? [ $school_id ] : [];

	IomadUserUnassignCompanies( 'student', $schools );

	if ( ! $school_id )
	{
		// Student is not enrolled, inactive.
		return false;
	}

	return IomadUserAssignCompanies( 'student', $schools );
}

// Register plugin functions to be hooked.
add_action( 'School_Setup/Rollover.php|rollover_after', 'IomadRolloverAfter', 0, 20 ); // After Moodle plugin hook.

/**
 * Rollover After action
 * Course Assign company for new school year courses.
 * Unassign current company from students
 * Assign next school year company to students
 *
 * @uses IomadUserUnassignCurrentCompany()
 * @uses IomadUserAssignCompany()
 *
 * @return bool
 */
function IomadRolloverAfter()
{
	global $RosarioPlugins;

	if ( empty( $RosarioPlugins['Moodle'] ) )
	{
		return false;
	}

	$next_syear = UserSyear() + 1;

	if ( ! empty( $_REQUEST['tables']['courses'] )
		|| ! empty( $_REQUEST['tables']['COURSES'] ) ) // Compat with RosarioSIS 9.3-.
	{
		$course_periods_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID
			FROM course_periods cp,moodlexrosario mxc
			WHERE cp.SYEAR='" . $next_syear . "'
			AND cp.SCHOOL_ID='" . UserSchool() . "'
			AND cp.ROLLOVER_ID IS NOT NULL
			AND cp.COURSE_PERIOD_ID=mxc.ROSARIO_ID
			AND mxc." . DBEscapeIdentifier( 'column' ) . "='course_period_id'" );

		foreach ( (array) $course_periods_RET as $rolled_course_period )
		{
			IomadCourseAssignCompany( $rolled_course_period['COURSE_PERIOD_ID'] );
		}
	}

	if ( empty( $_REQUEST['tables']['student_enrollment'] )
		&& empty( $_REQUEST['tables']['STUDENT_ENROLLMENT'] ) ) // Compat with RosarioSIS 9.3-.
	{
		// Not rolling Students, skip.
		return false;
	}

	// Unassign Students which were not Rolled (have no Enrollment in next Syear):
	// Students who "Do not enroll after this school year": NEXT_SCHOOL='-1',
	// or Students who "Next grade at current school": NEXT_SCHOOL='UserSchool()',
	// but have no Next Grade configured.
	// or Student is moved to another school: NEXT_SCHOOL NOT IN ('UserSchool()','0','-1').
	$unassign_students_RET = DBGet( "SELECT STUDENT_ID
		FROM student_enrollment
		WHERE NEXT_SCHOOL='-1'
		OR (NEXT_SCHOOL='" . UserSchool() . "'
			AND (SELECT NEXT_GRADE_ID
				FROM school_gradelevels g
				WHERE g.ID=GRADE_ID) IS NULL)
		OR NEXT_SCHOOL NOT IN ('" . UserSchool() . "','0','-1')" );

	foreach ( $unassign_students_RET as $unassign_student )
	{
		$return = IomadUserUnassignCurrentCompany( 'student', $unassign_student['STUDENT_ID'] );
	}

	// Assign Students to New School.
	$assign_students_RET = DBGet( "SELECT STUDENT_ID,NEXT_SCHOOL
		FROM student_enrollment
		WHERE NEXT_SCHOOL NOT IN ('" . UserSchool() . "','0','-1')" );

	foreach ( $assign_students_RET as $assign_student )
	{
		$return = IomadUserAssignCompany( 'student', $assign_student['STUDENT_ID'], $assign_student['NEXT_SCHOOL'] );
	}

	return $return;
}

// Register plugin functions to be hooked.
add_action( 'Scheduling/Courses.php|create_course_subject', 'IomadCreateCourseSubject', 0, 8 ); // Before Moodle plugin hook.

/**
 * Create Course Subject action
 * Set Subject Category Parent ID to Company Course Category.
 *
 * @uses IomadCompanyCourseCategory()
 * @global $_ROSARIO['MOODLE_COURSE_SUBJECT_PARENT_CATEGORY']
 *
 * @return bool
 */
function IomadCreateCourseSubject()
{
	global $_ROSARIO,
		$RosarioPlugins;

	if ( empty( $RosarioPlugins['Moodle'] ) )
	{
		return false;
	}

	$company_id = IomadGetSchoolCompanyID( UserSchool() );

	if ( ! $company_id )
	{
		return false;
	}

	// @since 5.8 Ability to set a Parent Category to Subjects. Used by Iomad plugin.
	$_REQUEST['MOODLE_COURSE_SUBJECT_PARENT_CATEGORY'] = IomadCompanyCourseCategory( $company_id );
}

// Register plugin functions to be hooked.
add_action( 'Scheduling/Courses.php|create_course_period', 'IomadCreateCoursePeriod', 0, 20 ); // After Moodle plugin hook.

/**
 * Create Course Period action
 * Assign course to company
 *
 * @uses IomadCourseAssignCompany()
 *
 * @return bool
 */
function IomadCreateCoursePeriod()
{
	global $RosarioPlugins,
		$id;

	if ( empty( $RosarioPlugins['Moodle'] ) )
	{
		return false;
	}

	$return = IomadCourseAssignCompany( $id );

	/*if ( ! $return )
	{*/
		return $return;
	//}

	// Do not Enrol Teacher, only for Students!
	/*$teacher_id = DBGetOne( "SELECT TEACHER_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $id . "'" );

	return IomadCourseAssignUser( $id, $teacher_id, 'staff_id' );*/
}


// Register plugin functions to be hooked.
add_action( 'Scheduling/Courses.php|update_course_period', 'IomadUpdateCoursePeriod', 0, 20 ); // After Moodle plugin hook.

/**
 * Update Course Period action
 * Assign course to company
 *
 * @uses IomadCreateCoursePeriod()
 *
 * @return bool
 */
function IomadUpdateCoursePeriod()
{
	return IomadCreateCoursePeriod();
}

// Register plugin functions to be hooked.
add_action( 'Scheduling/Schedule.php|schedule_student', 'IomadScheduleStudent', 0, 20 ); // After Moodle plugin hook.

/**
 * Schedule Student action
 * Assign course to user
 *
 * @uses IomadCourseAssignUser()
 *
 * @return bool
 */
function IomadScheduleStudent()
{
	global $RosarioPlugins,
		$date;

	if ( empty( $RosarioPlugins['Moodle'] ) )
	{
		return false;
	}

	return IomadCourseAssignUser( $_REQUEST['course_period_id'], UserStudentID(), 'student_id', $date );
}

// Register plugin functions to be hooked.
add_action( 'Scheduling/MassSchedule.php|schedule_student', 'IomadMassScheduleStudent', 0, 20 ); // After Moodle plugin hook.

/**
 * Mass Schedule Student action
 * Assign course to student
 *
 * @uses IomadCourseAssignUser()
 *
 * @return bool
 */
function IomadMassScheduleStudent()
{
	global $RosarioPlugins,
		$start_date,
		$student_id,
		$course_to_add;

	if ( empty( $RosarioPlugins['Moodle'] ) )
	{
		return false;
	}

	return IomadCourseAssignUser( $course_to_add['course_period_id'], $student_id, 'student_id', $start_date );
}

// Register plugin functions to be hooked.
add_action( 'Scheduling/Scheduler.php|schedule_student', 'IomadSchedulerScheduleStudent', 0, 20 ); // After Moodle plugin hook.

/**
 * Scheduler Schedule Student action
 * Assign course to student
 *
 * @uses IomadCourseAssignUser()
 *
 * @return bool
 */
function IomadSchedulerScheduleStudent()
{
	global $RosarioPlugins,
		$student_id,
		$course_period,
		$date;

	if ( empty( $RosarioPlugins['Moodle'] ) )
	{
		return false;
	}

	return IomadCourseAssignUser( $course_period['COURSE_PERIOD_ID'], $student_id, 'student_id', $date );
}
