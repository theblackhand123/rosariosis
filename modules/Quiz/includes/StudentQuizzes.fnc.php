<?php
/**
 * Student Quizzes functions
 *
 * @package Quiz module
 */

/**
 * Submit Student Quiz
 * Save eventual uploaded file
 * & TinyMCE message.
 *
 * @example $submitted = StudentQuizSubmit( $_REQUEST['quiz_id'], $error );
 *
 * @uses GetQuiz()
 * @uses GetAssignmentsFilesPath()
 * @uses FileUpload()
 * @uses SanitizeHTML()
 *
 * @param string $quiz_id Quiz ID.
 *
 * @return boolean False if error(s), else true.
 */
function StudentQuizSubmit( $quiz_id )
{
	global $error,
		$note;

	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	$quiz = GetQuiz( $quiz_id, UserStudentID() );

	if ( ! $quiz )
	{
		$error[] = _( 'You are not allowed to access this quiz.' );

		echo ErrorMessage( $error, 'fatal' );
	}

	if ( ! QuizCanSubmit( $quiz['DUE_DATE'] )
		|| $quiz['ANSWERED'] ) // Submit Quiz only once.
	{
		$note[] = dgettext( 'Quiz', 'Submissions for this quiz are closed.' );

		return false;
	}

	if ( empty( $_POST['values'] ) )
	{
		// No answers found...
		return false;
	}

	// TODO: check if Student not dropped?

	$student_id = UserStudentID();

	// Get Quiz questions.
	$questions = QuizGetQuestions( $quiz_id );

	$save_answers_sql = '';

	foreach ( $questions as $question )
	{
		$answer_post = null;

		if ( $question['TYPE'] === 'textarea'
			&& isset( $_POST['values'][ $question['ID'] ] ) )
		{
			 $answer_post = $_POST['values'][ $question['ID'] ];
		}
		elseif ( isset( $_REQUEST['values'][ $question['ID'] ] ) )
		{
			$answer_post = $_REQUEST['values'][ $question['ID'] ];
		}

		$save_answers_sql .= StudentQuizSaveAnswerSQL( $quiz, $student_id, $question, $answer_post );
	}

	if ( $save_answers_sql )
	{
		DBQuery( $save_answers_sql );
	}

	// Reset GetQuiz cache.
	$_ROSARIO['quiz'][ $quiz_id ] = false;

	return empty( $error );
}


function StudentQuizSaveAnswerSQL( $quiz, $student_id, $question, $answer_post )
{
	static $old_answers = null;

	$quiz_id = $quiz['ID'];

	if ( is_null( $old_answers ) )
	{
		// Old answers.
		$old_answers = QuizGetAnswers( $quiz_id, $student_id, 'QUIZXQUESTION_ID' );
	}

	$question_id = $question['ID'];

	$old_answer = empty( $old_answers[ $question_id ] ) ? [] : $old_answers[ $question_id ][1];

	$assignments_path = GetAssignmentsFilesPath( $quiz['STAFF_ID'] );

	$answer = '';

	switch ( $question['TYPE'] )
	{
		case 'gap':

			// Parse double underscores inside string and replace with SelectInput.
			$strings = explode( '__', $question['ANSWER'] );

			if ( ! $strings
				|| count( $strings ) === 1 )
			{
				// String is empty or does not contain double underscores.
				break;
			}

			$underscores_count = 0;

			$i = 0;

			foreach ( $strings as $string )
			{
				if ( $underscores_count++%2 )
				{
					// Remove trailing spaces.
					$answer_pf = trim( $answer_post[ $i++ ] );

					// Encode double underscores in answer, just in case...
					$answer_pf = str_replace( '_', '&#95;', $answer_pf );

					$answer .= '__' . $answer_pf . '__';

					continue;
				}

				$answer .= DBEscapeString( $string );
			}

			break;

		case 'select':

			$answer = (string) $answer_post;

			break;

		case 'multiple':

			foreach ( (array) $answer_post as $val )
			{
				if ( $val !== '' )
				{
					$answer .= (string) $val . '||';
				}
			}

			if ( $answer )
			{
				$answer = '||' . $answer;
			}

			break;

		case 'textarea':

			// Sanitize HMTL.
			$answer = DBEscapeString( SanitizeHTML( $answer_post, $assignments_path ) );

			break;

		case 'text':

			// Remove trailing spaces.
			$answer = trim( $answer_post );

			break;
	}

	if ( $old_answer
		&& DBEscapeString( $old_answer['ANSWER'] ) === $answer )
	{
		// Answer has not been edited, skip.
		return '';
	}

	// Check if file submitted.
	/*if ( ! empty( $_FILES['submission_file']['name'] ) )
	{
		$student_name_RET = DBGet( "SELECT " . DisplayNameSQL() . " AS NAME
			FROM students
			WHERE STUDENT_ID='" . UserStudentID() . "'" );

		$student_name = $student_name_RET[1]['NAME'];

		// Filename = [course_title]_quiz_[quiz_ID]_[student_name]_[timestamp].ext.
		$file_name_no_ext = no_accents( $quiz['COURSE_TITLE'] . '_Quiz' . $quiz_id . '_' .
			$student_name ) . '_' . $timestamp;

		// Upload file to AssignmentsFiles/[School_Year]/Teacher[teacher_ID]/Quarter[1,2,3,4...]/.
		$file = FileUpload(
			'submission_file',
			$assignments_path,
			FileExtensionWhiteList(),
			0,
			$error,
			'',
			$file_name_no_ext
		);

		if ( $file )
		{
			$files = array( $file );

			if ( $old_answers )
			{
				$old_data = unserialize( $old_answers['ANSWER'] );

				$old_file = isset( $old_data['files'][0] ) ? $old_data['files'][0] : '';

				if ( file_exists( $old_file ) )
				{
					// Delete old file if any.
					unlink( $old_file );
				}
			}
		}
	}*/

	// Save quiz answer.
	// Update or insert?
	if ( $old_answer )
	{
		// Update.
		return "UPDATE quiz_answers
			SET ANSWER='" . $answer . "',
			MODIFIED_AT=CURRENT_TIMESTAMP
			WHERE STUDENT_ID='" . (int) $student_id . "'
			AND QUIZXQUESTION_ID='" . (int) $question_id . "';";
	}

	// Insert.
	return "INSERT INTO quiz_answers
		(STUDENT_ID,QUIZXQUESTION_ID,ANSWER)
		VALUES ('" . $student_id . "','" . $question_id . "','" . $answer . "');";
}


/**
 * Student Quiz details
 * & Submission form.
 *
 * @example StudentQuizSubmissionOutput( $_REQUEST['quiz_id'] );
 *
 * @uses QuizQuestionOutput()
 *
 * @param string $quiz_id Quiz ID.
 *
 * @return boolean true if can submit, else false.
 */
function StudentQuizSubmissionOutput( $quiz_id, $student_id = 0 )
{
	global $_ROSARIO,
		$error,
		$note;

	require_once 'ProgramFunctions/FileUpload.fnc.php';

	$is_preview = true;

	if ( User( 'PROFILE' ) === 'student'
		|| User( 'PROFILE' ) === 'parent' )
	{
		$student_id = UserStudentID();

		$is_preview = false;
	}

	// Reset Quiz cache so we get accurate ANSWERED value.
	$_ROSARIO['quiz'][ $quiz_id ] = false;

	$quiz = GetQuiz( $quiz_id, $student_id );

	if ( ! $quiz )
	{
		$error[] = _( 'You are not allowed to access this quiz.' );

		echo ErrorMessage( $error, 'fatal' );
	}

	// Past due, in red.
	$due_date = MakeQuizDueDate( $quiz['DUE_DATE'] );

	// Display Quiz details.
	// Due date - Assigned date.
	DrawHeader(
		_( 'Due Date' ) . ': <b>' . $due_date . '</b>',
		_( 'Assigned Date' ) . ': <b>' . ProperDate( $quiz['ASSIGNED_DATE'] ) . '</b>'
	);

	// Course - Teacher.
	DrawHeader(
		_( 'Course Title' ) . ': <b>' . $quiz['COURSE_TITLE'] . '</b>',
		_( 'Teacher' ) . ': <b>' . GetTeacher( $quiz['STAFF_ID'] ) . '</b>'
	);

	$type_color = '';

	if ( $quiz['ASSIGNMENT_TYPE_COLOR'] )
	{
		$type_color = '<span style="background-color: ' .
			$quiz['ASSIGNMENT_TYPE_COLOR'] . ';">&nbsp;</span>&nbsp;';
	}

	if ( $quiz['TITLE'] !== $quiz['ASSIGNMENT_TITLE'] )
	{
		// Title - Type.
		DrawHeader(
			_( 'Title' ) . ': <b>' . $quiz['TITLE'] . '</b>',
			_( 'Assignment' ) . ': <b>' . $type_color . $quiz['ASSIGNMENT_TITLE'] . '</b>'
		);

		// Points.
		DrawHeader( _( 'Points' ) . ': <b>' . $quiz['POINTS'] . '</b>' );
	}
	else
	{
		// Title & Points.
		DrawHeader(
			_( 'Assignment' ) . ': <b>' . $type_color . $quiz['ASSIGNMENT_TITLE'] . '</b>',
			_( 'Points' ) . ': <b>' . $quiz['POINTS'] . '</b>'
		);
	}

	if ( $quiz['DESCRIPTION'] )
	{
		// Description.
		DrawHeader( _( 'Description' ) . ':<br />
			<div class="markdown-to-html">' . $quiz['DESCRIPTION'] . '</div>' );
	}

	if ( $quiz['ASSIGNMENT_FILE'] )
	{
		// @since 4.4 Assignment File.
		DrawHeader( _( 'File' ) . ': ' . GetAssignmentFileLink( $quiz['ASSIGNMENT_FILE'] ) );
	}

	// Get Quiz questions.
	$questions = QuizGetQuestions( $quiz_id );

	// Get quiz answers if any.
	$answers = QuizGetAnswers( $quiz_id, $student_id, 'QUIZXQUESTION_ID' );

	$can_submit = QuizCanSubmit( $quiz['DUE_DATE'] ) && ! $quiz['ANSWERED'];

	if ( ! $is_preview && ! $can_submit )
	{
		$note[] = dgettext( 'Quiz', 'Submissions for this quiz are closed.' );

		echo ErrorMessage( $note, 'note' );
	}

	// Allow Edit if can submit or is preview.
	if ( $can_submit || ! $student_id )
	{
		$_ROSARIO['allow_edit'] = true;
	}

	if ( $is_preview && $student_id )
	{
		$_ROSARIO['allow_edit'] = false;
	}

	$quiz = GetQuiz( $quiz_id );

	$show_correct_answer = QuizGetOption( $quiz, 'SHOW_CORRECT_ANSWERS' );

	$total_points = 0;

	$shuffle = QuizGetOption( $quiz, 'SHUFFLE' );

	if ( $shuffle )
	{
		// Random Question Order.
		shuffle( $questions );
	}

	foreach ( $questions as $question )
	{
		$answer = empty( $answers[ $question['ID'] ] ) ? [] : $answers[ $question['ID'] ][1];

		// Add Show correct answers option to each question for later use.
		$question['SHOW_CORRECT_ANSWERS'] = $show_correct_answer;

		QuizQuestionOutput( $question, $answer );

		if ( ! empty( $answer['POINTS'] ) )
		{
			$total_points += $answer['POINTS'];
		}

		echo '<br />';
	}

	// File upload.
	/*$file_id = 'submission_file';

	$file_html = FileInput( $file_id, _( 'File' ) );

	// Input div onclick only if old file.
	DrawHeader( $old_file ?
			$old_file . $file_html :
			$file_html,
		$old_file ? NoInput( $old_date, _( 'Date' ) ) : ''
	);*/

	if ( ( ! $is_preview || $_REQUEST['modname'] === 'Grades/Grades.php' )
		&& QuizIsGraded( $quiz_id, $student_id ) )
	{
		// Total Points header.
		DrawHeader(
			_( 'Total' ),
			'<b id="quiz' . $quiz_id . '-' . $student_id . '-total-points">' .
			$total_points . '</b> / ' . $quiz['POINTS']
		);
	}

	if ( $can_submit ) // Submit Quiz only once.
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Submit Quiz' ) ) . '</div>';
	}

	if ( $can_submit || ! $student_id )
	{
		$_ROSARIO['allow_edit'] = false;
	}

	if ( $is_preview && $student_id )
	{
		$_ROSARIO['allow_edit'] = true;
	}

	return $can_submit;
}


// @todo Check if Quiz was assigned to student.
function QuizCanSubmit( $due_date )
{
	// Check profile.
	if ( User( 'PROFILE' ) === 'teacher'
		|| User( 'PROFILE' ) === 'admin' )
	{
		return false;
	}

	// Check due date (TODAY <= DUE_DATE) or (!DUE_DATE && TODAY > User MP END_DATE).
	if ( ( $due_date
			&& DBDate() > $due_date )
		|| ( ! $due_date
			&& DBDate() > GetMP( UserMP(), 'END_DATE' ) ) )
	{
		return false;
	}

	return true;
}

/**
 * Quiz Question Output
 *
 * @uses QuizGetQuestionInput
 * @uses QuizQuestionPoints
 * @uses QuizQuestionShowCorrectAnswer
 *
 * @param array $question
 * @param array  $answer
 */
function QuizQuestionOutput( $question, $answer = [] )
{
	echo '<h3>' . $question['TITLE'] . '</h3>';

	echo $question['DESCRIPTION'];

	$input_html = QuizGetQuestionInput( $question, $answer );

	DrawHeader(
		$input_html,
		QuizQuestionPoints( $question, $answer ) . ' ' .
		QuizQuestionShowCorrectAnswer( $question, $answer )
	);
}

/**
 * Quiz Get Question Input HTML
 *
 * @param array $question
 * @param array  $answer
 *
 * @return string Question Input HTML
 */
function QuizGetQuestionInput( $question, $answer = [] )
{
	$value = $old_file = '';

	if ( ! empty( $answer ) )
	{
		$old_file = GetQuizFileLink( $old_file );

		$value = $answer['ANSWER'];

		$old_date = empty( $answer['MODIFIED_AT'] ) ? $answer['CREATED_AT'] : $answer['MODIFIED_AT'];

		$old_date = ProperDateTime( $old_date, 'short' );
	}

	$options = explode(
		"\r",
		str_replace( [ "\r\n", "\n" ], "\r", (string) $question['ANSWER'] )
	);

	$extra = 'required';

	$div = false;

	$name = 'values[' . $question['ID'] . ']';

	switch ( $question['TYPE'] )
	{
		case 'gap':

			$is_student_value = true;

			if ( ! $value )
			{
				$value = $question['ANSWER'];

				$is_student_value = false;
			}

			$input_html = QuizGapInput( $value, $name, '', $extra, $div, $is_student_value );

			break;

		case 'select':

			$options_clean = array_map( function( $option ) {
					// Remove * to mark correct answers.
					return ( mb_substr( $option, 0, 1 ) === '*' ? mb_substr( $option, 1 ) : $option );
				},
				$options
			);

			$input_html = RadioInput( $value, $name, '', $options_clean, false, $extra, $div );

			break;

		case 'multiple':

			// Not required.
			$extra = '';

			$options_clean = array_map( function( $option ) {
					// Remove * to mark correct answers.
					return ( mb_substr( $option, 0, 1 ) === '*' ? mb_substr( $option, 1 ) : $option );
				},
				$options
			);

			// Remove * to mark correct answers.
			$value_clean = str_replace( '||*', '||', (string) $value );

			$input_html = MultipleCheckboxInput( $value_clean, $name . '[]', '', $options_clean, $extra, $div );

			break;

		case 'textarea':

			$input_html = TinyMCEInput( $value, $name, '', $extra );

			break;

		case 'text':

			if ( ! $value )
			{
				$extra .= ' size="36" maxlength="255"';
			}

			$input_html = TextInput( $value, $name, '', $extra, $div );

			break;

		default:

			$input_html = '';
	}

	return $input_html;
}

/**
 * Quiz Question Show Correct Answer
 * Returns empty if not answered yet
 * or not graded yet and question type not automatically graded
 * or check/x button.
 *
 * @uses QuizQuestionIsAnswerCorrect
 *
 * @param array $question
 * @param array $answer
 *
 * @return string Empty or check/x button HTML.
 */
function QuizQuestionShowCorrectAnswer( $question, $answer )
{
	if ( empty( $answer ) )
	{
		// Not answered yet.
		return '';
	}

	if ( User( 'PROFILE' ) === 'student'
		|| User( 'PROFILE' ) === 'parent' )
	{
		if ( ! $question['SHOW_CORRECT_ANSWERS'] )
		{
			// Do not show correct answers (Quiz option).
			return '';
		}
	}

	$auto_correct_types = [ 'select', 'multiple', 'gap', 'text' ];

	if ( is_null( $answer['POINTS'] )
		&& ! in_array( $question['TYPE'], $auto_correct_types ) )
	{
		return '';
	}

	$points = $question['POINTS'];

	$answer_is_correct = QuizQuestionIsAnswerCorrect( $question, $answer );

	$button_icon = ( $answer_is_correct ? 'check' : 'x' );

	return button( $button_icon, '', '', 'bigger' );
}

/**
 * Quiz Question Is answer correct?
 * Answer is considered correct if:
 * - question points is equal to answer points.
 * - question is of text|gap|select|multiple type and answers are equal.
 *
 * @param array $question
 * @param array $answer
 *
 * @return bool True if answer correct, else false.
 */
function QuizQuestionIsAnswerCorrect( $question, $answer )
{
	// Answer points will be null if not graded yet.
	if ( ! is_null( $answer['POINTS'] ) )
	{
		// Answer is correct if question points is equal to answer points.
		return $question['POINTS'] == $answer['POINTS'];
	}

	$answer_value = (string) $answer['ANSWER'];

	$question_answer = (string) $question['ANSWER'];

	$options = explode(
		"\r",
		str_replace( [ "\r\n", "\n" ], "\r", (string) $question_answer )
	);

	switch ( $question['TYPE'] )
	{
		case 'text':

		case 'gap':

			// Compare answers (strip whitespaces), then in lower case.
			$correct = trim( $answer_value ) === trim( $question_answer )
				|| mb_strtolower( trim( $answer_value ) ) === mb_strtolower( trim( $question_answer ) );

			break;

		case 'select':

			// Answer value is select option index.
			// Correct answer begins with an asterisk *.
			$correct = ! empty( $options[ $answer_value ] )
				&& mb_substr( $options[ $answer_value ], 0, 1 ) === '*';

			break;

		case 'multiple':

			// Extract correct answers from question (begin with asterisk *).
			$correct_question_answers = array_filter( $options, function( $option ) {
				return mb_substr( $option, 0, 1 ) === '*';
			});

			// Remove asterisk * from correct answers.
			$correct_question_answers_f = array_map( function( $option ) {
				return trim( $option, '*' );
			}, $correct_question_answers );

			// Explode answers from answer (begin with asterisk *).
			$answer_options = explode( '||', trim( $answer_value, '||' ) );

			// Compare correct answers.
			$correct = count( $correct_question_answers_f ) === count( $answer_options )
				&& ! array_diff( $correct_question_answers_f, $answer_options );

			break;

		case 'textarea':

		default:

			// Those question types cannot be automatically corrected.
			$correct = false;
	}

	return $correct;
}

/**
 * Calculate Answer points for Gap Fill type question
 * Answer has all points if question and answers are equal.
 * Else, each gap will be compared (question and answer):
 * Points = (question points) * (correct gaps / total gaps)
 *
 * @since 10.2
 *
 * @param array $question
 * @param array $answer
 *
 * @return float Points = (question points) * (correct gaps / total gaps)
 */
function QuizQuestionGapAnswerPoints( $question, $answer )
{
	$answer_value = (string) $answer['ANSWER'];

	$question_answer = (string) $question['ANSWER'];

	// Compare answers (strip whitespaces), then in lower case.
	$correct = trim( $answer_value ) === trim( $question_answer )
		|| mb_strtolower( trim( $answer_value ) ) === mb_strtolower( trim( $question_answer ) );

	$points = $question['POINTS'];

	if ( $correct )
	{
		return $points;
	}

	// Parse double underscores inside string and replace with SelectInput.
	$strings = explode( '__', (string) $question_answer );

	$answer_strings = explode( '__', (string) $answer_value );

	$underscores_count = $gaps = $correct = 0;

	foreach ( $strings as $i => $string )
	{
		if ( $underscores_count++%2 )
		{
			$gaps++;

			// Compare answers (strip whitespaces), then in lower case.
			if ( trim( $answer_strings[ $i ] ) === trim( $string )
				|| mb_strtolower( trim( $answer_strings[ $i ] ) ) === mb_strtolower( trim( $string ) ) )
			{
				$correct++;
			}
		}
	}

	if ( ! $gaps )
	{
		return 0;
	}

	return (float) $points * ( $correct / $gaps );
}

/**
 * Quiz Question (and Answer) Points
 * Will generate answer point input for teachers.
 *
 * @param array $question
 * @param array  $answer
 *
 * @return string Question points
 */
function QuizQuestionPoints( $question, $answer = [] )
{
	global $_ROSARIO;

	$points = $question['POINTS'];

	$answer_points = null;

	if ( ! empty( $answer ) )
	{
		// Answer points will be null if not graded yet.
		$answer_points = $answer['POINTS'];
	}

	if ( User( 'PROFILE' ) === 'teacher'
		&& ! empty( $answer ) )
	{
		$value_points = is_null( $answer_points ) ?
			( QuizQuestionIsAnswerCorrect( $question, $answer ) ? $question['POINTS'] : 0 ) :
			$answer_points;

		if ( ! $value_points
			&& $question['TYPE'] === 'gap' )
		{
			// Gap Fill type: If there are more than 1 gap,
			// calculate how many gaps are correct and set points accordingly.
			$value_points = QuizQuestionGapAnswerPoints( $question, $answer );

			// Points are integer, round with 0 precision.
			$value_points = round( $value_points, 0 );
		}

		$_ROSARIO['allow_edit'] = true;

		$input_points = TextInput(
			(string) $value_points,
			'quiz_answer_points[' . $answer['ID'] . ']',
			'',
			'required type="number" min="0" max="' . (int) $question['POINTS'] . '"',
			false
		);

		$_ROSARIO['allow_edit'] = false;

		$points = $input_points . ' / ' . $points;
	}
	elseif ( ! is_null( $answer_points ) )
	{
		$points = $answer_points . ' / ' . $points;
	}

	return $points;
}

/**
 * Quiz Gap Input
 *
 * @uses TextInput()
 *
 * @param string  $value            Input value (gap values delimited by double underscores __).
 * @param string  $name             Input name.
 * @param string  $title            Input title (optional). Defaults to ''.
 * @param string  $extra            Extra HTML attributes added to the input.
 * @param boolean $div              Is input wrapped into <div onclick>? (optional). Defaults to true.
 * @param boolean $is_student_value Is $value a student answer?
 *
 * @return string Input HTML
 */
function QuizGapInput( $value, $name, $title = '', $extra = '', $div = true, $is_student_value = true )
{
	$fvalue = nl2br( $value );

	// Parse double underscores inside string and replace with SelectInput.
	$strings = explode( '__', $fvalue );

	if ( ! $strings
		|| count( $strings ) === 1 )
	{
		// String is empty or does not contain double underscores.
		return $fvalue;
	}

	$underscores_count = 0;

	$i = 0;

	$return = '';

	foreach ( $strings as $string )
	{
		if ( $underscores_count++%2 )
		{
			// Unsecape underscores.
			$string = str_replace( '&#95;', '_', $string );

			// Fix #2 Use fixed size so Students cannot guess the word based on Input / string size.
			$fextra = $extra . ' size="19" maxlength="255"';

			$value = ( $is_student_value ? $string : '' );

			if ( ! AllowEdit()
				&& $is_student_value )
			{
				// Visually delimit answer space.
				$value = '<code>' . $value . '</code>';
			}

			$return .= TextInput(
				$value,
				$name . '[' . $i++ . ']',
				'',
				$fextra,
				$div
			);

			continue;
		}

		$return .= $string;
	}

	return $return;
}


// @todo Preview when $student_id is 0!
function MakeQuizAnswersView( $quiz_id, $student_id = 0 )
{
	if ( ! QuizHasAnswers( $quiz_id, $student_id ) )
	{
		return '';
	}

	ob_start();

	StudentQuizSubmissionOutput( $quiz_id, $student_id );

	$quiz_answers_view = ob_get_clean();

	return $quiz_answers_view;
}


function QuizGetAnswers( $quiz_id, $student_id, $index = '' )
{
	// Check Quiz ID is int > 0 & Student ID.
	if ( $quiz_id < 1
		|| ! $student_id )
	{
		return false;
	}

	$answers_sql = "SELECT qa.ID,qa.ANSWER,qa.POINTS,qa.CREATED_AT,qa.MODIFIED_AT,qa.QUIZXQUESTION_ID
		FROM quiz_answers qa,quiz_quizxquestion qq
		WHERE qq.QUIZ_ID='" . (int) $quiz_id . "'
		AND qq.ID=qa.QUIZXQUESTION_ID
		AND qa.STUDENT_ID='" . (int) $student_id . "'
		ORDER BY qq.SORT_ORDER IS NULL,qq.SORT_ORDER";

	$index = $index ? [ $index ] : [];

	$answers_RET = DBGet( $answers_sql, [], $index );

	return $answers_RET;
}



function StudentQuizzesListOutput()
{
	// TODO: get Quiz Assignment type color!
	$quizzes_sql = "SELECT q.ID,q.STAFF_ID,q.TITLE,ga.ASSIGNED_DATE,ga.DUE_DATE,
		ga.MARKING_PERIOD_ID,
		(SELECT SUM(qq.POINTS) FROM quiz_quizxquestion qq WHERE qq.QUIZ_ID=q.ID) AS POINTS,
		c.TITLE AS COURSE_TITLE,
		(SELECT 1
			FROM quiz_answers qa,quiz_quizxquestion qq2
			WHERE qq2.QUIZ_ID=q.ID
			AND qq2.ID=qa.QUIZXQUESTION_ID
			AND qa.STUDENT_ID=ss.STUDENT_ID
			LIMIT 1) AS ANSWERED
		FROM gradebook_assignments ga,schedule ss,courses c,course_periods cp,quiz q
		WHERE ss.STUDENT_ID='" . UserStudentID() . "'
		AND ss.SYEAR='" . UserSyear() . "'
		AND ss.SCHOOL_ID='" . UserSchool() . "'
		AND q.ASSIGNMENT_ID=ga.ASSIGNMENT_ID
		AND ga.MARKING_PERIOD_ID='" . UserMP() . "'
		AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
		AND (ga.COURSE_PERIOD_ID IS NULL OR ss.COURSE_PERIOD_ID=ga.COURSE_PERIOD_ID)
		AND (ga.COURSE_ID IS NULL OR ss.COURSE_ID=ga.COURSE_ID)
		AND ga.STAFF_ID=cp.TEACHER_ID
		AND cp.COURSE_ID=c.COURSE_ID
		AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
		AND (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
		AND (ga.DUE_DATE IS NULL
			OR (ga.DUE_DATE>=ss.START_DATE
				AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE)))
		AND c.COURSE_ID=ss.COURSE_ID
		AND EXISTS(SELECT 1 FROM quiz_quizxquestion qq3 WHERE qq3.QUIZ_ID=q.ID)
		ORDER BY ga.DUE_DATE";

	$quizzes_RET = DBGet(
		$quizzes_sql,
		[
			'TITLE' => 'MakeQuizTitle',
			'STAFF_ID' => 'GetTeacher',
			'DUE_DATE' => 'MakeQuizDueDate',
			'ASSIGNED_DATE' => 'ProperDate',
			'ANSWERED' => 'MakeQuizAnswered',
		]
	);

	$columns = [
		'TITLE' => _( 'Title' ),
		'DUE_DATE' => _( 'Due Date' ),
		'ASSIGNED_DATE' => _( 'Assigned Date' ),
		'COURSE_TITLE' => _( 'Course Title' ),
		'STAFF_ID' => _( 'Teacher' ),
		'ANSWERED' => dgettext( 'Quiz', 'Answered' ),
	];

	$LO_options = [
		'save' => false,
	];

	ListOutput(
		$quizzes_RET,
		$columns,
		dgettext( 'Quiz', 'Quiz' ),
		dgettext( 'Quiz', 'Quizzes' ),
		[],
		[],
		$LO_options
	);

	return true;
}


function MakeQuizTitle( $value, $column )
{
	global $THIS_RET;

	if ( ! empty( $_REQUEST['LO_save'] ) )
	{
		// Export list.
		return $value;
	}

	// Truncate value to 36 chars.
	$title = QuizTruncateTitle( $value );

	if ( User( 'PROFILE' ) === 'teacher' )
	{
		$view_quiz_link = 'Modules.php?modname=Quiz/Quizzes.php';
	}
	else
	{
		$view_quiz_link = 'Modules.php?modname=Quiz/StudentQuizzes.php';
	}

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$view_quiz_link .= '&quiz_id=' . $THIS_RET['ID'];
	}

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		// @since 3.9 Add MP to outside links (see Portal), so current MP is correct.
		$view_quiz_link .= '&marking_period_id=' . $THIS_RET['MARKING_PERIOD_ID'];
	}

	return '<a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( $view_quiz_link ) :
		_myURLEncode( $view_quiz_link ) ) . '">' . $title . '</a>';
}


function MakeQuizDueDate( $value, $column = 'DUE_DATE' )
{
	$due_date = ProperDate( $value );

	if ( $value
		&& $value <= DBDate() )
	{
		// Past due, in red.
		$due_date = '<span style="color:red;">' . $due_date . '</span>';
	}

	return $due_date;
}


function MakeQuizAnswered( $value, $column )
{
	global $THIS_RET;

	return $value ? button( 'check' ) : button( 'x' );
}


function MakeStudentQuizAnswersView( $value, $column )
{
	global $THIS_RET;

	if ( $value !== 'Y' )
	{
		return '';
	}

	$student_id = UserStudentID() ? UserStudentID() : $THIS_RET['STUDENT_ID'];

	$answers = QuizGetAnswers( $THIS_RET['ID'], $student_id );

	if ( $answers )
	{
		$html = '<a class="colorboxinline" href="#quiz' . $THIS_RET['ID'] . '-' . $student_id . '">' .
		button( 'visualize', _( 'View Online' ), '', 'bigger' ) . '</a>';

		$answers_html = '';

		foreach ( $answers as $answer )
		{
			$data = unserialize( $answer['ANSWER'] );

			$file = isset( $data['files'][0] ) ? $data['files'][0] : '';

			$answers_html .= $data['message'];

			$date_answer = empty( $answer['MODIFIED_AT'] ) ? $answer['CREATED_AT'] : $answer['MODIFIED_AT'];

			if ( empty( $date )
				|| $date_answer > $date )
			{
				// Display only one date per Quiz: the last answer date.
				$date = $date_answer;
			}

			$answers_html .= NoInput( GetQuizFileLink( $file ), _( 'File' ) );
		}

		$html .= '<div class="hide">
			<div id="quiz' . $THIS_RET['ID'] . '-' . $student_id. '">' .
			NoInput( ProperDateTime( $date, 'short' ), _( 'Date' ) ) . '<br />' .
			$answers_html . FormatInputTitle( dgettext( 'Quiz', 'Answers' ), '', false, '' ) .
			'</div></div>';

		return $html;
	}

	return button( 'x' );
}


function GetQuizFileLink( $file_path )
{
	if ( ! file_exists( $file_path ) )
	{
		return '';
	}

	$file_name = basename( $file_path );

	$file_size = HumanFilesize( filesize( $file_path ) );

	return button(
		'download',
		_( 'Download' ),
		'"' . ( function_exists( 'URLEscape' ) ?
			URLEscape( $file_path ) :
			_myURLEncode( $file_path ) ) . '" target="_blank" title="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $file_name . ' (' . $file_size . ')' ) : htmlspecialchars( $file_name . ' (' . $file_size . ')', ENT_QUOTES ) ) . '"',
		'bigger'
	);
}


function QuizGetAssignmentStudentQuizzes( $assignment_id, $student_id )
{
	$quizzes_RET = DBGet( "SELECT q.ID,q.STAFF_ID,q.ASSIGNMENT_ID,
		q.TITLE,q.DESCRIPTION,q.OPTIONS/*q.SHUFFLE,q.SHOW_CORRECT_ANSWERS,q.ALLOW_EDIT,q.FILE*/
		FROM gradebook_assignments ga,schedule ss,courses c,quiz q
		WHERE ss.STUDENT_ID='" . (int) $student_id . "'
		AND ss.SYEAR='" . UserSyear() . "'
		AND ss.SCHOOL_ID='" . UserSchool() . "'
		AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
		AND ga.ASSIGNMENT_ID='" . (int) $assignment_id . "'
		AND ga.ASSIGNMENT_ID=q.ASSIGNMENT_ID
		AND (ga.COURSE_PERIOD_ID IS NULL OR ss.COURSE_PERIOD_ID=ga.COURSE_PERIOD_ID)
		AND (ga.COURSE_ID IS NULL OR ss.COURSE_ID=ga.COURSE_ID)
		AND (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
		AND (ga.DUE_DATE IS NULL
			OR (ga.DUE_DATE>=ss.START_DATE
				AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE)))
		AND c.COURSE_ID=ss.COURSE_ID" );

	return $quizzes_RET;
}


function QuizGradeStudentQuiz( $quiz_id, $student_id, $answer_points )
{
	global $error;

	$quiz_answers = QuizGetAnswers( $quiz_id, $student_id, 'ID' );

	if ( empty( $answer_points )
		|| ! is_array( $answer_points )
		|| ! $quiz_answers )
	{
		$error[] = sprintf(
			_( 'No %s were found.' ),
			mb_strtolower( ngettext( 'Grade', 'Grades', 0 ) )
		);

		return 0;
	}

	$quiz_answers_sql = '';

	$total_points = 0;

	$quiz_answer_ids = array_keys( $quiz_answers );

	foreach ( $answer_points as $answer_id => $points )
	{
		if ( ! in_array( $answer_id, $quiz_answer_ids ) )
		{
			continue;
		}

		$total_points += $points;

		$quiz_answers_sql .= "UPDATE quiz_answers
			SET POINTS='" . $points . "'
			WHERE ID='" . (int) $answer_id . "'
			AND STUDENT_ID='" . (int) $student_id . "';";
	}

	if ( ROSARIO_DEBUG )
	{
		var_dump( $quiz_answers_sql );
	}

	if ( empty( $quiz_answers_sql ) )
	{
		$error[] = sprintf(
			_( 'No %s were found.' ),
			mb_strtolower( ngettext( 'Grade', 'Grades', 0 ) )
		);

		return $total_points;
	}

	DBQuery( $quiz_answers_sql );

	return $total_points;
}


function QuizStudentQuizGradedAJAXUpdate( $quiz_id, $student_id, $total_points )
{
	global $error;

	if ( $error )
	{
		echo ErrorMessage( $error );

		return;
	}

	$assignment_id = DBGetOne( "SELECT ASSIGNMENT_ID
		FROM quiz
		WHERE ID='" . (int) $quiz_id . "'" );

	// Update Total points & Grade POINTS input with Total.
	$points_input_id = '#values' .
		$student_id . $assignment_id . 'POINTS';

	$points_total_id = '#quiz' . $quiz_id . '-' .
		$student_id . '-total-points';
	?>
	<script>
		var pointsInputId = <?php echo json_encode( $points_input_id ); ?>;

		// Update Grade POINTS input with Total.
		if ( $( pointsInputId ).length ) {
			$( pointsInputId ).val( <?php echo json_encode( $total_points ); ?> );
		}

		var pointsTotalId = <?php echo json_encode( $points_total_id ); ?>;

		// Update Total points header.
		if ( $( pointsTotalId ).length ) {
			$( pointsTotalId ).html( <?php echo json_encode( $total_points ); ?> );
		}
	</script>
	<?php

	$note[] = button( 'check' ) . '&nbsp;' .
		dgettext( 'Quiz', 'You already have graded this Quiz.' );

	echo ErrorMessage( $note, 'note' );
}
