<?php
/**
 * Functions
 *
 * @package TinyMCE Record Audio Video
 */

require_once 'plugins/TinyMCE_Record_Audio_Video/includes/common.fnc.php';

add_action( 'functions/Inputs.php|tinymce_before_init', 'TinyMCERecordAudioVideoAddPlugin' );

/**
 * Add TinyMCE plugin (2 buttons)
 */
function TinyMCERecordAudioVideoAddPlugin()
{
	$plugin_js_url = TinyMCERecordAudioVideoDirURL() . 'plugins/TinyMCE_Record_Audio_Video/tinymce/recordrtc.js';
	?>
	<script>
		var recordrtc = {
			'audiortc': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Insert audio recording' ) ); ?>,
			'videortc': <?php echo json_encode( dgettext( 'TinyMCE_Record_Audio_Video', 'Insert video recording' ) ); ?>,
		};

		tinymceSettings.toolbar += ' videortc audiortc';
		tinymceSettings.external_plugins.recordrtc = <?php echo json_encode( $plugin_js_url ); ?>;
	</script>
	<?php
}
