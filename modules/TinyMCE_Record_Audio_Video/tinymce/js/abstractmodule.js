// TinyMCE recordrtc library functions for function abstractions.
// @package    tinymce_recordrtc.
// @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com).
// @author     Jacob Prud'homme (jacob [dt] prudhomme [at] blindsidenetworks [dt] com)
// @copyright  2016 onwards, Blindside Networks Inc.
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.

// ESLint directives.
/* global tinyMCEPopup */
/* exported countdownTicker, playerDOM */
/* eslint-disable camelcase, no-alert */

// Scrutinizer CI directives.
/** global: tinymce_recordrtc */
/** global: Y */
/** global: tinyMCEPopup */

var tinymce_recordrtc = tinymce_recordrtc || {};

// A helper for making a Moodle alert appear.
// Subject is the content of the alert (which error the alert is for).
// Possibility to add on-alert-close event.
tinymce_recordrtc.show_alert = function(subject, onCloseEvent) {
    alert( subject );
};

// Handle getUserMedia errors.
tinymce_recordrtc.handle_gum_errors = function(error, commonConfig) {
    var btnLabel = recordrtc.recordingfailed,
        treatAsStopped = function() {
            commonConfig.onMediaStopped(btnLabel);
        };

    // Changes 'CertainError' -> 'gumcertain' to match language string names.
    var stringName = 'gum' + error.name.replace('Error', '').toLowerCase();

    // After alert, proceed to treat as stopped recording, or close dialogue.
    if (stringName !== 'gumsecurity') {
        tinymce_recordrtc.show_alert(recordrtc[stringName], treatAsStopped);
    } else {
        tinymce_recordrtc.show_alert(recordrtc[stringName], function() {
            parent.tinymce.activeEditor.windowManager.close();
        });
    }
};

// Select best options for the recording codec.
tinymce_recordrtc.select_rec_options = function(recType) {
    var types, options;

    if (recType === 'audio') {
        types = [
            'audio/webm;codecs=opus',
            'audio/ogg;codecs=opus'
        ];
        options = {
            audioBitsPerSecond: window.parseInt(recordrtc.audiobitrate)
        };
    } else {
        types = [
            'video/webm;codecs=vp9,opus',
            'video/webm;codecs=h264,opus',
            'video/webm;codecs=vp8,opus'
        ];
        options = {
            audioBitsPerSecond: window.parseInt(recordrtc.audiobitrate),
            videoBitsPerSecond: window.parseInt(recordrtc.videobitrate)
        };
    }

    var compatTypes = types.filter(function(type) {
        return window.MediaRecorder.isTypeSupported(type);
    });

    if (compatTypes.length !== 0) {
        options.mimeType = compatTypes[0];
    }

    return options;
};
