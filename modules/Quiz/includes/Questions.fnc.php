<?php
/**
 * Questions functions
 *
 * @package Quiz module
 */

if ( ! function_exists( 'QuizQuestionAllowEdit' ) )
{
	function QuizQuestionAllowEdit( $question_id = 0 )
	{
		global $_ROSARIO;

		static $initial_allow_edit = null;

		if ( is_null( $initial_allow_edit ) )
		{
			$initial_allow_edit = AllowEdit() || User( 'PROFILE' ) === 'teacher';
		}

		if ( ! $question_id
			|| $question_id === 'new' )
		{
			$_ROSARIO['allow_edit'] = $initial_allow_edit;

			return $initial_allow_edit;
		}

		if ( User( 'PROFILE' ) !== 'teacher'
			&& User( 'PROFILE' ) !== 'admin' )
		{
			// Not a Teacher, and (admin) cannot Edit.
			return false;
		}

		// Check if Question has been answered!!
		$question_has_answers = DBGetOne( "SELECT 1
			FROM quiz_answers qa,quiz_quizxquestion qxq
			WHERE qxq.QUESTION_ID='" . (int) $question_id . "'
			AND qa.QUIZXQUESTION_ID=qxq.ID" );

		$_ROSARIO['allow_edit'] = ! $question_has_answers;

		return ! $question_has_answers;
	}
}

/**
 * Get Question or Question Category Form
 *
 * @example echo GetQuestionsForm( $title, $RET );
 *
 * @example echo GetQuestionsForm(
 *              $title,
 *              $RET,
 *              null,
 *              array( 'text' => _( 'Text' ), 'textarea' => _( 'Long Text' ) )
 *          );
 *
 * @uses DrawHeader()
 * @uses MakeQuestionType()
 *
 * @param  string $title                 Form Title.
 * @param  array  $RET                   Question or Question Category Data.
 * @param  array  $extra_category_fields Extra fileds for Question Category.
 * @param  array  $type_options          Associative array of Question Types (optional). Defaults to null.
 *
 * @return string Question or Question Category Form HTML
 */
function QuizGetQuestionsForm( $title, $RET, $extra_category_fields = [], $type_options = null )
{
	global $_ROSARIO;

	$id = issetVal( $RET['ID'] );

	$category_id = issetVal( $RET['CATEGORY_ID'] );

	if ( empty( $id )
		&& empty( $category_id ) )
	{
		return '';
	}

	$new = $id === 'new' || $category_id === 'new';

	$action = 'Modules.php?modname=' . $_REQUEST['modname'];

	if ( $category_id
		&& $category_id !== 'new' )
	{
		$action .= '&category_id=' . $category_id;
	}

	if ( $id
		&& $id !== 'new' )
	{
		$action .= '&id=' . $id;
	}

	if ( $id )
	{
		$full_table = 'quiz_questions';
	}
	else
	{
		$full_table = 'quiz_categories';
	}

	$action .= '&table=' . $full_table;

	$form = '<form action="' . ( function_exists( 'URLEscape' ) ? URLEscape( $action ) : _myURLEncode( $action ) ) . '" method="POST">';

	$allow_edit = QuizQuestionAllowEdit( $id );

	$div = $allow_edit;

	$delete_button = '';

	if ( $allow_edit
		&& ! $new
		&& ( $id || ! QuizCategoryHasQuestions( $category_id ) ) )
	{
		$delete_URL = ( function_exists( 'URLEscape' ) ?
			URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] . '&modfunc=delete&category_id=' . $category_id . '&id=' . $id ) :
			_myURLEncode( "Modules.php?modname=" . $_REQUEST['modname'] . '&modfunc=delete&category_id=' . $category_id . '&id=' . $id ) );

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
		// FJ question name required.
		$header .= '<td>' . TextInput(
			issetVal( $RET['TITLE'] ),
			'tables[' . $id . '][TITLE]',
			dgettext( 'Quiz', 'Question' ),
			'required maxlength=200' .
			( empty( $RET['TITLE'] ) ? ' size=35' : '' ),
			$div
		) . '</td>';

		if ( ! $type_options )
		{
			$type_options = QuizMakeQuestionType( '' );
		}

		if ( ! $new )
		{
			// You can't change a student question type after it has been created.
			$type_options = false;
		}

		// Answer Type question.
		if ( ! $type_options )
		{
			$header .= '<td>' . NoInput(
				QuizMakeQuestionType( $RET['TYPE'] ),
				_( 'Type' )
			) . '</td>';
		}
		else
		{
			$extra = QuizQuestionTypeInputExtra( QuizQuestionGetAnswerInput( $id, 'id' ) );

			$header .= '<td' . ( ! $category_id ? ' colspan="2"' : '' ) . '>' . SelectInput(
				issetVal( $RET['TYPE'] ),
				'tables[' . $id . '][TYPE]',
				_( 'Type' ),
				$type_options,
				false,
				$extra
			) . '</td>';
		}

		$header .= '</tr><tr class="st">';

		// @todo Add TinyMCE Math plugin
		// @link https://stackoverflow.com/questions/20682820/inserting-mathematical-symbols-into-tinymce-4#20686520
		$header .= '<td>' . TinyMCEInput(
			issetVal( $RET['DESCRIPTION'] ),
			'tables[' . $id . '][DESCRIPTION]',
			_( 'Description' )
		) . '</td>';

		// Select Options TextArea question.
		if ( ! empty( $RET['TYPE'] )
			&& in_array( $RET['TYPE'], [ 'select', 'multiple', 'gap', 'text' ] )
			|| ( $new
				&& array_intersect(
					array_keys( $type_options ),
					[ 'select', 'multiple', 'gap', 'text' ] ) ) )
		{
			$type_default = empty( $RET['TYPE'] ) ? key( $type_options ) : $RET['TYPE'];

			$header .= '<td>' . TextAreaInput(
				issetVal( $RET['ANSWER'] ),
				QuizQuestionGetAnswerInput( $id, 'name' ),
				QuizQuestionGetAnswerInput( $type_default, 'title' ),
				QuizQuestionGetAnswerInput( $type_default, 'extra' ),
				true,
				'text'
			) . '</td>';
		}

		$header .= '</tr><tr class="st">';

		// Always Edit Category & Sort Order.
		// $_ROSARIO['allow_edit'] = true;

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

		// Sort Order question.
		$header .= '<td>' . TextInput(
			issetVal( $RET['SORT_ORDER'] ),
			'tables[' . $id . '][SORT_ORDER]',
			_( 'Sort Order' ),
			' type="number" min="-9999" max="9999"'
		) . '</td>';

		// Set back $_ROSARIO['allow_edit'].
		QuizQuestionAllowEdit( $id );

		$header .= '</tr></table>';
	}
	// Questions Category Form.
	else
	{
		$title = isset( $RET['TITLE'] ) ? $RET['TITLE'] : '';

		// Title question.
		$header .= '<td>' . TextInput(
			$title,
			'tables[' . $category_id . '][TITLE]',
			_( 'Title' ),
			'required maxlength=255' . ( empty( $title ) ? ' size=20' : '' )
		) . '</td>';

		// Sort Order question.
		$header .= '<td>' . TextInput(
			( isset( $RET['SORT_ORDER'] ) ? $RET['SORT_ORDER'] : '' ),
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

function QuizGetQuestionAuthorHeader( $RET )
{
	$header = '';

	$id = issetVal( $RET['ID'] );

	if ( ! $id
		|| $id === 'new' )
	{
		return $header;
	}

	$teacher_name = DBGetOne( "SELECT " . DisplayNameSQL() . "
		FROM staff
		WHERE STAFF_ID='" . (int) $RET['CREATED_BY'] . "'" );

	$header = NoInput(
		$teacher_name,
		_( 'Teacher' )
	);

	$header_right = NoInput(
		ProperDate( mb_substr( $RET['CREATED_AT'], 0, 10 ) ),
		_( 'Date' )
	);

	ob_start();

	DrawHeader( $header, $header_right );

	$author_header = ob_get_clean();

	return $author_header;
}


// @todo Display ListOutput where you can Edit Points and add new.
function QuizGetAddQuestionToQuizForm( $RET )
{
	global $_ROSARIO;

	$header = '';

	$id = issetVal( $RET['ID'] );

	if ( ! $id
		|| $id === 'new' )
	{
		return $header;
	}

	$form = '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=Quiz/Quizzes.php&modfunc=save&category_id=' ) :
		_myURLEncode( 'Modules.php?modname=Quiz/Quizzes.php&modfunc=save&category_id=' ) ) . '" method="POST" id="quiz-add-question-form">';

	$header .= dgettext( 'Quiz', 'Quizzes' ) . ' - ' . GetMP( UserMP() );

	// Get CP Quizzes.
	$cp_quizzes = QuizGetCoursePeriodQuizzes();

	$question_quizzes = [];

	if ( $cp_quizzes )
	{
		// Display list of Quizzes where Question has already been added.
		$question_quizzes = QuizGetQuestionQuizzes( $id, $cp_quizzes, UserMP() );
	}

	if ( empty( $question_quizzes )
		&& User( 'PROFILE' ) !== 'teacher' )
	{
		return '';
	}

	$question_quizzes_teacher_mp = [];

	$cp_quiz_options = [];

	foreach ( $cp_quizzes as $cp_quiz_ind => $cp_quiz )
	{
		// Remove Quizzes from list where Question has already been added.
		if ( ! empty( $question_quizzes[ $cp_quiz['ID'] ] ) )
		{
			$question_quizzes_teacher_mp[] = $question_quizzes[ $cp_quiz['ID'] ];

			continue;
		}

		if ( QuizHasAnswers( $cp_quiz['ID'] ) )
		{
			// Remove Quizzes which already has answers from list.
			continue;
		}

		$cp_quiz_options[ $cp_quiz['ID'] ] = $cp_quiz['TITLE'];
	}

	if ( AllowUse( 'Quiz/Quizzes.php' ) )
	{
		$header .= '<ul>';

		$question_quizzes_list = empty( $question_quizzes_teacher_mp ) ?
			$question_quizzes :
			$question_quizzes_teacher_mp;

		foreach ( $question_quizzes_list as $question_quiz )
		{
			$header .= '<li><a href="' . ( function_exists( 'URLEscape' ) ?
				URLEscape( 'Modules.php?modname=Quiz/Quizzes.php&category_id=' . $question_quiz[1]['QUIZ_ID'] ) :
				_myURLEncode( 'Modules.php?modname=Quiz/Quizzes.php&category_id=' . $question_quiz[1]['QUIZ_ID'] ) ) . '">' . $question_quiz[1]['TITLE'] . '</a></li>';
		}

		$header .= '</ul>';
	}

	if ( empty( $cp_quiz_options ) )
	{
		if ( User( 'PROFILE' ) === 'teacher' )
		{
			if ( empty( $cp_quizzes ) || ! $question_quizzes_list )
			{
				$warning = sprintf(
					_( 'No %s were found.' ),
					mb_strtolower( dngettext( 'Quiz', 'Quiz', 'Quizzes', 0 ) )
				);
			}
			else
			{
				$add_quiz_link = ' <a href="Modules.php?modname=Quiz/Quizzes.php&category_id=new"><b>' .
					dgettext( 'Quiz', 'Quizzes' ) . '</b></a>';

				$warning = sprintf(
					dgettext( 'Quiz', 'Quizzes already contain this question. Please add a new Quiz: %s' ),
					$add_quiz_link
				);
			}

			$header .= ErrorMessage( [ $warning ], 'warning' );
		}
	}
	else
	{
		// Fix Teacher cannot add new Quiz.
		$allow_edit_tmp = false;

		if ( ! AllowEdit() && User( 'PROFILE' ) === 'teacher' )
		{
			$allow_edit_tmp = true;

			QuizQuestionAllowEdit();
		}

		$header .= '<table class="width-100p valign-top fixed-col"><tr class="st"><tr class="st">';

		$header .= '<input type="hidden" name="tables[new][QUESTION_ID]" value="' . $id .'" />';

		$header .= '<input type="hidden" name="table" value="quiz_quizxquestion" />';

		$header .= '<td>' . SelectInput(
			'',
			'tables[new][QUIZ_ID]',
			dgettext( 'Quiz', 'Quiz' ),
			$cp_quiz_options,
			'N/A',
			'required'
		) . '</td>';

		$header .= '<td>' . TextInput(
			'',
			'tables[new][POINTS]',
			_( 'Points' ),
			'type="number" min="0" max="999" required size="3"'
		) . '</td>';

		// Sort Order question.
		$header .= '<td>' . TextInput(
			'',
			'tables[new][SORT_ORDER]',
			_( 'Sort Order' ),
			' type="number" min="-9999" max="9999"'
		) . '</td>';

		$header .= '</tr></table>';

		$header .= '<script>
		var quizAddIdToAction = function(e) {
			e.preventDefault();
			var quizSelect = document.getElementById(\'tablesnewQUIZ_ID\');
			var quizSelectValue = quizSelect.value;

			// Check required fields are not empty.
			if (! quizSelectValue) {
				return false;
			}
			this.action += quizSelectValue;
			ajaxPostForm( this, true );
		}
		$("#quiz-add-question-form").submit(quizAddIdToAction);
		</script>';

		$header .= SubmitButton(
			dgettext( 'Quiz', 'Add Question' ),
			'',
			'class="button-primary"'
		);

		if ( $allow_edit_tmp )
		{
			QuizQuestionAllowEdit( $id );
		}
	}

	ob_start();

	DrawHeader( $header );

	$form .= ob_get_clean();

	$form .= '</form>';

	return $form;
}


/**
 * Get Quizzes containing Question
 *
 * @param int   $question_id Question ID.
 * @param array $quizzes     Quizzes array. Optional.
 * @param int   $mp_id       Marking Period ID. Optional.
 *
 * @return array Question Quizzes.
 */
function QuizGetQuestionQuizzes( $question_id, $quizzes = [], $mp_id = 0 )
{
	if ( ! $question_id
		|| $question_id < 0 )
	{
		return [];
	}

	$quizzes_sql = '';

	if ( is_array( $quizzes ) && ! empty( $quizzes ) )
	{
		$quizzes_list = [];

		foreach ( $quizzes as $quiz )
		{
			$quizzes_list[] = $quiz['ID'];
		}

		$quizzes_sql .= " AND qqq.QUIZ_ID IN(" . implode( ',', $quizzes_list ) . ")";
	}

	if ( $mp_id > 0 )
	{
		$quizzes_sql .= " AND ga.ASSIGNMENT_ID=q.ASSIGNMENT_ID
			AND ga.MARKING_PERIOD_ID='" . (int) $mp_id . "'";
	}

	$question_quizzes_RET = DBGet( "SELECT qqq.QUIZ_ID,q.TITLE
		FROM quiz_quizxquestion qqq,quiz q,gradebook_assignments ga
		WHERE qqq.QUESTION_ID='" . (int) $question_id . "'
		AND q.ID=qqq.QUIZ_ID" . $quizzes_sql, [], [ 'QUIZ_ID' ] );

	return $question_quizzes_RET;
}

/**
 * Outputs Questions or Question Categories Menu
 *
 * @example QuestionsMenuOutput( $questions_RET, $_REQUEST['id'], $_REQUEST['category_id'] );
 * @example QuestionsMenuOutput( $categories_RET, $_REQUEST['category_id'] );
 *
 * @uses ListOutput()
 *
 * @param array  $RET         Question Categories (ID, TITLE, SORT_ORDER columns) or Questions (+ TYPE column) RET.
 * @param string $id          Question Category ID or Question ID.
 * @param string $category_id Question Category ID (optional). Defaults to '0'.
 */
function QuizQuestionsMenuOutput( $RET, $id, $category_id = '0' )
{
	if ( $RET
		&& $id
		&& $id !== 'new' )
	{
		foreach ( (array) $RET as $key => $value )
		{
			if ( $value['ID'] == $id )
			{
				$RET[ $key ]['row_color'] = Preferences( 'HIGHLIGHT' );
			}
		}
	}

	$LO_options = [ 'save' => false, 'search' => false, 'responsive' => false ];

	$LO_columns = [
		'TITLE' => ( $category_id || $category_id === false ? dgettext( 'Quiz', 'Question' ) : _( 'Category' ) ),
		'SORT_ORDER' => _( 'Sort Order' ),
	];

	if ( $category_id )
	{
		$LO_columns['TYPE'] = _( 'Type' );
	}

	$LO_link = [];

	$LO_link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'];

	if ( $category_id )
	{
		$LO_link['TITLE']['link'] .= '&category_id=' . $category_id;
	}

	$LO_link['TITLE']['variables'] = [ ( ! $category_id ? 'category_id' : 'id' ) => 'ID' ];

	$LO_link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=';

	$LO_link['add']['link'] .= $category_id ? $category_id . '&id=new' : 'new';

	// Fix Teacher or Admin cannot add new Quiz / not displaying Questions total.
	QuizQuestionAllowEdit();

	if ( ! $category_id )
	{
		ListOutput(
			$RET,
			$LO_columns,
			dgettext( 'Quiz', 'Question Category' ),
			dgettext( 'Quiz', 'Question Categories' ),
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
			dgettext( 'Quiz', 'Question' ),
			dgettext( 'Quiz', 'Questions' ),
			$LO_link,
			[],
			$LO_options
		);
	}

	QuizQuestionAllowEdit( $id );
}


if ( ! function_exists( 'QuizMakeQuestionType' ) )
{
	/**
	 * Make Question Type
	 *
	 * @example QuizMakeQuestionType( 'select' );
	 *
	 * To get type options array, pass an empty value.
	 * @example QuizMakeQuestionType( '' );
	 *
	 * @see Can be called through DBGet()'s functions parameter
	 *
	 * @param  string $value  Question Type value.
	 * @param  string $column 'TYPE' (optional). Defaults to ''.
	 *
	 * @return string Translated Question type
	 */
	function QuizMakeQuestionType( $value, $column = '' )
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


if ( ! function_exists( 'QuizQuestionTypeInputExtra' ) )
{
	function QuizQuestionGetAnswerInput( $type_or_question_id = '', $input_param_name = '' )
	{
		if ( $input_param_name === 'name'
			&& $type_or_question_id )
		{
			return 'tables[' . $type_or_question_id . '][ANSWER]';
		}

		if ( $input_param_name === 'id'
			&& $type_or_question_id )
		{
			return GetInputID( 'tables[' . $type_or_question_id . '][ANSWER]' );
		}

		$type = $type_or_question_id;

		$input_title = function( $title, $tooltip = '' ) {
			if ( ! empty( $tooltip ) )
			{
				$title .= ' <div class="tooltip"><i>' . $tooltip . '</i></div>';
			}

			return $title;
		};

		$type_options_answer_input = [
			'select' => [
				'title' => $input_title(
					_( 'Options' ),
					_( 'One per line' ) . '. ' . dgettext( 'Quiz', 'Mark correct answer with an asterisk *.' )
				),
				'required' => true,
				'placeholder' => dgettext( 'Quiz', "*True\nFalse" ),
			],
			'multiple' => [
				'title' => $input_title(
					_( 'Options' ),
					_( 'One per line' ) . '. ' . dgettext( 'Quiz', 'Mark correct answer with an asterisk *.' )
				),
				'required' => true,
				'placeholder' => dgettext(
					'Quiz',
					"*Correct option #1\nWrong option #2\nWrong option #3\n*Correct option #4"
				),
			],
			'gap' => [
				'title' => $input_title(
					dgettext( 'Quiz', 'Text with gaps' ),
					dgettext( 'Quiz', 'Delimit gaps with double underscores __' )
				),
				'required' => true,
				'placeholder' => dgettext( 'Quiz', "The sky is __blue__.\nThe grass is __green__." ),
			],
			'text' => [
				'title' => $input_title( dgettext( 'Quiz', 'Answer' ) ),
				'required' => false,
				'placeholder' => dgettext( 'Quiz', 'Correct answer (optional).' ),
			],
			'textarea' => [
				'title' => '', // Empty title, will hide input.
				'required' => false,
				'placeholder' => '',
			],
			// 'file' => dgettext( 'Quiz', 'File Upload' ),
		];

		if ( ! $type )
		{
			return $type_options_answer_input;
		}

		if ( empty( $type_options_answer_input[ $type ] ) )
		{
			return [];
		}

		if ( empty( $input_param_name ) )
		{
			return $type_options_answer_input[ $type ];
		}

		if ( $input_param_name === 'extra' )
		{
			return 'rows=5 cols=40 ' .
				( $type_options_answer_input[ $type ]['required'] ? 'required ' : '' ) .
				'placeholder="' .
				htmlspecialchars( $type_options_answer_input[ $type ]['placeholder'], ENT_QUOTES ) . '" ';
		}

		if ( ! isset( $type_options_answer_input[ $type ][ $input_param_name ] ) )
		{
			return '';
		}

		return $type_options_answer_input[ $type ][ $input_param_name ];
	}


	/**
	 * Make Question Type Input Extra parameters
	 *
	 * @example $extra = QuizQuestionTypeInputExtra( $type_options );
	 *
	 * @param  array $answer_input_id Answer input ID.
	 *
	 * @return string Question Type Input Extra parameters
	 */
	function QuizQuestionTypeInputExtra( $answer_input_id )
	{
		if ( empty( $answer_input_id ) )
		{
			return '';
		}

		$onchange_js = 'quizQuestionAnswerInputUpdate(' . json_encode( $answer_input_id ) . ', this.value);';

		$extra = 'autocomplete="off" onchange="' . ( function_exists( 'AttrEscape' ) ?
			AttrEscape( $onchange_js ) : htmlspecialchars( $onchange_js, ENT_QUOTES ) ) . '"';

		$type_options_answer_input = QuizQuestionGetAnswerInput();

		// Print our JS code directly, do not return.
		?>
		<script>
			var quizQuestionTypeOptions = <?php echo json_encode( $type_options_answer_input ); ?>;

			var quizQuestionAnswerInputUpdate = function( answerInputId, typeInputValue ) {
				var answerInput = $( '#' + answerInputId );

				if ( ! answerInput.length ) {
					return false;
				}

				if ( ! quizQuestionTypeOptions.hasOwnProperty( typeInputValue ) ) {
					return false;
				}

				var typeOptions = quizQuestionTypeOptions[ typeInputValue ];

				// Update Answer input.
				answerInput.value = '';
				answerInput.attr( 'required', typeOptions.required );
				answerInput.attr( 'placeholder', typeOptions.placeholder );

				var answerLabel = answerInput.nextAll( 'label' );

				// Update Answer label.
				answerLabel.html( typeOptions.title );

				if ( typeOptions.title === '' ) {
					// If empty title, hide input.
					answerLabel.hide();
					answerInput.hide();
				} else {
					answerLabel.show();
					answerInput.show();
				}

				return true;
			};
		</script>
		<?php

		return $extra;
	}
}

/**
 * Truncate Question Title to 36 chars
 * for responsive display in List.
 * Full title is in tooltip.
 *
 * @see Can be called through DBGet()'s functions parameter
 *
 * @param  string $value  Question Title value.
 * @param  string $column 'TITLE' (optional). Defaults to ''.
 */
function QuizTruncateQuestionTitle( $value, $column = '' )
{
	// Truncate value to 36 chars.
	$title = mb_strlen( $value ) <= 36 ?
		$value :
		'<span title="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $value ) : htmlspecialchars( $value, ENT_QUOTES ) ) . '">' . mb_substr( $value, 0, 33 ) . '...</span>';

	return $title;
}


/**
 * Category has Questions?
 *
 * @param int $category_id Questions Category ID.
 *
 * @return bool True if Category has Questions.
 */
function QuizCategoryHasQuestions( $category_id )
{
	if ( (string) (int) $category_id != $category_id
		|| $category_id < 1 )
	{
		return false;
	}

	$category_has_questions = DBGetOne( "SELECT 1
		FROM quiz_questions
		WHERE CATEGORY_ID='" . (int) $category_id . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	return (bool) $category_has_questions;
}
