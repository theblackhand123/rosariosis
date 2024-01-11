<?php
/**
 * English Help texts - Semester Rollover module
 *
 * Texts are organized by:
 * - Module
 * - Profile
 *
 * @author François Jacquet
 *
 * @package Semester Rollover module
 */

if ( User( 'PROFILE' ) === 'admin' ) :

	$help['Semester_Rollover/SemesterRolloverStudents.php'] = '<p>' . _help( '<i>Semester Rollover Students</i> lets you roll students to the next semester. The Rollover program only serves for one school year to another, and thus does not allow for passing students to the next grade during the year. The Semester Rollover Students program works by first dropping students and re-enroll them in the Next Grade, on the first day of the next semester. See Reference below.', 'Semester_Rollover' ) . '</p>
	<p>' . _help( 'Note: you may want to create specific <i>Enrollment Codes</i> such as "Beginning of Semester 2" and a "End of Semester 1" drop code.', 'Semester_Rollover' ) . '</p>
	<p>' . _help( 'Students are rolled to the next Semester based on their individual "Rolling / Retention Options" (see <i>Students > Student Info</i>):', 'Semester_Rollover' ) . '</p>
	<ul><li>' . _help( 'Next grade at current school: the student is enrolled in the next Grade Level (see <i>School > Grade Levels</i>) or only Dropped if no Next Grade is set.', 'Semester_Rollover' ) . '</li>
	<li>' . _help( 'Retain: no need to drop and re-enroll the student in the same Grade Level. The student is skipped.', 'Semester_Rollover' ) . '</li>
	<li>' . _help( 'Do not enroll after this school year: student is Dropped. Interpreted here as "Do not enroll after this semester".', 'Semester_Rollover' ) . '</li>
	<li>' . _help( 'Other School: the student is enrolled in another school. Note: no Grade Level or Calendar are set.', 'Semester_Rollover' ) . '</li></ul>';

endif;
