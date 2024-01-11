/**
 * Javascript
 *
 * @package Assignment Max Points plugin
 */

/**
 * Set Points field value (+min & max attributes)
 * 1. For new Assignment
 * 2. When editing existing Assignment
 *
 * @param {int} points Points
 */
var assignmentMaxPointsAssignments = function(points) {
	$('#tablesnewPOINTS').attr('max', points).attr('min', points).val(points);

	// Wait for AJAX URL to be updated.
	setTimeout(function(){
		// Get Assignment ID from URL.
		// @link https://stackoverflow.com/questions/827368/using-the-get-parameter-of-a-url-in-javascript
		var matches = /assignment_id=([^&#=]*)/.exec(window.location.search);

		if ( ! matches ) {
			return;
		}

		var assignmentId = matches[1];

		if ( assignmentId
			&& assignmentId != 'new' ) {
			// Set Max/Min Points for existing Assignments. Max/min are inside global divOnClick var.
			window['htmltables' + assignmentId + 'POINTS'] = window['htmltables' + assignmentId + 'POINTS'].replace(
				'max="9999"',
				'max="' + points + '"'
			).replace(
				'min="0"',
				'min="' + points + '"'
			).replace(
				'min="1"',
				'min="' + points + '"'
			);
		}
	}, 100);
};

/**
 * Check entered Points are not > max
 * Else, prevent form submit & display alert msg
 * 1. For the Gradebook Grades form
 *
 * @param {int} maxPoints Max Points
 */
var assignmentMaxPointsGradebookGrades = function(maxPoints) {
	// Get main form.
	var $forms = $('#body form');

	if ( ! $forms.length ) {
		return;
	}

	var $gradesForm = $forms.first();

	if ( modname === 'Users/TeacherPrograms.php&include=Grades/Grades.php' ) {
		if ( $forms.length < 2 ) {
			return;
		}

		// Grades form is the second form on Teacher Programs.
		$gradesForm = $forms.eq(1);
	}

	$gradesForm.submit(function(){
		// Get Assignment ID from URL.
		// @link https://stackoverflow.com/questions/827368/using-the-get-parameter-of-a-url-in-javascript
		var matches = /assignment_id=([^&#=]*)/.exec(window.location.search);
		var assignmentId = matches[1];

		var canSubmit = true;

		// Note: assignmentId can be "all"
		if ( assignmentId ) {
			// Check each id="values[studentId][assignmentId]POINTS" field.
			$('[id$="POINTS"]').each(function(){

				var points = $(this).val();

				if ( ! points || points === '*' ) {
					return;
				}

				points = parseFloat(points.replace(",", "."));

				if ( points > maxPoints ) {
					canSubmit = false;
				}
			});
		}

		if ( ! canSubmit ) {
			alert( assignmentMaxPointsGradebookGradesError );
		}

		return canSubmit;
	});
};

if ( modname === 'Grades/Assignments.php'
	|| modname === 'Grades/MassCreateAssignments.php' ) {

	assignmentMaxPointsAssignments( assignmentMaxPoints );
} else if ( modname === 'Grades/Grades.php'
	|| modname === 'Users/TeacherPrograms.php&include=Grades/Grades.php' ) {

	assignmentMaxPointsGradebookGrades( assignmentMaxPoints );
}

