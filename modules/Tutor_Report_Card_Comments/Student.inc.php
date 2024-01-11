<?php
/**
 * Tutor Report Card Comments Student Info tab
 *
 * @package Tutor Report Card Comments plugin
 */

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'plugins/Tutor_Report_Card_Comments/includes/TutorReportCardComments.fnc.php';
require_once 'modules/Grades/includes/Grades.fnc.php';

$mps = TutorReportCardCommentsGetMPs( UserMP() );

if ( AllowEdit()
	&& ! empty( $_REQUEST['values'] ) )
{
	foreach ( $_REQUEST['values'] as $mp_id => $comment )
	{
		$existing_comment = (bool) TutorReportCardCommentsGetComment( UserStudentID(), $mp_id );

		if ( ! $existing_comment )
		{
			// Insert comment.
			DBQuery( "INSERT INTO student_mp_tutor_report_card_comments
				(STUDENT_ID,SYEAR,MARKING_PERIOD_ID,COMMENT,TUTOR_NAME)
				VALUES ('" . UserStudentID() . "',
				'" . UserSyear() . "',
				'" . $mp_id . "',
				'" . $comment['COMMENT'] . "',
				'" . $comment['TUTOR_NAME'] . "')" );
		}
		else
		{
			// Update comment.
			DBQuery( "UPDATE student_mp_tutor_report_card_comments
				SET COMMENT='" . $comment['COMMENT'] . "',TUTOR_NAME='" . $comment['TUTOR_NAME'] . "'
				WHERE STUDENT_ID='" . UserStudentID() . "'
				AND SYEAR='" . UserSyear() . "'
				AND MARKING_PERIOD_ID='" . (int) $mp_id . "'" );
		}
	}

	RedirectURL( 'values' );
}

if ( ! $_REQUEST['modfunc'] )
{
	$info = dgettext( 'Tutor_Report_Card_Comments', 'Student\'s Tutor or Homeroom Teacher can enter global Comments for each graded Marking Period. Comments will be displayed on the Report Card. "Name" field is optional.' );

	echo ErrorMessage( [ $info ], 'note' );

	if ( $mps )
	{
		$student_ranks = GetClassRankRow( UserStudentID(), $mps );
	}

	foreach ( $mps as $mp_id )
	{
		$comment = TutorReportCardCommentsGetComment( UserStudentID(), $mp_id );

		$student_average = DBGetOne( "SELECT CUM_WEIGHTED_GPA
			FROM transcript_grades
			WHERE STUDENT_ID='" . (int) UserStudentID() . "'
			AND MARKING_PERIOD_ID='" . (int) $mp_id . "'" ) ;

		$student_rank = issetVal( $student_ranks[ $mp_id ] );

		?>
		<fieldset><legend><?php echo GetMP( $mp_id, 'TITLE' ); ?></legend>
		<table>
			<tr><td colspan="3">
				<?php echo TextAreaInput(
					issetVal( $comment['COMMENT'], '' ),
					'values[' . $mp_id . '][COMMENT]',
					_( 'Comments' ),
					'cols="90" rows="5"' . ( AllowEdit() ? '' : ' readonly' ),
					false,
					'text'
				); ?>
			</td></tr>
			<tr class="st"><td>
				<?php echo TextInput(
					issetVal( $comment['TUTOR_NAME'], '' ),
					'values[' . $mp_id . '][TUTOR_NAME]',
					_( 'Name' ),
					'',
					false
				); ?>
    		</td>
    		<td>
    			<?php echo $student_average === false ? '' :
    			NoInput(
    				(float) $student_average,
					_( 'GPA' )
				); ?>
			</td>
    		<td>
    			<?php echo ! $student_rank ? '' :
    			NoInput(
    				$student_rank,
    				_( 'Class Rank' )
    			); ?>
    		</td></tr>
		</table>
		</fieldset>
		<br />
		<?php
	}
}


