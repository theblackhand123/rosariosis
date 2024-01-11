<?php
/**
 * Common functions
 *
 * @package TinyMCE Record Audio Video
 */

/**
 * Get script directory URL
 */
function TinyMCERecordAudioVideoDirURL()
{
	if ( function_exists( 'RosarioURL' ) )
	{
		return RosarioURL();
	}

	$site_url = 'http://';

	if ( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' )
		|| ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' )
		|| ( isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ) )
	{
		// Fix detect https inside Docker or behind reverse proxy.
		$site_url = 'https://';
	}

	$site_url .= $_SERVER['SERVER_NAME'];

	if ( $_SERVER['SERVER_PORT'] != '80'
		&& $_SERVER['SERVER_PORT'] != '443' )
	{
		$site_url .= ':' . $_SERVER['SERVER_PORT'];
	}

	$site_url .= dirname( $_SERVER['SCRIPT_NAME'] ) === DIRECTORY_SEPARATOR ?
		// Add trailing slash.
		'/' : dirname( $_SERVER['SCRIPT_NAME'] ) . '/';

	return $site_url;
}

/**
 * Plugin popup JS (files and config: strings and settings)
 *
 * @param string $type Type: audio or video.
 */
function TinyMCERecordAudioVideoJS( $type )
{
	$plugin_upload_url = TinyMCERecordAudioVideoDirURL() . 'upload.php';

	?>
	<script>
	var recordrtc = {
		'uploadurl': <?php echo json_encode( $plugin_upload_url ); ?>,
		'nowebrtc': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Your browser offers limited or no support for WebRTC technologies yet, and cannot be used with this plugin. Please switch or upgrade your browser' ) ); ?>,
		'gumabort': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Something strange happened which prevented the webcam/microphone from being used' ) ); ?>,
		'gumnotallowed': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'The user must allow the browser access to the webcam/microphone' ) ); ?>,
		'gumnotfound': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'There is no input device connected or enabled' ) ); ?>,
		'gumnotreadable': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Something is preventing the browser from accessing the webcam/microphone' ) ); ?>,
		'gumoverconstrained': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'The current webcam/microphone can not produce a stream with the required constraints' ) ); ?>,
		'gumsecurity': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Your browser does not support recording over an insecure connection and must close the plugin' ) ); ?>,
		'gumtype': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Tried to get stream from the webcam/microphone, but no constraints were specified' ) ); ?>,
		'startrecording': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Start Recording' ) ); ?>,
		'stoprecording': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Stop Recording' ) ); ?>,
		'recordagain': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Record Again' ) ); ?>,
		'recordingfailed': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Recording failed, try again' ) ); ?>,
		'attachrecording': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Attach Recording as Annotation' ) ); ?>,
		'norecordingfound': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Something appears to have gone wrong, it seems nothing has been recorded' ) ); ?>,
		'uploadfailed': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Upload failed:' ) ); ?>,
		'uploadfailed404': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Upload failed: file too large' ) ); ?>,
		'uploadaborted': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Upload aborted:' ) ); ?>,
		'nearingmaxsize': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'You have attained the maximum size limit for file uploads' ) ); ?>,
		'uploadprogress': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'completed' ) ); ?>,
		'annotationprompt': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'What should the annotation appear as?' ) ); ?>,
		'annotation:audio': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Audio annotation' ) ); ?>,
		'annotation:video': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Video annotation' ) ); ?>,
		'maxfilesize': <?php echo json_encode( ini_get('upload_max_filesize') ); ?>,
		// Get values from config.
		'timelimit': <?php echo (int) ( Config( 'TINYMCE_RECORD_AUDIO_VIDEO_TIME_LIMIT' ) ?
			Config( 'TINYMCE_RECORD_AUDIO_VIDEO_TIME_LIMIT' ) : 120 ); ?>,
		'audiobitrate': 128000,
		'videobitrate': 2500000
	};
	</script>
	<link rel="stylesheet" href="tinymce/css/style.css" />
	<script src="../../assets/js/jquery.js"></script>
	<script src="vendor/js/adapter.js"></script>
	<script src="tinymce/js/commonmodule.js"></script>
	<script src="tinymce/js/abstractmodule.js"></script>
	<script src="tinymce/js/compatcheckmodule.js"></script>
	<script src="tinymce/js/<?php echo $type; ?>module.js"></script>
	<script>
		$(window).load(function() {
			tinymce_recordrtc.view_init();
		});
	</script>
	<?php
}

/**
 * Render Plugin popup HTML
 *
 * @param string $type Type: audio or video.
 */
function TinyMCERecordAudioVideoRender( $type )
{
	?>
	<div class="mce-container" hidefocus="1" tabindex="-1">
	<div class="mce-container-body container-fluid">
		<div class="row hide">
			<div class="col-xs-12">
				<div id="alert-danger" class="alert alert-danger">

					<strong><?php echo dgettext( 'TinyMCE_Record_Audio_Video', 'Insecure connection!' ); ?></strong>
					<?php echo dgettext( 'TinyMCE_Record_Audio_Video', 'Your browser might not allow this plugin to work unless it is used either over HTTPS or from localhost' ); ?>
				</div>
			</div>
		</div>

		<div class="row hide">
			<div class="col-xs-12">
				<?php if ( $type === 'audio' ) : ?>

					<audio id="player"></audio>

				<?php else : ?>

					<video id="player"></video>

				<?php endif; ?>
			</div>
		</div>

		<div class="row">
			<div class="col-xs-1"></div>
			<div class="col-xs-10">
				<button id="start-stop" class="btn btn-lg btn-outline-danger btn-block"><?php echo dgettext( 'TinyMCE_Record_Audio_Video', 'Start Recording' ); ?></button>
			</div>
			<div class="col-xs-1"></div>
		</div>

		<div class="row hide">
			<div class="col-xs-3"></div>
			<div class="col-xs-6">
				<button id="upload" class="btn btn-primary btn-block"><?php echo dgettext( 'TinyMCE_Record_Audio_Video', 'Attach Recording as Annotation' ); ?></button>
			</div>
			<div class="col-xs-3"></div>
		</div>

	</div>
	</div>
	<?php
}
