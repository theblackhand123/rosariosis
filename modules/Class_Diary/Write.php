<?php
/**
 * Teacher Diary
 *
 * @package Class Diary module
 */

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'modules/Class_Diary/includes/common.fnc.php';

if ( ! empty( $_REQUEST['period'] )
	&& function_exists( 'SetUserCoursePeriod' ) )
{
	// @since RosarioSIS 10.9 Set current User Course Period.
	SetUserCoursePeriod( $_REQUEST['period'] );
}

DrawHeader( dgettext( 'Class_Diary', 'Class Diary' ) . ' &mdash; ' . ProgramTitle() );

// If running as a teacher program then rosario[allow_edit] will already be set according to admin permissions.
if ( ! isset( $_ROSARIO['allow_edit'] ) )
{
	$_ROSARIO['allow_edit'] = true;
}

if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit() )
{
	// Bypass strip_tags on the $_REQUEST vars.
	$REQUEST_message = SanitizeHTML( isset( $_POST['message'] ) ? $_POST['message'] : '' );

	if ( $REQUEST_message )
	{
		$from = User( 'NAME' );

		$data = [
			'from' => $from,
			'message' => $REQUEST_message,
		];

		$saved = ClassDiarySaveEntry(
			'new',
			UserCoursePeriod(),
			$data
		);

		if ( $saved )
		{
			$note[] = dgettext( 'Class_Diary', 'The entry has been added to the diary.' );
		}
	}

	// Unset modfunc, message & redirect URL.
	RedirectURL( [ 'modfunc', 'message' ] );
}

if ( ! UserCoursePeriod() )
{
	echo ErrorMessage( [ _( 'No courses assigned to teacher.' ) ], 'fatal' );
}

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

/**
 * Adding `'&period=' . UserCoursePeriod()` to the Teacher form URL will prevent the following issue:
 * If form is displayed for CP A, then Teacher opens a new browser tab and switches to CP B
 * Then teacher submits the form, data would be saved for CP B...
 *
 * Must be used in combination with
 * `if ( ! empty( $_REQUEST['period'] ) ) SetUserCoursePeriod( $_REQUEST['period'] );`
 */
echo '<form method="POST" action="' . PreparePHP_SELF(
	[],
	[],
	[ 'modfunc' => 'save', 'period' => UserCoursePeriod() ]
) . '">';

DrawHeader(
	ClassDiaryCoursePeriodTitle( UserCoursePeriod() ),
	SubmitButton()
);

DrawHeader( ClassDiarySubjectTitle( UserCoursePeriod() ) );

$message_inputs = ClassDiaryWriteInputs( UserCoursePeriod() );

DrawHeader( $message_inputs );

echo '<br /><div class="center">' . SubmitButton() . '</div></form>';
