<?php
/**
 * Audio RTC
 *
 * @package TinyMCE Record Audio Video
 */

chdir( '../..' );

require_once 'Warehouse.php';
require_once 'plugins/TinyMCE_Record_Audio_Video/includes/common.fnc.php';

$type = 'audio';

TinyMCERecordAudioVideoJS( $type );

TinyMCERecordAudioVideoRender( $type );
