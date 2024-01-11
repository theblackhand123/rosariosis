<?php
/**
 * Tutor Report Card Comments functions
 *
 * @package Tutor Report Card Comments plugin
 */

add_action( 'Grades/includes/ReportCards.fnc.php|pdf_footer', 'TutorReportCardCommentsPDFFooter', 3 );

function TutorReportCardCommentsPDFFooter( $hook, $student_id, &$freetext )
{
	require_once 'plugins/Tutor_Report_Card_Comments/includes/TutorReportCardComments.fnc.php';

	// MPs.
	if ( ! empty( $_REQUEST['mp_arr'] ) )
	{
		$comment = [];

		// Choose good MP ID.
		foreach ( $_REQUEST['mp_arr'] as $mp_id )
		{
			// Last is best.
			// Get Student MP Tutor Comment.
			$comment = TutorReportCardCommentsGetComment( $student_id, $mp_id );

			if ( ! empty( $comment['COMMENT'] ) )
			{
				echo TutorReportCardCommentsDisplayComment( $comment, $mp_id );
			}
		}

	}
}
