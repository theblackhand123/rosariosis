<?php
/**
 * Tutor Report Card Comments functions
 *
 * @package Tutor Report Card Comments plugin
 */

/**
 * Display Student MP Tutor's comment on Report Card
 *
 * @param array $comment Comment info.
 */
function TutorReportCardCommentsDisplayComment( $comment, $mp_id )
{
	static $tab_title;

	if ( empty( $tab_title ) )
	{
		// Title = Student Info tab Title.
		$tab_title = DBGetOne( "SELECT TITLE
			FROM student_field_categories
			WHERE INCLUDE='Tutor_Report_Card_Comments/Student'" );

		$tab_title = ParseMLField( $tab_title );
	}

	$comments_classes = '';

	if ( Config( 'TUTOR_REPORT_CARD_COMMENTS_SMALL_FONT_SIZE' ) === 'Y' )
	{
		$comments_classes = 'size-1';
	}

	ob_start(); ?>
	<br />
	<div>
		<p style="display:inline-block; background-color: <?php echo Preferences( 'HEADER' ); ?>; padding: 6px 11px; margin: 0; color: #fff;">
			<b><?php echo $tab_title; ?></b> &mdash; <?php echo GetMP( $mp_id ); ?>
		</p>
		<p style="border: 1px solid #ccc; margin: 0; padding: 6px 11px;" class="<?php echo ( function_exists( 'AttrEscape' ) ? AttrEscape( $comments_classes ) : htmlspecialchars( $comments_classes, ENT_QUOTES ) ); ?>">
			<?php echo nl2br( $comment['COMMENT'] ); ?>
		</p>
		<?php if ( ! empty( $comment['TUTOR_NAME'] ) ) : // Tutor Name is optional. ?>
			<p style="border: 1px solid #ccc; border-top: 0; margin: 0; padding: 6px 11px 4px 11px;">
				<span style="color: #333;">
					<?php echo _( 'Name' ); ?>:
				</span>
				<?php echo $comment['TUTOR_NAME']; ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Get Tutor's comments for Student and Marking Period
 *
 * @param int $student_id Student ID.
 * @param int $mp_id      MP ID.
 *
 * @return array Tutor's comments for Student and Marking Period
 */
function TutorReportCardCommentsGetComment( $student_id, $mp_id )
{
	$student_mp_tutor_comment = DBGet( "SELECT COMMENT,TUTOR_NAME
		FROM student_mp_tutor_report_card_comments
		WHERE STUDENT_ID='" . (int) $student_id . "'
		AND SYEAR='" . UserSyear() . "'
		AND MARKING_PERIOD_ID='" . (int) $mp_id . "'" );

	return issetVal( $student_mp_tutor_comment[1] );
}

/**
 * Get Marking Periods: graded + comments enabled.
 *
 * @param int $mp_id MP ID
 *
 * @return array Marking Period IDs
 */
function TutorReportCardCommentsGetMPs( $mp_id )
{
	// Get comments Marking Periods (graded + comments).
	// Order by MP type.
	$mps_RET = DBGet( "SELECT MARKING_PERIOD_ID
		FROM school_marking_periods
		WHERE DOES_GRADES='Y'
		AND DOES_COMMENTS='Y'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND MARKING_PERIOD_ID IN(" . GetAllMP( 'PRO', $mp_id ) . ")
		ORDER BY MP='FY' DESC,MP='SEM' DESC,MP='QTR' DESC,MP='PRO' DESC,SORT_ORDER IS NULL,SORT_ORDER" );

	$mps = [];

	foreach ( $mps_RET as $mp )
	{
		$mps[] = $mp['MARKING_PERIOD_ID'];
	}

	return $mps;
}
