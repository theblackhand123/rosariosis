<?php
/**
 * Setup Assistant Steps functions
 *
 * @package Setup Assistant plugin
 */

function SetupAssistantSchoolSetupSteps( $profile )
{
	$steps = [];

	if ( $profile === 'admin' )
	{
		$steps[] = SetupAssistantStepsModuleTitle( 'School_Setup' );

		$modname = 'School_Setup/Schools.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'school_information',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Edit %s' ),
					_( 'School Information' )
				),
				'quick_setup_guide' => '#school-information',
				'help' => true,
			];
		}

		$modname = 'School_Setup/Configuration.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'edit_school_configuration',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Edit %s' ),
					_( 'Configuration' )
				),
				'quick_setup_guide' => '#school-configuration',
				'help' => true,
			];

			/*$steps[] = array(
				'id' => 'activate_modules',
				'link' => 'Modules.php?modname=' . $modname . '&tab=modules',
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Activate or deactivate %s' ),
					_( 'Modules' )
				),
			);

			$steps[] = array(
				'id' => 'activate_plugins',
				'link' => 'Modules.php?modname=' . $modname . '&tab=plugins',
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Activate or deactivate %s' ),
					_( 'Plugins' )
				),
			);*/
		}

		$modname = 'School_Setup/MarkingPeriods.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'create_marking_periods',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Create %s' ),
					_( 'Marking Periods' )
				),
				'quick_setup_guide' => '#marking-periods-setup',
				'help' => true,
			];
		}

		$modname = 'School_Setup/Calendar.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'create_calendar',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Create %s' ),
					_( 'Calendars' )
				),
				'quick_setup_guide' => '#school-calendar',
				'help' => true,
			];
		}

		$modname = 'School_Setup/Periods.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'create_periods',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Edit %s' ),
					_( 'Periods' )
				),
				'quick_setup_guide' => '#school-periods-setup',
				'help' => true,
			];
		}

		$modname = 'School_Setup/GradeLevels.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'edit_grade_levels',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Edit %s' ),
					_( 'Grade Levels' )
				),
				'quick_setup_guide' => '#grade-levels-setup',
				'help' => true,
			];
		}
	}

	return $steps;
}


function SetupAssistantGradesSteps( $profile )
{
	global $RosarioModules;

	$steps = [];

	if ( ! $RosarioModules['Grades'] )
	{
		return $steps;
	}

	if ( $profile === 'admin' )
	{
		$steps[] = SetupAssistantStepsModuleTitle( 'Grades' );

		$modname = 'Grades/ReportCardGrades.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'setup_grade_scales',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Setup %s' ),
					_( 'Grading Scales' )
				),
				'quick_setup_guide' => '#report-card-grades-setup',
				'help' => true,
			];
		}

		$modname = 'Grades/ReportCardCommentCodes.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'create_comment_codes',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Create %s' ),
					_( 'Comment Codes' )
				),
				'help' => true,
			];
		}

		$modname = 'Grades/ReportCardComments.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'create_report_card_comments',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Create %s' ),
					_( 'Report Card Comments' )
				),
				'quick_setup_guide' => '#report-card-comments-setup',
				'help' => true,
			];
		}
	}

	if ( $profile === 'teacher' )
	{
		$steps[] = SetupAssistantStepsModuleTitle( 'Grades' );

		$modname = 'Grades/Configuration.php';

		if ( AllowUse( $modname ) )
		{
			$steps[] = [
				'id' => 'edit_gradebook_configuration',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Setup %s' ),
					_( 'Gradebook' )
				),
				'help' => true,
			];
		}
	}

	return $steps;
}


function SetupAssistantAttendanceSteps( $profile )
{
	global $RosarioModules;

	$steps = [];

	if ( ! $RosarioModules['Attendance'] )
	{
		return $steps;
	}

	if ( $profile === 'admin' )
	{
		$steps[] = SetupAssistantStepsModuleTitle( 'Attendance' );

		$modname = 'Attendance/AttendanceCodes.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'edit_attendance_codes',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Edit %s' ),
					_( 'Attendance Codes' )
				),
				'quick_setup_guide' => '#attendance-codes-setup',
				'help' => true,
			];
		}
	}

	return $steps;
}


function SetupAssistantStudentsSteps( $profile )
{
	global $RosarioModules;

	$steps = [];

	if ( ! $RosarioModules['Students'] )
	{
		return $steps;
	}

	if ( $profile === 'admin' )
	{
		$steps[] = SetupAssistantStepsModuleTitle( 'Students' );

		$modname = 'Students/EnrollmentCodes.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'edit_enrollment_codes',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Edit %s' ),
					_( 'Enrollment Codes' )
				),
				'quick_setup_guide' => '#student-enrollment-codes-setup',
				'help' => true,
			];
		}

		$modname = 'Students/Student.php&include=General_Info&student_id=new';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'add_student',
				'modname' => $modname,
				'text' => _( 'Add a Student' ),
				'quick_setup_guide' => '#add-students',
				'help' => true,
			];
		}

		if ( ! empty( $RosarioModules['Students_Import'] ) )
		{
			$modname = 'Students_Import/StudentsImport.php';

			if ( AllowEdit( $modname ) )
			{
				$steps[] = [
					'id' => 'import_students',
					'modname' => $modname,
					'text' => dgettext( 'Setup_Assistant', 'Import Students' ),
					'quick_setup_guide' => '',
					'help' => true,
				];
			}
		}

		$modname = 'Students/StudentFields.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'create_student_fields',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Create %s' ),
					_( 'Student Fields' )
				),
				'quick_setup_guide' => '#add-custom-fields',
				'help' => true,
			];
		}
	}

	if ( $profile === 'parent' )
	{
		$steps[] = SetupAssistantStepsModuleTitle( 'Students' );

		$modname = 'Custom/Registration.php';

		if ( AllowUse( $modname ) )
		{
			$steps[] = [
				'id' => 'parent_registration',
				'modname' => $modname,
				'text' => _( 'Registration' ),
				'help' => true,
			];
		}
	}

	return $steps;
}


function SetupAssistantUsersSteps( $profile )
{
	global $_ROSARIO,
		$RosarioModules;

	$steps = [];

	if ( ! $RosarioModules['Users'] )
	{
		return $steps;
	}

	if ( $profile === 'admin' )
	{
		$steps[] = SetupAssistantStepsModuleTitle( 'Users' );

		$modname = 'Users/User.php&category_id=1';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'edit_user_info',
				'link' => 'Modules.php?modname=' . $modname . '&staff_id=' . User( 'STAFF_ID' ),
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Edit my %s' ),
					_( 'User Info' )
				),
				'help' => true,
			];
		}

		$modname = 'Users/User.php&staff_id=new';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'add_user',
				'modname' => $modname,
				'text' => _( 'Add a User' ),
				'quick_setup_guide' => '#add-users',
				'help' => true,
			];
		}

		$modname = 'Users/Profiles.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'edit_user_profiles',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Edit %s' ),
					_( 'User Profiles' )
				),
				'help' => true,
			];
		}

		$modname = 'Users/Exceptions.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'edit_user_permissions',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Edit %s' ),
					_( 'User Permissions' )
				),
				'help' => true,
			];
		}

		$modname = 'Users/UserFields.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'create_user_fields',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Create %s' ),
					_( 'User Fields' )
				),
				'help' => true,
			];
		}
	}

	if ( User( 'PROFILE' ) !== 'admin' )
	{
		$can_edit_from_where = " FROM profile_exceptions WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'";

		if ( ! User( 'PROFILE_ID' ) )
		{
			$can_edit_from_where = " FROM staff_exceptions WHERE USER_ID='" . User( 'STAFF_ID' ) . "'";
		}

		$can_edit_RET = DBGet( "SELECT MODNAME " . $can_edit_from_where .
			" AND MODNAME='Users/User.php&category_id=1'
			AND CAN_EDIT='Y'" );

		if ( $can_edit_RET )
		{
			// Teacher or Parent can Edit User Info.
			$_ROSARIO['allow_edit'] = true;
		}
	}

	if ( $profile === 'teacher' )
	{
		$steps[] = SetupAssistantStepsModuleTitle( 'Users' );

		$modname = 'Users/User.php&category_id=1';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'edit_user_info',
				'link' => 'Modules.php?modname=' . $modname . '&staff_id=' . User( 'STAFF_ID' ),
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Edit my %s' ),
					_( 'User Info' )
				),
				/*'help' => true,*/
			];
		}

		$modname = 'Users/User.php';

		if ( AllowUse( $modname ) )
		{
			$steps[] = [
				'id' => 'consult_my_schedule',
				'link' => 'Modules.php?modname=' . $modname . '&category_id=2&staff_id=' . User( 'STAFF_ID' ),
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Consult my %s' ),
					_( 'Schedule' )
				),
				/*'help' => true,*/
			];
		}
	}

	if ( $profile === 'parent' )
	{
		$steps[] = SetupAssistantStepsModuleTitle( 'Users' );

		$modname = 'Users/User.php&category_id=1';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'edit_user_info',
				'link' => 'Modules.php?modname=' . $modname . '&staff_id=' . User( 'STAFF_ID' ),
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Edit my %s' ),
					_( 'User Info' )
				),
				/*'help' => true,*/
			];
		}
	}

	return $steps;
}


function SetupAssistantSchedulingSteps( $profile )
{
	global $RosarioModules;

	$steps = [];

	if ( ! $RosarioModules['Scheduling'] )
	{
		return $steps;
	}

	if ( $profile === 'admin' )
	{
		$steps[] = SetupAssistantStepsModuleTitle( 'Scheduling' );

		$modname = 'Scheduling/Courses.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'create_courses',
				'modname' => $modname,
				'text' => sprintf(
					dgettext( 'Setup_Assistant', 'Create %s' ),
					_( 'Courses' )
				),
				'quick_setup_guide' => '#create-courses',
				'help' => true,
			];
		}

		$modname = 'Scheduling/MassSchedule.php';

		if ( AllowEdit( $modname ) )
		{
			$steps[] = [
				'id' => 'group_schedule',
				'modname' => $modname,
				'text' => _( 'Group Schedule' ),
				'quick_setup_guide' => '#schedule-students',
				'help' => true,
			];
		}
	}

	if ( $profile === 'teacher' )
	{
		$steps[] = SetupAssistantStepsModuleTitle( 'Scheduling' );

		$modname = 'Scheduling/PrintClassPictures.php';

		if ( AllowUse( $modname ) )
		{
			$steps[] = [
				'id' => 'print_class_pictures',
				'modname' => $modname,
				'text' => _( 'Print Class Pictures' ),
				/*'help' => true,*/
			];
		}
	}

	return $steps;
}
