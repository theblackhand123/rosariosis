<?php
/**
 * Student Quizzes
 *
 * Consult & Submit Quizzes
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'modules/Quiz/includes/common.fnc.php';
require_once 'modules/Quiz/includes/StudentQuizzes.fnc.php';
require_once 'modules/Grades/includes/StudentAssignments.fnc.php';

if ( ! empty( $_REQUEST['quiz_id'] )
	&& ! empty( $_REQUEST['marking_period_id'] ) )
{
	// Outside link: Quiz is in the current MP?
	if ( $_REQUEST['marking_period_id'] != UserMP() )
	{
		// Reset current MarkingPeriod.
		$_SESSION['UserMP'] = $_REQUEST['marking_period_id'];
	}

	RedirectURL( 'marking_period_id' );
}

DrawHeader( ProgramTitle() . ' - ' . GetMP( UserMP() ) );

if ( isset( $_REQUEST['quiz_id'] )
	&& $_REQUEST['quiz_id'] )
{
	if ( $_REQUEST['modfunc'] === 'save' )
	{
		$submitted = StudentQuizSubmit( $_REQUEST['quiz_id'] );

		if ( $submitted )
		{
			$note[] = button( 'check', '', '', 'bigger' ) . '&nbsp;' . dgettext( 'Quiz', 'Quiz submitted.' );
		}

		echo ErrorMessage( $error );

		// Unset modfunc, values & redirect URL.
		RedirectURL( [ 'modfunc', 'values' ] );
	}

	$quizzes_link = PreparePHP_SELF( $_REQUEST, [ 'search_modfunc', 'quiz_id' ] );

	DrawHeader( '<a href="' . $quizzes_link . '">' . dgettext( 'Quiz', 'Back to Quizzes' ) . '</a>' );

	$form_action = PreparePHP_SELF( $_REQUEST, [], [ 'modfunc' => 'save' ] );

	echo '<form method="POST" action="' . $form_action . '">';

	StudentQuizSubmissionOutput( $_REQUEST['quiz_id'] );

	echo '</form>';
}
else
{
	// Output Current Quarter's Quizzes List.
	StudentQuizzesListOutput();
}
