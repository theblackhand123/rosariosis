<?php
/**
 * Common functions
 *
 * @package Lesson Plan module
 */

if ( ! function_exists( 'DBInsert' ) )
{
	// @dperecated since RosarioSIS 11.0 Include DBInsert() functions if not exist
	require_once 'modules/Lesson_Plan/includes/DBUpsert.php';
}

/**
 * Get Course Period Title
 *
 * @param int $cp_id Course Period ID.
 *
 * @return string Course Period Title.
 */
function LessonPlanCoursePeriodTitle( $cp_id )
{
	$cp_title = DBGetOne( "SELECT TITLE
		FROM course_periods
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

	return $cp_title;
}

/**
 * Get Subject Title
 *
 * @param int $cp_id Course Period ID.
 *
 * @return string Subject Title.
 */
function LessonPlanSubjectTitle( $cp_id )
{
	$subject_title = DBGetOne( "SELECT cs.TITLE
		FROM course_periods cp,courses c,course_subjects cs
		WHERE cp.COURSE_PERIOD_ID='" . (int) $cp_id . "'
		AND cp.COURSE_ID=c.COURSE_ID
		AND c.SUBJECT_ID=cs.SUBJECT_ID" );

	return $subject_title;
}

if ( ! function_exists( 'LessonPlanAddInputs' ) )
{
	/**
	 * Get Add Lesson inputs HTML
	 *
	 * @param int $cp_id Course Period ID.
	 *
	 * @return string Add Lesson inputs HTML.
	 */
	function LessonPlanAddInputs( $cp_id )
	{
		$inputs = '<tr class="st"><td colspan="2">' . TextInput(
			'',
			'values[TITLE]',
			_( 'Title' ),
			'required maxlength="100" size="30"'
		) . '</td></tr>';

		$inputs .= '<tr class="st"><td>' . DateInput(
			'',
			'values[ON_DATE]',
			_( 'Date' ),
			false,
			true,
			true
		) . '</td>';

		$period_length = DBGetOne( "SELECT LENGTH
			FROM school_periods sp,course_period_school_periods cpsp
			WHERE cpsp.COURSE_PERIOD_ID='" . (int) $cp_id . "'
			AND sp.PERIOD_ID=cpsp.PERIOD_ID
			LIMIT 1" );

		$inputs .= '<td>' . TextInput(
			$period_length ? $period_length : '',
			'values[LENGTH_MINUTES]',
			_( 'Length (minutes)' ),
			'type="number" min="0" max="999"',
			false
		) . '</td></tr>';

		$inputs .= '<tr class="st"><td>' . TextInput(
			'',
			'values[LESSON_NUMBER]',
			dgettext( 'Lesson_Plan', 'Lesson Number' ),
			'maxlength="50" size="4"'
		) . '</td>';

		$inputs .= '<td>' . LessonPlanLocationAutoSelect(
			'',
			$cp_id,
			'new'
		) . '</td></tr>';

		$tooltip = '<div class="tooltip"><i>' .
			dgettext(
				'Lesson_Plan',
				'What are learners expected to learn after completing the lesson? These should be specific and able to be assessed.'
			) .
		'</i></div>';

		$inputs .= '<tr class="st"><td colspan="2">' . TinyMCEInput(
			'',
			'message',
			dgettext( 'Lesson_Plan', 'Learning outcomes/objective' ) . $tooltip
		) . '</td></tr>';

		return $inputs;
	}
}

/**
 * Item List Output
 *
 * @param int|string $lesson_id Lesson ID (o for new).
 * @param array      $items     Lesson items.
 *
 * @return void Item List Output.
 */
function LessonPlanItemListOutput( $lesson_id, $items )
{
	$items_RET = [];

	$i = 1;

	foreach ( $items as $item )
	{
		$items_RET[ $i++ ] = LessonPlanItemFormatData( $item['DATA'] );
	}

	$tooltips = [
		'TEACHER_ACTIVITY' => '',
		'LEARNER_ACTIVITY' => '',
		'ASSESSMENT' => '',
		'RESOURCES' => '',
	];

	if ( ! $lesson_id )
	{
		$tooltips = [
			'TEACHER_ACTIVITY' => dgettext( 'Lesson_Plan', 'How are you explaining and illustrating the topic?' ),
			'LEARNER_ACTIVITY' => dgettext( 'Lesson_Plan', 'What are the learners doing to help them understand the topic?' ),
			'ASSESSMENT' => dgettext( 'Lesson_Plan', 'How do you plan to assess learning as it is happening?' ),
			'RESOURCES' => dgettext( 'Lesson_Plan', 'What resources will you use that will support the teaching, learning and assessment activities?' ),
		];
	}

	$tooltip_html = function( $text )
	{
		if ( ! $text )
		{
			return '';
		}

		return '<div class="tooltip" style="text-transform: none"><i>' . $text . '</i></div>';
	};

	$columns = [
		'TIME' => dgettext( 'Lesson_Plan', 'Time' ),
		'TEACHER_ACTIVITY' => dgettext( 'Lesson_Plan', 'Content and teacher activity' ) .
			$tooltip_html( $tooltips['TEACHER_ACTIVITY'] ),
		'LEARNER_ACTIVITY' => dgettext( 'Lesson_Plan', 'Learner activity' ) .
			$tooltip_html( $tooltips['LEARNER_ACTIVITY'] ),
		'ASSESSMENT' => dgettext( 'Lesson_Plan', 'Formative assessment' ) .
			$tooltip_html( $tooltips['ASSESSMENT'] ),
		'RESOURCES' => dgettext( 'Lesson_Plan', 'Learning materials and resources' ) .
			$tooltip_html( $tooltips['RESOURCES'] ),
	];

	$link = [];

	if ( ! $lesson_id )
	{
		$link['add']['html'] = [
			'TIME' => TextInput(
				'',
				'item[1][TIME]',
				'',
				'type="number" min="0" max="999"'
			),
			'TEACHER_ACTIVITY' => TextAreaInput(
				'',
				'item[1][TEACHER_ACTIVITY]'
			),
			'LEARNER_ACTIVITY' => TextAreaInput(
				'',
				'item[1][LEARNER_ACTIVITY]'
			),
			'ASSESSMENT' => TextAreaInput(
				'',
				'item[1][ASSESSMENT]'
			),
			'RESOURCES' => TextAreaInput(
				'',
				'item[1][RESOURCES]'
			),
		];
	}

	$options = [ 'search' => false, 'sort' => false ];

	if ( ! $lesson_id )
	{
		$options['count'] = false;
	}

	ListOutput(
		$items_RET,
		$columns,
		dgettext( 'Lesson_Plan', 'Part of the Lesson' ),
		dgettext( 'Lesson_Plan', 'Parts of the Lesson' ),
		$link,
		[],
		$options
	);
}

/**
 * Format Part of the Lesson data
 * Ready for ListOutput()
 *
 * @param string|array $data Item data. JSON or associative array.
 *
 * @return array Formatted Item data
 */
function LessonPlanItemFormatData( $data )
{
	static $i = 1;

	$data_array = json_decode( $data, true );

	if ( json_last_error() !== JSON_ERROR_NONE )
	{
		$data_array = (array) $data;
	}

	$data_f = [];

	foreach ( $data_array as $column => $value )
	{
		if ( ! $value )
		{
			$data_f[ $column ] = $value;

			continue;
		}

		if ( $column === 'TIME' )
		{
			$data_f[ $column ] = $value . '&nbsp;' . dgettext( 'Lesson_Plan', 'min.' );

			continue;
		}

		// MarkDown to HTML + responsive table to ColorBox.
		$data_f[ $column ] = '<div id="' . $column . $i . '" class="rt2colorBox"><div class="markdown-to-html">' .
			$value . '</div></div>' ;
	}

	$i++;

	return $data_f;
}

if ( ! function_exists( 'LessonPlanSaveEntry' ) )
{
	/**
	 * Save Lesson Plan entry
	 *
	 * @param string $entry_id Entry ID or 'new'.
	 * @param int    $cp_id    Course Period ID.
	 * @param array  $columns  Entry columns, associative array: name, message...
	 *
	 * @return int True on success or Entry ID on INSERT.
	 */
	function LessonPlanSaveEntry( $entry_id, $cp_id, $columns )
	{
		if ( ! $entry_id
			|| ! $cp_id
			|| ! $columns )
		{
			return false;
		}

		if ( ! empty( $columns['DATA'] ) )
		{
			$columns['DATA'] = DBEscapeString( json_encode( $columns['DATA'] ) );
		}

		if ( $entry_id === 'new' )
		{
			// Save message.
			$entry_id = DBInsert(
				'lesson_plan_lessons',
				$columns + [ 'COURSE_PERIOD_ID' => (int) $cp_id ],
				'id'
			);
		}
		else
		{
			DBUpdate(
				'lesson_plan_lessons',
				$columns + [ 'COURSE_PERIOD_ID' => (int) $cp_id ],
				[ 'ID' => (int) $entry_id ]
			);
		}

		return $entry_id;
	}
}

/**
 * Save Part of the Lesson
 *
 * @param string $item_id   Item ID or 'new'.
 * @param string $lesson_id Lesson ID.
 * @param array  $columns   Entry columns, associative array: name, message...
 *
 * @return int True on success or Item ID on INSERT.
 */
function LessonPlanSaveItem( $item_id, $lesson_id, $columns )
{
	if ( ! $item_id
		|| ! $lesson_id
		|| ! $columns )
	{
		return false;
	}

	$data = DBEscapeString( json_encode( $columns ) );

	if ( $item_id === 'new' )
	{
		// Save message.
		$item_id = DBInsert(
			'lesson_plan_items',
			[ 'DATA' => $data, 'LESSON_ID' => (int) $lesson_id ],
			'id'
		);
	}
	else
	{
		DBUpdate(
			'lesson_plan_items',
			[ 'DATA' => $data, 'LESSON_ID' => (int) $lesson_id ],
			[ 'ID' => (int) $entry_id ]
		);
	}

	return $item_id;
}

if ( ! function_exists( 'LessonPlanDeleteEntry' ) )
{
	/**
	 * Delete Lesson Plan entry
	 *
	 * @param int $entry_id Entry ID.
	 * @param int $cp_id Course Period ID.
	 *
	 * @return bool True on success.
	 */
	function LessonPlanDeleteEntry( $entry_id, $cp_id )
	{
		if ( ! $entry_id
			|| ! $cp_id )
		{
			return false;
		}

		DBQuery( "DELETE FROM lesson_plan_items
			WHERE LESSON_ID='" . (int) $entry_id . "'" );

		DBQuery( "DELETE FROM lesson_plan_lessons
			WHERE ID='" . (int) $entry_id . "'
			AND COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

		return true;
	}
}

/**
 * Get Lesson Plan entries from DB
 *
 * @param int $cp_id Course Period ID.
 *
 * @return array Lesson Plan entries.
 */
function LessonPlanGetEntries( $cp_id )
{
	// Set start date.
	$start_date = RequestedDate( 'start', DBDate() );

	// Set end date.
	$end_date = RequestedDate( 'end', date( 'Y-m-d', strtotime( '+7 days' ) ) );

	$entries = DBGet( "SELECT ID,ON_DATE,TITLE,LENGTH_MINUTES,LESSON_NUMBER,LOCATION,DATA,
		COURSE_PERIOD_ID,CREATED_AT
		FROM lesson_plan_lessons
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'
		AND ON_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'
		ORDER BY ON_DATE,CREATED_AT" );

	return $entries;
}

/**
 * Get Parts of the Lesson from DB
 *
 * @param int $lesson_id Lesson ID.
 *
 * @return array Parts of the Lesson.
 */
function LessonPlanGetItems( $lesson_id )
{
	$items = DBGet( "SELECT ID,LESSON_ID,DATA,CREATED_AT
		FROM lesson_plan_items
		WHERE LESSON_ID='" . (int) $lesson_id . "'
		ORDER BY ID" );

	return $items;
}

if ( ! function_exists( 'LessonPlanDisplayEntry' ) )
{
	/**
	 * Format Lesson Plan entry HTML for display
	 *
	 * @param array $entry Lesson Plan entry from DB.
	 *
	 * @return string Entry HTML for display.
	 */
	function LessonPlanDisplayEntry( $entry )
	{
		$entry_html = '<tr class="st"><td>' . NoInput(
			'<b>' . $entry['TITLE'] . '</b>',
			ProperDate( $entry['ON_DATE'] )
		) . '</td>';

		if ( $entry['LOCATION'] )
		{
			$entry_html .= '<td>' . NoInput(
				$entry['LOCATION'],
				dgettext( 'Lesson_Plan', 'Location' )
			) . '</td>';
		}
		else
		{
			$entry_html .= '<td></td>';
		}

		$entry_html .= '<tr class="st">';

		$missing_td = 0;

		if ( $entry['LESSON_NUMBER'] )
		{
			$entry_html .= '<td>' . NoInput(
				$entry['LESSON_NUMBER'],
				dgettext( 'Lesson_Plan', 'Lesson Number' )
			) . '</td>';
		}
		else
		{
			$missing_td++;
		}

		if ( $entry['LENGTH_MINUTES'] )
		{
			$entry_html .= '<td>' . NoInput(
				$entry['LENGTH_MINUTES'],
				_( 'Length (minutes)' )
			) . '</td>';
		}
		else
		{
			$missing_td++;
		}

		for ( $i = 0; $i < $missing_td; $i++ )
		{
			$entry_html .= '<td></td>';
		}

		$entry_html .= '</tr>';

		$data = json_decode( $entry['DATA'], true );

		if ( $data['message'] )
		{
			$message = '<div style="max-width: 1024px">' .
				$data['message'] .
				FormatInputTitle(
					dgettext( 'Lesson_Plan', 'Learning outcomes/objective' ),
					'',
					false,
					''
				) . '</div>';

			$entry_html .= '<tr class="st"><td colspan="2">' . $message . '</td></tr>';
		}

		return $entry_html;
	}
}

/**
 * Check Student is enrolled in Course Period
 *
 * @param int $student_id Student ID.
 * @param int $cp_id      Course Period ID.
 *
 * @return bool True if Student is enrolled in Course Period.
 */
function LessonPlanCheckStudentCoursePeriod( $student_id, $cp_id )
{
	$has_cp_id = DBGetOne( "SELECT 1
		FROM schedule s
		WHERE s.STUDENT_ID='" . (int) $student_id . "'
		AND s.COURSE_PERIOD_ID='" . (int) $cp_id . "'
		AND s.START_DATE<=CURRENT_DATE
		AND (s.END_DATE IS NULL OR s.END_DATE>=CURRENT_DATE)
		AND s.SCHOOL_ID='" . UserSchool() . "'
		AND s.SYEAR='" . UserSyear() . "'"
	);

	return (bool) $has_cp_id;
}

/**
 * Get Lesson Plans for student, to display list
 *
 * @param int $student_id Student ID.
 *
 * @return array Lesson Plans for student from DB.
 */
function LessonPlanGetStudentPlans( $student_id )
{
	$lesson_plans = DBGet( "SELECT cs.TITLE AS SUBJECT,cp.COURSE_PERIOD_ID,cp.TITLE,cp.COURSE_PERIOD_ID AS ENTRIES_COUNT,
		cp.COURSE_PERIOD_ID AS LAST_ENTRY_DATE,cp.COURSE_PERIOD_ID AS READ_LINK
		FROM course_periods cp,courses c,course_subjects cs,schedule s
		WHERE s.STUDENT_ID='" . (int) $student_id . "'
		AND s.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
		AND EXISTS(SELECT 1 FROM lesson_plan_lessons cdm
			WHERE cdm.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)
		AND s.START_DATE<=CURRENT_DATE
		AND (s.END_DATE IS NULL OR s.END_DATE>=CURRENT_DATE)
		AND s.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
		AND s.SCHOOL_ID='" . UserSchool() . "'
		AND s.SYEAR='" . UserSyear() . "'
		AND c.COURSE_ID=cp.COURSE_ID
		AND cs.SUBJECT_ID=c.SUBJECT_ID
		ORDER BY cs.SORT_ORDER IS NULL,cs.SORT_ORDER,c.TITLE,cp.TITLE",
		[
			'ENTRIES_COUNT' => 'LessonPlanMakeEntriesCount',
			'LAST_ENTRY_DATE' => 'LessonPlanMakeLastEntryDate',
			'READ_LINK' => 'LessonPlanMakeReadLink',
		]
	);

	return $lesson_plans;
}

/**
 * Get Lesson Plans for admin (current school), to display list
 *
 * @return array Lesson Plans for admin from DB.
 */
function LessonPlanGetPlans()
{
	$lesson_plans = DBGet( "SELECT cs.TITLE AS SUBJECT,cp.COURSE_PERIOD_ID,cp.TITLE,cp.COURSE_PERIOD_ID AS ENTRIES_COUNT,
		cp.COURSE_PERIOD_ID AS LAST_ENTRY_DATE,cp.COURSE_PERIOD_ID AS READ_LINK
		FROM course_periods cp,courses c,course_subjects cs
		WHERE EXISTS(SELECT 1 FROM lesson_plan_lessons cdm
			WHERE cdm.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)
		AND cp.SCHOOL_ID='" . UserSchool() . "'
		AND cp.SYEAR='" . UserSyear() . "'
		AND c.COURSE_ID=cp.COURSE_ID
		AND cs.SUBJECT_ID=c.SUBJECT_ID
		ORDER BY cs.SORT_ORDER IS NULL,cs.SORT_ORDER,c.TITLE,cp.TITLE",
		[
			'ENTRIES_COUNT' => 'LessonPlanMakeEntriesCount',
			'LAST_ENTRY_DATE' => 'LessonPlanMakeLastEntryDate',
			'READ_LINK' => 'LessonPlanMakeReadLink',
		]
	);

	return $lesson_plans;
}

/**
 * Make Lesson Plan entries count
 *
 * DBGet() function hook
 *
 * @param int    $value  Course Period ID.
 * @param string $column DB column.
 *
 * @return int Lesson Plan entries count
 */
function LessonPlanMakeEntriesCount( $value, $column = 'ENTRIES_COUNT' )
{
	$entries_count = DBGetOne( "SELECT COUNT(ID) AS ENTRIES_COUNT
		FROM lesson_plan_lessons
		WHERE COURSE_PERIOD_ID='" . (int) $value . "'" );

	return $entries_count;
}

/**
 * Make Lesson Plan last entry date
 *
 * DBGet() function hook
 *
 * @param int    $value  Course Period ID.
 * @param string $column DB column.
 *
 * @return string Lesson Plan last entry date
 */
function LessonPlanMakeLastEntryDate( $value, $column = 'LAST_ENTRY_DATE' )
{
	$last_entry_date = DBGetOne( "SELECT ON_DATE
		FROM lesson_plan_lessons
		WHERE COURSE_PERIOD_ID='" . (int) $value . "'
		ORDER BY ON_DATE DESC
		LIMIT 1" );

	return ProperDate( $last_entry_date );
}

/**
 * Make Lesson Plan Read link
 *
 * DBGet() function hook
 *
 * @param int    $value  Course Period ID.
 * @param string $column DB column.
 *
 * @return string Lesson Plan Read link
 */
function LessonPlanMakeReadLink( $value, $column = 'READ_LINK' )
{
	$read_url = PreparePHP_SELF(
		[],
		[],
		[ 'cp_id' => $value ]
	);

	$read_link = '<a href="' . $read_url . '">' . dgettext( 'Lesson_Plan', 'Read' ) . '</a>';

	return $read_link;
}

/**
 * Location Auto Select Input
 *
 * @param  string $value   Field value.
 * @param  string $cp_id   Course Period ID.
 *
 * @return string          Auto Select Input
 */
function LessonPlanLocationAutoSelect( $value, $cp_id, $id = 'new' )
{
	static $js_included = false;

	// Add the 'new' option, is also the separator.
	$options['---'] = '-' . _( 'Edit' ) . '-';

	$options_RET = DBGet( "SELECT DISTINCT LOCATION
		FROM lesson_plan_lessons
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'
		ORDER BY LOCATION" );

	foreach ( (array) $options_RET as $option )
	{
		if ( $option['LOCATION'] != ''
			&& ! isset( $options[$option['LOCATION']] ) )
		{
			$options[$option['LOCATION']] = [ $option['LOCATION'], $option['LOCATION'] ];
		}
	}

	$input_name = 'values[LOCATION]';

	$extra = ' maxlength=100';

	if ( $value === '---'
		|| count( (array) $options ) <= 1 )
	{
		return TextInput(
			$value,
			$input_name,
			dgettext( 'Lesson_Plan', 'Location' ),
			$extra
		);
	}

	// When -Edit- option selected, change the auto pull-down to text field.
	$return = '';

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] )
		&& ! $js_included )
	{
		$js_included = true;

		ob_start();?>
		<script>
		function LessonPlanMaybeEditTextInput(el) {

			// -Edit- option's value is ---.
			if ( el.value === '---' ) {

				var $el = $( el );

				// Remove parent <div> if any
				if ( $el.parent('div').length ) {
					$el.unwrap();
				}
				// Remove the select input.
				$el.remove();

				// Show & enable the text input of the same name.
				$( '[name="' + el.name + '_text"]' ).prop('name', el.name).prop('disabled', false).show().focus();
			}
		}
		</script>
		<?php $return = ob_get_clean();
	}

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// Add hidden & disabled Text input in case user chooses -Edit-.
		$return .= TextInput(
			'',
			$input_name . '_text',
			'',
			$extra . ' disabled style="display:none;"',
			false
		);
	}

	$return .= SelectInput(
		$value,
		$input_name,
		dgettext( 'Lesson_Plan', 'Location' ),
		$options,
		'N/A',
		'onchange="LessonPlanMaybeEditTextInput(this);"',
		( $id !== 'new' )
	);

	return $return;
}

/**
 * Is Course Period scheduled on Date?
 * Return true also if CP has no schedule at all
 *
 * @since RosarioSIS 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
 *
 * @param int    $cp_id Course Period ID.
 * @param string $date  ISO date of calendar day.
 *
 * @return bool True if Course Period scheduled on Date, or if CP has no schedule at all!
 */
function LessonPlanIsCoursePeriodScheduledOnDate( $cp_id, $date )
{
	global $DatabaseType;

	if ( ! $cp_id
		|| ! $date )
	{
		return false;
	}

	$cp_has_schedule = DBGetOne( "SELECT 1
		FROM course_period_school_periods
		WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "'" );

	if ( ! $cp_has_schedule )
	{
		// Return true if CP has no schedule at all
		return true;
	}

	$qtr_id = GetCurrentMP( 'QTR', $date, false );

	if ( ! $qtr_id )
	{
		// Date not in a school quarter.
		return false;
	}

	$minutes = DBGetOne( "SELECT MINUTES
		FROM attendance_calendar
		WHERE SCHOOL_DATE='" . $date . "'
		AND CALENDAR_ID=(SELECT CALENDAR_ID
			FROM course_periods
			WHERE COURSE_PERIOD_ID='" . (int) $cp_id . "')" );

	if ( empty( $minutes ) )
	{
		// Not a School Day for CP's Calendar.
		return false;
	}

	$where_sql = " AND (sp.BLOCK IS NULL
		AND position(substring('UMTWHFS' FROM " .
		( $DatabaseType === 'mysql' ?
			"DAYOFWEEK(acc.SCHOOL_DATE)" :
			"cast(extract(DOW FROM acc.SCHOOL_DATE)+1 AS int)" ) .
		" FOR 1) IN cpsp.DAYS)>0
		OR (sp.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK))";

	if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
	{
		$where_sql = " AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast(
			(SELECT CASE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
			FROM attendance_calendar
			WHERE SCHOOL_DATE>=(SELECT START_DATE
				FROM school_marking_periods
				WHERE START_DATE<=acc.SCHOOL_DATE
				AND END_DATE>=acc.SCHOOL_DATE
				AND MP='QTR'
				AND SCHOOL_ID=acc.SCHOOL_ID
				AND SYEAR=acc.SYEAR)
			AND SCHOOL_DATE<=acc.SCHOOL_DATE
			AND CALENDAR_ID=cp.CALENDAR_ID)
			" . ( $DatabaseType === 'mysql' ? "AS UNSIGNED)" : "AS INT)" ) .
			" FOR 1) IN cpsp.DAYS)>0 OR (sp.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK))";
	}

	$is_cp_scheduled = DBGetOne( "SELECT 1
	FROM attendance_calendar acc,course_periods cp,school_periods sp,course_period_school_periods cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND cp.COURSE_PERIOD_ID='" . (int) $cp_id . "'
	AND acc.SCHOOL_DATE='" . $date . "'
	AND cp.CALENDAR_ID=acc.CALENDAR_ID
	AND cp.MARKING_PERIOD_ID IN(SELECT MARKING_PERIOD_ID
		FROM school_marking_periods
		WHERE (MP='FY' OR MP='SEM' OR MP='QTR')
		AND SCHOOL_ID=acc.SCHOOL_ID
		AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
	AND sp.PERIOD_ID=cpsp.PERIOD_ID" .
	$where_sql );

	return (bool) $is_cp_scheduled;
}
