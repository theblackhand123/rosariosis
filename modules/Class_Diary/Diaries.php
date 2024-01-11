<?php
/**
 * Diaries
 *
 * @package Class Diary module
 */

if ( ! empty( $_REQUEST['cp_id'] ) )
{
	require_once 'modules/Class_Diary/Read.php';
}
else
{
	require_once 'modules/Class_Diary/DiariesList.php';
}
