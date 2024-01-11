<?php
/**
 * StaffAbsences
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/Fields.fnc.php';
require_once 'ProgramFunctions/StudentsUsersInfo.fnc.php';
require_once 'ProgramFunctions/Template.fnc.php';
require_once 'ProgramFunctions/Substitutions.fnc.php';

require_once 'modules/Staff_Absences/includes/common.fnc.php';
require_once 'modules/Staff_Absences/includes/StaffAbsences.fnc.php';
require_once 'modules/Staff_Absences/includes/Notifications.fnc.php';

// @deprecated since 2.0.
require_once 'modules/Staff_Absences/includes/Update.inc.php';

if ( User( 'PROFILE' ) === 'teacher' )
{
	$_ROSARIO['allow_edit'] = true;
}

DrawHeader( ProgramTitle() );

// Add eventual Dates to $_REQUEST['tables'].
AddRequestedDates( 'tables', 'post' );

if ( AllowEdit()
	&& $_REQUEST['modfunc'] === 'save'
	&& ( isset( $_POST['tables'] )
		&& is_array( $_POST['tables'] )
		/*|| ! empty( $_FILES )*/ ) )
{
	$table = issetVal( $_REQUEST['table'] );

	if ( ! in_array( $table, [ 'staff_absences' ] ) )
	{
		// Security: SQL prevent INSERT or UPDATE on any table
		$table = '';

		$_REQUEST['tables'] = [];
	}

	$id = $_REQUEST['id'];

	$_REQUEST['tables'][ $id ] = FilterCustomFieldsMarkdown( 'staff_absence_fields', 'tables', $id );

	if ( ! empty( $_REQUEST['inputstaffabsenceemailtext'] ) )
	{
		SaveTemplate( $_REQUEST['inputstaffabsenceemailtext'] );
	}

	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		// FJ added SQL constraint START_DATE, END_DATE is not null.
		if ( ( ! isset( $columns['START_DATE'] )
				|| ! empty( $columns['START_DATE'] ) )
			&& ( ! isset( $columns['END_DATE'] )
				|| ! empty( $columns['END_DATE'] ) ) )
		{
			// Check Start Date is anterior to End Date.
			if ( $columns['START_DATE'] <= $columns['END_DATE'] )
			{
				if ( isset( $columns['START_DATE'] ) )
				{
					// Add Time to Date: Morning starts at midnight, afternoon starts at noon.
					$columns['START_DATE'] .= $columns['START_DATE_AM_PM'] === 'AM' ?
						' 00:00:00' : ' 12:00:00';

					unset( $columns['START_DATE_AM_PM'] );
				}

				if ( isset( $columns['END_DATE'] ) )
				{
					// Add Time to Date: Morning ends before noon, afternoon ends before midnight.
					$columns['END_DATE'] .= $columns['END_DATE_AM_PM'] === 'AM' ?
						' 11:59:59' : ' 23:59:59';

					unset( $columns['END_DATE_AM_PM'] );
				}

				// New Absence.
				$sql = 'INSERT INTO ' . DBEscapeIdentifier( $table ) . ' ';

				// New Document.
				if ( $table === 'staff_absences' )
				{
					$staff_id = User( 'PROFILE' ) === 'admin' ? UserStaffID() : User( 'STAFF_ID' );

					$fields = 'SYEAR,CREATED_BY,STAFF_ID,';

					$values = "'" . UserSyear() . "','" . User( 'STAFF_ID' ) . "','" . $staff_id . "',";
				}

				$fields_RET = DBGet( "SELECT ID,TYPE
					FROM staff_absence_fields
					ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );

				$go = false;

				foreach ( (array) $columns as $column => $value )
				{
					if ( isset( $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] )
						&& $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] == 'numeric'
						&& $value != ''
						&& ! is_numeric( $value ) )
					{
						$error[] = _( 'Please enter valid Numeric data.' );
						continue;
					}

					if ( is_array( $value ) )
					{
						// Select Multiple from Options field type format.
						$value = implode( '||', $value ) ? '||' . implode( '||', $value ) : '';
					}

					if ( ! empty( $value )
						|| $value == '0' )
					{
						$fields .= DBEscapeIdentifier( $column ) . ',';

						$values .= "'" . $value . "',";

						$go = true;
					}
				}

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

				if ( $go )
				{
					DBQuery( $sql );

					if ( function_exists( 'DBLastInsertID' ) )
					{
						$id = DBLastInsertID();
					}
					else
					{
						// @deprecated since RosarioSIS 9.2.1.
						$id = DBGetOne( "SELECT LASTVAL();" );
					}

					if ( $table === 'staff_absences' )
					{
						$_REQUEST['id'] = $id;
					}

					if ( isset( $_REQUEST['cancelledcp'] ) )
					{
						foreach ( $_REQUEST['cancelledcp'] as $course_period_id )
						{
							if ( ! $course_period_id )
							{
								// Fix regression since RosarioSIS 10.8.4, skip hidden empty input
								continue;
							}

							if ( $_REQUEST['emailscpto'] )
							{
								$cp_emails = StaffAbsenceNotificationGetCoursePeriodEmails(
									$course_period_id,
									$_REQUEST['emailscpto']
								);

								$email_sent = StaffAbsenceSendNotification( $_REQUEST['id'], $cp_emails );
							}

							// SQL insert Cancelled Course Period.
							DBQuery( "INSERT INTO staff_absence_course_periods (STAFF_ABSENCE_ID,COURSE_PERIOD_ID)
								VALUES('" . $_REQUEST['id'] . "','" . $course_period_id ."')" );
						}
					}

					if ( ! empty( $_REQUEST['admin_emails'] )
						|| ! empty( $_REQUEST['teacher_emails'] ) )
					{
						$emails = array_merge(
							(array) $_REQUEST['admin_emails'],
							(array) $_REQUEST['teacher_emails']
						);

						$email_sent = StaffAbsenceSendNotification( $_REQUEST['id'], $emails );
					}
				}

				$uploaded = FilesUploadUpdate(
					'staff_absences',
					'tablesnew',
					$FileUploadsPath . 'Staff_Absences/' . (int) $_REQUEST['id'] . '/'
				);

				if ( $go )
				{
					$note[] = dgettext( 'Staff_Absences', 'That absence has been referred to an administrator.' );

					if ( ! empty( $email_sent ) )
					{
						$note[] = dgettext( 'Staff_Absences', 'That absence has been emailed.' );
					}
				}
			}
			else
			{
				$error[] = _( 'Start date must be anterior to end date.' );
			}
		}
		else
		{
			$error[] = _( 'Please fill in the required fields' );
		}
	}

	// Unset tables, modfunc & redirect URL.
	RedirectURL( [ 'tables', 'modfunc' ] );
}


if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error );

	if ( UserStaffID() )
	{
		$is_admin_teacher = DBGetOne( "SELECT 1
			FROM staff
			WHERE STAFF_ID='" . UserStaffID() . "'
			AND PROFILE IN('admin','teacher')" );

		if ( ! $is_admin_teacher
			|| User( 'PROFILE' ) === 'teacher' )
		{
			unset( $_SESSION['staff_id'] );
		}
	}

	if ( User( 'PROFILE' ) === 'admin' )
	{
		// Only search Admin & Teachers.
		$extra['WHERE'] = " AND s.PROFILE IN('admin','teacher') ";

		Search( 'staff_id', $extra );

		// Remove parent & none profiles from list.
		?>
		<script>
			$("#profile option[value='parent']").remove();
			$("#profile option[value='none']").remove();
			$("input[name='include_inactive']").parent( 'label' ).remove();
		</script>
		<?php
	}

	if ( UserStaffID()
		|| User( 'PROFILE' ) === 'teacher' )
	{
		$RET = [];

		$RET['ID'] = 'new';

		$extra_fields = GetStaffAbsenceFields( $RET['ID'] );

		echo StaffAbsenceGetForm(
			$RET,
			isset( $extra_fields ) ? $extra_fields : []
		);
	}
}
