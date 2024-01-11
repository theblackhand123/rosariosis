<?php
/**
 * Menu.php file
 * Required
 * - Add Menu entries to other modules
 *
 * @package Embedded Resources module
 */

// Use dgettext() function instead of _() for Module specific strings translation.
// See locale/README file for more information.

// Add a Menu entry to the Resources module.
if ( $RosarioModules['Resources'] ) // Verify Resources module is activated.
{
	$menu['Resources']['admin']['Embedded_Resources/EmbeddedResources.php'] = dgettext( 'Embedded_Resources', 'Embedded Resources' );

	$menu_resources_where_sql = '';

	if ( User( 'PROFILE' ) === 'student' )
	{
		// Limit to Grade Levels.
		$menu_resources_where_sql = " AND (PUBLISHED_GRADE_LEVELS IS NULL
			OR position(CONCAT(',', (SELECT GRADE_ID
				FROM student_enrollment
				WHERE STUDENT_ID='" . UserStudentID() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY START_DATE DESC
				LIMIT 1), ',') IN PUBLISHED_GRADE_LEVELS)>0)";
	}

	$menu_resources_RET = DBGet( "SELECT ID,TITLE
		FROM resources_embedded
		WHERE TRUE " . $menu_resources_where_sql . "
		ORDER BY TITLE" );

	// Add Embedded Resources.
	foreach ( (array) $menu_resources_RET as $resource )
	{
		$resource_modname = 'Embedded_Resources/EmbedResource.php&id=' . $resource['ID'];

		$menu['Resources']['admin'][ $resource_modname ] = $resource['TITLE'];
		$menu['Resources']['teacher'][ $resource_modname ] = $resource['TITLE'];
		$menu['Resources']['parent'][ $resource_modname ] = $resource['TITLE'];
	}
}
