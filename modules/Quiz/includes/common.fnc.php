<?php
/**
 * Quiz common functions
 *
 * @package Quiz module
 */


/**
 * Get Quiz details from DB.
 *
 * @example $quiz = GetQuiz( $quiz_id, UserStudentID() );
 *
 * @since 2.8 Fix #1 SQL Course Title in Preview
 *
 * @param string $quiz_id Quiz ID.
 * @param integer $student_id Student ID, defaults to 0.
 *
 * @return boolean|array Quiz details array or false.
 */
function GetQuiz( $quiz_id, $student_id = 0 )
{
	global $_ROSARIO;

	if ( ! isset( $_ROSARIO['quiz'] ) )
	{
		$_ROSARIO['quiz'] = [];
	}

	if ( ! empty( $_ROSARIO['quiz'][ $quiz_id ] ) )
	{
		return $_ROSARIO['quiz'][ $quiz_id ];
	}

	// Check Quiz ID is int > 0.
	if ( ! $quiz_id
		|| (string) (int) $quiz_id !== $quiz_id
		|| $quiz_id < 1 )
	{
		return false;
	}

	$assignment_file_sql = version_compare( ROSARIO_VERSION, '4.4-beta', '<' ) ?
		",NULL AS ASSIGNMENT_FILE" :
		",ga.FILE AS ASSIGNMENT_FILE";

	$quiz_sql = "SELECT q.ID,q.STAFF_ID,
		q.TITLE,q.OPTIONS,ga.ASSIGNED_DATE,ga.DUE_DATE,
		(SELECT SUM(qq.POINTS) FROM quiz_quizxquestion qq WHERE qq.QUIZ_ID=q.ID) AS POINTS,
		(SELECT 1
			FROM quiz_answers qa,quiz_quizxquestion qq2 WHERE
			qq2.QUIZ_ID=q.ID
			AND qq2.ID=qa.QUIZXQUESTION_ID " .
			( $student_id > 0 ? "AND qa.STUDENT_ID=ss.STUDENT_ID " : '' ) .
			" LIMIT 1) AS ANSWERED,
		q.DESCRIPTION,c.TITLE AS COURSE_TITLE,
		q.CREATED_AT,
		q.CREATED_BY,
		gat.COLOR AS ASSIGNMENT_TYPE_COLOR,
		ga.TITLE AS ASSIGNMENT_TITLE" . $assignment_file_sql .
		" FROM gradebook_assignments ga,courses c,gradebook_assignment_types gat,quiz q";

	if ( $student_id > 0 )
	{
		$quiz_sql .= ",schedule ss";
	}

	$quiz_sql .= " WHERE q.ID='" . (int) $quiz_id . "'
		AND q.SCHOOL_ID='" . UserSchool() . "'
		AND ga.ASSIGNMENT_ID=q.ASSIGNMENT_ID
		AND ga.MARKING_PERIOD_ID='" . UserMP() . "'
		AND gat.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
		AND ((ga.COURSE_ID IS NOT NULL AND c.COURSE_ID=ga.COURSE_ID)
			OR c.COURSE_ID=(SELECT COURSE_ID
				FROM course_periods
				WHERE COURSE_PERIOD_ID=ga.COURSE_PERIOD_ID))";

	if ( $student_id > 0 )
	{
		$quiz_sql .= " AND ss.STUDENT_ID='" . (int) $student_id . "'
		AND ss.SYEAR='" . UserSyear() . "'
		AND ss.SCHOOL_ID='" . UserSchool() . "'
		AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
		AND (ga.COURSE_PERIOD_ID IS NULL OR ss.COURSE_PERIOD_ID=ga.COURSE_PERIOD_ID)
		AND (ga.COURSE_ID IS NULL OR ss.COURSE_ID=ga.COURSE_ID)
		AND (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)"; // Why not?
		// @todo Remove Due date checks, and let QuizCanSubmit handle it??
	}

	$quiz_RET = DBGet( $quiz_sql, [], [ 'ID' ] );

	$_ROSARIO['quiz'][ $quiz_id ] = isset( $quiz_RET[ $quiz_id ] ) ?
		$quiz_RET[ $quiz_id ][1] : false;

	return $_ROSARIO['quiz'][ $quiz_id ];
}

/**
 * Truncate Title to 36 chars
 * for responsive display in List.
 * Full title is in tooltip.
 *
 * @see Can be called through DBGet()'s functions parameter
 *
 * @param  string $value  Title value.
 * @param  string $column 'TITLE' (optional). Defaults to ''.
 */
function QuizTruncateTitle( $value, $column = '' )
{
	// Truncate value to 36 chars.
	$title = mb_strlen( $value ) <= 36 ?
		$value :
		'<span title="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $value ) : htmlspecialchars( $value, ENT_QUOTES ) ) . '">' . mb_substr( $value, 0, 33 ) . '...</span>';

	return $title;
}


function QuizGetQuestions( $quiz_id, $functions = [], $index = [] )
{
	$functions = ! empty( $functions ) && is_array( $functions ) ? $functions : [];
	$index = ! empty( $index ) && is_array( $index ) ? $index : [];

	$questions_RET = DBGet( "SELECT qqq.ID,qqq.QUESTION_ID,qqq.QUIZ_ID,qq.TITLE,qqq.POINTS,
		qq.TYPE,qq.DESCRIPTION,qq.ANSWER,qq.FILE,qqq.SORT_ORDER
		FROM quiz_quizxquestion qqq,quiz_questions qq
		WHERE qq.SCHOOL_ID='" . UserSchool() . "'
		AND qqq.QUIZ_ID='" . (int) $quiz_id . "'
		AND qq.ID=qqq.QUESTION_ID
		ORDER BY qqq.SORT_ORDER IS NULL,qqq.SORT_ORDER,qq.TITLE", $functions, $index );

	return $questions_RET;
}


/**
 * Quiz Get Option
 * If no option is specified, get all options.
 *
 * @param array  $quiz
 * @param string $option
 *
 * @return array|string Array of options or option.
 */
function QuizGetOption( $quiz, $option = '' )
{
	static $options_cache = [];

	if ( empty( $quiz['OPTIONS'] ) )
	{
		return ( $option === '' ? [] : '' );
	}
	elseif ( isset( $options_cache[ $quiz['ID'] ] ) )
	{
		$options = $options_cache[ $quiz['ID'] ];
	}
	else
	{
		$options = unserialize( $quiz['OPTIONS'] );

		$options_cache[ $quiz['ID'] ] = $options;
	}

	if ( $option === '' )
	{
		return $options;
	}

	return ( ! isset( $options[ $option ] ) ? '' : $options[ $option ] );
}



function QuizHasAnswers( $quiz_id, $student_id = 0 )
{
	if ( $quiz_id < 1 ||
		$student_id < 0 )
	{
		return false;
	}

	$quiz_answered = DBGetOne( "SELECT 1
		FROM quiz_answers qa,quiz_quizxquestion qq
		WHERE qq.QUIZ_ID='" . (int) $quiz_id . "'
		AND qq.ID=qa.QUIZXQUESTION_ID " .
		( $student_id > 1 ? "AND qa.STUDENT_ID='" . (int) $student_id . "'" : '' ) .
		" LIMIT 1" );

	return (bool) $quiz_answered;
}



function QuizIsGraded( $quiz_id, $student_id = 0 )
{
	if ( $quiz_id < 1 ||
		$student_id < 0 )
	{
		return false;
	}

	$quiz_graded = DBGetOne( "SELECT 1
		FROM quiz_answers qa,quiz_quizxquestion qq
		WHERE qq.QUIZ_ID='" . (int) $quiz_id . "'
		AND qq.ID=qa.QUIZXQUESTION_ID " .
		( $student_id > 0 ? "AND qa.STUDENT_ID='" . (int) $student_id . "'" : '' ) .
		" AND qa.POINTS IS NOT NULL
		LIMIT 1" );

	return (bool) $quiz_graded;
}

