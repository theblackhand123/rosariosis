<?php
/**
 * Iomad plugin common functions
 *
 * @package Iomad plugin
 */

/**
 * Get Moodle ID by RosarioSIS ID
 *
 * @param string $column     Column: 'staff_id', 'student_id', 'course_period_id', etc.
 * @param int    $rosario_id Rosario ID.
 *
 * @return Moodle ID.
 */
function IomadGetMoodleByRosarioID( $column, $rosario_id )
{
	// @deprecated since 6.0 use MoodleXRosarioGet().
	return DBGetOne( "SELECT MOODLE_ID
		FROM moodlexrosario
		WHERE " . DBEscapeIdentifier( 'COLUMN' ) . "='" . $column . "'
		AND ROSARIO_ID='" . (int) $rosario_id . "'" );
}

/**
 * Get Iomad Companies
 *
 * @uses block_iomad_company_admin_get_companies WS function.
 *
 * @return array Empty if URL or Token not set or invalid, else companies.
 */
function IomadGetCompanies()
{
	static $companies;

	require_once 'plugins/Moodle/client.php';

	// Check Iomad URL if set + token set.
	if ( ! MOODLE_URL
		|| ! MOODLE_TOKEN )
	{
		return [];
	}

	$serverurl = MOODLE_URL . '/webservice/xmlrpc/server.php?wstoken=' . MOODLE_TOKEN;

	if ( ! filter_var( $serverurl, FILTER_VALIDATE_URL ) )
	{
		return [];
	}

	if ( $companies )
	{
		// Return cached data directly.
		return $companies;
	}

	// Check URL is responding with cURL.
	$functionname = 'block_iomad_company_admin_get_companies';

	if ( ! function_exists( 'block_iomad_company_admin_get_companies_response' ) )
	{
		// Dummy response function.
		function block_iomad_company_admin_get_companies_response( $response )
		{
			// We had a response, return companies.
			return empty( $response['companies'] ) ? [] : $response['companies'];
		}
	}

	/**
	 block_iomad_company_admin_get_companies
	 [criteria] =>
	    Array
	        (
	        [0] =>
	            Array
	                (
	                [key] => string
	                [value] => string
	                )
	        )
	//the company column to search, expected keys (value format) are:
	    "id" (int) matching company id,
	    "name" (string) company name (Note: you can use % for searching but it may be considerably slower!),
	    "shortname" (string) company short name (Note: you can use % for searching but it may be considerably slower!),
	    "suspended" (bool) company is suspended or not,
	    "city" (string) matching company city,
	    "country" (string) matching company country,
	    "timezone" (int) company timezone,
	    "lang" (string) matching company language setting
	 */
	// All companies but the ones which are suspended.
	$criteria = [
		'key' => 'suspended',
		'value' => false,
	];

	$object = [ 'criteria' => $criteria ];

	$companies = moodle_xmlrpc_call( $functionname, $object );

	return $companies;
}

/**
 * Get Iomad Company ID for a RosarioSIS School
 *
 * @uses Config()
 *
 * @param integer $school_id School ID. Defaults to 0 (all schools).
 *
 * @return int|array Iomad company ID or array indexed by School ID with their Company ID as value.
 */
function IomadGetSchoolCompanyID( $school_id = 0 )
{
	if ( $school_id )
	{
		return Config( 'IOMAD_SCHOOL_' . $school_id );
	}

	// RosarioSIS Schools and Iomad Companies.
	$schools_RET = DBGet( "SELECT ID
		FROM schools
		WHERE SYEAR='" . UserSyear() . "'
		ORDER BY ID;" );

	$schools_companies = [];

	foreach ( $schools_RET as $school )
	{
		$schools_companies[ $school['ID'] ] = Config( 'IOMAD_SCHOOL_' . $school['ID'] );
	}

	return $schools_companies;
}

/**
 * Create Iomad Company
 *
 * @uses block_iomad_company_admin_create_companies WS function
 *
 * @return int Company ID or 0.
 */
function IomadCreateCompany()
{
	global $error;

	$name = $shortname = SchoolInfo( 'TITLE' );

	$city = 'City';

	$country = 'US';

	// Check URL is responding with cURL.
	$functionname = 'block_iomad_company_admin_create_companies';

	if ( ! function_exists( 'block_iomad_company_admin_create_companies_response' ) )
	{
		// Dummy response function.
		function block_iomad_company_admin_create_companies_response( $response )
		{
			// We had a response, return companies.
			return $response;
		}
	}

	/**
	 General structure

	 list of (
		 object {
			 name string   //Company long name
			 shortname string   //Compay short name
			 city string   //Company location city
			 country string   //Company location country
			 maildisplay int  Default to "2" //User default email display
			 mailformat int  Default to "1" //User default email format
			 maildigest int  Default to "0" //User default digest type
			 autosubscribe int  Default to "1" //User default forum auto-subscribe
			 trackforums int  Default to "0" //User default forum tracking
			 htmleditor int  Default to "1" //User default text editor
			 screenreader int  Default to "0" //User default screen reader
			 timezone string  Default to "99" //User default timezone
			 lang string  Default to "en" //User default language
			 suspended int  Default to "0" //Company is suspended when <> 0
			 ecommerce int  Default to "0" //Ecommerce is disabled when = 0
			 parentid int  Default to "0" //ID of parent company
			 customcss string   //Company custom css
			 validto int  Default to "null" //Contract termination date in unix timestamp
			 suspendafter int  Default to "0" //Number of seconds after termination date to suspend the company
		 }
	 )

	 XML-RPC (PHP structure)

	 [companies] =>
     Array
         (
         [0] =>
             Array
                 (
                 [name] => string
                 [shortname] => string
                 [city] => string
                 [country] => string
                 [maildisplay] => int
                 [mailformat] => int
                 [maildigest] => int
                 [autosubscribe] => int
                 [trackforums] => int
                 [htmleditor] => int
                 [screenreader] => int
                 [timezone] => string
                 [lang] => string
                 [suspended] => int
                 [ecommerce] => int
                 [parentid] => int
                 [customcss] => string
                 [validto] => int
                 [suspendafter] => int
                 )
         )
	 */

	$companies = [
		'name' => $name,
		'shortname' => $shortname,
		'city' => $city,
		'country' => $country,
		'customcss' => '',
	];

	$object = [ 'companies' => $companies ];

	$return = moodle_xmlrpc_call( $functionname, $object );

	if ( empty( $return[0]['id'] ) )
	{
		$error[] = dgettext( 'Iomad', 'Iomad: Could not create company.' );
	}
	else
	{
		// Save company ID.
		// Insert value (does not exist), always in School 0!
		DBQuery( "INSERT INTO config (CONFIG_VALUE,TITLE,SCHOOL_ID)
			VALUES('" . $return[0]['id'] . "','IOMAD_SCHOOL_" . UserSchool() . "','0')" );
	}

	return empty( $return[0]['id'] ) ? 0 : $return[0]['id'];
}


/**
 * Get Iomad Company course Category
 * Search it based on Company name and save it if first time.
 *
 * @param int    $company_id   Company ID.
 * @param string $company_name Company name, is School Title on Copy School.
 *
 * @return int 0 or Company course Category ID.
 */
function IomadCompanyCourseCategory( $company_id, $company_name = '' )
{
	global $error;

	if ( ! $company_id )
	{
		return 0;
	}

	if ( Config( 'IOMAD_COMPANY_' . $company_id . '_CATEGORY_ID' ) )
	{
		return Config( 'IOMAD_COMPANY_' . $company_id . '_CATEGORY_ID' );
	}

	$category_name = $company_name;

	if ( ! $category_name )
	{
		$iomad_companies = IomadGetCompanies();

		foreach ( $iomad_companies as $iomad_company )
		{
			if ( $iomad_company['id'] == $company_id )
			{
				$category_name = $iomad_company['name'];

				break;
			}
		}
	}

	if ( ! $category_name )
	{
		return 0;
	}

	// Check URL is responding with cURL.
	$functionname = 'core_course_get_categories';

	if ( ! function_exists( 'core_course_get_categories_response' ) )
	{
		// Dummy response function.
		function core_course_get_categories_response( $response )
		{
			// We had a response, return companies.
			return $response;
		}
	}

	/**
	 General structure

	 list of (
		 object {
			 key string   //The category column to search, expected keys (value format) are:"id" (int) the category id,"ids" (string) category ids separated by commas,"name" (string) the category name,"parent" (int) the parent category id,"idnumber" (string) category idnumber - user must have 'moodle/category:manage' to search on idnumber,"visible" (int) whether the returned categories must be visible or hidden. If the key is not passed,
			                                              then the function return all categories that the user can see. - user must have 'moodle/category:manage' or 'moodle/category:viewhiddencategories' to search on visible,"theme" (string) only return the categories having this theme - user must have 'moodle/category:manage' to search on theme
			 value string   //the value to match
		 }
	 )

	 XML-RPC (PHP structure)

	 [criteria] =>
	     Array
	         (
	         [0] =>
	             Array
	                 (
	                 [key] => string
	                 [value] => string
	                 )
	         )
	 */

	$criteria = [
		'key' => 'name',
		'value' => $category_name,
	];

	$object = [ 'criteria' => $criteria ];

	$return = moodle_xmlrpc_call( $functionname, $object );

	if ( empty( $return[0]['id'] ) )
	{
		$error[] = dgettext( 'Iomad', 'Iomad: Could not get company category.' );

		return 0;
	}

	$category_id = $return[0]['id'];

	// Save company category ID.
	// Insert value (does not exist), always in School 0!
	DBQuery( "INSERT INTO config (CONFIG_VALUE,TITLE,SCHOOL_ID)
		VALUES('" . $category_id . "','IOMAD_COMPANY_" . $company_id . "_CATEGORY_ID','0')" );

	return $category_id;
}


/**
 * Suspend Iomad Company (cannot delete).
 *
 * @uses block_iomad_company_admin_edit_companies WS function
 *
 * @return bool
 */
function IomadDeleteCompany()
{
	$companyid = IomadGetSchoolCompanyID( UserSchool() );

	if ( ! $companyid )
	{
		return false;
	}

	// Check URL is responding with cURL.
	$functionname = 'block_iomad_company_admin_edit_companies';

	if ( ! function_exists( 'block_iomad_company_admin_edit_companies_response' ) )
	{
		// Dummy response function.
		function block_iomad_company_admin_edit_companies_response( $response )
		{
			// We had a response, return companies.
			return ! empty( $response );
		}
	}

	/**
	 General structure

	 list of (
		 object {
			 id int   //Company id number
			 name string  Optional //Company long name
			 shortname string  Optional //Compay short name
			 city string  Optional //Company location city
			 country string  Optional //Company location country
			 maildisplay int  Optional //User default email display
			 mailformat int  Optional //User default email format
			 maildigest int  Optional //User default digest type
			 autosubscribe int  Optional //User default forum auto-subscribe
			 trackforums int  Optional //User default forum tracking
			 htmleditor int  Optional //User default text editor
			 screenreader int  Optional //User default screen reader
			 timezone string  Optional //User default timezone
			 lang string  Optional //User default language
			 suspended int  Default to "0" //Company is suspended when <> 0
			 ecommerce int  Default to "0" //Ecommerce is disabled when = 0
			 parentid int  Default to "0" //ID of parent company
			 customcss string   //Company custom css
			 validto int  Default to "null" //Contract termination date in unix timestamp
			 suspendafter int  Default to "0" //Number of seconds after termination date to suspend the company
			 companyterminated int  Default to "0" //Company contract is terminated when <> 0
		 }
	 )

	 XML-RPC (PHP structure)

	 [companies] =>
     Array
         (
         [0] =>
             Array
                 (
                 [id] => int
                 [name] => string
                 [shortname] => string
                 [city] => string
                 [country] => string
                 [maildisplay] => int
                 [mailformat] => int
                 [maildigest] => int
                 [autosubscribe] => int
                 [trackforums] => int
                 [htmleditor] => int
                 [screenreader] => int
                 [timezone] => string
                 [lang] => string
                 [suspended] => int
                 [ecommerce] => int
                 [parentid] => int
                 [customcss] => string
                 [validto] => int
                 [suspendafter] => int
                 [companyterminated] => int
                 )
         )
	 */

	$companies = [
		'id' => $companyid,
		'suspended' => 1,
		'customcss' => '',
	];

	$object = [ 'companies' => $companies ];

	$return = moodle_xmlrpc_call( $functionname, $object );

	if ( ! $return )
	{
		$error[] = dgettext( 'Iomad', 'Iomad: Could not delete company.' );
	}
	else
	{
		// Delete company ID.
		DBQuery( "DELETE FROM config
			WHERE TITLE='IOMAD_SCHOOL_" . $school_id . "'" );
	}

	return $return;
}


/**
 * Edit Iomad Company
 * Edit Title, Short name or City.
 *
 * @uses block_iomad_company_admin_edit_companies WS function
 *
 * @return bool
 */
function IomadEditCompany()
{
	$companyid = IomadGetSchoolCompanyID( UserSchool() );

	if ( ! $companyid )
	{
		return false;
	}

	$name = SchoolInfo( 'TITLE' );

	$shortname = SchoolInfo( 'SHORT_NAME' ) ? SchoolInfo( 'SHORT_NAME' ) : SchoolInfo( 'TITLE' );

	$city = SchoolInfo( 'CITY' );

	// Check URL is responding with cURL.
	$functionname = 'block_iomad_company_admin_edit_companies';

	if ( ! function_exists( 'block_iomad_company_admin_edit_companies_response' ) )
	{
		// Dummy response function.
		function block_iomad_company_admin_edit_companies_response( $response )
		{
			// We had a response, return companies.
			return ! empty( $response );
		}
	}

	/**
	 General structure

	 list of (
		 object {
			 id int   //Company id number
			 name string  Optional //Company long name
			 shortname string  Optional //Compay short name
			 city string  Optional //Company location city
			 country string  Optional //Company location country
			 maildisplay int  Optional //User default email display
			 mailformat int  Optional //User default email format
			 maildigest int  Optional //User default digest type
			 autosubscribe int  Optional //User default forum auto-subscribe
			 trackforums int  Optional //User default forum tracking
			 htmleditor int  Optional //User default text editor
			 screenreader int  Optional //User default screen reader
			 timezone string  Optional //User default timezone
			 lang string  Optional //User default language
			 suspended int  Default to "0" //Company is suspended when <> 0
			 ecommerce int  Default to "0" //Ecommerce is disabled when = 0
			 parentid int  Default to "0" //ID of parent company
			 customcss string   //Company custom css
			 validto int  Default to "null" //Contract termination date in unix timestamp
			 suspendafter int  Default to "0" //Number of seconds after termination date to suspend the company
			 companyterminated int  Default to "0" //Company contract is terminated when <> 0
		 }
	 )

	 XML-RPC (PHP structure)

	 [companies] =>
     Array
         (
         [0] =>
             Array
                 (
                 [id] => int
                 [name] => string
                 [shortname] => string
                 [city] => string
                 [country] => string
                 [maildisplay] => int
                 [mailformat] => int
                 [maildigest] => int
                 [autosubscribe] => int
                 [trackforums] => int
                 [htmleditor] => int
                 [screenreader] => int
                 [timezone] => string
                 [lang] => string
                 [suspended] => int
                 [ecommerce] => int
                 [parentid] => int
                 [customcss] => string
                 [validto] => int
                 [suspendafter] => int
                 [companyterminated] => int
                 )
         )
	 */

	$companies = [
		'id' => $companyid,
		'name' => $name,
		'shortname' => $shortname,
		'city' => ( empty( $city ) ? '' : $city ),
		'customcss' => '',
	];

	$object = [ 'companies' => $companies ];

	$return = moodle_xmlrpc_call( $functionname, $object );

	if ( ! $return )
	{
		$error[] = dgettext( 'Iomad', 'Iomad: Could not edit company.' );
	}

	return $return;
}

// Dummy response function.
function block_iomad_company_admin_assign_users_response( $response )
{
	// We had a response, return companies.
	return ! empty( $response );
}

/**
 * Assign Iomad Companies to User (or student)
 *
 * @uses block_iomad_company_admin_assign_users WS function
 * @since 1.7 Fix add educator param since Iomad 3.6
 *
 * @param string $profile Profile: 'admin', 'student', 'teacher'.
 * @param array  $schools School IDs.
 *
 * @return bool
 */
function IomadUserAssignCompanies( $profile, $schools )
{
	global $error;

	// User.
	$managertype = 0;
	$educator = 0;

	if ( $profile === 'admin' )
	{
		// Company manager.
		$managertype = 1;
	}

	if ( $profile === 'teacher' )
	{
		// Educator.
		$educator = 1;
	}

	$user_id = IomadGetMoodleByRosarioID(
		( $profile === 'student' ? 'student_id' : 'staff_id' ),
		( $profile === 'student' ? UserStudentID() : UserStaffID() )
	);

	if ( ! $user_id )
	{
		return false;
	}

	// Check URL is responding with cURL.
	$functionname = 'block_iomad_company_admin_assign_users';

	/**
	 General structure

	 list of (
		 object {
			 userid int  Default to "0" //User ID
			 companyid int  Default to "0" //User company ID
			 departmentid int  Default to "0" //User company department ID
			 managertype int  Default to "0" //User manager type 0 => User, 1 => company manager 2 => department manager
			 educator int Default to "0" //User educator 0 => No, 1 => Yes
		 }
	 )

	 XML-RPC (PHP structure)

	 [users] =>
     Array
         (
         [0] =>
             Array
                 (
                 [userid] => int
                 [companyid] => int
                 [departmentid] => int
                 [managertype] => int
                 [educator] => int
                 )
         )
	 */

	$users = [];

	$schools_companies = IomadGetSchoolCompanyID();

	$return = false;

	foreach ( $schools_companies as $school_id => $company_id )
	{
		if ( $company_id &&
			( empty( $schools ) || in_array( $school_id, $schools ) ) )
		{
			// Empty means all schools, so all companies.
			$users = [
				'userid' => $user_id,
				'companyid' => $company_id,
				'departmentid' => 0,
				'managertype' => $managertype,
				'educator' => $educator,
			];

			$object = [ 'users' => $users ];

			$return = moodle_xmlrpc_call( $functionname, $object );

			if ( ! $return )
			{
				$error[] = sprintf(
					dgettext( 'Iomad', 'Iomad: Could not assign user to company ID %d' ),
					$company_id
				);
			}
		}
	}

	return $return;
}


/**
 * Assign Iomad Company to user (or student).
 *
 * @uses block_iomad_company_admin_assign_users WS function
 * @since 1.7 Fix add educator param since Iomad 3.6
 *
 * @param string $profile   Profile: 'admin', 'student', 'teacher'.
 * @param int    $user_id   User ID.
 * @param int    $school_id School ID.
 *
 * @return boool
 */
function IomadUserAssignCompany( $profile, $user_id, $school_id )
{
	global $error;

	$company_id = IomadGetSchoolCompanyID( $school_id );

	if ( ! $company_id )
	{
		return false;
	}

	// User.
	$managertype = 0;
	$educator = 0;

	if ( $profile === 'admin' )
	{
		// Company manager.
		$managertype = 1;
	}

	if ( $profile === 'teacher' )
	{
		// Educator.
		$educator = 1;
	}

	$user_id = IomadGetMoodleByRosarioID(
		( $profile === 'student' ? 'student_id' : 'staff_id' ),
		( $profile === 'student' ? $user_id : $user_id )
	);

	if ( ! $user_id )
	{
		return false;
	}

	// Check URL is responding with cURL.
	$functionname = 'block_iomad_company_admin_assign_users';

	/**
	 General structure

	 list of (
		 object {
			 userid int  Default to "0" //User ID
			 companyid int  Default to "0" //User company ID
			 departmentid int  Default to "0" //User company department ID
			 managertype int  Default to "0" //User manager type 0 => User, 1 => company manager 2 => department manager
			 educator int Default to "0" //User educator 0 => No, 1 => Yes
		 }
	 )

	 XML-RPC (PHP structure)

	 [users] =>
     Array
         (
         [0] =>
             Array
                 (
                 [userid] => int
                 [companyid] => int
                 [departmentid] => int
                 [managertype] => int
                 [educator] => int
                 )
         )
	 */


	// Empty means all schools, so all companies.
	$users = [
		'userid' => $user_id,
		'companyid' => $company_id,
		'departmentid' => 0,
		'managertype' => $managertype,
		'educator' => $educator,
	];

	$object = [ 'users' => $users ];

	$return = moodle_xmlrpc_call( $functionname, $object );

	if ( ! $return )
	{
		$error[] = sprintf(
			dgettext( 'Iomad', 'Iomad: Could not assign user to company ID %d' ),
			$company_id
		);
	}

	return $return;
}


// Dummy response function.
function block_iomad_company_admin_unassign_users_response( $response )
{
	// We had a response, return companies.
	return ! empty( $response );
}

/**
 * Unassign User (or student) from Iomad Company.
 *
 * @uses block_iomad_company_admin_unassign_users WS function.
 *
 * @param string $profile        Profile: 'admin', 'student', 'teacher'.
 * @param array  $assign_schools Assign school IDs. Will unassign schools which are not in this array,
 *                               unless for staff when array is empty: means all schools.
 *
 * @return bool
 */
function IomadUserUnassignCompanies( $profile, $assign_schools )
{
	global $error,
		$note;

	if ( $profile !== 'student'
		&& ! $assign_schools )
	{
		// Assign to all schools, so do not unassign any.
		return false;
	}

	$user_id = IomadGetMoodleByRosarioID(
		( $profile === 'student' ? 'student_id' : 'staff_id' ),
		( $profile === 'student' ? UserStudentID() : UserStaffID() )
	);

	if ( ! $user_id )
	{
		return false;
	}

	// Check URL is responding with cURL.
	$functionname = 'block_iomad_company_admin_unassign_users';

	/**
	 General structure

	 list of (
		 object {
			 userid int  Default to "0" //User ID
			 companyid int  Default to "0" //User company ID
			 usertype int  Default to "0" //Old user manager type
		 }
	 )

	 XML-RPC (PHP structure)

	 [users] =>
     Array
         (
         [0] =>
             Array
                 (
                 [userid] => int
                 [companyid] => int
                 [usertype] => int
                 )
         )
	 */

	$users = [];

	$schools_companies = IomadGetSchoolCompanyID();

	$return = false;

	foreach ( $schools_companies as $school_id => $company_id )
	{
		if ( $company_id &&
			! in_array( $school_id, $assign_schools ) )
		{
			$users = [
				'userid' => $user_id,
				'companyid' => $company_id,
				'usertype' => 0,
			];

			$object = [ 'users' => $users ];

			$return = moodle_xmlrpc_call( $functionname, $object );

			if ( ! $return )
			{
				// Error because we are trying to unassign user from a company he is not in...
				/*$error[] = sprintf(
					dgettext( 'Iomad', 'Iomad: Could not unassign user from company ID %d' ),
					$company_id
				);*/
			}
			else
			{
				$note[] = sprintf(
					dgettext( 'Iomad', 'Iomad: successfully unassigned user from company ID %d' ),
					$company_id
				);
			}
		}
	}

	return $return;
}

/**
 * Unassign user (actually, a student) from current company.
 *
 * @uses block_iomad_company_admin_unassign_users WS function
 *
 * @param string $profile Profile: 'admin', 'student', 'teacher'.
 * @param int    $user_id User ID.
 *
 * @return bool
 */
function IomadUserUnassignCurrentCompany( $profile, $user_id )
{
	global $error;

	$company_id = IomadGetSchoolCompanyID( UserSchool() );

	if ( ! $company_id )
	{
		return false;
	}

	$user_id = IomadGetMoodleByRosarioID(
		( $profile === 'student' ? 'student_id' : 'staff_id' ),
		( $profile === 'student' ? $user_id : $user_id )
	);

	if ( ! $user_id )
	{
		return false;
	}

	// Check URL is responding with cURL.
	$functionname = 'block_iomad_company_admin_unassign_users';

	/**
	 General structure

	 list of (
		 object {
			 userid int  Default to "0" //User ID
			 companyid int  Default to "0" //User company ID
			 usertype int  Default to "0" //Old user manager type
		 }
	 )

	 XML-RPC (PHP structure)

	 [users] =>
     Array
         (
         [0] =>
             Array
                 (
                 [userid] => int
                 [companyid] => int
                 [usertype] => int
                 )
         )
	 */

	$users = [
		'userid' => $user_id,
		'companyid' => $company_id,
		//'usertype' => 0,
	];

	$object = [ 'users' => $users ];

	$return = moodle_xmlrpc_call( $functionname, $object );

	if ( ! $return )
	{
		$error[] = sprintf(
			dgettext( 'Iomad', 'Iomad: Could not unassign user from company ID %d' ),
			$company_id
		);
	}

	return $return;
}

/**
 * Assign Course to Iomad Company
 * Courses are not licensed by default.
 *
 * @uses block_iomad_company_admin_assign_courses_response WS function
 *
 * @param int $course_period_id Course Period ID.
 *
 * @return bool
 */
function IomadCourseAssignCompany( $course_period_id )
{
	global $error;

	$company_id = IomadGetSchoolCompanyID( UserSchool() );

	if ( ! $company_id )
	{
		return false;
	}

	$course_id = IomadGetMoodleByRosarioID( 'course_period_id', $course_period_id );

	if ( ! $course_id )
	{
		return false;
	}

	// Check URL is responding with cURL.
	$functionname = 'block_iomad_company_admin_assign_courses';

	if ( ! function_exists( 'block_iomad_company_admin_assign_courses_response' ) )
	{
		// Dummy response function.
		function block_iomad_company_admin_assign_courses_response( $response )
		{
			// We had a response, return courses.
			return ! empty( $response );
		}
	}

	/**
	 General structure

	 list of (
		 object {
			 courseid int  Default to "0" //Course ID
			 companyid int  Default to "0" //Course company ID
			 departmentid int  Default to "0" //Course department ID
			 owned int  Default to "" //Does the company own the course
			 licensed int  Default to "" //Is the course licensed
		 }
	 )

	 XML-RPC (PHP structure)

	 [courses] =>
     Array
         (
         [0] =>
             Array
                 (
                 [courseid] => int
                 [companyid] => int
                 [departmentid] => int
                 [owned] => int
                 [licensed] => int
                 )
         )
	 */

	$courses = [
		'courseid' => $course_id,
		'companyid' => $company_id,
		'departmentid' => 0,
		'owned' => 1,
		'licensed' => 0, // Not licensed by default.
	];

	$object = [ 'courses' => $courses ];

	$return = moodle_xmlrpc_call( $functionname, $object );

	if ( ! $return )
	{
		$error[] = sprintf(
			dgettext( 'Iomad', 'Iomad: Could not assign course to company ID %d' ),
			$company_id
		);
	}

	return $return;
}

/**
 * Assign (enrol) User (or student) to Iomad course
 *
 * @uses block_iomad_company_admin_enrol_users WS function
 *
 * @param int    $course_period_id Course Period ID.
 * @param int    $user_id          User ID.
 * @param string $column           moodlexrosario column: 'staff_id' or 'student_id'.
 * @param string $start_date       Enrolment start date.
 * @param string $end_date         Enrolment end date (only work if course is licensed).
 *
 * @return bool
 */
function IomadCourseAssignUser( $course_period_id, $user_id, $column = 'staff_id', $start_date = '', $end_date = '' )
{
	global $error;

	$company_id = IomadGetSchoolCompanyID( UserSchool() );

	if ( ! $company_id )
	{
		return false;
	}

	$course_id = IomadGetMoodleByRosarioID( 'course_period_id', $course_period_id );

	if ( ! $course_id )
	{
		return false;
	}

	$moodle_user_id = IomadGetMoodleByRosarioID( $column, $user_id );

	if ( ! $moodle_user_id )
	{
		return false;
	}

	// Check URL is responding with cURL.
	$functionname = 'block_iomad_company_admin_enrol_users';

	if ( ! function_exists( 'block_iomad_company_admin_enrol_users_response' ) )
	{
		// Dummy response function.
		function block_iomad_company_admin_enrol_users_response( $response )
		{
			// We had a response, return courses.
			return ! empty( $response );
		}
	}

	/**
	 General structure

	 list of (
		 object {
			 roleid int   //Role to assign to the user
			 userid int   //The user that is going to be enrolled
			 courseid int   //The course to enrol the user role in
			 timestart int  Optional //Timestamp when the enrolment start
			 timeend int  Optional //Timestamp when the enrolment end
			 suspend int  Optional //set to 1 to suspend the enrolment
			 quantity int  Optional //Number of items purchased.
		 }
	 )

	 XML-RPC (PHP structure)

	 [enrolments] =>
     Array
         (
         [0] =>
             Array
                 (
                 [roleid] => int
                 [userid] => int
                 [courseid] => int
                 [timestart] => int
                 [timeend] => int
                 [suspend] => int
                 [quantity] => int
                 )
         )
	 */

	$courses = [
		'roleid' => ( $column === 'staff_id' ? 4 : 5 ), // Teacher or student.
		'userid' => $moodle_user_id,
		'courseid' => $course_id,
	];

	if ( $start_date && $start_date !== DBDate() && $start_date > DBDate() )
	{
		$courses['timestart'] = date( 'U', strtotime( $start_date ) );
	}

	if ( $end_date )
	{
		$courses['timeend'] = date( 'U', strtotime( $end_date ) );
	}
	else
	{
		if ( $column === 'staff_id' )
		{
			// End date is Course MP end date.
			$mp_id = DBGetOne( "SELECT MARKING_PERIOD_ID
				FROM course_periods
				WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'" );
		}
		else
		{
			// End date is Schedule MP end date.
			$mp_id = DBGetOne( "SELECT MARKING_PERIOD_ID
				FROM schedule
				WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'
				AND STUDENT_ID='" . (int) $user_id . "'
				AND SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );
		}

		$end_date = GetMP( $mp_id, 'END_DATE' );

		$courses['timeend'] = date( 'U', strtotime( $end_date ) );
	}

	$object = [ 'courses' => $courses ];

	$return = moodle_xmlrpc_call( $functionname, $object );

	if ( ! $return )
	{
		$error[] = sprintf(
			dgettext( 'Iomad', 'Iomad: Could not assign user to course ID %d' ),
			$course_id
		);
	}

	return $return;
}
