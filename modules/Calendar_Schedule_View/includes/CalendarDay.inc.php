<?php
/**
 * Calendar Day functions
 * Load our functions in place of the default ones if Schedule view is activated.
 *
 * @package Calendar Schedule View plugin
 */

if ( ! function_exists( 'CalendarDayClasses' ) )
{
	/**
	 * Calendar Day CSS classes
	 *
	 * @since 4.5
	 *
	 * @param string $date        ISO date.
	 * @param int    $minutes     Minutes.
	 * @param array  $events      Events array (optional).
	 * @param array  $assignments Assignments array (optional).
	 * @param string $mode        Mode: day|inner|number (optional).
	 *
	 * @return string HTML
	 */
	function CalendarDayClasses( $date, $minutes, $events = [], $assignments = [], $mode = 'day' )
	{
		$assignments = [];

		// Day has "events" if has course periods.
		// This will make Number bold and inner have hover class.
		$events = CalendarScheduleViewGetDayCoursePeriods( $date, $minutes );

		return CalendarDayClassesDefault( $date, $minutes, $events, $assignments, $mode );
	}
}

if ( ! function_exists( 'CalendarDayMinutesHTML' ) )
{
	/**
	 * Calendar Day Minutes HTML
	 *
	 * @since 4.5
	 *
	 * @param string $date        ISO date.
	 * @param int    $minutes     Minutes.
	 *
	 * @return string HTML
	 */
	function CalendarDayMinutesHTML( $date, $minutes )
	{
		$html = '';

		if ( ! AllowEdit() )
		{
			return $html;
		}

		// Minutes.
		if ( ! empty( $minutes )
			&& $minutes !== '999' )
		{
			$html .= $minutes;
		}

		return $html;
	}
}

if ( ! function_exists( 'CalendarDayBlockHTML' ) )
{
	/**
	 * Calendar Day Minutes HTML
	 *
	 * @since 4.5
	 *
	 * @param string $date        ISO date.
	 * @param int    $minutes     Minutes.
	 * @param string $day_block   Day block.
	 *
	 * @return string HTML
	 */
	function CalendarDayBlockHTML( $date, $minutes, $day_block )
	{
		$html = '';

		// Blocks.
		if ( $day_block )
		{
			$html .= $day_block;
		}

		return $html;
	}
}


if ( ! function_exists( 'CalendarDayEventsHTML' ) )
{
	/**
	 * Calendar Day Events HTML
	 *
	 * @since 4.5
	 *
	 * @param string $date        ISO date.
	 * @param array  $events      Events array.
	 *
	 * @return string HTML
	 */
	function CalendarDayEventsHTML( $date, $events )
	{
		static $inline_css = false,
			$course_period_html = [];

		if ( ! $inline_css )
		{
			// Inline CSS.
			?>
			<style>
				.calendar-event .tipmsg-label {
					border-left: 0;
				}
			</style>
			<?php

			$inline_css = true;
		}

		$html = '';

		// Day has "events" if has course periods.
		// This will make Number bold and inner have hover class.
		$course_period_groups = CalendarScheduleViewGetDayCoursePeriods( $date, 0 );

		foreach ( (array) $course_period_groups as $course_period_id => $course_periods )
		{
			if ( isset( $course_period_html[ $course_period_id ] ) )
			{
				$html .= $course_period_html[ $course_period_id ];

				continue;
			}

			$html .= CalendarScheduleViewCoursePeriodHTML( $course_periods );

			// $course_period_html[ $course_period_id ] = $cp_html;
		}

		return $html;
	}
}


if ( ! function_exists( 'CalendarDayAssignmentsHTML' ) )
{
	/**
	 * Calendar Day Assignments HTML
	 *
	 * @since 4.5
	 *
	 * @param string $date        ISO date.
	 * @param array  $assignments Assignments array.
	 *
	 * @return string HTML
	 */
	function CalendarDayAssignmentsHTML( $date, $assignments )
	{
		$html = '';

		return $html;
	}
}


if ( ! function_exists( 'CalendarDayNewAssignmentHTML' ) )
{
	/**
	 * Calendar Day New Assignment HTML
	 *
	 * @since 4.5
	 *
	 * @param string $date        ISO date.
	 * @param array  $assignments Assignments array.
	 *
	 * @return string HTML
	 */
	function CalendarDayNewAssignmentHTML( $date, $assignments )
	{
		$html = '';

		return $html;
	}
}
