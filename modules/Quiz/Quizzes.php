<?php
/**
 * Quizzes
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'modules/Quiz/includes/common.fnc.php';
require_once 'modules/Quiz/includes/Quizzes.fnc.php';
require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'modules/Grades/includes/StudentAssignments.fnc.php';

if ( ! empty( $_SESSION['is_secondary_teacher'] ) )
{
	// @since 6.9 Add Secondary Teacher: set User to main teacher.
	UserImpersonateTeacher();
}

$_REQUEST['category_id'] = issetVal( $_REQUEST['category_id'] );

$_REQUEST['id'] = issetVal( $_REQUEST['id'] );

QuizAllowEdit( $_REQUEST['category_id'] );

// Grade Quiz. AJAX call from Grades/Grades.php.
if ( $_REQUEST['modfunc'] === 'grade'
	&& AllowEdit()
	&& ! empty( $_REQUEST['category_id'] ) )
{
	require_once 'modules/Quiz/includes/StudentQuizzes.fnc.php';

	$total_points = QuizGradeStudentQuiz(
		$_REQUEST['category_id'],
		$_REQUEST['student_id'],
		$_REQUEST['quiz_answer_points']
	);

	// Update Total Points, Grade POINTS input, & display error or note message.
	echo QuizStudentQuizGradedAJAXUpdate(
		$_REQUEST['category_id'],
		$_REQUEST['student_id'],
		$total_points
	);

	die();
}

if ( ! empty( $_REQUEST['category_id'] )
	&& ! empty( $_REQUEST['marking_period_id'] ) )
{
	// Outside link: Assignment is in the current MP?
	if ( $_REQUEST['marking_period_id'] != UserMP() )
	{
		// Reset current MarkingPeriod.
		$_SESSION['UserMP'] = $_REQUEST['marking_period_id'];
	}

	RedirectURL( 'marking_period_id' );
}

DrawHeader( ProgramTitle() . ' - ' . GetMP( UserMP() ) );

QuizAllowEdit( $_REQUEST['category_id'] );

// @since 3.0 Do action hook.
do_action( 'Quiz/Quizzes.php|modfunc' );

if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit()
	&& ! empty( $_POST['tables'] ) )
{
	$table = in_array( $_REQUEST['table'], [ 'quiz', 'quiz_quizxquestion' ] ) ?
		$_REQUEST['table'] :
		null;

	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		if ( isset( $columns['DESCRIPTION'] ) )
		{
			$columns['DESCRIPTION'] = DBEscapeString( SanitizeHTML( $_POST['tables'][ $id ]['DESCRIPTION'] ) );
		}

		// FJ added SQL constraint TITLE is not null.
		if ( ! isset( $columns['TITLE'] )
			|| ! empty( $columns['TITLE'] ) )
		{
			if ( $table === 'quiz' )
			{
				// Remove options columns and add serialized OPTIONS column.
				$columns = QuizSaveOptionsColumns( $columns, $id );
			}

			// Update Quiz / Category.
			if ( $id !== 'new' )
			{
				$sql = 'UPDATE ' . DBEscapeIdentifier( $table ) . ' SET ';

				foreach ( (array) $columns as $column => $value )
				{
					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";

				$go = true;
			}
			// New Quiz / Category.
			else
			{
				$sql = 'INSERT INTO ' . DBEscapeIdentifier( $table ) . ' ';

				// New Quiz.
				if ( $table === 'quiz' )
				{
					// School, Created by.
					$fields = 'SCHOOL_ID,STAFF_ID,CREATED_BY,';

					$values = "'" . UserSchool() . "','" . User( 'STAFF_ID' ) . "','" . User( 'STAFF_ID' ) . "',";
				}

				if ( $table === 'quiz_quizxquestion' )
				{
					$fields = '';

					$values = '';
				}

				$go = false;

				foreach ( (array) $columns as $column => $value )
				{
					if ( ! empty( $value )
						|| $value == '0' )
					{
						$fields .= DBEscapeIdentifier( $column ) . ',';

						$values .= "'" . $value . "',";

						$go = true;
					}
				}

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';
			}

			if ( $go )
			{
				DBQuery( $sql );

				if ( $id === 'new' )
				{
					if ( function_exists( 'DBLastInsertID' ) )
					{
						$id = DBLastInsertID();
					}
					else
					{
						// @deprecated since RosarioSIS 9.2.1.
						$id = DBGetOne( "SELECT LASTVAL();" );
					}

					if ( $table === 'quiz' )
					{
						$_REQUEST['category_id'] = $id;
					}
					elseif ( $table === 'quiz_quizxquestion' )
					{
						$_REQUEST['id'] = $id;
					}
				}
			}
		}
		else
			$error[] = _( 'Please fill in the required fields' );
	}

	// Unset tables & redirect URL.
	RedirectURL( [ 'modfunc', 'table', 'tables' ] );
}

// Delete Quiz / Category.
if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( intval( $_REQUEST['id'] ) > 0 )
	{
		if ( DeletePrompt( dgettext( 'Quiz', 'Question' ) ) )
		{
			DBQuery( "DELETE FROM quiz_quizxquestion
				WHERE ID='" . (int) $_REQUEST['id'] . "'" );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}
	elseif ( intval( $_REQUEST['category_id'] ) > 0 )
	{
		if ( DeletePrompt( dgettext( 'Quiz', 'Quiz' ) ) )
		{
			DBQuery( "DELETE FROM quiz
				WHERE ID='" . (int) $_REQUEST['category_id'] . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			// Unset modfunc & category ID & redirect URL.
			RedirectURL( [ 'modfunc', 'category_id' ] );
		}
	}
}

// Preview Quiz / Category.
if ( $_REQUEST['modfunc'] === 'preview'
	&& intval( $_REQUEST['category_id'] ) > 0 )
{
	require_once 'modules/Quiz/includes/StudentQuizzes.fnc.php';

	$quizzes_link = PreparePHP_SELF( $_REQUEST, [ 'modfunc' ] );

	DrawHeader( '<a href="' . $quizzes_link . '">' . dgettext( 'Quiz', 'Back to Quizzes' ) . '</a>' );

	StudentQuizSubmissionOutput( $_REQUEST['category_id'], 0 );
}


if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	// @todo Admin: select a teacher first === TEACHER PROGRAM!!
	// @todo Course Period!
	// QUIZZES.
	$quizzes_RET = DBGet( "SELECT q.ID,q.TITLE,ga.TITLE AS ASSIGNMENT_TITLE
		FROM quiz q,gradebook_assignments ga
		WHERE q.SCHOOL_ID='" . UserSchool() . "'
		AND ga.ASSIGNMENT_ID=q.ASSIGNMENT_ID
		AND ga.MARKING_PERIOD_ID='" . UserMP() . "' " .
		( User( 'PROFILE' ) === 'teacher' ? "AND ga.STAFF_ID='" . User( 'STAFF_ID' ) . "' " : '' ) .
		"ORDER BY q.CREATED_AT,q.TITLE" );

	// Check Quiz ID is in Quizzes list!
	if ( ! empty( $_REQUEST['category_id'] )
		&& $_REQUEST['category_id'] !== 'new' )
	{
		$quiz_not_found = true;

		foreach ( $quizzes_RET as $quiz )
		{
			if ( $quiz['ID'] === $_REQUEST['category_id'] )
			{
				$quiz_not_found = false;

				break;
			}
		}

		if ( $quiz_not_found )
		{
			// Unset quiz ID & redirect URL.
			RedirectURL( 'category_id' );
		}
	}

	$RET = [];

	$title = '';

	// ADDING & EDITING FORM.
	/*if ( ! empty( $_REQUEST['id'] )
		&& $_REQUEST['id'] !== 'new' )
	{
	}
	else*/if ( $_REQUEST['category_id']
		&& $_REQUEST['category_id'] !== 'new'
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = DBGet( "SELECT ID,ASSIGNMENT_ID,TITLE,
			DESCRIPTION,CREATED_AT,CREATED_BY,OPTIONS/*SHUFFLE,SHOW_CORRECT_ANSWERS,ALLOW_EDIT,FILE*/
			FROM quiz
			WHERE ID='" . (int) $_REQUEST['category_id'] . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );

		$RET = $RET[1];

		$title = $RET['TITLE'];
	}
	/*elseif ( ! empty( $_REQUEST['id'] )
		&& $_REQUEST['id'] === 'new' )
	{
		$title = dgettext( 'Quiz', 'Add Question' );

		$RET['ID'] = 'new';

		$RET['CATEGORY_ID'] = isset( $_REQUEST['category_id'] ) ? $_REQUEST['category_id'] : null;
	}*/
	elseif ( $_REQUEST['category_id'] === 'new' )
	{
		$title = dgettext( 'Quiz',  'New Quiz' );

		$RET['ID'] = 'new';
	}

	echo QuizGetQuizzesForm(
		$title,
		$RET,
		isset( $extra_fields ) ? $extra_fields : []
	);

	echo QuizGetTeacherLinkHeader( $_REQUEST['category_id'] );

	echo QuizGetAssignmentLinkHeader( $_REQUEST['category_id'] );

	echo QuizGetPreviewLinkHeader( $_REQUEST['category_id'] );

	echo QuizGetGradesLinkHeader( $_REQUEST['category_id'] );

	// @since 3.0 Do action hook.
	do_action( 'Quiz/Quizzes.php|header' );

	// DISPLAY THE MENU.
	echo '<div class="st">';

	QuizQuizzesMenuOutput( $quizzes_RET, $_REQUEST['category_id'] );

	echo '</div>';

	// QUESTIONS.
	if ( ! empty( $_REQUEST['category_id'] )
		&& $_REQUEST['category_id'] !== 'new'
		&& $quizzes_RET )
	{
		$questions = QuizGetQuestions(
			$_REQUEST['category_id'],
			[ 'TITLE' => 'QuizTruncateTitle' ]
		);

		echo '<div class="st">';

		QuizQuizzesMenuOutput(
			$questions,
			( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' ),
			( isset( $_REQUEST['category_id'] ) ? $_REQUEST['category_id'] : '' )
		);

		echo '</div>';
	}
}
