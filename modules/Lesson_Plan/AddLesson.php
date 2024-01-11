<?php
/**
 * Add Lesson
 *
 * @package Lesson Plan module
 */

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'modules/Lesson_Plan/includes/common.fnc.php';

if ( ! empty( $_REQUEST['period'] )
	&& function_exists( 'SetUserCoursePeriod' ) )
{
	// @since RosarioSIS 10.9 Set current User Course Period.
	SetUserCoursePeriod( $_REQUEST['period'] );
}

DrawHeader( dgettext( 'Lesson_Plan', 'Lesson Plan' ) . ' &mdash; ' . ProgramTitle() );

// If running as a teacher program then rosario[allow_edit] will already be set according to admin permissions.
if ( ! isset( $_ROSARIO['allow_edit'] ) )
{
	$_ROSARIO['allow_edit'] = true;
}

if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit() )
{
	AddRequestedDates( 'values' );

	$confirm_ok = true;

	// Check if Lesson already exists for that date.
	$existing_lesson_RET = DBGet( "SELECT ON_DATE,TITLE
		FROM lesson_plan_lessons
		WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
		AND ON_DATE='" . $_REQUEST['values']['ON_DATE'] . "'
		LIMIT 1" );

	if ( ! empty( $existing_lesson_RET[1]['ON_DATE'] ) )
	{
		$message = sprintf(
			dgettext( 'Lesson_Plan', 'A lesson already exists for this course period and date: %s' ),
			'<br />' . ProperDate( $existing_lesson_RET[1]['ON_DATE'] ) . ' &mdash; ' .
			$existing_lesson_RET[1]['TITLE']
		);

		if ( empty( $_REQUEST['delete_ok'] ) )
		{
			// Maintain message & item in $_POST.
			$message .= '<input type="hidden" name="message" value="' . AttrEscape( $_POST['message'] ) . '" />';
			$message .= '<input type="hidden" name="item" value="' . AttrEscape( json_encode( $_POST['item'] ) ) . '" />';

			RedirectURL( [ 'message', 'item' ] );
		}

		$confirm_ok = Prompt(
			'Confirm',
			dgettext( 'Lesson_Plan', 'Do you want to add a lesson on that day?' ),
			$message
		);
	}
	elseif ( ! LessonPlanIsCoursePeriodScheduledOnDate( UserCoursePeriod(), $_REQUEST['values']['ON_DATE'] ) )
	{
		$message = sprintf(
			dgettext( 'Lesson_Plan', 'The Course Period is not scheduled on that date: %s. You can correct the date in case of mistake, or cancel.' ),
			ProperDate( $_REQUEST['values']['ON_DATE'] )
		);

		$message .= '<br /><br />' . DateInput(
			$_REQUEST['values']['ON_DATE'],
			'values[ON_DATE]',
			'',
			false,
			true,
			true
		);

		if ( empty( $_REQUEST['delete_ok'] ) )
		{
			// Maintain message & item in $_POST.
			$message .= '<input type="hidden" name="message" value="' . AttrEscape( $_POST['message'] ) . '" />';
			$message .= '<input type="hidden" name="item" value="' . AttrEscape( json_encode( $_POST['item'] ) ) . '" />';

			RedirectURL( [ 'message', 'item' ] );
		}

		$confirm_ok = Prompt(
			'Confirm',
			dgettext( 'Lesson_Plan', 'Do you want to add a lesson on that day?' ),
			$message
		);
	}

	if ( $confirm_ok )
	{
		if ( ! empty( $_REQUEST['values']['ON_DATE'] )
			&& ! empty( $_REQUEST['values']['TITLE'] ) )
		{
			$from = User( 'NAME' );

			// Bypass strip_tags on the $_REQUEST vars.
			$REQUEST_message = SanitizeHTML( $_POST['message'] );

			$data = [
				'from' => $from,
				'message' => $REQUEST_message,
			];

			$columns = [
				'DATA' => $data,
			] + $_REQUEST['values'];

			$lesson_id = LessonPlanSaveEntry(
				'new',
				UserCoursePeriod(),
				$columns
			);

			if ( $lesson_id )
			{
				if ( ! empty( $_REQUEST['item'] ) )
				{
					if ( ! is_array( $_POST['item'] ) )
					{
						// Array is JSON (maintained from Confirm Prompt), decode.
						$_POST['item'] = json_decode( $_POST['item'], true );
					}

					// We use $_POST to get raw MarkDown (strip_tags on $_REQUEST)
					foreach ( $_POST['item'] as $columns )
					{
						$insert_columns = [];

						// Check if we have at least one column not empty.
						foreach ( $columns as $column => $value )
						{
							if ( ! $value
								&& $value !== '0' )
							{
								continue;
							}

							if ( $column === 'TIME' )
							{
								$insert_columns[ $column ] = (int) $value;

								continue;
							}

							$insert_columns[ $column ] = SanitizeMarkDown( $value );
						}

						if ( ! $insert_columns )
						{
							continue;
						}

						if ( count( $insert_columns ) === 1
							&& isset( $insert_columns['TIME'] ) )
						{
							// Only Time set, skip.
							continue;
						}

						$item_id = LessonPlanSaveItem(
							'new',
							$lesson_id,
							$insert_columns
						);
					}
				}

				$note[] = dgettext( 'Lesson_Plan', 'The lesson has been added to the plan.' );
			}
		}
		else
		{
			$error[] = _( 'Please fill in the required fields' );
		}

		// Unset modfunc, message, values, item & redirect URL.
		RedirectURL( [ 'modfunc', 'message', 'values', 'item' ] );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
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
		LessonPlanSubjectTitle( UserCoursePeriod() ) . ' &mdash; ' . LessonPlanCoursePeriodTitle( UserCoursePeriod() ),
		SubmitButton()
	);

	$message_inputs = LessonPlanAddInputs( UserCoursePeriod() );

	DrawHeader( '<table class="width-100p valign-top fixed-col">' . $message_inputs . '</table>' );

	echo '<div class="lesson-item-list">';

	LessonPlanItemListOutput( '0', [] );

	echo '</div>';

	/**
	 * Add item/part list JS and link
	 *
	 * 1. Clone only once in global var, before input values are set.
	 * Get HTML to fix jQuery insertAfter replacing last list...
	 *
	 * 2. Empty input & textarea values (just in case).
	 *
	 * 3. Update input name, id, link onclick & mdPreview id:
	 * Replace digit with current lesson plan item i/ID.
	 *
	 * @link https://stackoverflow.com/questions/51286061/jquery-clone-element-and-replace-part-of-its-name
	 */
	?>
	<script>
		var lessonPlanItemI = 1,
			$lessonPlanItemList = $( '.lesson-item-list' ).first().clone().html();

		function LessonPlanAddItemList() {
			$( '.lesson-item-list' ).last().after( '<div class="lesson-item-list">' + $lessonPlanItemList + '</div>' );

			lessonPlanItemI++;

			var $lastList = $( '.lesson-item-list' ).last(),
				$inputs = $lastList.find('input,textarea');

			$inputs.val('');

			$inputs.prop('name', function(_, curr) {
				return curr.replace(/\d+/, lessonPlanItemI);
			});

			$inputs.prop('id', function(_, curr) {
				return curr.replace(/\d+/, lessonPlanItemI);
			});

			$lastList.find('a').attr('onclick', function(_, curr) {
				if ( ! curr ) {
					return curr;
				}

				return curr.replace(/\d+/, lessonPlanItemI);
			});

			$lastList.find('.markdown-to-html').prop('id', function(_, curr) {
				return curr.replace(/\d+/, lessonPlanItemI);
			});
		}
	</script>
	<?php
	echo '<a href="#!" onclick="javascript: LessonPlanAddItemList();">' . button(
		'add',
		dgettext( 'Lesson_Plan', 'Add new Part' )
	) . '</a>';

	echo '<br /><div class="center">' . SubmitButton() . '</div></form>';
}
