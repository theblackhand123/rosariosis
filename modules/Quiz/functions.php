<?php
/**
 * Module Functions / Actions
 * (Loaded on each page)
 *
 * @package Quiz module
 */

/**
 * Quiz header on Assignments program.
 *
 * @uses Grades/Assignments.php|header hook
 *
 * @return true if header, else false.
 */
function QuizAssignmentsHeader()
{
	require_once 'modules/Quiz/includes/Quizzes.fnc.php';

	if ( ! empty( $_REQUEST['assignment_id'] )
		&& $_REQUEST['assignment_id'] !== 'new'
		&& AllowUse( 'Quiz/Quizzes.php' ) )
	{
		// Quizzes program link header.
		$quizzes_program_link =
		dgettext( 'Quiz', 'Quizzes' ) . ': ';

		$quizzes = QuizGetAssignmentQuizzes( $_REQUEST['assignment_id'] );

		if ( $quizzes )
		{
			// @todo Handle case where Assignment has multiple Quizzes?

			$quizzes_program_link .= '<b><a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=Quiz/Quizzes.php&category_id=' . $quizzes[1]['ID'] ) :
			_myURLEncode( 'Modules.php?modname=Quiz/Quizzes.php&category_id=' . $quizzes[1]['ID'] ) ) . '">' .
			$quizzes[1]['TITLE'] . '</a></b>';
		}
		else
		{
			$quizzes_program_link .= '<b><a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=Quiz/Quizzes.php&category_id=new&assignment_id=' . $_REQUEST['assignment_id'] ) :
			_myURLEncode( 'Modules.php?modname=Quiz/Quizzes.php&category_id=new&assignment_id=' . $_REQUEST['assignment_id'] ) ) . '">' .
			dgettext( 'Quiz', 'New Quiz' ) . '</a></b>';
		}

		DrawHeader( $quizzes_program_link );

		return true;
	}

	return false;
}

/**
 * Register & Hook our function to
 * the 'Grades/Assignments.php|header' action tag.
 *
 * List of available actions:
 * @see functions/Actions.php
 */
add_action( 'Grades/Assignments.php|header', 'QuizAssignmentsHeader' );


/**
 * Quiz header on Assignment Submission screen.
 *
 * @uses Grades/includes/StudentAssignments.fnc.php|submission_header hook
 *
 * @return true if header, else false.
 */
function QuizAssignmentSubmissionHeader()
{
	global $note;

	require_once 'modules/Quiz/includes/StudentQuizzes.fnc.php';

	if ( ! empty( $_REQUEST['assignment_id'] )
		&& AllowUse( 'Quiz/StudentQuizzes.php' ) )
	{
		$quizzes = QuizGetAssignmentStudentQuizzes( $_REQUEST['assignment_id'], UserStudentID() );

		if ( $quizzes )
		{
			// @todo Handle case where Assignment has multiple Quizzes?

			// Student Quizzes program link header.
			$quizzes_program_link = dgettext( 'Quiz', 'Quiz' ) . ': ';
			$quizzes_program_link .= '<b><a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=Quiz/StudentQuizzes.php&quiz_id=' . $quizzes[1]['ID'] ) :
			_myURLEncode( 'Modules.php?modname=Quiz/StudentQuizzes.php&quiz_id=' . $quizzes[1]['ID'] ) ) . '">' .
			$quizzes[1]['TITLE'] . '</a></b>';

			$note[] = $quizzes_program_link;

			echo ErrorMessage( $note, 'note' );

			return true;
		}
	}

	return false;
}

/**
 * Register & Hook our function to
 * the 'Grades/includes/StudentAssignments.fnc.php|submission_header' action tag.
 *
 * List of available actions:
 * @see functions/Actions.php
 */
add_action( 'Grades/includes/StudentAssignments.fnc.php|submission_header', 'QuizAssignmentSubmissionHeader' );


/**
 * Quiz header on Assignments program.
 *
 * @uses Grades/Assignments.php|header hook
 *
 * @return true if header, else false.
 */
function QuizGradesSubmissionColumn( $tag )
{
	require_once 'modules/Quiz/includes/common.fnc.php';
	require_once 'modules/Quiz/includes/Quizzes.fnc.php';
	require_once 'modules/Quiz/includes/StudentQuizzes.fnc.php';

	global $THIS_RET,
		$submission_column_html;

	$student_id = UserStudentID() ? UserStudentID() : $THIS_RET['STUDENT_ID'];

	if ( empty( $THIS_RET['ASSIGNMENT_ID'] )
		|| ! AllowUse( 'Quiz/Quizzes.php' ) )
	{
		return false;
	}

	$assignment_id = $THIS_RET['ASSIGNMENT_ID'];

	$quizzes = QuizGetAssignmentQuizzes( $assignment_id );

	if ( empty( $quizzes ) )
	{
		return false;
	}

	// @todo Handle case where Assignment has multiple Quizzes?

	$quiz_answers_view = MakeQuizAnswersView( $quizzes[1]['ID'], $student_id );

	if ( ! $quiz_answers_view )
	{
		$submission_column_html .= button( 'x' ) . ' ' . dgettext( 'Quiz', 'Quiz' );

		return true;
	}

	$note_message = '';

	if ( QuizIsGraded( $quizzes[1]['ID'], $student_id ) )
	{
		$note[] = button( 'check' ) . '&nbsp;' .
			dgettext( 'Quiz', 'You already have graded this Quiz.' );

		$note_message = ErrorMessage( $note, 'note' );
	}

	$submission_id = 'submission-quiz' . $quizzes[1]['ID'] . '-' . $student_id;

	$submission_form_target_id = $submission_id . '-submit-grade';

	$quiz_answers_view = '<form method="POST"
		action="Modules.php?modname=Quiz/Quizzes.php&modfunc=grade&category_id=' . $quizzes[1]['ID'] . '"
		target="' . $submission_form_target_id . '">
		<input type="hidden" name="assignment_id" value="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $assignment_id ) : htmlspecialchars( $assignment_id, ENT_QUOTES ) ) . '" />
		<input type="hidden" name="student_id" value="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $student_id ) : htmlspecialchars( $student_id, ENT_QUOTES ) ) . '" />' .
		$quiz_answers_view .
		'<div id="' . $submission_form_target_id . '">' . $note_message . '</div>' .
		'<div class="center">' . SubmitButton( dgettext( 'Quiz', 'Grade Quiz' ) ) .
		'<span class="loading"></span>
		</div><br /></form>';

	$column_html = '<a class="colorboxinline" href="#' . $submission_id .
	'" title="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $quizzes[1]['TITLE'] ) : htmlspecialchars( $quizzes[1]['TITLE'], ENT_QUOTES ) ) . '">
	<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/visualize.png" class="button bigger" /> ' .
	dgettext( 'Quiz', 'Quiz' ) . '</a>';

	// Echo form now so we are outside the Gradebook form (nested forms do not work)!
	echo '<div class="hide">
		<div id="' . $submission_id . '">' .
	$quiz_answers_view .
		'</div></div>';

	$submission_column_html .= $column_html;

	return true;
}

/**
 * Register & Hook our function to
 * the 'Grades/includes/StudentAssignments.fnc.php|grades_submission_column' action tag.
 *
 * List of available actions:
 * @see functions/Actions.php
 */
add_action( 'Grades/includes/StudentAssignments.fnc.php|grades_submission_column', 'QuizGradesSubmissionColumn', 2 );
