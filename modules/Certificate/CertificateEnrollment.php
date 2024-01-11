<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/Template.fnc.php';
require_once 'ProgramFunctions/Substitutions.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( empty( $_REQUEST['st_arr'] ) )
	{
		BackPrompt( _( 'You must choose at least one student.' ) );
	}

	$_REQUEST['mailing_labels'] = issetVal( $_REQUEST['mailing_labels'], '' );

	$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

	$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

	if ( $_REQUEST['mailing_labels'] == 'Y' )
	{
		Widgets( 'mailing_labels' );
	}

	$extra['SELECT'] = issetVal( $extra['SELECT'], '' );

	// SELECT s.* Custom Fields for Substitutions.
	$extra['SELECT'] .= ",s.*";

	$extra['SELECT'] .= ",(SELECT sch.PRINCIPAL FROM schools sch
		WHERE ssm.SCHOOL_ID=sch.ID
		AND sch.SYEAR='" . UserSyear() . "') AS SCHOOL_PRINCIPAL";

	if ( empty( $_REQUEST['_search_all_schools'] ) )
	{
		// School Title.
		$extra['SELECT'] .= ",(SELECT sch.TITLE FROM schools sch
			WHERE ssm.SCHOOL_ID=sch.ID
			AND sch.SYEAR='" . UserSyear() . "') AS SCHOOL_TITLE";
	}

	$RET = GetStuList( $extra );

	if ( empty( $RET ) )
	{
		BackPrompt( _( 'No Students were found.' ) );
	}

	if ( User( 'PROFILE' ) === 'admin' )
	{
		// Use only 1 template for all users (default), set to 0.
		SaveTemplate( DBEscapeString( SanitizeHTML( $_POST['certificate_text'] ) ), '', 0 );
	}

	$certificate_text_template = GetTemplate();

	$handle = PDFStart();

	foreach ( (array) $RET as $student )
	{
		unset( $_ROSARIO['DrawHeader'] );

		if ( $_REQUEST['mailing_labels'] == 'Y' )
		{
			echo '<br /><br /><br />';
		}

		DrawHeader( '&nbsp;' );
		DrawHeader( $student['FULL_NAME'], $student['STUDENT_ID'] );
		DrawHeader( $student['GRADE_ID'], $student['SCHOOL_TITLE'] );
		DrawHeader( ProperDate( DBDate() ) );

		if ( $_REQUEST['mailing_labels'] == 'Y' )
		{
			echo '<br /><br /><table class="width-100p"><tr><td style="width:50px;"> &nbsp; </td><td>' . $student['MAILING_LABEL'] . '</td></tr></table><br />';
		}

		$substitutions = [
			'__FULL_NAME__' => $student['FULL_NAME'],
			'__LAST_NAME__' => $student['LAST_NAME'],
			'__FIRST_NAME__' => $student['FIRST_NAME'],
			'__MIDDLE_NAME__' =>  $student['MIDDLE_NAME'],
			'__STUDENT_ID__' => $student['STUDENT_ID'],
			'__SCHOOL_TITLE__' => $student['SCHOOL_TITLE'],
			'__SCHOOL_YEAR__' => FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ),
			'__SCHOOL_PRINCIPAL__' => $student['SCHOOL_PRINCIPAL'],
			'__GRADE_ID__' => $student['GRADE_ID'],
			'__DATE_TODAY__' => ProperDate( DBDate() ),
		];

		$substitutions += SubstitutionsCustomFieldsValues( 'student', $student );

		$certificate_text = SubstitutionsTextMake( $substitutions, $certificate_text_template );

		echo '<br />' . $certificate_text;
		echo '<div style="page-break-after: always;"></div>';
	}

	PDFStop( $handle );
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	if ( isset( $_REQUEST['search_modfunc'] )
		&& $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&include_inactive=' .
			issetVal( $_REQUEST['include_inactive'], '' ) . '&_search_all_schools=' .
			issetVal( $_REQUEST['_search_all_schools'], '' ) . '&_ROSARIO_PDF=true' ) . '" method="POST">';

		$extra['header_right'] = Buttons( dgettext( 'Certificate', 'Print Certificate for Selected Students' ) );

		if ( User( 'PROFILE' ) === 'admin' )
		{
			Widgets( 'mailing_labels' );

			$extra['extra_header_left'] = '<table>' . $extra['search'] . '</table>';
			$extra['search'] = '';

			// FJ add TinyMCE to the textarea.
			$extra['extra_header_left'] .= '<table class="width-100p"><tr><td>' .
			TinyMCEInput(
				GetTemplate(),
				'certificate_text',
				dgettext( 'Certificate', 'Certificate Text' )
			) . '</td></tr>';

			$substitutions = [
				'__FULL_NAME__' => _( 'Display Name' ),
				'__LAST_NAME__' => _( 'Last Name' ),
				'__FIRST_NAME__' => _( 'First Name' ),
				'__MIDDLE_NAME__' =>  _( 'Middle Name' ),
				'__STUDENT_ID__' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
				'__SCHOOL_TITLE__' => _( 'School' ),
				'__SCHOOL_YEAR__' => _( 'School Year' ),
				'__SCHOOL_PRINCIPAL__' => _( 'Principal of School' ),
				'__GRADE_ID__' => _( 'Grade Level' ),
				'__DATE_TODAY__' => dgettext( 'Certificate', 'Today\'s date' ),
			];

			if ( User( 'PROFILE' ) !== 'admin' )
			{
				$substitutions['__FULL_NAME__'] = _( 'Your Name' );
			}

			$substitutions += SubstitutionsCustomFields( 'student' );

			$extra['extra_header_left'] .= '<tr class="st"><td class="valign-top">' .
				SubstitutionsInput( $substitutions ) .
			'</td></tr></table>';
		}
	}

	$extra['SELECT'] = issetVal( $extra['SELECT'], '' );

	$extra['SELECT'] .= ",s.STUDENT_ID AS CHECKBOX";
	$extra['link'] = [ 'FULL_NAME' => false ];
	$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];
	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox(
		// @since RosarioSIS 11.5 Prevent submitting form if no checkboxes are checked
		( version_compare( ROSARIO_VERSION, '11.5', '<' ) ? 'Y' : 'Y_required' ),
		'',
		'st_arr'
	) ];
	$extra['options']['search'] = false;
	$extra['new'] = true;

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' .
		Buttons( dgettext( 'Certificate', 'Print Certificate for Selected Students' ) ) . '</div></form>';
	}
}
