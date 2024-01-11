<?php
/**
 * Video RTC
 *
 * @package TinyMCE Record Audio Video
 */

chdir( '../..' );

require_once 'Warehouse.php';
require_once 'plugins/TinyMCE_Record_Audio_Video/includes/common.fnc.php';

$type = 'video';

TinyMCERecordAudioVideoJS( $type );

TinyMCERecordAudioVideoRender( $type );
