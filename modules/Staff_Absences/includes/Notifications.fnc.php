<?php
/**
 * Staff Absences Notifications functions
 *
 * @package Staff Absences module
 */

/**
 * "Email Absence to" header
 *
 * @uses StaffAbsenceNotificationHeaderAdminTeachersEmail()
 * @uses StaffAbsenceNotificationHeaderCoursePeriodsEmail()
 * @uses StaffAbsenceNotificationHeaderEmailTextSubstitutions()
 *
 * @return string HTML header.
 */
function StaffAbsenceNotificationHeader()
{
	$header = '<fieldset><legend>' . dgettext( 'Staff_Absences', 'Email Absence to' ) . '</legend>';

	$staff_id = User( 'PROFILE' ) === 'admin' ? UserStaffID() : User( 'STAFF_ID' );

	$is_teacher = User( 'PROFILE' ) === 'teacher'
		|| DBGetOne( "SELECT 1 FROM staff
			WHERE STAFF_ID='" . (int) $staff_id . "'
			AND PROFILE='teacher'" );

	if ( $is_teacher )
	{
		// Email Absence to: Parent or Students enrolled in Course Periods (Teacher only).
		$header .= StaffAbsenceNotificationHeaderCoursePeriodsEmail( $staff_id );
	}

	// Email Absence to: Administrators and/or Teachers
	$header .= StaffAbsenceNotificationHeaderAdminTeachersEmail();

	$header .= StaffAbsenceNotificationHeaderEmailTextSubstitutions();

	return $header . '</fieldset>';
}

/**
 * Admin & Teachers email mutilple select inputs.
 * Header part
 *
 * @return string Header part.
 */
function StaffAbsenceNotificationHeaderAdminTeachersEmail()
{
	$users_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME,
		EMAIL,PROFILE
		FROM staff
		WHERE SYEAR='" . UserSyear() . "'
		AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)
		AND PROFILE IN ('admin','teacher')
		ORDER BY FULL_NAME" );

	// Get Administrators & Teachers with valid emails:
	$emailadmin_options = $emailteacher_options = [];

	foreach ( (array) $users_RET as $user )
	{
		if ( filter_var( $user['EMAIL'], FILTER_VALIDATE_EMAIL ) )
		{
			if ( $user['PROFILE'] === 'admin' )
			{
				$emailadmin_options[ $user['EMAIL'] ] = $user['FULL_NAME'];
			}
			elseif ( $user['PROFILE'] === 'teacher' )
			{
				$emailteacher_options[ $user['EMAIL'] ] = $user['FULL_NAME'];
			}
		}
	}

	$value = $allow_na = $div = false;

	// Chosen Multiple select inputs.
	$extra = 'multiple';

	$header = '<table><tr class="st"><td>';

	// @since RosarioSIS 10.7 Use Select2 input instead of Chosen, fix overflow issue.
	$select_input_function = function_exists( 'Select2Input' ) ? 'Select2Input' : 'ChosenSelectInput';

	$header .= $select_input_function(
		$value,
		'admin_emails[]',
		_( 'Administrators' ),
		$emailadmin_options,
		$allow_na,
		$extra,
		$div
	);

	$header .= '</td><td>';

	$header .= $select_input_function(
		$value,
		'teacher_emails[]',
		_( 'Teachers' ),
		$emailteacher_options,
		$allow_na,
		$extra,
		$div
	);

	$header .= '</td></tr></table>';

	return $header;
}

/**
 * Cours Periods (Students or Parents having students enrolled in) email mutilple select inputs.
 * Header part
 *
 * @return string Header part.
 */
function StaffAbsenceNotificationHeaderCoursePeriodsEmail( $staff_id )
{
	$cp_RET = DBGet( "SELECT COURSE_PERIOD_ID,TITLE
		FROM course_periods cp
		WHERE SYEAR='" . UserSyear() . "'
		AND TEACHER_ID='" . (int) $staff_id . "'
		AND MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
		AND EXISTS(SELECT 1 FROM schedule
			WHERE COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)
		ORDER BY SHORT_NAME" );

	if ( version_compare( ROSARIO_VERSION, '6.9', '>=' ) )
	{
		// @since 6.9 Add Secondary Teacher.
		$cp_RET = DBGet( "SELECT COURSE_PERIOD_ID,TITLE
			FROM course_periods cp
			WHERE SYEAR='" . UserSyear() . "'
			AND (TEACHER_ID='" . (int) $staff_id . "'
				OR SECONDARY_TEACHER_ID='" . (int) $staff_id . "')
			AND MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
			AND EXISTS(SELECT 1 FROM schedule
				WHERE COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)
			ORDER BY SHORT_NAME" );
	}

	if ( ! $cp_RET )
	{
		return '';
	}

	$value = $div = $extra = false;

	$header = '<table><tr><td>';

	$emailcpto_options = [
		'student' => dgettext( 'Staff_Absences', 'Students enrolled in cancelled classes' ),
		'parent' => dgettext( 'Staff_Absences', 'Parents having students enrolled in cancelled classes' ),
	];

	$header .= SelectInput(
		$value,
		'emailscpto',
		'',
		$emailcpto_options,
		'N/A',
		$extra,
		$div
	);

	$header .= '</td></tr></table>';

	return $header;
}

/**
 * Email text inputs + Substitutions inputs.
 * Header part
 *
 * @return string Header part.
 */
function StaffAbsenceNotificationHeaderEmailTextSubstitutions()
{
	$header = '<table class="width-100p">';

	$template = GetTemplate();

	// Email Template Textarea.
	$header .= '<tr class="st"><td>' . TextAreaInput(
		$template,
		'inputstaffabsenceemailtext',
		_( 'Email Text' ),
		'',
		false,
		'text'
	) . '</td></tr>';

	$substitutions = [
		'__FULL_NAME__' => _( 'Display Name' ),
		'__SCHOOL_ID__' => _( 'School' ),
		'__START_DATE__' => _( 'Start Date' ),
		'__END_DATE__' => dgettext( 'Staff_Absences', 'End Date' ),
	];

	$substitutions += SubstitutionsCustomFields( 'STAFF_ABSENCE' );

	$header .= '<tr><td>' . SubstitutionsInput( $substitutions ) . '</td></tr>';

	$header .= '</table>';

	return $header;
}

/**
 * Send Staff Absence notification email
 *
 * @param int   $absence_id Absence ID.
 * @param array $emails     Email addresses (to).
 *
 * @return bool True if email sent, else false.
 */
function StaffAbsenceSendNotification( $absence_id, $emails )
{
	require_once 'ProgramFunctions/SendEmail.fnc.php';

	$absence = StaffAbsenceGet( $absence_id );

	// Verify emails array and build TO.
	$to_emails = [];

	foreach ( (array) $emails as $email )
	{
		if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) )
		{
			$to_emails[] = $email;
		}
	}

	$template = GetTemplate();

	if ( ! $absence
		|| ! $to_emails
		|| ! $template )
	{
		return false;
	}

	// Email To.
	$to = implode( ', ', $to_emails );

	// Email From, if User has email set.
	$from = null;

	if ( filter_var( User( 'EMAIL' ), FILTER_VALIDATE_EMAIL ) )
	{
		$from = User( 'EMAIL' );
	}

	$full_name = StaffAbsenceMakeName( $absence['STAFF_ID'], 'FULL_NAME' );

	$substitutions = [
		'__FULL_NAME__' => $full_name,
		'__SCHOOL_ID__' => Config( 'NAME' ),
		'__START_DATE__' => StaffAbsenceMakeDate( $absence['START_DATE'] ),
		'__END_DATE__' => StaffAbsenceMakeDate( $absence['END_DATE'] ),
	];

	$substitutions += SubstitutionsCustomFieldsValues( 'STAFF_ABSENCE', $absence );

	// Email Message.
	$message = SubstitutionsTextMake( $substitutions, $template );

	// Email Subject.
	$subject = dgettext( 'Staff_Absences', 'New staff absence' ) . ' - ' . $full_name;

	//var_dump($to, $subject,$message, $from);

	return SendEmail( $to, $subject, $message, $from );
}

/**
 * Get Course Period related email addresses (Students or Parents having students enrolled in).
 *
 * @param int    $course_period_id Course Period ID.
 * @param string $type             Type: student or parent.
 *
 * @return array Course Period related email addresses.
 */
function StaffAbsenceNotificationGetCoursePeriodEmails( $course_period_id, $type = 'student' )
{
	/**
	 * SQL result as comma separated list
	 *
	 * @deprecated since RosarioSIS 10.8
	 *
	 * @since RosarioSIS 9.3 Add MySQL support
	 * @link https://dev.mysql.com/doc/refman/5.7/en/aggregate-functions.html#function_group-concat
	 *
	 * @param string $column    SQL column.
	 * @param string $separator List separator, default to comma.
	 *
	 * @return string MySQL or PostgreSQL function
	 */
	$sql_comma_separated_result = function( $column, $separator = ',' )
	{
		global $DatabaseType;

		if ( $DatabaseType === 'mysql' )
		{
			return "GROUP_CONCAT(" . $column . " SEPARATOR '" . DBEscapeString( $separator ) . "')";
		}

		return "ARRAY_TO_STRING(ARRAY_AGG(" . $column . "), '" . DBEscapeString( $separator ) . "')";
	};

	if ( function_exists( 'DBSQLCommaSeparatedResult' ) )
	{
		// @since RosarioSIS 10.8
		$sql_comma_separated_result = 'DBSQLCommaSeparatedResult';
	}

	// Get Students enrolled in Course Period.
	$students_RET = DBGet( "SELECT " . $sql_comma_separated_result( 'sch.STUDENT_ID' ) . " AS STUDENTS_LIST
		FROM schedule sch,student_enrollment se
		WHERE sch.SYEAR='" . UserSyear() . "'
		AND se.SYEAR=sch.SYEAR
		AND se.SCHOOL_ID=sch.SCHOOL_ID
		AND sch.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
		AND sch.START_DATE<=CURRENT_DATE
		AND se.START_DATE<=CURRENT_DATE
		AND (sch.END_DATE IS NULL OR sch.END_DATE>=CURRENT_DATE)
		AND (se.END_DATE IS NULL OR se.END_DATE>=CURRENT_DATE)" );

	if ( empty( $students_RET[1]['STUDENTS_LIST'] ) )
	{
		return [];
	}

	if ( $type === 'student' )
	{
		if ( ! Config( 'STUDENTS_EMAIL_FIELD' ) )
		{
			return [];
		}

		$email_field = Config( 'STUDENTS_EMAIL_FIELD' ) === 'USERNAME' ?
			'USERNAME' : 'CUSTOM_' . (int) Config( 'STUDENTS_EMAIL_FIELD' );

		$emails_RET = DBGet( "SELECT " . $email_field . " AS EMAIL
			FROM students
			WHERE STUDENT_ID IN(" . $students_RET[1]['STUDENTS_LIST'] . ")" );
	}
	else
	{
		$emails_RET = DBGet( "SELECT EMAIL
			FROM staff
			WHERE STAFF_ID IN(SELECT STAFF_ID
				FROM students_join_users
				WHERE STUDENT_ID IN(" . $students_RET[1]['STUDENTS_LIST'] . "))
			AND EMAIL IS NOT NULL" );
	}

	$emails = [];

	foreach( (array) $emails_RET as $email )
	{
		$emails[] = $email['EMAIL'];
	}

	return $emails;
}
