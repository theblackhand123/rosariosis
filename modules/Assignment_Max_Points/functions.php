<?php
/**
 * Functions
 *
 * @package Assignment Max Points
 */

add_action( 'Warehouse.php|footer', 'AssignmentMaxPointsDo' );

/**
 * Do Assignment Max Points action
 *
 * Inject JS to the footer: global vars + plugins/Assignment_Max_Points/js/scripts.js file
 *
 * Only if we are on Assignments / Mass Create Assignments program,
 * or we are on Gradebook Grades (Teacher Programs) program.
 *
 * @return bool True if JS injected
 */
function AssignmentMaxPointsDo()
{
	$max_points = Config( 'ASSIGNMENT_MAX_POINTS' );

	if ( ! $max_points )
	{
		return false;
	}

	// Check we are on Assignments / Mass Create Assignments program.
	// or check we are on Gradebook Grades (Teacher Programs) program.
	if ( $_REQUEST['modname'] === 'Grades/MassCreateAssignments.php'
		|| $_REQUEST['modname'] === 'Grades/Assignments.php'
		|| $_REQUEST['modname'] === 'Grades/Grades.php'
		|| $_REQUEST['modname'] === 'Users/TeacherPrograms.php&include=Grades/Grades.php' )
	{
		// Inject JS to set Max Assignment Points.
		// or inject JS to prevent saving Grades > Max Points.
		?>
		<script>
			var assignmentMaxPoints=<?php echo json_encode( $max_points ); ?>;
			var assignmentMaxPointsGradebookGradesError=<?php echo json_encode( sprintf( dgettext(
				'Assignment_Max_Points',
				'Some points are over the maximum of %d, please correct.'
			), $max_points ) ); ?>;
		</script>
		<script src="plugins/Assignment_Max_Points/js/scripts.js"></script>
		<?php

		return true;
	}

	return false;
}
