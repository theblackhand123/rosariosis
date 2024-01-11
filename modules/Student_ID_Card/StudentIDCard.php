<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/Template.fnc.php';
require_once 'ProgramFunctions/Substitutions.fnc.php';
require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'modules/Student_ID_Card/includes/StudentIDCard.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( empty( $_REQUEST['st_arr'] ) )
	{
		BackPrompt( _( 'You must choose at least one student.' ) );
	}

	if ( User( 'PROFILE' ) === 'admin'
		&& AllowEdit() )
	{
		if ( ! empty( $_FILES['background_image']['name'] ) )
		{
			// Upload background image.
			$_REQUEST['custom_css']['background_image'] = ImageUpload(
				'background_image',
				[],
				$FileUploadsPath,
				[ '.jpg', '.png', '.gif' ],
				null,
				'student-id-card-background'
			);

			$custom_css = StudentIDCardGetCustomCSS();

			if ( $_REQUEST['custom_css']['background_image']
				&& $_REQUEST['custom_css']['background_image'] !== $custom_css['background_image']
				&& file_exists( $custom_css['background_image'] ) )
			{
				// Delete old background image.
				unlink( $custom_css['background_image'] );
			}
		}
		else
		{
			$background_path = (array) glob( $FileUploadsPath . 'student-id-card-background.*' );

			$_REQUEST['custom_css']['background_image'] = end( $background_path );
		}

		StudentIDCardSaveCustomCSS( $_REQUEST['custom_css'] );
	}

	$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

	$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

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

	if ( User( 'PROFILE' ) === 'admin'
		&& AllowEdit() )
	{
		// Use only 1 template for all users (default), set to 0.
		SaveTemplate( DBEscapeString( SanitizeHTML( $_POST['student_id_card_text'] ) ), '', 0 );
	}

	$student_id_card_text_template = GetTemplate();

	// Temporarily deactivate wkhtmltopdf, we want HTML output.
	$wkhtmltopdfPath_tmp = $wkhtmltopdfPath;
	$wkhtmltopdfPath = '';

	$handle = PDFStart();

	// Load JS to take screenshot of HTML (convert HTML to PNG) + save images as zip.
	?>
	<script async defer src="modules/Student_ID_Card/js/dom-to-image.min.js"></script>
	<script async defer src="modules/Student_ID_Card/js/jszip.min.js"></script>
	<script async defer src="modules/Student_ID_Card/js/FileSaver.min.js"></script>
	<script async defer src="modules/Student_ID_Card/js/scripts.js"></script>
	<?php

	// Custom CSS & Background image.
	$custom_css = StudentIDCardGetCustomCSS();

	$custom_css_variables = StudentIDCardGetCustomCSSVariables( $custom_css );

	echo '<style>:root {';

	foreach ( $custom_css_variables as $custom_css_var => $custom_css_val )
	{
		echo $custom_css_var . ': ' . $custom_css_val . ';';
	}

	echo '}</style>';

	?>
	<link rel="stylesheet" type="text/css" href="modules/Student_ID_Card/css/stylesheet.css" />
	<?php

	// Load custom CSS :)
	if ( file_exists( 'modules/Student_ID_Card/css/custom.css' ) ) : ?>
		<link rel="stylesheet" type="text/css" href="modules/Student_ID_Card/css/custom.css" />
	<?php endif;

	// Action hook for Premium module.
	do_action( 'Student_ID_Card/StudentIDCard.php|load_extra_js_css' );

	DrawHeader( ProgramTitle() );

	DrawHeader(
		dgettext(
			'Student_ID_Card',
			'To print in high quality (144dpi) and at true size, save the cards as an image, and print to 50&percnt; scale.'
		)
	);

	DrawHeader(
		'',
		'<input type="button" id="convert-images-button" onclick="takeScreenshots(\'student-id-card\');" value="' . dgettext(
			'Student_ID_Card',
			'Convert Student ID Cards to Images'
		) . '"><span class="loading"></span>' .
		'<input type="button" class="hide" id="download-zip-button" onclick="downloadZip(\'.output img\')" value="' . dgettext(
			'Student_ID_Card',
			'Download Student ID Cards as zip'
		) . '">'
	);

	$i = 0;

	$cards_per_page = 3;

	foreach ( (array) $RET as $student )
	{
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
		];

		$substitutions += SubstitutionsCustomFieldsValues( 'STUDENT', $student );

		if ( ! empty( $substitutions['__STUDENT_200000004__'] ) )
		{
			// Fix CSS first-letter capitalize for .proper-date
			// not taken in account when exporting to image
			// force capital first-letter for Birthdate.
			$substitutions['__STUDENT_200000004__'] = ucfirst( $substitutions['__STUDENT_200000004__'] );
		}

		$student_id_card_text = SubstitutionsTextMake( $substitutions, $student_id_card_text_template );

		echo StudentIDCardHTML( $student['STUDENT_ID'], $student_id_card_text );

		echo '<br /><br />';

		if ( ++$i % $cards_per_page === 0 )
		{
			// 4 cards per page, 3 on first page.
			echo '<div style="page-break-after: always;"></div>';
		}

		if ( $i >= 3
			&& $cards_per_page === 3 )
		{
			$cards_per_page = 4;

			$i = 0;
		}
	}

	PDFStop( $handle );

	// Reactivate wkhtmltopdf.
	$wkhtmltopdfPath = $wkhtmltopdfPath_tmp;
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		// Always load CSS (.student-id-card class used on <form>)
		// Custom CSS & Background image.
		$custom_css = StudentIDCardGetCustomCSS();

		$custom_css_variables = StudentIDCardGetCustomCSSVariables( $custom_css );

		echo '<style>:root {';

		foreach ( $custom_css_variables as $custom_css_var => $custom_css_val )
		{
			echo $custom_css_var . ': ' . $custom_css_val . ';';
		}

		echo '}</style>';

		?>
		<link rel="stylesheet" type="text/css" href="modules/Student_ID_Card/css/stylesheet.css" />
		<?php

		// Load custom CSS :)
		if ( file_exists( 'modules/Student_ID_Card/css/custom.css' ) ) : ?>
			<link rel="stylesheet" type="text/css" href="modules/Student_ID_Card/css/custom.css" />
		<?php endif;

		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&include_inactive=' .
			issetVal( $_REQUEST['include_inactive'], '' ) . '&_search_all_schools=' .
			issetVal( $_REQUEST['_search_all_schools'], '' ) . '&_ROSARIO_PDF=true' ) . '" method="POST" enctype="multipart/form-data">';

		$extra['header_right'] = Buttons( dgettext( 'Student_ID_Card', 'Generate ID Card for Selected Students' ) );

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$extra['search'] = '';

			// FJ add TinyMCE to the textarea.
			$extra['extra_header_left'] = '<table class="width-100p"><tr><td>' .
			TinyMCEInput(
				GetTemplate(),
				'student_id_card_text',
				dgettext( 'Student_ID_Card', 'Student ID Card Text' ),
				'style="width:var( --card_width);"'
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
			];

			if ( User( 'PROFILE' ) !== 'admin' )
			{
				$substitutions['__FULL_NAME__'] = _( 'Your Name' );
			}

			$substitutions += SubstitutionsCustomFields( 'STUDENT' );

			$extra['extra_header_left'] .= '<table class="width-100p cellpadding-5"><tr class="st"><td class="valign-top">' .
				SubstitutionsInput( $substitutions ) .
			'<hr /></td></tr>';

			// Custom CSS & Background image.
			$custom_css = StudentIDCardGetCustomCSS();

			$extra['extra_header_left'] .= StudentIDCardOptionsFormHTML( $custom_css );

			$extra['extra_header_left'] .= '</table>';
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
		Buttons( dgettext( 'Student_ID_Card', 'Generate ID Card for Selected Students' ) ) . '</div></form>';
	}
}
