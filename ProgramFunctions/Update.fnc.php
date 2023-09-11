<?php
/**
 * Update functions
 *
 * Incremental updates
 *
 * Update() function called if ROSARIO_VERSION != version in DB
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Update manager function
 *
 * Call the specific versions functions
 *
 * @since 2.9
 *
 * @return boolean false if wrong version or update failed, else true
 */
function Update()
{
	$from_version = Config( 'VERSION' );

	$to_version = ROSARIO_VERSION;

	/**
	 * Check if Update() version < ROSARIO_VERSION.
	 *
	 * Prevent DB version update if new Update.fnc.php file has NOT been uploaded YET.
	 * Update must be run once both new Warehouse.php & Update.fnc.php files are uploaded.
	 */
	if ( version_compare( '11.2.1', ROSARIO_VERSION, '<' ) )
	{
		return false;
	}

	// Check if version in DB >= ROSARIO_VERSION.
	if ( version_compare( $from_version, $to_version, '>=' ) )
	{
		return false;
	}

	require_once 'ProgramFunctions/UpdateV2_3.fnc.php';
	require_once 'ProgramFunctions/UpdateV4_5.fnc.php';

	$return = true;

	switch ( true )
	{
		case version_compare( $from_version, '2.9-alpha', '<' ) :

			$return = _update29alpha();

		case version_compare( $from_version, '2.9.2', '<' ) :

			$return = _update292();

		case version_compare( $from_version, '2.9.5', '<' ) :

			$return = _update295();

		case version_compare( $from_version, '2.9.12', '<' ) :

			$return = _update2912();

		case version_compare( $from_version, '2.9.13', '<' ) :

			$return = _update2913();

		case version_compare( $from_version, '2.9.14', '<' ) :

			$return = _update2914();

		case version_compare( $from_version, '3.0', '<' ) :

			$return = _update30();

		case version_compare( $from_version, '3.1', '<' ) :

			$return = _update31();

		case version_compare( $from_version, '3.5', '<' ) :

			$return = _update35();

		case version_compare( $from_version, '3.7-beta', '<' ) :

			$return = _update37beta();

		case version_compare( $from_version, '3.9', '<' ) :

			$return = _update39();

		case version_compare( $from_version, '4.0-beta', '<' ) :

			$return = _update40beta();

		case version_compare( $from_version, '4.2-beta', '<' ) :

			$return = _update42beta();

		case version_compare( $from_version, '4.3-beta', '<' ) :

			$return = _update43beta();

		case version_compare( $from_version, '4.4-beta', '<' ) :

			$return = _update44beta();

		case version_compare( $from_version, '4.4-beta2', '<' ) :

			$return = _update44beta2();

		case version_compare( $from_version, '4.5-beta2', '<' ) :

			$return = _update45beta2();

		case version_compare( $from_version, '4.6-beta', '<' ) :

			$return = _update46beta();

		case version_compare( $from_version, '4.7-beta', '<' ) :

			$return = _update47beta();

		case version_compare( $from_version, '4.7-beta2', '<' ) :

			$return = _update47beta2();

		case version_compare( $from_version, '4.9-beta', '<' ) :

			$return = _update49beta();

		case version_compare( $from_version, '5.0-beta', '<' ) :

			$return = _update50beta();

		case version_compare( $from_version, '5.0.1', '<' ) :

			$return = _update501();

		case version_compare( $from_version, '5.2-beta', '<' ) :

			$return = _update52beta();

		case version_compare( $from_version, '5.3-beta', '<' ) :

			$return = _update53beta();

		case version_compare( $from_version, '5.4.1', '<' ) :

			$return = _update541();

		case version_compare( $from_version, '5.4.2', '<' ) :

			$return = _update542();

		case version_compare( $from_version, '5.5-beta3', '<' ) :

			$return = _update55beta3();

		case version_compare( $from_version, '5.7', '<' ) :

			$return = _update57();

		case version_compare( $from_version, '5.8-beta5', '<' ) :

			$return = _update58beta5();

		case version_compare( $from_version, '5.9-beta', '<' ) :

			$return = _update59beta();

		case version_compare( $from_version, '5.9-beta2', '<' ) :

			$return = _update59beta2();

		case version_compare( $from_version, '5.9', '<' ) :

			$return = _update59();

		case version_compare( $from_version, '5.9.1', '<' ) :

			$return = _update591();

		case version_compare( $from_version, '6.3', '<' ) :

			$return = _update63();

		case version_compare( $from_version, '6.6', '<' ) :

			$return = _update66();

		case version_compare( $from_version, '6.9-beta', '<' ) :

			$return = _update69beta();

		case version_compare( $from_version, '8.1', '<' ) :

			$return = _update81();

		case version_compare( $from_version, '8.3', '<' ) :

			$return = _update83();

		case version_compare( $from_version, '8.4', '<' ) :

			$return = _update84();

		case version_compare( $from_version, '8.5', '<' ) :

			$return = _update85();

		case version_compare( $from_version, '8.7', '<' ) :

			$return = _update87();

		case version_compare( $from_version, '9.2', '<' ) :

			$return = _update92();

		case version_compare( $from_version, '9.2.1', '<' ) :

			$return = _update921();

		case version_compare( $from_version, '9.3', '<' ) :

			$return = _update93();

		case version_compare( $from_version, '10.1', '<' ) :

			$return = _update101();

		case version_compare( $from_version, '10.6.1', '<' ) :

			$return = _update1061();

		case version_compare( $from_version, '10.8', '<' ) :

			$return = _update108();

		case version_compare( $from_version, '10.9', '<' ) :

			$return = _update109();

		case version_compare( $from_version, '11.0', '<' ) :

			$return = _update110();

		case version_compare( $from_version, '11.1', '<' ) :

			$return = _update111();

		case version_compare( $from_version, '11.2', '<' ) :

			$return = _update112();

		case version_compare( $from_version, '11.2.1', '<' ) :

			$return = _update1121();
	}

	// Update version in DB config table.
	Config( 'VERSION', ROSARIO_VERSION );

	return $return;
}


/**
 * Is function called by Update()?
 *
 * Local function
 *
 * @example _isCallerUpdate( debug_backtrace() );
 *
 * @since 2.9.13
 *
 * @param  array   $callers debug_backtrace().
 *
 * @return boolean          Exit with error message if not called by Update().
 */
function _isCallerUpdate( $callers )
{
	if ( ! isset( $callers[1]['function'] )
		|| $callers[1]['function'] !== 'Update' )
	{
		exit( 'Error: the update functions must be called by Update() only!' );
	}

	return true;
}


/**
 * Update to version 6.3
 *
 * 1. Add CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL to config table.
 *
 * Local function
 *
 * @since 6.3
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update63()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL to config table.
	 */
	$default_school_added = DBGetOne( "SELECT 1 FROM config
		WHERE TITLE='CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL'" );

	if ( ! $default_school_added )
	{
		DBQuery( "INSERT INTO config VALUES (0, 'CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL', NULL);" );
	}

	return $return;
}

/**
 * Update to version 6.6
 *
 * Add Registration program for Administrators.
 * 1. Add Custom/Registration.php to profile_exceptions table.
 *
 * Local function
 *
 * @since 6.6
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update66()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add Custom/Registration.php to profile_exceptions table.
	 */
	$admin_profiles_RET = DBGet( "SELECT id
		FROM user_profiles
		WHERE profile='admin'" );

	foreach ( (array) $admin_profiles_RET as $admin_profile )
	{
		$profile_id = $admin_profile['ID'];

		$registration_profile_exceptions_exists = DBGet( "SELECT 1
			FROM profile_exceptions
			WHERE profile_id='" . $profile_id . "'
			AND modname='Custom/Registration.php'" );

		if ( ! $registration_profile_exceptions_exists )
		{
			DBQuery( "INSERT INTO profile_exceptions
				VALUES ('" . $profile_id . "', 'Custom/Registration.php', 'Y', 'Y');" );
		}
	}

	return $return;
}


/**
 * Update to version 6.9
 *
 * 1. course_periods table: Add SECONDARY_TEACHER_ID column.
 *
 * Local function
 *
 * @since 6.9
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update69beta()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. course_periods table: Add SECONDARY_TEACHER_ID column.
	 */
	$secondary_teacher_id_column_exists = DBGetOne( "SELECT 1 FROM pg_attribute
		WHERE attrelid=(SELECT oid FROM pg_class WHERE relname='course_periods')
		AND attname='secondary_teacher_id';" );

	if ( ! $secondary_teacher_id_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY course_periods
			ADD COLUMN secondary_teacher_id integer REFERENCES staff(staff_id);" );
	}

	return $return;
}


/**
 * Update to version 8.1
 *
 * 1. accounting_salaries table: Add FILE_ATTACHED column.
 * 2. billing_fees table: Add FILE_ATTACHED column.
 *
 * Local function
 *
 * @since 8.1
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update81()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. accounting_salaries table: Add FILE_ATTACHED column.
	 */
	$file_attached_column_exists = DBGetOne( "SELECT 1 FROM pg_attribute
		WHERE attrelid=(SELECT oid FROM pg_class WHERE relname='accounting_salaries')
		AND attname='file_attached';" );

	if ( ! $file_attached_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY accounting_salaries
			ADD COLUMN file_attached text;" );
	}

	/**
	 * 2. billing_fees table: Add FILE_ATTACHED column.
	 */
	$file_attached_column_exists = DBGetOne( "SELECT 1 FROM pg_attribute
		WHERE attrelid=(SELECT oid FROM pg_class WHERE relname='billing_fees')
		AND attname='file_attached';" );

	if ( ! $file_attached_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY billing_fees
			ADD COLUMN file_attached text;" );
	}

	return $return;
}


/**
 * Update to version 8.3
 *
 * 1. accounting_payments table: Add FILE_ATTACHED column.
 * 2. billing_payments table: Add FILE_ATTACHED column.
 *
 * Local function
 *
 * @since 8.3
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update83()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. accounting_payments table: Add FILE_ATTACHED column.
	 */
	$file_attached_column_exists = DBGetOne( "SELECT 1 FROM pg_attribute
		WHERE attrelid=(SELECT oid FROM pg_class WHERE relname='accounting_payments')
		AND attname='file_attached';" );

	if ( ! $file_attached_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY accounting_payments
			ADD COLUMN file_attached text;" );
	}

	/**
	 * 2. billing_payments table: Add FILE_ATTACHED column.
	 */
	$file_attached_column_exists = DBGetOne( "SELECT 1 FROM pg_attribute
		WHERE attrelid=(SELECT oid FROM pg_class WHERE relname='billing_payments')
		AND attname='file_attached';" );

	if ( ! $file_attached_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY billing_payments
			ADD COLUMN file_attached text;" );
	}

	return $return;
}


/**
 * Update to version 8.4
 *
 * 1. gradebook_grades table: Change comment column type to text.
 * 2. accounting_incomes table: Add FILE_ATTACHED column.
 *
 * Local function
 *
 * @since 8.4
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update84()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. gradebook_grades table:
	 * Change comment column type to text
	 * Was character varying(100) which was too short for teachers.
	 */
	DBQuery( "ALTER TABLE gradebook_grades
		ALTER COLUMN comment TYPE text;" );

	/**
	 * 2. accounting_incomes table: Add FILE_ATTACHED column.
	 */
	$file_attached_column_exists = DBGetOne( "SELECT 1 FROM pg_attribute
		WHERE attrelid=(SELECT oid FROM pg_class WHERE relname='accounting_incomes')
		AND attname='file_attached';" );

	if ( ! $file_attached_column_exists )
	{
		DBQuery( "ALTER TABLE ONLY accounting_incomes
			ADD COLUMN file_attached text;" );
	}

	return $return;
}


/**
 * Update to version 8.5
 *
 * 1. profile_exceptions table: Add Admin Student Payments Delete restriction.
 * 2. staff_exceptions table: Add Admin Student Payments Delete restriction.
 *
 * Local function
 *
 * @since 8.5
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update85()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. profile_exceptions table
	 * Add Admin Student Payments Delete restriction.
	 */
	DBQuery( "INSERT INTO profile_exceptions
		SELECT profile_id,'Student_Billing/StudentPayments.php&modfunc=remove','Y','Y'
		FROM profile_exceptions
		WHERE modname='Student_Billing/StudentPayments.php'
		AND can_edit='Y'
		AND profile_id NOT IN(SELECT profile_id
			FROM profile_exceptions
			WHERE modname='Student_Billing/StudentPayments.php&modfunc=remove');" );

	/**
	 * 2. staff_exceptions table
	 * Add Admin Student Payments Delete restriction.
	 */
	DBQuery( "INSERT INTO staff_exceptions
		SELECT user_id,'Student_Billing/StudentPayments.php&modfunc=remove','Y','Y'
		FROM staff_exceptions
		WHERE modname='Student_Billing/StudentPayments.php'
		AND can_edit='Y'
		AND user_id NOT IN(SELECT user_id
			FROM staff_exceptions
			WHERE modname='Student_Billing/StudentPayments.php&modfunc=remove');" );

	return $return;
}

/**
 * Update to version 8.7
 *
 * 1. Fix SQL transcript_grades view, grades were duplicated for each school year
 *
 * Local function
 *
 * @since 8.7
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update87()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	// 1. Fix SQL transcript_grades view, grades were duplicated for each school year.
	DBQuery( "CREATE OR REPLACE VIEW transcript_grades AS
		SELECT mp.syear,mp.school_id,mp.marking_period_id,mp.mp_type,
		mp.short_name,mp.parent_id,mp.grandparent_id,
		(SELECT mp2.end_date
			FROM student_report_card_grades
				JOIN marking_periods mp2
				ON mp2.marking_period_id::text = student_report_card_grades.marking_period_id::text
			WHERE student_report_card_grades.student_id = sms.student_id::numeric
			AND (student_report_card_grades.marking_period_id::text = mp.parent_id::text
				OR student_report_card_grades.marking_period_id::text = mp.grandparent_id::text)
			AND student_report_card_grades.course_title::text = srcg.course_title::text
			ORDER BY mp2.end_date LIMIT 1) AS parent_end_date,
		mp.end_date,sms.student_id,
		(sms.cum_weighted_factor * COALESCE(schools.reporting_gp_scale, (SELECT reporting_gp_scale FROM schools WHERE mp.school_id = id ORDER BY syear LIMIT 1))) AS cum_weighted_gpa,
		(sms.cum_unweighted_factor * schools.reporting_gp_scale) AS cum_unweighted_gpa,
		sms.cum_rank,sms.mp_rank,sms.class_size,
		((sms.sum_weighted_factors / sms.count_weighted_factors) * schools.reporting_gp_scale) AS weighted_gpa,
		((sms.sum_unweighted_factors / sms.count_unweighted_factors) * schools.reporting_gp_scale) AS unweighted_gpa,
		sms.grade_level_short,srcg.comment,srcg.grade_percent,srcg.grade_letter,
		srcg.weighted_gp,srcg.unweighted_gp,srcg.gp_scale,srcg.credit_attempted,
		srcg.credit_earned,srcg.course_title,srcg.school AS school_name,
		schools.reporting_gp_scale AS school_scale,
		((sms.cr_weighted_factors / sms.count_cr_factors::numeric) * schools.reporting_gp_scale) AS cr_weighted_gpa,
		((sms.cr_unweighted_factors / sms.count_cr_factors::numeric) * schools.reporting_gp_scale) AS cr_unweighted_gpa,
		(sms.cum_cr_weighted_factor * schools.reporting_gp_scale) AS cum_cr_weighted_gpa,
		(sms.cum_cr_unweighted_factor * schools.reporting_gp_scale) AS cum_cr_unweighted_gpa,
		srcg.class_rank,sms.comments,
		srcg.credit_hours
		FROM marking_periods mp
			JOIN student_report_card_grades srcg
			ON mp.marking_period_id::text = srcg.marking_period_id::text
			JOIN student_mp_stats sms
			ON sms.marking_period_id::numeric = mp.marking_period_id
				AND sms.student_id::numeric = srcg.student_id
			LEFT OUTER JOIN schools
			ON mp.school_id = schools.id
				AND (mp.mp_source<>'History' AND mp.syear = schools.syear)
					OR (mp.mp_source='History' AND mp.syear=(SELECT syear FROM schools WHERE mp.school_id = id ORDER BY syear LIMIT 1))
		ORDER BY srcg.course_period_id;" );

	return $return;
}

/**
 * Update to version 9.2
 *
 * 1. Drop transcript_grades view, so we can alter student_report_card_grades table
 * 2. SQL student_report_card_grades table: convert MARKING_PERIOD_ID column to integer
 * 3. Recreate transcript_grades view
 *
 * Local function
 *
 * @since 9.2
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update92()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	// 1. Drop transcript_grades view, so we can alter student_report_card_grades table
	DBQuery( "DROP VIEW transcript_grades;" );

	// 2. SQL student_report_card_grades table: convert MARKING_PERIOD_ID column to integer
	DBQuery( "ALTER TABLE student_report_card_grades
	ALTER marking_period_id TYPE integer USING marking_period_id::integer;" );

	// 3. Recreate transcript_grades view
	DBQuery( "CREATE VIEW transcript_grades AS
	SELECT mp.syear,mp.school_id,mp.marking_period_id,mp.mp_type,
	mp.short_name,mp.parent_id,mp.grandparent_id,
	(SELECT mp2.end_date
		FROM student_report_card_grades
			JOIN marking_periods mp2
			ON mp2.marking_period_id = student_report_card_grades.marking_period_id
		WHERE student_report_card_grades.student_id = sms.student_id
		AND (student_report_card_grades.marking_period_id = mp.parent_id
			OR student_report_card_grades.marking_period_id = mp.grandparent_id)
		AND student_report_card_grades.course_title = srcg.course_title
		ORDER BY mp2.end_date LIMIT 1) AS parent_end_date,
	mp.end_date,sms.student_id,
	(sms.cum_weighted_factor * COALESCE(schools.reporting_gp_scale, (SELECT reporting_gp_scale FROM schools WHERE mp.school_id = id ORDER BY syear LIMIT 1))) AS cum_weighted_gpa,
	(sms.cum_unweighted_factor * schools.reporting_gp_scale) AS cum_unweighted_gpa,
	sms.cum_rank,sms.mp_rank,sms.class_size,
	((sms.sum_weighted_factors / sms.count_weighted_factors) * schools.reporting_gp_scale) AS weighted_gpa,
	((sms.sum_unweighted_factors / sms.count_unweighted_factors) * schools.reporting_gp_scale) AS unweighted_gpa,
	sms.grade_level_short,srcg.comment,srcg.grade_percent,srcg.grade_letter,
	srcg.weighted_gp,srcg.unweighted_gp,srcg.gp_scale,srcg.credit_attempted,
	srcg.credit_earned,srcg.course_title,srcg.school AS school_name,
	schools.reporting_gp_scale AS school_scale,
	((sms.cr_weighted_factors / sms.count_cr_factors::numeric) * schools.reporting_gp_scale) AS cr_weighted_gpa,
	((sms.cr_unweighted_factors / sms.count_cr_factors::numeric) * schools.reporting_gp_scale) AS cr_unweighted_gpa,
	(sms.cum_cr_weighted_factor * schools.reporting_gp_scale) AS cum_cr_weighted_gpa,
	(sms.cum_cr_unweighted_factor * schools.reporting_gp_scale) AS cum_cr_unweighted_gpa,
	srcg.class_rank,sms.comments,
	srcg.credit_hours
	FROM marking_periods mp
		JOIN student_report_card_grades srcg
		ON mp.marking_period_id = srcg.marking_period_id
		JOIN student_mp_stats sms
		ON sms.marking_period_id = mp.marking_period_id
			AND sms.student_id = srcg.student_id
		LEFT OUTER JOIN schools
		ON mp.school_id = schools.id
			AND (mp.mp_source<>'History' AND mp.syear = schools.syear)
				OR (mp.mp_source='History' AND mp.syear=(SELECT syear FROM schools WHERE mp.school_id = id ORDER BY syear LIMIT 1))
	ORDER BY srcg.course_period_id;" );

	return $return;
}


/**
 * Update to version 9.2.1
 *
 * 1. SQL set default nextval (auto increment) for RosarioSIS version < 5.0 on install,
 * serial column (auto increment was implemented in RosarioSIS 5.0)
 * 2. SQL set default nextval (auto increment) for old add-on modules.
 *
 * Local function
 *
 * @since 9.2.1
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update921()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	$set_default_nextval = function( $table, $id_column, $sequence )
	{
		if ( strlen( $sequence) > 63 )
		{
			$cut_at_char = ( 63 - strlen( '_seq' ) );

			// Note: sequence name is limited to 63 chars
			// @link https://www.postgresql.org/docs/9.0/sql-syntax-lexical.html#SQL-SYNTAX-IDENTIFIERS
			$sequence = substr( $sequence, 0, $cut_at_char ) . '_seq';
		}

		$sequence_exists = DBGetOne( "SELECT 1 FROM pg_class
			WHERE relname='" . DBEscapeString( $sequence ) . "';" );

		if ( $sequence_exists )
		{
			DBQuery( "ALTER TABLE " . DBEscapeIdentifier( $table ) .
				" ALTER COLUMN " . DBEscapeIdentifier( $id_column ) .
				" SET DEFAULT NEXTVAL('" . DBEscapeString( $sequence ) . "');" );
		}
	};


	/**
	 * 1. Set default nextval (auto increment) for RosarioSIS version < 5.0 on install,
	 * serial column (auto increment was implemented in RosarioSIS 5.0)
	 */
	$set_default_nextval( 'user_profiles', 'id', 'user_profiles_id_seq' );
	$set_default_nextval( 'students_join_people', 'id', 'students_join_people_id_seq' );
	$set_default_nextval( 'students_join_address', 'id', 'students_join_address_id_seq' );
	$set_default_nextval( 'students', 'student_id', 'students_student_id_seq' );
	$set_default_nextval( 'student_report_card_grades', 'id', 'student_report_card_grades_id_seq' );
	$set_default_nextval( 'student_medical_visits', 'id', 'student_medical_visits_id_seq' );
	$set_default_nextval( 'student_medical_alerts', 'id', 'student_medical_alerts_id_seq' );
	$set_default_nextval( 'student_medical', 'id', 'student_medical_id_seq' );
	$set_default_nextval( 'student_field_categories', 'id', 'student_field_categories_id_seq' );
	$set_default_nextval( 'student_enrollment_codes', 'id', 'student_enrollment_codes_id_seq' );
	$set_default_nextval( 'student_enrollment', 'id', 'student_enrollment_id_seq' );
	$set_default_nextval( 'staff_fields', 'id', 'staff_fields_id_seq' );
	$set_default_nextval( 'staff_field_categories', 'id', 'staff_field_categories_id_seq' );
	$set_default_nextval( 'staff', 'staff_id', 'staff_staff_id_seq' );
	$set_default_nextval( 'school_periods', 'period_id', 'school_periods_period_id_seq' );
	$set_default_nextval( 'schools', 'id', 'schools_id_seq' );
	$set_default_nextval( 'school_gradelevels', 'id', 'school_gradelevels_id_seq' );
	$set_default_nextval( 'school_fields', 'id', 'school_fields_id_seq' );
	$set_default_nextval( 'schedule_requests', 'request_id', 'schedule_requests_request_id_seq' );
	$set_default_nextval( 'resources', 'id', 'resources_id_seq' );
	$set_default_nextval( 'report_card_grades', 'id', 'report_card_grades_id_seq' );
	$set_default_nextval( 'report_card_grade_scales', 'id', 'report_card_grade_scales_id_seq' );
	$set_default_nextval( 'report_card_comments', 'id', 'report_card_comments_id_seq' );
	$set_default_nextval( 'report_card_comment_codes', 'id', 'report_card_comment_codes_id_seq' );
	$set_default_nextval( 'report_card_comment_code_scales', 'id', 'report_card_comment_code_scales_id_seq' );
	$set_default_nextval( 'report_card_comment_categories', 'id', 'report_card_comment_categories_id_seq' );
	$set_default_nextval( 'portal_polls', 'id', 'portal_polls_id_seq' );
	$set_default_nextval( 'portal_poll_questions', 'id', 'portal_poll_questions_id_seq' );
	$set_default_nextval( 'portal_notes', 'id', 'portal_notes_id_seq' );
	$set_default_nextval( 'people_join_contacts', 'id', 'people_join_contacts_id_seq' );
	$set_default_nextval( 'people_fields', 'id', 'people_fields_id_seq' );
	$set_default_nextval( 'people_field_categories', 'id', 'people_field_categories_id_seq' );
	$set_default_nextval( 'people', 'person_id', 'people_person_id_seq' );
	$set_default_nextval( 'school_marking_periods', 'marking_period_id', 'school_marking_periods_marking_period_id_seq' );
	$set_default_nextval( 'gradebook_assignments', 'assignment_id', 'gradebook_assignments_assignment_id_seq' );
	$set_default_nextval( 'gradebook_assignment_types', 'assignment_type_id', 'gradebook_assignment_types_assignment_type_id_seq' );
	$set_default_nextval( 'food_service_transactions', 'transaction_id', 'food_service_transactions_transaction_id_seq' );
	$set_default_nextval( 'food_service_staff_transactions', 'transaction_id', 'food_service_staff_transactions_transaction_id_seq' );
	$set_default_nextval( 'food_service_menus', 'menu_id', 'food_service_menus_menu_id_seq' );
	$set_default_nextval( 'food_service_menu_items', 'menu_item_id', 'food_service_menu_items_menu_item_id_seq' );
	$set_default_nextval( 'food_service_items', 'item_id', 'food_service_items_item_id_seq' );
	$set_default_nextval( 'food_service_categories', 'category_id', 'food_service_categories_category_id_seq' );
	$set_default_nextval( 'eligibility_activities', 'id', 'eligibility_activities_id_seq' );
	$set_default_nextval( 'discipline_referrals', 'id', 'discipline_referrals_id_seq' );
	$set_default_nextval( 'discipline_fields', 'id', 'discipline_fields_id_seq' );
	$set_default_nextval( 'discipline_field_usage', 'id', 'discipline_field_usage_id_seq' );
	$set_default_nextval( 'custom_fields', 'id', 'custom_fields_id_seq' );
	$set_default_nextval( 'course_subjects', 'subject_id', 'course_subjects_subject_id_seq' );
	$set_default_nextval( 'course_period_school_periods', 'course_period_school_periods_id', 'course_period_school_periods_course_period_school_periods_id_seq' );
	$set_default_nextval( 'courses', 'course_id', 'courses_course_id_seq' );
	$set_default_nextval( 'course_periods', 'course_period_id', 'course_periods_course_period_id_seq' );
	$set_default_nextval( 'calendar_events', 'id', 'calendar_events_id_seq' );
	$set_default_nextval( 'billing_payments', 'id', 'billing_payments_id_seq' );
	$set_default_nextval( 'billing_fees', 'id', 'billing_fees_id_seq' );
	$set_default_nextval( 'attendance_codes', 'id', 'attendance_codes_id_seq' );
	$set_default_nextval( 'attendance_code_categories', 'id', 'attendance_code_categories_id_seq' );
	$set_default_nextval( 'attendance_calendars', 'calendar_id', 'attendance_calendars_calendar_id_seq' );
	$set_default_nextval( 'address_fields', 'id', 'address_fields_id_seq' );
	$set_default_nextval( 'address_field_categories', 'id', 'address_field_categories_id_seq' );
	$set_default_nextval( 'address', 'address_id', 'address_address_id_seq' );
	$set_default_nextval( 'accounting_payments', 'id', 'accounting_payments_id_seq' );
	$set_default_nextval( 'accounting_salaries', 'id', 'accounting_salaries_id_seq' );
	$set_default_nextval( 'accounting_incomes', 'id', 'accounting_incomes_id_seq' );

	/**
	 * 2. Set default nextval (auto increment) for old add-on modules.
	 */
	$set_default_nextval( 'billing_fees_monthly', 'id', 'billing_fees_monthly_id_seq' );
	$set_default_nextval( 'school_inventory_categories', 'category_id', 'school_inventory_categories_category_id_seq' );
	$set_default_nextval( 'school_inventory_items', 'item_id', 'school_inventory_items_item_id_seq' );
	$set_default_nextval( 'saved_reports', 'id', 'saved_reports_id_seq' );
	$set_default_nextval( 'saved_calculations', 'id', 'saved_calculations_id_seq' );
	$set_default_nextval( 'messages', 'message_id', 'messages_message_id_seq' );

	return $return;
}


/**
 * Update to version 9.3
 *
 * 1. config table: update DISPLAY_NAME to use CONCAT() instead of pipes ||.
 *
 * Local function
 *
 * @since 9.3
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update93()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. config table: update DISPLAY_NAME to use CONCAT() instead of pipes ||.
	 */
	$display_names_update = [
		"FIRST_NAME||' '||LAST_NAME" => "CONCAT(FIRST_NAME,' ',LAST_NAME)",
		"FIRST_NAME||' '||LAST_NAME||coalesce(' '||NAME_SUFFIX,' ')" => "CONCAT(FIRST_NAME,' ',LAST_NAME,coalesce(NULLIF(CONCAT(' ',NAME_SUFFIX),' '),''))",
		"FIRST_NAME||coalesce(' '||MIDDLE_NAME||' ',' ')||LAST_NAME" => "CONCAT(FIRST_NAME,coalesce(NULLIF(CONCAT(' ',MIDDLE_NAME,' '),'  '),' '),LAST_NAME)",
		"FIRST_NAME||', '||LAST_NAME||coalesce(' '||MIDDLE_NAME,' ')" => "CONCAT(FIRST_NAME,', ',LAST_NAME,coalesce(NULLIF(CONCAT(' ',MIDDLE_NAME),' '),''))",
		"LAST_NAME||' '||FIRST_NAME" => "CONCAT(LAST_NAME,' ',FIRST_NAME)",
		"LAST_NAME||', '||FIRST_NAME" => "CONCAT(LAST_NAME,', ',FIRST_NAME)",
		"LAST_NAME||', '||FIRST_NAME||' '||COALESCE(MIDDLE_NAME,' ')" => "CONCAT(LAST_NAME,', ',FIRST_NAME,coalesce(NULLIF(CONCAT(' ',MIDDLE_NAME),' '),''))",
		"LAST_NAME||coalesce(' '||MIDDLE_NAME||' ',' ')||FIRST_NAME" => "CONCAT(LAST_NAME,coalesce(NULLIF(CONCAT(' ',MIDDLE_NAME,' '),'  '),' '),FIRST_NAME)",
	];

	$display_name_sql = '';

	foreach ( $display_names_update as $display_name_pipes => $display_name_concat )
	{
		$display_name_sql .= "UPDATE config SET CONFIG_VALUE='" . DBEscapeString( $display_name_concat ) . "'
			WHERE CONFIG_VALUE='" . DBEscapeString( $display_name_pipes ) . "'
			AND TITLE='DISPLAY_NAME';";
	}

	DBQuery( $display_name_sql );

	return $return;
}


/**
 * Update to version 10.1
 *
 * 1. Add dual VIEW for compatibility with MySQL 5.6 to avoid syntax error when WHERE without FROM clause
 *
 * Local function
 *
 * @since 10.1
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update101()
{
	global $DatabaseType;

	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add dual VIEW for compatibility with MySQL 5.6 to avoid syntax error when WHERE without FROM clause
	 * PostgreSQL only
	 */
	if ( $DatabaseType !== 'mysql' )
	{
		DBQuery( "CREATE OR REPLACE VIEW dual AS SELECT 'X' AS dummy;" );
	}

	return $return;
}

/**
 * Update to version 10.6.1
 * Fix Class Rank float comparison issue: do NOT use double precision type (inexact), use numeric (exact)
 * @link https://www.rosariosis.org/forum/d/665-le-classement-diff-rent-mais-m-me-moyenne/
 * Fix regression since 10.0, change sum/cum factors & credit_attempted/earned columns type from double precision to numeric
 *
 * PostgreSQL
 * 1. Drop transcript_grades view, so we can alter student_report_card_grades table
 * 2. SQL student_report_card_grades table: convert CREDIT_ATTEMPTED & CREDIT_EARNED columns to numeric
 * 3. SQL student_mp_stats table: convert sum/cum factors & credit_attempted/earned columns to numeric
 * 4. Recreate transcript_grades view
 *
 * MySQL
 * 1. SQL student_report_card_grades table: convert CREDIT_ATTEMPTED & CREDIT_EARNED columns to numeric(22,16)
 * 2. SQL student_mp_stats table: convert sum/cum factors & credit_attempted/earned columns to numeric(22,16)
 *
 * Local function
 *
 * @since 10.6.1
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update1061()
{
	global $DatabaseType;

	_isCallerUpdate( debug_backtrace() );

	$return = true;

	if ( $DatabaseType === 'postgresql' )
	{
		// Check if data type is numeric already.
		$data_type_is_numeric = DBGetOne( "SELECT 1
			FROM student_report_card_grades
			WHERE CAST(PG_TYPEOF(credit_attempted) AS varchar(7))='numeric'
			LIMIT 1;" );
	}
	else
	{
		// Check if data type is numeric already.
		$data_type_is_numeric = DBGetOne( "SELECT 1
			FROM information_schema.columns
			WHERE table_schema=DATABASE()
			AND table_name='student_report_card_grades'
			AND COLUMN_NAME='credit_attempted'
			AND DATA_TYPE='decimal'
			LIMIT 1;" );
	}

	if ( $data_type_is_numeric )
	{
		return $return;
	}

	if ( $DatabaseType === 'postgresql' )
	{
		// 1. Drop transcript_grades view, so we can alter student_report_card_grades table
		DBQuery( "DROP VIEW transcript_grades;" );

		// 2. SQL student_report_card_grades table: convert CREDIT_ATTEMPTED & CREDIT_EARNED columns to numeric
		DBQuery( "ALTER TABLE student_report_card_grades
		ALTER credit_attempted TYPE numeric,
		ALTER credit_earned TYPE numeric;" );

		// 3. SQL student_mp_stats table: convert sum/cum factors & credit_attempted/earned columns to numeric
		DBQuery( "ALTER TABLE student_mp_stats
		ALTER cum_weighted_factor TYPE numeric,
		ALTER cum_unweighted_factor TYPE numeric,
		ALTER sum_weighted_factors TYPE numeric,
		ALTER sum_unweighted_factors TYPE numeric,
		ALTER cr_weighted_factors TYPE numeric,
		ALTER cr_unweighted_factors TYPE numeric,
		ALTER cum_cr_weighted_factor TYPE numeric,
		ALTER cum_cr_unweighted_factor TYPE numeric,
		ALTER credit_attempted TYPE numeric,
		ALTER credit_earned TYPE numeric,
		ALTER gp_credits TYPE numeric,
		ALTER cr_credits TYPE numeric" );

		// 4. Recreate transcript_grades view
		DBQuery( "CREATE VIEW transcript_grades AS
		SELECT mp.syear,mp.school_id,mp.marking_period_id,mp.mp_type,
		mp.short_name,mp.parent_id,mp.grandparent_id,
		(SELECT mp2.end_date
			FROM student_report_card_grades
				JOIN marking_periods mp2
				ON mp2.marking_period_id = student_report_card_grades.marking_period_id
			WHERE student_report_card_grades.student_id = sms.student_id
			AND (student_report_card_grades.marking_period_id = mp.parent_id
				OR student_report_card_grades.marking_period_id = mp.grandparent_id)
			AND student_report_card_grades.course_title = srcg.course_title
			ORDER BY mp2.end_date LIMIT 1) AS parent_end_date,
		mp.end_date,sms.student_id,
		(sms.cum_weighted_factor * COALESCE(schools.reporting_gp_scale, (SELECT reporting_gp_scale FROM schools WHERE mp.school_id = id ORDER BY syear LIMIT 1))) AS cum_weighted_gpa,
		(sms.cum_unweighted_factor * schools.reporting_gp_scale) AS cum_unweighted_gpa,
		sms.cum_rank,sms.mp_rank,sms.class_size,
		((sms.sum_weighted_factors / sms.count_weighted_factors) * schools.reporting_gp_scale) AS weighted_gpa,
		((sms.sum_unweighted_factors / sms.count_unweighted_factors) * schools.reporting_gp_scale) AS unweighted_gpa,
		sms.grade_level_short,srcg.comment,srcg.grade_percent,srcg.grade_letter,
		srcg.weighted_gp,srcg.unweighted_gp,srcg.gp_scale,srcg.credit_attempted,
		srcg.credit_earned,srcg.course_title,srcg.school AS school_name,
		schools.reporting_gp_scale AS school_scale,
		((sms.cr_weighted_factors / sms.count_cr_factors::numeric) * schools.reporting_gp_scale) AS cr_weighted_gpa,
		((sms.cr_unweighted_factors / sms.count_cr_factors::numeric) * schools.reporting_gp_scale) AS cr_unweighted_gpa,
		(sms.cum_cr_weighted_factor * schools.reporting_gp_scale) AS cum_cr_weighted_gpa,
		(sms.cum_cr_unweighted_factor * schools.reporting_gp_scale) AS cum_cr_unweighted_gpa,
		srcg.class_rank,sms.comments,
		srcg.credit_hours
		FROM marking_periods mp
			JOIN student_report_card_grades srcg
			ON mp.marking_period_id = srcg.marking_period_id
			JOIN student_mp_stats sms
			ON sms.marking_period_id = mp.marking_period_id
				AND sms.student_id = srcg.student_id
			LEFT OUTER JOIN schools
			ON mp.school_id = schools.id
				AND (mp.mp_source<>'History' AND mp.syear = schools.syear)
					OR (mp.mp_source='History' AND mp.syear=(SELECT syear FROM schools WHERE mp.school_id = id ORDER BY syear LIMIT 1))
		ORDER BY srcg.course_period_id;" );
	}
	else
	{
		// MySQL.
		// 1. SQL student_report_card_grades table: convert CREDIT_ATTEMPTED & CREDIT_EARNED columns to numeric(22,16)
		DBQuery( "ALTER TABLE student_report_card_grades
		CHANGE credit_attempted credit_attempted numeric(22,16),
		CHANGE credit_earned credit_earned numeric(22,16);" );

		// 2. SQL student_mp_stats table: convert sum/cum factors & credit_attempted/earned columns to numeric(22,16)
		DBQuery( "ALTER TABLE student_mp_stats
		CHANGE cum_weighted_factor cum_weighted_factor numeric(22,16),
		CHANGE cum_unweighted_factor cum_unweighted_factor numeric(22,16),
		CHANGE sum_weighted_factors sum_weighted_factors numeric(22,16),
		CHANGE sum_unweighted_factors sum_unweighted_factors numeric(22,16),
		CHANGE cr_weighted_factors cr_weighted_factors numeric(22,16),
		CHANGE cr_unweighted_factors cr_unweighted_factors numeric(22,16),
		CHANGE cum_cr_weighted_factor cum_cr_weighted_factor numeric(22,16),
		CHANGE cum_cr_unweighted_factor cum_cr_unweighted_factor numeric(22,16),
		CHANGE credit_attempted credit_attempted numeric(22,16),
		CHANGE credit_earned credit_earned numeric(22,16),
		CHANGE gp_credits gp_credits numeric(22,16),
		CHANGE cr_credits cr_credits numeric(22,16);" );
	}

	return $return;
}


/**
 * Update to version 10.8
 *
 * 1. resources table: Add PUBLISHED_PROFILES & PUBLISHED_GRADE_LEVELS columns.
 *
 * Local function
 *
 * @since 10.8
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update108()
{
	global $DatabaseType;

	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. resources table: Add PUBLISHED_PROFILES & PUBLISHED_GRADE_LEVELS columns.
	 */
	$published_profiles_column_exists = DBGetOne( "SELECT 1
		FROM information_schema.columns
		WHERE table_schema=" . ( $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
		AND table_name='resources'
		AND column_name='published_profiles';" );

	if ( ! $published_profiles_column_exists )
	{
		DBQuery( "ALTER TABLE resources
		ADD published_profiles text,
		ADD published_grade_levels text;" );
	}

	return $return;
}


/**
 * Update to version 10.9
 *
 * 1. gradebook_assignments table: Add WEIGHT column.
 *
 * Local function
 *
 * @since 10.9
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update109()
{
	global $DatabaseType;

	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. gradebook_assignments table: Add WEIGHT column.
	 */
	$weight_column_exists = DBGetOne( "SELECT 1
		FROM information_schema.columns
		WHERE table_schema=" . ( $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
		AND table_name='gradebook_assignments'
		AND column_name='weight';" );

	if ( ! $weight_column_exists )
	{
		DBQuery( "ALTER TABLE gradebook_assignments
		ADD weight integer;" );
	}

	return $return;
}


/**
 * Update to version 11.0
 *
 * 1. Move "Progress Reports" from Teacher Programs to Grades menu (admin)
 * 2. Add accounting_categories table
 * 3. Add TITLE column to accounting_payments table
 * 4. Add CATEGORY_ID column to accounting_incomes & accounting_payments tables
 * 5. Give admin profile access to Accounting > Categories program
 *
 * Local function
 *
 * @since 11.0
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update110()
{
	global $DatabaseType;

	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Move "Progress Reports" from Teacher Programs to Grades menu (admin)
	 */
	$profile_exception_exists = DBGetOne( "SELECT 1
		FROM profile_exceptions
		WHERE MODNAME='Grades/ProgressReports.php'
		AND PROFILE_ID='1'" );

	if ( ! $profile_exception_exists )
	{
		DBQuery( "UPDATE profile_exceptions
			SET MODNAME='Grades/ProgressReports.php'
			WHERE MODNAME='Users/TeacherPrograms.php&include=Grades/ProgressReports.php'
			AND PROFILE_ID='1'" );
	}

	$staff_exceptions_user_ids = DBGetOne( "SELECT " . DBSQLCommaSeparatedResult( 'USER_ID' ) . " AS USER_IDS
		FROM staff_exceptions
		WHERE MODNAME='Grades/ProgressReports.php'" );

	$where_not_user_id_sql = '';

	if ( $staff_exceptions_user_ids )
	{
		$where_not_user_id_sql = " AND USER_ID NOT IN(" . $staff_exceptions_user_ids . ")";
	}

	DBQuery( "UPDATE staff_exceptions
		SET MODNAME='Grades/ProgressReports.php'
		WHERE MODNAME='Users/TeacherPrograms.php&include=Grades/ProgressReports.php'" . $where_not_user_id_sql );

	/**
	 * 2. Add accounting_categories table
	 */
	$accounting_categories_table_exists = DBGetOne( "SELECT 1
		FROM information_schema.tables
		WHERE table_schema=" . ( $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
		AND table_name='accounting_categories';" );

	if ( ! $accounting_categories_table_exists )
	{
		if ( $DatabaseType === 'mysql' )
		{
			DBQuery( "CREATE TABLE accounting_categories (
				id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
				school_id integer NOT NULL,
				title text NOT NULL,
				short_name varchar(10),
				type varchar(100),
				sort_order numeric,
				created_at timestamp DEFAULT current_timestamp,
				updated_at timestamp NULL ON UPDATE current_timestamp
			);" );
		}
		else
		{
			DBQuery( "CREATE TABLE accounting_categories (
				id serial PRIMARY KEY,
				school_id integer NOT NULL,
				title text NOT NULL,
				short_name varchar(10),
				type varchar(100),
				sort_order numeric,
				created_at timestamp DEFAULT current_timestamp,
				updated_at timestamp
			);" );
		}
	}

	/**
	 * 3. Add TITLE column to accounting_payments table
	 */
	$title_column_exists = DBGetOne( "SELECT 1
		FROM information_schema.columns
		WHERE table_schema=" . ( $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
		AND table_name='accounting_payments'
		AND column_name='title';" );

	if ( ! $title_column_exists )
	{
		DBQuery( "ALTER TABLE accounting_payments ADD title text" );
	}

	/**
	 * 4. Add CATEGORY_ID column to accounting_incomes & accounting_payments tables
	 */
	$category_id_column_exists = DBGetOne( "SELECT 1
		FROM information_schema.columns
		WHERE table_schema=" . ( $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
		AND table_name='accounting_incomes'
		AND column_name='category_id';" );

	if ( ! $category_id_column_exists )
	{
		DBQuery( "ALTER TABLE accounting_incomes ADD category_id integer,
			ADD FOREIGN KEY (category_id) REFERENCES accounting_categories(id);
			ALTER TABLE accounting_payments ADD category_id integer,
			ADD FOREIGN KEY (category_id) REFERENCES accounting_categories(id);" );
	}

	/**
	 * 5. Give admin profile access to Accounting > Categories program
	 */
	DBQuery( "INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
		SELECT 1, 'Accounting/Categories.php', 'Y', 'Y'
		FROM DUAL
		WHERE NOT EXISTS (SELECT profile_id
			FROM profile_exceptions
			WHERE modname='Accounting/Categories.php'
			AND profile_id=1);" );

	return $return;
}


/**
 * Update to version 11.1
 *
 * 0. Drop calc_cum_cr_gpa & calc_cum_gpa procedures (MySQL only)
 * 0. Create plpgsql language in case it does not exist (PostgreSQL only)
 * 1. SQL set min Credits to 0 & fix division by zero error
 *
 * Local function
 *
 * @since 11.1
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update111()
{
	global $DatabaseType;

	_isCallerUpdate( debug_backtrace() );

	$return = true;

	if ( $DatabaseType === 'mysql' )
	{
		/**
		 * MySQL
		 *
		 * 0. Drop calc_cum_cr_gpa & calc_cum_gpa procedures
		 * 1. SQL set min Credits to 0 & fix division by zero error
		 */
		$mysql_no_delimiter = MySQLRemoveDelimiter( "DROP PROCEDURE IF EXISTS calc_cum_cr_gpa;
		DROP PROCEDURE IF EXISTS calc_cum_gpa;

		--
		-- Name: calc_cum_cr_gpa(mp_id integer, s_id integer); Type: FUNCTION;
		-- @since 11.1 SQL set min Credits to 0 & fix division by zero error
		--

		DELIMITER $$
		CREATE PROCEDURE calc_cum_cr_gpa(mp_id integer, s_id integer)
		BEGIN
			UPDATE student_mp_stats
			SET cum_cr_weighted_factor = (case when cr_credits = '0' THEN '0' ELSE cr_weighted_factors/cr_credits END),
				cum_cr_unweighted_factor = (case when cr_credits = '0' THEN '0' ELSE cr_unweighted_factors/cr_credits END)
			WHERE student_mp_stats.student_id = s_id and student_mp_stats.marking_period_id = mp_id;
		END$$
		DELIMITER ;


		--
		-- Name: calc_cum_gpa(mp_id integer, s_id integer); Type: FUNCTION;
		-- @since 11.1 SQL set min Credits to 0 & fix division by zero error
		--

		DELIMITER $$
		CREATE PROCEDURE calc_cum_gpa(mp_id integer, s_id integer)
		BEGIN
			UPDATE student_mp_stats
			SET cum_weighted_factor = (case when gp_credits = '0' THEN '0' ELSE sum_weighted_factors/gp_credits END),
				cum_unweighted_factor = (case when gp_credits = '0' THEN '0' ELSE sum_unweighted_factors/gp_credits END)
			WHERE student_mp_stats.student_id = s_id and student_mp_stats.marking_period_id = mp_id;
		END$$
		DELIMITER ;" );

		DBQuery( $mysql_no_delimiter );

		return $return;
	}

	/**
	 * PostgreSQL
	 *
	 * 0. Create plpgsql language in case it does not exist
	 * 1. SQL set min Credits to 0 & fix division by zero error
	 */
	DBQuery( "CREATE OR REPLACE FUNCTION create_language_plpgsql() RETURNS boolean AS $$
		CREATE LANGUAGE plpgsql;
		SELECT TRUE;
	$$ LANGUAGE SQL;

	SELECT CASE WHEN NOT
		(
			SELECT  TRUE AS exists
			FROM    pg_language
			WHERE   lanname = 'plpgsql'
			UNION
			SELECT  FALSE AS exists
			ORDER BY exists DESC
			LIMIT 1
		)
	THEN
		create_language_plpgsql()
	ELSE
		FALSE
	END AS plpgsql_created;

	DROP FUNCTION create_language_plpgsql();


	--
	-- Name: calc_cum_cr_gpa(mp_id integer, s_id integer); Type: FUNCTION; Schema: public; Owner: postgres
	-- @since 11.1 SQL set min Credits to 0 & fix division by zero error
	--

	CREATE OR REPLACE FUNCTION calc_cum_cr_gpa(mp_id integer, s_id integer) RETURNS integer AS $$
	BEGIN
		UPDATE student_mp_stats
		SET cum_cr_weighted_factor = (case when cr_credits = '0' THEN '0' ELSE cr_weighted_factors/cr_credits END),
			cum_cr_unweighted_factor = (case when cr_credits = '0' THEN '0' ELSE cr_unweighted_factors/cr_credits END)
		WHERE student_mp_stats.student_id = s_id and student_mp_stats.marking_period_id = mp_id;
		RETURN 1;
	END;
	$$ LANGUAGE plpgsql;


	--
	-- Name: calc_cum_gpa(mp_id integer, s_id integer); Type: FUNCTION; Schema: public; Owner: postgres
	-- @since 11.1 SQL set min Credits to 0 & fix division by zero error
	--

	CREATE OR REPLACE FUNCTION calc_cum_gpa(mp_id integer, s_id integer) RETURNS integer AS $$
	BEGIN
		UPDATE student_mp_stats
		SET cum_weighted_factor = (case when gp_credits = '0' THEN '0' ELSE sum_weighted_factors/gp_credits END),
			cum_unweighted_factor = (case when gp_credits = '0' THEN '0' ELSE sum_unweighted_factors/gp_credits END)
		WHERE student_mp_stats.student_id = s_id and student_mp_stats.marking_period_id = mp_id;
		RETURN 1;
	END;
	$$ LANGUAGE plpgsql;" );

	return $return;
}


/**
 * Update to version 11.2
 *
 * 1. Add CREATED_BY column to billing_fees & billing_payments tables
 *
 * Local function
 *
 * @since 11.2
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update112()
{
	global $DatabaseType;

	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add CREATED_BY column to billing_fees & billing_payments tables
	 */
	$created_by_column_exists = DBGetOne( "SELECT 1
		FROM information_schema.columns
		WHERE table_schema=" . ( $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
		AND table_name='billing_fees'
		AND column_name='created_by';" );

	if ( ! $created_by_column_exists )
	{
		DBQuery( "ALTER TABLE billing_fees ADD created_by text;
			ALTER TABLE billing_payments ADD created_by text;" );
	}

	return $return;
}


/**
 * Update to version 11.2.1
 *
 * 1. Add MENU_ITEM_ID column to food_service_transaction_items & food_service_staff_transaction_items tables
 * Fix regression since 11.2 PostgreSQL error duplicate key value violates unique constraint
 * Fix regression since 11.2 MySQL error duplicate entry '2-3' for key 'PRIMARY'
 *
 * Local function
 *
 * @since 11.2.1
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update1121()
{
	global $DatabaseType;

	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add MENU_ITEM_ID column to food_service_transaction_items & food_service_staff_transaction_items tables
	 */
	$menu_item_id_column_exists = DBGetOne( "SELECT 1
		FROM information_schema.columns
		WHERE table_schema=" . ( $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
		AND table_name='food_service_transaction_items'
		AND column_name='menu_item_id';" );

	if ( ! $menu_item_id_column_exists )
	{
		DBQuery( "ALTER TABLE food_service_transaction_items
		ADD menu_item_id integer;" );
	}

	$menu_item_id_column_exists = DBGetOne( "SELECT 1
		FROM information_schema.columns
		WHERE table_schema=" . ( $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
		AND table_name='food_service_staff_transaction_items'
		AND column_name='menu_item_id';" );

	if ( ! $menu_item_id_column_exists )
	{
		DBQuery( "ALTER TABLE food_service_staff_transaction_items
		ADD menu_item_id integer;" );
	}

	return $return;
}

// @deprecated since 11.4 Assignments Files upload path $AssignmentsFilesPath global var
// @deprecated since 11.4 Portal Notes Files upload path $PortalNotesFilesPath global var
// @deprecated since 11.4 Food Service Icons upload path $FS_IconsPath global var
// TODO 11.4, try to remove assets/FS_icons/, AssignmentsFiles/ & PortalNotesFiles/ only if default files in it.
