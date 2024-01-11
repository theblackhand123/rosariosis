<?php
/**
 * Quizzes functions
 *
 * @package Quiz module
 */


function QuizAllowEdit( $quiz_id = 0 )
{
	global $_ROSARIO;

	if ( ! AllowEdit()
		&& User( 'PROFILE' ) !== 'teacher' )
	{
		// Not a Teacher, and (admin) cannot Edit.
		return false;
	}

	if ( User( 'PROFILE' ) === 'admin' )
	{
		if ( $_REQUEST['modfunc'] === 'grade' )
		{
			// Allow Edit for Admins as Teacher Program.
			return true;
		}

		$_ROSARIO['allow_edit'] = false;

		return false;
	}

	if ( ! $quiz_id
		|| $quiz_id === 'new' )
	{
		$_ROSARIO['allow_edit'] = true;

		return true;
	}

	// Check if Quiz has been answered!!
	$quiz_has_answers = QuizHasAnswers( $quiz_id );

	$_ROSARIO['allow_edit'] = ! $quiz_has_answers;

	if ( $_REQUEST['modfunc'] === 'grade' )
	{
		$_ROSARIO['allow_edit'] = $quiz_has_answers;
	}

	return $_ROSARIO['allow_edit'];
}

function QuizGetAssignmentLinkHeader( $quiz_id )
{
	if ( ! $quiz_id
		|| $quiz_id === 'new'
		|| User( 'PROFILE' ) !== 'teacher' )
	{
		return '';
	}

	$assignment = QuizGetQuizAssignment( $quiz_id );

	if ( ! AllowUse( 'Grades/Assignments.php' )
		|| empty( $assignment ) )
	{
		return '';
	}

	DrawHeader( '<a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=Grades/Assignments.php&assignment_id=' . $assignment['ASSIGNMENT_ID'] ) :
		_myURLEncode( 'Modules.php?modname=Grades/Assignments.php&assignment_id=' . $assignment['ASSIGNMENT_ID'] ) ) . '"><b>' .
		_( 'Assignment' ) . '</b></a>' );
}

function QuizGetPreviewLinkHeader( $quiz_id )
{
	if ( ! $quiz_id
		|| $quiz_id === 'new'
		|| ! QuizGetQuestions( $quiz_id ) )
	{
		return '';
	}

	DrawHeader( '<a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=Quiz/Quizzes.php&category_id=' . $quiz_id . '&modfunc=preview' ) :
		_myURLEncode( 'Modules.php?modname=Quiz/Quizzes.php&category_id=' . $quiz_id . '&modfunc=preview' ) ) . '"><b>' .
		_( 'Preview' ) . '</b></a>' );
}

function QuizGetGradesLinkHeader( $quiz_id )
{
	if ( ! $quiz_id
		|| $quiz_id === 'new'
		|| User( 'PROFILE' ) !== 'teacher'
		|| QuizAllowEdit( $quiz_id ) )
	{
		return '';
	}

	$assignment = QuizGetQuizAssignment( $quiz_id );

	if ( ! AllowUse( 'Grades/Grades.php' )
		|| empty( $assignment ) )
	{
		return '';
	}

	DrawHeader( '<a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=Grades/Grades.php&type_id=' .
			$assignment['ASSIGNMENT_TYPE_ID'] . '&assignment_id=' . $assignment['ASSIGNMENT_ID'] ) :
		_myURLEncode( 'Modules.php?modname=Grades/Grades.php&type_id=' .
			$assignment['ASSIGNMENT_TYPE_ID'] . '&assignment_id=' . $assignment['ASSIGNMENT_ID'] ) ) . '"><b>' .
		_( 'Grades' ) . '</b></a>' );
}


function QuizGetTeacherLinkHeader( $quiz_id )
{
	if ( ! $quiz_id
		|| $quiz_id === 'new'
		|| User( 'PROFILE' ) === 'parent' )
	{
		return '';
	}

	if ( ! AllowUse( 'Users/User.php&category_id=1' ) )
	{
		return '';
	}

	$quiz = GetQuiz( $quiz_id );

	// Is Quiz owner?
	$is_quiz_owner = ( User( 'STAFF_ID' ) === $quiz['CREATED_BY'] );

	if ( $is_quiz_owner )
	{
		return '';
	}

	DrawHeader( _( 'Teacher' ) .
		': <a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=Users/User.php&staff_id=' . $quiz['CREATED_BY'] ) :
			_myURLEncode( 'Modules.php?modname=Users/User.php&staff_id=' . $quiz['CREATED_BY'] ) ) .
		'">' . GetTeacher( $quiz['CREATED_BY'] ) . '</a>',
		_( 'Course' ) . ': ' . $quiz['COURSE_TITLE'] );
}

/**
 * Get Quiz or Quiz Category Form
 *
 * @example echo GetQuizzesForm( $title, $RET );
 *
 * @example echo GetQuizzesForm(
 *              $title,
 *              $RET,
 *              null,
 *              array( 'text' => _( 'Text' ), 'textarea' => _( 'Long Text' ) )
 *          );
 *
 * @uses DrawHeader()
 * @uses MakeQuizType()
 *
 * @param  string $title                 Form Title.
 * @param  array  $RET                   Quiz or Quiz Category Data.
 * @param  array  $extra_category_fields Extra fileds for Quiz Category.
 * @param  array  $type_options          Associative array of Quiz Types (optional). Defaults to null.
 *
 * @return string Quiz or Quiz Category Form HTML
 */
function QuizGetQuizzesForm( $title, $RET, $extra_category_fields = [], $type_options = null )
{
	$id = issetVal( $RET['ID'] );

	$quiz_options = QuizGetOption( $RET );

	$RET = array_merge( $RET, $quiz_options );

	$category_id = issetVal( $RET['CATEGORY_ID'] );

	if ( empty( $id )
		&& empty( $category_id ) )
	{
		return '';
	}

	$new = $id === 'new' || $category_id === 'new';

	$action = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save';

	if ( $category_id
		&& $category_id !== 'new' )
	{
		$action .= '&category_id=' . $category_id;
	}

	if ( $id
		&& $id !== 'new' )
	{
		$action .= '&category_id=' . $id;
	}

	if ( $id )
	{
		$full_table = 'quiz';
	}
	else
	{
		$full_table = 'quiz_categories';
	}

	$action .= '&table=' . $full_table;

	$form = '<form action="' . ( function_exists( 'URLEscape' ) ? URLEscape( $action ) : _myURLEncode( $action ) ) . '" method="POST">';

	$allow_edit = QuizAllowEdit( $id );

	$div = $allow_edit;

	$delete_button = '';

	if ( $allow_edit
		&& ! $new
		&& $id )
	{
		// @todo Delete category only if has no Quizzes!
		$delete_URL = ( function_exists( 'URLEscape' ) ?
			URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] . '&modfunc=delete&category_id=' . $id ) :
			_myURLEncode( "Modules.php?modname=" . $_REQUEST['modname'] . '&modfunc=delete&category_id=' . $id ) );

		$onclick_link = 'ajaxLink(' . json_encode( $delete_URL ) . ');';

		$delete_button = '<input type="button" value="' .
		( function_exists( 'AttrEscape' ) ? AttrEscape( _( 'Delete' ) ) : htmlspecialchars( _( 'Delete' ), ENT_QUOTES ) ) .
		'" onclick="' .
		( function_exists( 'AttrEscape' ) ? AttrEscape( $onclick_link ) : htmlspecialchars( $onclick_link, ENT_QUOTES ) ) .
		'" /> ';
	}

	ob_start();

	DrawHeader( $title, $delete_button . SubmitButton() );

	$form .= ob_get_clean();

	$header = '<table class="width-100p valign-top fixed-col cellpadding-5"><tr class="st">';

	if ( $id )
	{
		// Is Quiz owner?
		$is_quiz_owner = $id === 'new'
			|| ( User( 'STAFF_ID' ) === $RET['CREATED_BY'] );

		$quiz_title = isset( $RET['TITLE'] ) ? $RET['TITLE'] : '';

		$quiz_description = isset( $RET['DESCRIPTION'] ) ? $RET['DESCRIPTION'] : '';

		// Get Course Period Assignments. All but he ones associated with other Quizzes.
		$cp_assignments = QuizGetCoursePeriodAssignments(
			UserCoursePeriod(),
			UserMP(),
			User( 'STAFF_ID' ),
			$RET['ID']
		);

		$cp_assignments_options = [];

		foreach ( $cp_assignments as $cp_assignment )
		{
			if ( $new
				&& ! empty( $_REQUEST['assignment_id'] )
				&& $cp_assignment['ASSIGNMENT_ID'] === $_REQUEST['assignment_id'] )
			{
				// Coming from the Assignments program, put Assignment first in the list.
				$cp_assignments_options = [ $cp_assignment['ASSIGNMENT_ID'] => $cp_assignment['TITLE'] ] +
					$cp_assignments_options;

				// Prefill Quiz title & description with Assignment values.
				$quiz_title = $cp_assignment['TITLE'];

				$quiz_description = $cp_assignment['DESCRIPTION'];

				continue;
			}

			$cp_assignments_options[ $cp_assignment['ASSIGNMENT_ID'] ] = $cp_assignment['TITLE'];
		}

		if ( $is_quiz_owner && ! $cp_assignments_options )
		{
			$assignments_program = AllowEdit( 'Grades/Assignments.php' ) ?
				'<a href="Modules.php?modname=Grades/Assignments.php"><b>' .
				_( 'Assignments' ) . '</b></a>' :
				_( 'Assignments' );

			// No Assignments found for CP and MP.
			$no_assignments = sprintf(
				dgettext( 'Quiz', 'No Assignments are available. Please add a new Assignment: %s' ),
				$assignments_program
			);

			$header .= '<td>' . ErrorMessage( [ $no_assignments ], 'warning' ) . '</td>';
		}
		else
		{
			// FJ question name required.
			$header .= '<td>' . TextInput(
				$quiz_title,
				'tables[' . $id . '][TITLE]',
				dgettext( 'Quiz', 'Quiz' ),
				'required maxlength=1000' .
				( empty( $quiz_title ) ? ' size=35' : '' ),
				! $new
			) . '</td>';

			if ( ! $is_quiz_owner )
			{
				$quiz = QuizGetQuizAssignment( $id );

				$header .= '<td>' . NoInput(
					$quiz['TITLE'],
					_( 'Assignment' )
				) . '</td>';
			}
			else
			{
				$header .= '<td>' . SelectInput(
					( isset( $RET['ASSIGNMENT_ID'] ) ? $RET['ASSIGNMENT_ID'] : '' ),
					'tables[' . $id . '][ASSIGNMENT_ID]',
					_( 'Assignment' ),
					$cp_assignments_options,
					false
				) . '</td>';
			}


			$header .= '</tr><tr class="st">';

			$header .= '<td colspan="2">' . TinyMCEInput(
				// @deprecated since 4.4 Quiz Description is now HTML.
				MarkDownToHTML( $quiz_description ),
				'tables[' . $id . '][DESCRIPTION]',
				_( 'Description' )
			) . '</td>';

			$header .= '</tr><tr class="st">';

			// Always Edit Category & Sort Order.
			$_ROSARIO['allow_edit'] = true;

			if ( $category_id )
			{
				// CATEGORIES.
				$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
					FROM quiz_categories
					ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

				foreach ( (array) $categories_RET as $category )
				{
					$categories_options[ $category['ID'] ] = $category['TITLE'];
				}

				$header .= '<td>' . SelectInput(
					$RET['CATEGORY_ID'] ? $RET['CATEGORY_ID'] : $category_id,
					'tables[' . $id . '][CATEGORY_ID]',
					_( 'Category' ),
					$categories_options,
					false
				) . '</td>';
			}

			// Show correct answers.
			$header .= '<td>' . CheckboxInput(
				( isset( $RET['SHOW_CORRECT_ANSWERS'] ) ? $RET['SHOW_CORRECT_ANSWERS'] : 'Y' ),
				'tables[' . $id . '][SHOW_CORRECT_ANSWERS]',
				dgettext( 'Quiz', 'Show Correct Answers' ),
				'',
				( $id === 'new' )
			) . '</td>';

			// Random Question Order.
			$header .= '<td>' . CheckboxInput(
				( isset( $RET['SHUFFLE'] ) ? $RET['SHUFFLE'] : '' ),
				'tables[' . $id . '][SHUFFLE]',
				dgettext( 'Quiz', 'Random Question Order' ),
				'',
				( $id === 'new' )
			) . '</td>';
		}

		// Set back $_ROSARIO['allow_edit'].
		QuizAllowEdit( $id );

		$header .= '</tr></table>';
	}
	// Quizzes Category Form.
	else
	{
		// Title question.
		$header .= '<td>' . TextInput(
			$RET['TITLE'],
			'tables[' . $category_id . '][TITLE]',
			_( 'Title' ),
			'required maxlength=255'
		) . '</td>';

		// Sort Order question.
		$header .= '<td>' . TextInput(
			$RET['SORT_ORDER'],
			'tables[' . $category_id . '][SORT_ORDER]',
			_( 'Sort Order' ),
			' type="number" min="-9999" max="9999"'
		) . '</td>';

		// Extra Fields.
		if ( ! empty( $extra_category_fields ) )
		{
			$i = 2;

			foreach ( (array) $extra_category_fields as $extra_field )
			{
				if ( $i % 3 === 0 )
				{
					$header .= '</tr><tr class="st">';
				}

				$colspan = 1;

				if ( $i === ( count( $extra_category_fields ) + 1 ) )
				{
					$colspan = abs( ( $i % 3 ) - 3 );
				}

				$header .= '<td colspan="' . $colspan . '">' . $extra_field . '</td>';

				$i++;
			}
		}

		$header .= '</tr></table>';
	}

	ob_start();

	DrawHeader( $header );

	$form .= ob_get_clean();

	$form .= '</form>';

	return $form;
}


/**
 * Outputs Quizzes or Quiz Categories Menu
 *
 * @example QuizzesMenuOutput( $questions_RET, $_REQUEST['id'], $_REQUEST['category_id'] );
 * @example QuizzesMenuOutput( $categories_RET, $_REQUEST['category_id'] );
 *
 * @uses ListOutput()
 *
 * @param array  $RET         Quiz Categories (ID, TITLE, SORT_ORDER columns) or Quizzes (+ TYPE column) RET.
 * @param string $id          Quiz Category ID or Quiz ID.
 * @param string $category_id Quiz ID (optional). Defaults to '0'.
 */
function QuizQuizzesMenuOutput( $RET, $id, $category_id = '0' )
{
	$points_total = 0;

	if ( $RET )
	{
		foreach ( (array) $RET as $key => $value )
		{
			if ( $value['ID'] == $id )
			{
				$RET[ $key ]['row_color'] = Preferences( 'HIGHLIGHT' );
			}

			if ( $category_id )
			{
				// Sum Question points.
				$points_total += $value['POINTS'];
			}
		}
	}

	$LO_options = [ 'save' => false, 'search' => false, 'responsive' => false ];

	$LO_columns = [
		'TITLE' => _( 'Title' ),
	];

	if ( $category_id )
	{
		$LO_columns['SORT_ORDER'] = _( 'Sort Order' );

		$LO_columns['POINTS'] = _( 'Points' );

		// @todo Premium add a GPA column based on Students answers.
	}
	else
	{
		$LO_columns['ASSIGNMENT_TITLE'] = _( 'Assignment' );
	}

	$LO_link = [];

	if ( ! $category_id )
	{
		$LO_link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'];

		$LO_link['TITLE']['variables'] = [ 'category_id' => 'ID' ];

		$LO_link['add']['link'] = 'Modules.php?modname=Quiz/Quizzes.php&category_id=new';
	}
	else
	{
		$LO_link['TITLE']['link'] = 'Modules.php?modname=Quiz/Questions.php';

		$LO_link['TITLE']['variables'] = [ 'id' => 'QUESTION_ID' ];

		if ( QuizAllowEdit( $category_id ) )
		{
			$LO_link['remove']['link'] = 'Modules.php?modname=Quiz/Quizzes.php&modfunc=delete';

			$LO_link['remove']['variables'] = [
				'category_id' => 'QUIZ_ID',
				'id' => 'ID',
			];
		}

		if ( $RET )
		{
			// Compare total to Assignment points.
			$quiz_assignment = QuizGetQuizAssignment( $category_id );

			if ( $quiz_assignment
				&& $quiz_assignment['POINTS'] != $points_total )
			{
				// Total points ≠ Assignment Points.
				$points_total = '<span style="color: red;"><b>' . $points_total . '</b></span>' .
					' &#8800; ' . $quiz_assignment['POINTS'];
			}
			else
			{
				$points_total = '<b>' . $points_total . '</b>';
			}

			// Add Total.
			$LO_link['add']['html'] = [
				'TITLE' => _( 'Total' ),
				'POINTS' => $points_total,
			];

			if ( QuizAllowEdit( $category_id ) )
			{
				$LO_link['add']['html']['remove'] = button(
					'add',
					'',
					// @deprecated since RosarioSIS 11.1 use of double quotes around URL (if no other attributes).
					'"' . URLEscape( 'Modules.php?modname=Quiz/Questions.php' ) . '"'
				);
			}
		}
		else
		{
			$LO_link['add']['link'] = 'Modules.php?modname=Quiz/Questions.php';
		}
	}

	// Fix Teacher cannot add new Quiz / not displaying Questions total.
	$tmp_allow_edit = false;

	if ( ! AllowEdit() && User( 'PROFILE' ) === 'teacher' )
	{
		QuizAllowEdit();

		$tmp_allow_edit = true;
	}

	if ( $category_id )
	{
		ListOutput(
			$RET,
			$LO_columns,
			dgettext( 'Quiz', 'Question' ),
			dgettext( 'Quiz', 'Questions' ),
			$LO_link,
			[],
			$LO_options
		);
	}
	else
	{
		ListOutput(
			$RET,
			$LO_columns,
			dgettext( 'Quiz', 'Quiz' ),
			dgettext( 'Quiz', 'Quizzes' ),
			$LO_link,
			[],
			$LO_options
		);
	}

	if ( $tmp_allow_edit )
	{
		QuizAllowEdit( $id );
	}
}


if ( ! function_exists( 'QuizMakeQuizType' ) )
{
	/**
	 * Make Quiz Type
	 *
	 * @example QuizMakeQuizType( 'select' );
	 *
	 * To get type options array, pass an empty value.
	 * @example QuizMakeQuizType( '' );
	 *
	 * @see Can be called through DBGet()'s functions parameter
	 *
	 * @param  string $value  Quiz Type value.
	 * @param  string $column 'TYPE' (optional). Defaults to ''.
	 *
	 * @return string Translated Quiz type
	 */
	function QuizMakeQuizType( $value, $column = '' )
	{
		$type_options = [
			'select' => dgettext( 'Quiz', 'Select One from Options' ),
			'multiple' => _( 'Select Multiple from Options' ),
			'gap' => dgettext( 'Quiz', 'Gap Fill' ),
			'text' => _( 'Text' ),
			'textarea' => _( 'Long Text' ),
			// 'file' => dgettext( 'Quiz', 'File Upload' ),
		];

		if ( ! $value )
		{
			return $type_options;
		}

		return isset( $type_options[ $value ] ) ? $type_options[ $value ] : $value;
	}
}

function QuizGetQuizAssignment( $quiz_id )
{
	static $assignment_cache = [];

	if ( empty( $quiz_id ) )
	{
		return [];
	}

	if ( ! empty( $assignment_cache[ $quiz_id ] ) )
	{
		return $assignment_cache[ $quiz_id ];
	}

	// Get Quiz Assignment.
	$assignment_RET = DBGet( "SELECT ga.ASSIGNMENT_ID,ga.TITLE,ga.DESCRIPTION,ga.POINTS,
		ga.ASSIGNED_DATE,ga.DUE_DATE,ga.ASSIGNMENT_TYPE_ID
		FROM gradebook_assignments ga,quiz q
		WHERE q.ASSIGNMENT_ID=ga.ASSIGNMENT_ID
		AND q.ID='" . (int) $quiz_id . "'" );

	$assignment_cache[ $quiz_id ] = $assignment_RET[1];

	return $assignment_RET[1];
}



function QuizGetCoursePeriodAssignments( $cp_id, $mp_id, $teacher_id, $no_quiz = false )
{
	static $assignments_cache = [];

	$assignments_key = $cp_id . $mp_id . $teacher_id . $no_quiz;

	if ( ! empty( $assignments_cache[ $assignments_key ] ) )
	{
		return $assignments_cache[ $assignments_key ];
	}

	$course_id = DBGet( "SELECT COURSE_ID
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

	if ( ! $course_id )
	{
		// Course Period not found!
		return [];
	}

	$course_id = $course_id[1]['COURSE_ID'];

	$mp_sql = $mp_id ? " AND ga.MARKING_PERIOD_ID='" . (int) $mp_id . "'" : '';

	$teacher_sql = $teacher_id ? " AND ga.STAFF_ID='" . (int) $teacher_id . "'" : '';

	$no_quiz_sql = $no_quiz ? " AND NOT EXISTS(SELECT 1
		FROM quiz q
		WHERE q.ASSIGNMENT_ID=ga.ASSIGNMENT_ID" .
		( is_numeric( $no_quiz ) ? " AND q.ID<>'" . $no_quiz ."')" : ')' ) : '';

	// Get Assignments for Course Period.
	$assignments_RET = DBGet( "SELECT ga.ASSIGNMENT_ID,ga.TITLE,ga.DESCRIPTION,ga.POINTS
		FROM gradebook_assignments ga
		WHERE (ga.COURSE_PERIOD_ID='" . (int) $cp_id . "'
		OR ga.COURSE_ID='" . (int) $course_id . "')" . $mp_sql . $teacher_sql . $no_quiz_sql .
		" ORDER BY ga.ASSIGNED_DATE,ga.TITLE" );

	$assignments_cache[ $assignments_key ] = $assignments_RET;

	return $assignments_RET;
}

/**
 * Get Quizzes for Course Period and Marking Period
 * Defaults to current course period and current teacher.
 * If admin, you will get quizzes for all teachers if none is specified.
 *
 * @param integer $cp_id      Course Period ID. Optional. Defaults to UserCoursePeriod().
 * @param integer $mp_id      Marking Period ID. Optional. Defaults to UserMP().
 * @param integer $teacher_id Teacher ID.
 */
function QuizGetCoursePeriodQuizzes( $cp_id = 0, $mp_id = 0, $teacher_id = 0 )
{
	if ( ! $cp_id )
	{
		if ( ! UserCoursePeriod() )
		{
			return [];
		}

		$cp_id = UserCoursePeriod();
	}

	if ( ! $mp_id )
	{
		if ( ! UserMP() )
		{
			return [];
		}

		$mp_id = UserMP();
	}
	elseif ( GetMP( $mp_id, 'MP' ) !== 'QTR' )
	{
		return [];
	}

	if ( ! $teacher_id )
	{
		if ( User( 'PROFILE' ) === 'teacher' )
		{
			$staff_id = User( 'STAFF_ID' );
		}
	}
	elseif ( ! GetTeacher( $teacher_id ) )
	{
		return [];
	}

	$assignments = QuizGetCoursePeriodAssignments( $cp_id, $mp_id, $teacher_id );

	if ( empty( $assignments ) )
	{
		return [];
	}

	$assignments_list = [];

	foreach ( $assignments as $assignment )
	{
		$assignments_list[] = $assignment['ASSIGNMENT_ID'];
	}

	$assignments_list = implode( ',', $assignments_list );

	$teacher_sql = $teacher_id ? " AND STAFF_ID='" . (int) $teacher_id . "'" : '';

	$quizzes_RET = DBGet( "SELECT ID,STAFF_ID,ASSIGNMENT_ID,
		TITLE,DESCRIPTION,OPTIONS/*SHUFFLE,SHOW_CORRECT_ANSWERS,ALLOW_EDIT,FILE*/
		FROM quiz
		WHERE ASSIGNMENT_ID IN(" . $assignments_list . ")
		AND SCHOOL_ID='" . UserSchool() . "'" . $teacher_sql );

	return $quizzes_RET;
}

// New Quiz from Assignment: auto fill TITLE, DESCRIPTION!
function QuizGetAssignmentQuizzes( $assignment_id )
{
	$quizzes_RET = DBGet( "SELECT ID,STAFF_ID,ASSIGNMENT_ID,
		TITLE,DESCRIPTION,OPTIONS/*SHUFFLE,SHOW_CORRECT_ANSWERS,ALLOW_EDIT,FILE*/
		FROM quiz
		WHERE ASSIGNMENT_ID='" . (int) $assignment_id . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	return $quizzes_RET;
}


function QuizSaveOptionsColumns( $columns, $id )
{
	if ( $id === 'new' )
	{
		$options = [];
	}
	else
	{
		$options = QuizGetOption( $id );
	}

	/*SHUFFLE,SHOW_CORRECT_ANSWERS,ALLOW_EDIT,FILE*/
	$option_columns = [ 'SHUFFLE', 'SHOW_CORRECT_ANSWERS', 'ALLOW_EDIT', 'FILE' ];

	foreach ( (array) $option_columns as $field )
	{
		if ( isset( $columns[ $field ] ) )
		{
			$options[ $field ] = $columns[ $field ];

			// Unset option's column, so it does not get processed.
			unset( $columns[ $field ] );
		}
		else
		{
			$options[ $field ] = '';
		}
	}

	// Add OPTIONS column and serialize array.
	$columns['OPTIONS'] = serialize( $options );

	return $columns;
}
