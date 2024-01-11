<?php
/**
 * Questions
 *
 * @package RosarioSIS
 * @subpackage modules
 */

require_once 'modules/Quiz/includes/common.fnc.php';
require_once 'modules/Quiz/includes/Questions.fnc.php';
require_once 'modules/Quiz/includes/Quizzes.fnc.php';

DrawHeader( ProgramTitle() );

$_REQUEST['category_id'] = issetVal( $_REQUEST['category_id'] );

$_REQUEST['id'] = issetVal( $_REQUEST['id'] );

QuizQuestionAllowEdit( $_REQUEST['id'] );

// @since 3.0 Do action hook.
do_action( 'Quiz/Questions.php|modfunc' );

if ( AllowEdit()
	&& isset( $_POST['tables'] )
	&& is_array( $_POST['tables'] ) )
{
	$table = issetVal( $_REQUEST['table'] );

	if ( ! in_array( $table, [ 'quiz_categories', 'quiz_questions' ] ) )
	{
		// Security: SQL prevent INSERT or UPDATE on any table
		$table = '';

		$_REQUEST['tables'] = [];
	}

	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		if ( isset( $columns['DESCRIPTION'] ) )
		{
			$columns['DESCRIPTION'] = DBEscapeString( SanitizeHTML( $_POST['tables'][ $id ]['DESCRIPTION'] ) );
		}

		// FJ fix SQL bug invalid sort order.
		if ( empty( $columns['SORT_ORDER'] )
			|| is_numeric( $columns['SORT_ORDER'] ) )
		{
			// FJ added SQL constraint TITLE is not null.
			if ( ! isset( $columns['TITLE'] )
				|| ! empty( $columns['TITLE'] ) )
			{
				// Trim Answer.
				if ( ! empty( $columns['ANSWER'] ) )
				{
					// Remove trailing white spaces and new lines from answer / options.
					$columns['ANSWER'] = trim( $columns['ANSWER'] );
				}

				// Update Question / Category.
				if ( $id !== 'new' )
				{
					if ( isset( $columns['CATEGORY_ID'] )
						&& $columns['CATEGORY_ID'] != $_REQUEST['category_id'] )
					{
						$_REQUEST['category_id'] = $columns['CATEGORY_ID'];
					}

					$sql = 'UPDATE ' . DBEscapeIdentifier( $table ) . ' SET ';

					foreach ( (array) $columns as $column => $value )
					{
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					}

					$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";

					$go = true;
				}
				// New Question / Category.
				else
				{
					$sql = 'INSERT INTO ' . DBEscapeIdentifier( $table ) . ' ';

					// New Question.
					if ( $table === 'quiz_questions' )
					{
						if ( isset( $columns['CATEGORY_ID'] ) )
						{
							$_REQUEST['category_id'] = $columns['CATEGORY_ID'];

							unset( $columns['CATEGORY_ID'] );
						}

						$fields = 'CATEGORY_ID,CREATED_BY,';

						$values = "'" . $_REQUEST['category_id'] . "','" . User( 'STAFF_ID' ) . "',";
					}
					// New Category.
					elseif ( $table === 'quiz_categories' )
					{
						$fields = '';

						$values = '';
					}

					// School, Created by.
					$fields .= 'SCHOOL_ID,';

					$values .= "'" . UserSchool() . "',";

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

						if ( $table === 'quiz_questions' )
						{
							$_REQUEST['id'] = $id;
						}
						elseif ( $table === 'quiz_categories' )
						{
							$_REQUEST['category_id'] = $id;
						}
					}
				}
			}
			else
				$error[] = _( 'Please fill in the required fields' );
		}
		else
			$error[] = _( 'Please enter valid Numeric data.' );
	}

	// Unset tables & redirect URL.
	RedirectURL( [ 'tables' ] );
}

// Delete Question / Category.
if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( intval( $_REQUEST['id'] ) > 0 )
	{
		if ( DeletePrompt( dgettext( 'Quiz', 'Question' ) ) )
		{
			DBQuery( "DELETE FROM quiz_questions
				WHERE ID='" . (int) $_REQUEST['id'] . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}
	elseif ( isset( $_REQUEST['category_id'] )
		&& intval( $_REQUEST['category_id'] ) > 0
		&& ! QuizCategoryHasQuestions( $_REQUEST['category_id'] ) )
	{
		if ( DeletePrompt( dgettext( 'Quiz', 'Question Category' ) ) )
		{
			DBQuery( "DELETE FROM quiz_categories
				WHERE ID='" . (int) $_REQUEST['category_id'] . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			// Unset modfunc & category ID redirect URL.
			RedirectURL( [ 'modfunc', 'category_id' ] );
		}
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	$RET = [];

	// ADDING & EDITING FORM.
	if ( ! empty( $_REQUEST['id'] )
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = DBGet( "SELECT ID,CATEGORY_ID,TITLE,TYPE,
			DESCRIPTION,SORT_ORDER,ANSWER,FILE,CREATED_AT,CREATED_BY,
			(SELECT TITLE
				FROM quiz_categories
				WHERE ID=CATEGORY_ID) AS CATEGORY_TITLE
			FROM quiz_questions
			WHERE ID='" . (int) $_REQUEST['id'] . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );

		$RET = $RET[1];

		$title = $RET['CATEGORY_TITLE'] . ' - ' . $RET['TITLE'];

		// Set Question Category ID if not set yet.
		if ( empty( $_REQUEST['category_id'] ) || $_REQUEST['category_id'] === '-1' )
		{
			$_REQUEST['category_id'] =  $RET['CATEGORY_ID'];
		}
	}
	elseif ( ! empty( $_REQUEST['category_id'] )
		&& $_REQUEST['category_id'] !== 'new'
		&& empty( $_REQUEST['id'] ) )
	{
		$RET = DBGet( "SELECT ID AS CATEGORY_ID,TITLE,SORT_ORDER
			FROM quiz_categories
			WHERE ID='" . (int) $_REQUEST['category_id'] . "'" );

		$RET = $RET[1];

		$title = $RET['TITLE'];
	}
	elseif ( ! empty( $_REQUEST['id'] )
		&& $_REQUEST['id'] === 'new' )
	{
		$title = dgettext( 'Quiz', 'New Question' );

		$RET['ID'] = 'new';

		$RET['CATEGORY_ID'] = isset( $_REQUEST['category_id'] ) ? $_REQUEST['category_id'] : null;
	}
	elseif ( $_REQUEST['category_id'] === 'new' )
	{
		$title = dgettext( 'Quiz',  'New Question Category' );

		$RET['CATEGORY_ID'] = 'new';
	}

	echo QuizGetQuestionsForm(
		$title,
		$RET,
		isset( $extra_fields ) ? $extra_fields : []
	);

	echo QuizGetQuestionAuthorHeader( $RET );

	echo QuizGetAddQuestionToQuizForm( $RET );

	// @since 3.0 Do action hook.
	do_action( 'Quiz/Questions.php|header' );

	// CATEGORIES.
	$categories_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM quiz_categories
		WHERE SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

	// DISPLAY THE MENU.
	echo '<div class="st">';

	QuizQuestionsMenuOutput( $categories_RET, $_REQUEST['category_id'] );

	echo '</div>';

	// QUESTIONS.
	if ( ! empty( $_REQUEST['category_id'] )
		&& $_REQUEST['category_id'] !=='new'
		&& $categories_RET )
	{
		$questions_RET = DBGet( "SELECT ID,TITLE,TYPE,SORT_ORDER
			FROM quiz_questions
			WHERE CATEGORY_ID='" . (int) $_REQUEST['category_id'] . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE",
		[
			'TYPE' => 'QuizMakeQuestionType',
			'TITLE' => 'QuizTruncateTitle',
		] );

		echo '<div class="st">';

		QuizQuestionsMenuOutput( $questions_RET, $_REQUEST['id'], $_REQUEST['category_id'] );

		echo '</div>';
	}
}
