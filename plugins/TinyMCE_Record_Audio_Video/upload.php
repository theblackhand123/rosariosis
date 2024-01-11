<?php
/**
 * Upload Video or Audio file.
 *
 * @package TinyMCE Record Audio Video
 */

chdir( '../..' );

require_once 'Warehouse.php';
require_once 'plugins/TinyMCE_Record_Audio_Video/includes/common.fnc.php';
require_once 'ProgramFunctions/FileUpload.fnc.php';

if ( empty( $_FILES['upload_file'] )
	|| ( ! User( 'STAFF_ID' ) && ! UserStudentID() ) )
{
	// No uploaded file or not logged in.
	header( $_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", true, 404 );

	exit;
}

$path = $FileUploadsPath . 'TinyMCE_Record_Audio_Video/' .
	( User( 'STAFF_ID' ) ? 'User' . User( 'STAFF_ID' ) : 'Student' . UserStudentID() ) . '/';

// @since RosarioSIS 11.0 Add microseconds to filename format to make it harder to predict.
$file_name_no_ext = date( 'Y-m-d_His' ) . '.' . substr( (string) microtime(), 2, 6 );

$final_ext = mb_strtolower( mb_strrchr( $_FILES['upload_file']['name'], '.' ) );

$file_path = FileUpload( 'upload_file', $path, [ '.ogg', '.webm' ], 0, $error, $final_ext, $file_name_no_ext );

if ( $error )
{
	// File upoad error.
	header( $_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", true, 404 );

	var_dump( $error );

	exit;
}

$dir_url = TinyMCERecordAudioVideoDirURL();

$path_url = str_replace( 'plugins/TinyMCE_Record_Audio_Video/', $path, $dir_url );

$file_url = $path_url . $file_name_no_ext . $final_ext;

echo json_encode( [ 'url' => $file_url ] );
