<?php
/**
 * Staff Absences functions
 *
 * @package Staff Absences module
 */


/**
 * Get Staff Absence Form
 *
 * @example echo StaffAbsenceGetForm( $title, $RET );
 *
 * @example echo StaffAbsenceGetForm(
 *              $title,
 *              $RET,
 *              null,
 *              array( 'text' => _( 'Text' ), 'textarea' => _( 'Long Text' ) )
 *          );
 *
 * @uses DrawHeader()
 *
 * @param  array  $RET                   Absence Data.
 * @param  array  $extra_fields          Extra fields for Absence.
 *
 * @return string Absence Form HTML
 */
function StaffAbsenceGetForm( $RET, $extra_fields = [] )
{
	$id = issetVal( $RET['ID'] );

	if ( empty( $id ) )
	{
		return '';
	}

	$new = $id === 'new';

	$action = 'Modules.php?modname=' . $_REQUEST['modname'] . '&id=' . $id;

	if ( $id )
	{
		$full_table = 'staff_absences';
	}

	$action .= '&table=' . $full_table . '&modfunc=save';

	$form = '<form action="' . ( function_exists( 'URLEscape' ) ? URLEscape( $action ) : _myURLEncode( $action ) ) . '" method="POST" enctype="multipart/form-data">';

	$allow_edit = AllowEdit();

	$div = $allow_edit && ! $new;

	$delete_button = '';

	if ( $allow_edit
		&& ! $new
		&& $id )
	{
		$delete_URL = ( function_exists( 'URLEscape' ) ?
			URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] . '&modfunc=delete&id=' . $id ) :
			_myURLEncode( "Modules.php?modname=" . $_REQUEST['modname'] . '&modfunc=delete&id=' . $id ) );

		$onclick_link = 'ajaxLink(' . json_encode( $delete_URL ) . ');';

		$delete_button = '<input type="button" value="' .
		( function_exists( 'AttrEscape' ) ? AttrEscape( _( 'Delete' ) ) : htmlspecialchars( _( 'Delete' ), ENT_QUOTES ) ) .
		'" onclick="' .
		( function_exists( 'AttrEscape' ) ? AttrEscape( $onclick_link ) : htmlspecialchars( $onclick_link, ENT_QUOTES ) ) .
		'" /> ';
	}

	ob_start();

	DrawHeader( '', $delete_button . SubmitButton() );

	$staff_id = User( 'PROFILE' ) === 'admin' ? UserStaffID() : User( 'STAFF_ID' );

	if ( ! $new )
	{
		$staff_id = $RET['STAFF_ID'];
	}

	DrawHeader( StaffAbsenceMakeName( $staff_id, 'PHOTO' ) );

	$form .= ob_get_clean();

	$header = '<table class="width-100p valign-top fixed-col cellpadding-5"><tr class="st">';

	$am_pm_options = [
		'AM' => dgettext( 'Staff_Absences', 'Morning' ),
		'PM' => dgettext( 'Staff_Absences', 'Afternoon' ),
	];

	// Start Date.
	$header .= '<td colspan="2">';

	if ( empty( $RET['START_DATE'] ) )
	{
		$RET['START_DATE'] = DBDate();
	}

	$date_html = '<table><tr><td>' . DateInput(
		mb_substr( $RET['START_DATE'], 0, 10 ),
		'tables[' . $id . '][START_DATE]',
		_( 'Start Date' ),
		false,
		false,
		true
	) . '</td><td>' .
	SelectInput(
		( mb_substr( $RET['START_DATE'], 11, 2 ) >= 12 ? 'PM' : 'AM' ),
		'tables[' . $id . '][START_DATE_AM_PM]',
		'',
		$am_pm_options,
		false,
		'',
		false
	) . '</td></tr></table>';

	if ( $id === 'new' )
	{
		$header .= $date_html;
	}
	else
	{
		$header .= InputDivOnclick(
			'tables' . $id . 'startdate',
			$date_html,
			StaffAbsenceMakeDate( $RET['START_DATE'] ),
			FormatInputTitle( _( 'Starts' ), 'tables' . $id . 'startdate' )
		);
	}

	$header .= '</td></tr>';

	// End Date.
	$header .= '<tr><td colspan="2">';

	if ( empty( $RET['END_DATE'] ) )
	{
		$RET['END_DATE'] = '';
	}

	$date_html = '<table><tr><td>' . DateInput(
		mb_substr( $RET['END_DATE'], 0, 10 ),
		'tables[' . $id . '][END_DATE]',
		dgettext( 'Staff_Absences', 'End Date' ),
		false,
		true,
		true
	) . '</td><td>' .
	SelectInput(
		( mb_substr( $RET['END_DATE'], 11, 2 ) >= 12 ? 'PM' : 'AM' ),
		'tables[' . $id . '][END_DATE_AM_PM]',
		'',
		$am_pm_options,
		false,
		'',
		false
	)  . '</td></tr></table>';

	if ( $id === 'new' )
	{
		$header .= $date_html;
	}
	else
	{
		$header .= InputDivOnclick(
			'tables' . $id . 'enddate',
			$date_html,
			StaffAbsenceMakeDate( $RET['END_DATE'] ),
			FormatInputTitle( _( 'Ends' ), 'tables' . $id . 'enddate' )
		);
	}

	$header .= '</td></tr>';

	$is_teacher = User( 'PROFILE' ) === 'teacher';

	$staff_id = User( 'PROFILE' ) === 'admin' ? UserStaffID() : User( 'STAFF_ID' );

	if ( ! $is_teacher )
	{
		if ( $id !== 'new' )
		{
			$staff_id = $RET['STAFF_ID'];
		}

		$is_teacher = DBGetOne( "SELECT 1 FROM staff
				WHERE STAFF_ID='" . (int) $staff_id . "'
				AND PROFILE='teacher'" );
	}

	if ( $is_teacher )
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

		if ( $cp_RET )
		{
			// Get Course Periods:
			$cp_options = [];

			foreach ( (array) $cp_RET as $cp )
			{
				$cp_options[ $cp['COURSE_PERIOD_ID'] ] = $cp['TITLE'];
			}

			$values = $allow_na = $div = $extra = false;

			if ( $id !== 'new' )
			{
				// Get Cancelled Classes.
				$cancelled_cp_RET = DBGet( "SELECT COURSE_PERIOD_ID
					FROM staff_absence_course_periods
					WHERE STAFF_ABSENCE_ID='" . (int) $id . "'" );

				$values = [];

				foreach ( $cancelled_cp_RET as $cancelled_cp )
				{
					$values[] = $cancelled_cp['COURSE_PERIOD_ID'];
				}
			}

			// Chosen Multiple select inputs.
			$extra = 'multiple autocomplete="off"';

			// @since RosarioSIS 10.7 Use Select2 input instead of Chosen, fix overflow issue.
			$select_input_function = function_exists( 'Select2Input' ) ? 'Select2Input' : 'ChosenSelectInput';

			// Select Course Periods (Teacher only).
			$header .= '<tr><td colspan="2">' . $select_input_function(
				$values,
				'cancelledcp[]',
				dgettext( 'Staff_Absences', 'Cancelled Classes' ),
				$cp_options,
				$allow_na,
				$extra,
				$div
			) . '</td></tr>';
		}
	}

	// Extra Fields.
	if ( ! empty( $extra_fields ) )
	{
		$header .= '<tr><td colspan="2"><hr /></td></tr><tr class="st">';

		$i = 0;

		foreach ( (array) $extra_fields as $extra_field )
		{
			if ( $i && $i % 2 === 0 )
			{
				$header .= '</tr><tr class="st">';
			}

			$colspan = 1;

			if ( $i === ( count( $extra_fields ) - 1 ) )
			{
				$colspan = abs( ( $i % 2 ) - 2 );
			}

			$header .= '<td colspan="' . $colspan . '">' . $extra_field . '</td>';

			$i++;
		}

		$header .= '</tr>';
	}

	$header .= '</table>';

	ob_start();

	DrawHeader( $header );

	if ( function_exists( 'StaffAbsenceNotificationHeader' ) )
	{
		DrawHeader( StaffAbsenceNotificationHeader() );
	}

	$form .= ob_get_clean();

	$form .= '<br /><div class="center">' . SubmitButton() . '</div>';

	$form .= '</form>';

	return $form;
}


/**
 * Outputs Staff Absences list
 *
 * @example StaffAbsencesListOutput( $absences_RET );
 *
 * @uses ListOutput()
 *
 * @param array  $RET        Absences RET.
 * @param array  $LO_columns List columns.
 */
function StaffAbsencesListOutput( $RET, $LO_columns )
{
	$link['FULL_NAME']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'];
	$link['FULL_NAME']['variables'] = [ 'id' => 'ID' ];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete';
	$link['remove']['variables'] = [ 'id' => 'ID' ];

	ListOutput(
		$RET,
		$LO_columns,
		dgettext( 'Staff_Absences', 'Absence' ),
		_( 'Absences' ),
		$link
	);
}

/**
 * Get Staff Absence Fields
 *
 * @param int $absence_id Absence ID.
 *
 * @return array Staff Absence Fields.
 */
function GetStaffAbsenceFields( $absence_id )
{
	global $value,
		$field;

	// FJ add Staff Absence Fields.
	$fields_RET = DBGet( "SELECT ID,TITLE,TYPE,SELECT_OPTIONS,DEFAULT_SELECTION,REQUIRED
		FROM staff_absence_fields
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

	$fields_RET = ParseMLArray( $fields_RET, 'TITLE' );

	if ( $absence_id !== 'new' )
	{
		$custom_RET = DBGet( "SELECT *
			FROM staff_absences
			WHERE ID='" . (int) $absence_id . "'" );

		$value = $custom_RET[1];
	}
	else
	{
		$value = [];
	}

	$absence_fields = [];

	foreach ( (array) $fields_RET as $field )
	{
		$value_custom = isset( $value['CUSTOM_' . $field['ID']] ) ? $value['CUSTOM_' . $field['ID']] : '';

		if ( ! isset( $value['CUSTOM_' . $field['ID']] ) )
		{
			$value['CUSTOM_' . $field['ID']] = '';
		}

		$div = true;

		$title_custom = AllowEdit() && ! $value_custom && $field['REQUIRED'] ?
		'<span class="legend-red">' . $field['TITLE'] . '</span>' :
		$field['TITLE'];

		switch ( $field['TYPE'] )
		{
			case 'text':
			case 'numeric':

				$absence_fields[] = _makeTextInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'tables[' . $absence_id . ']' );

				break;

			case 'date':

				$absence_fields[] = _makeDateInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'tables[' . $absence_id . ']' );

				break;

			case 'textarea':

				$absence_fields[] = _makeTextAreaInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'tables[' . $absence_id . ']' );

				break;

			// Add School Field types.
			case 'radio':
				$absence_fields[] = CheckboxInput(
					$value_custom,
					'tables[' . $absence_id . '][CUSTOM_' . $field['ID'] . ']',
					$title_custom,
					'',
					false,
					'Yes',
					'No',
					$div,
					( $field['REQUIRED'] ? ' required' : '' )
				);

				break;

			case 'multiple':

				$absence_fields[] = _makeMultipleInput( 'CUSTOM_' . $field['ID'], $title_custom, 'tables[' . $absence_id . ']' );

				break;

			case 'autos':

				$col_name = DBEscapeIdentifier( 'CUSTOM_' . $field['ID'] );

				$sql_options = "SELECT DISTINCT sa." . $col_name . ",upper(sa." . $col_name . ") AS SORT_KEY
					FROM staff_absences sa
					WHERE sa." . $col_name . " IS NOT NULL
					AND s." . $col_name . "<>''
					AND s." . $col_name . "<>'---'
					ORDER BY SORT_KEY";

				$options_RET = DBGet( $sql_options );

				$absence_fields[] = _makeAutoSelectInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'tables[' . $absence_id . ']', $options_RET );

				break;

			case 'exports':
			case 'select':

				$absence_fields[] = _makeSelectInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'tables[' . $absence_id . ']' );

				break;

			case 'files':

				$absence_fields[] = _makeFilesInput(
					'CUSTOM_' . $field['ID'],
					$field['TITLE'],
					'tables' . $absence_id,
					'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove_file&field_id=' .
						$field['ID'] . '&id=' . $absence_id . '&filename='
				);

				break;
		}
	}

	return $absence_fields;
}
