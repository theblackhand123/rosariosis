<?php
/**
 * Lesson Plans
 *
 * @package Lesson Plan module
 */

if ( ! empty( $_REQUEST['cp_id'] ) )
{
	require_once 'modules/Lesson_Plan/Read.php';
}
else
{
	require_once 'modules/Lesson_Plan/LessonPlansList.php';
}
