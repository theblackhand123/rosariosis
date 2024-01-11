<?php
/**
 * Gradebook Grades module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 *
 * @package RosarioSIS
 * @subpackage modules
 */

dgettext( 'Grades_Import', 'Grades Import' );

if ( $RosarioModules['Grades'] )
{
	// Place Import Grades program right after Gradebook separator.
	$teacher_programs_pos = array_search( 1, array_keys( $menu['Grades']['teacher'] ) );

	$menu['Grades']['teacher'] = array_merge(
		array_slice( $menu['Grades']['teacher'], 0, $teacher_programs_pos + 1 ),
		[ 'Grades_Import/GradebookGradesImport.php' => dgettext( 'Grades_Import', 'Import Grades' ) ],
		array_slice( $menu['Grades']['teacher'], $teacher_programs_pos + 1 )
	);
}

if ( $RosarioModules['Users'] )
{
	// Place Import Gradebook Grades program right after Teacher Programs separator.
	$teacher_programs_pos = array_search( 2, array_keys( $menu['Users']['admin'] ) );

	$menu['Users']['admin'] = array_merge(
		array_slice( $menu['Users']['admin'], 0, $teacher_programs_pos + 1 ),
		[ 'Users/TeacherPrograms.php&include=Grades_Import/GradebookGradesImport.php' => dgettext( 'Grades_Import', 'Import Grades' ) ],
		array_slice( $menu['Users']['admin'], $teacher_programs_pos + 1 )
	);
}

