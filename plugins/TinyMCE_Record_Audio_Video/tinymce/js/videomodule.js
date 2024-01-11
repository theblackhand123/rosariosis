// TinyMCE recordrtc library functions.
// @package    tinymce_recordrtc.
// @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com).
// @author     Jacob Prud'homme (jacob [dt] prudhomme [at] blindsidenetworks [dt] com)
// @copyright  2016 onwards, Blindside Networks Inc.
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.

// ESLint directives.
/* global recordrtc, player, playerDOM, startStopBtn, uploadBtn */
/* global recType, maxUploadSize, chunks, blobSize, countdownTicker */
/* exported maxUploadSize, chunks, blobSize */
/* eslint-disable camelcase, no-global-assign */

// JSHint directives.
/* global player: true, playerDOM: true, startStopBtn: true */
/* global uploadBtn: true, recType: true, maxUploadSize: true, chunks: true, blobSize: true */

// Scrutinizer CI directives.
/** global: tinymce_recordrtc */
/** global: Y */
/** global: recordrtc */
/** global: blobSize */
/** global: chunks */
/** global: countdownSeconds */
/** global: countdownTicker */
/** global: maxUploadSize */
/** global: player */
/** global: playerDOM */
/** global: recType */
/** global: startStopBtn */
/** global: uploadBtn */

// This function is initialized from PHP.
tinymce_recordrtc.view_init = function() {
    // Assignment of global variables.
    player = $('video#player');
    playerDOM = document.querySelector('video#player');
    startStopBtn = $('button#start-stop');
    uploadBtn = $('button#upload');
    recType = 'video';
    // Extract the numbers from the string, and convert to bytes.
    maxUploadSize = window.parseInt(recordrtc.maxfilesize.match(/\d+/)[0], 10) * Math.pow(1024, 2);

    // Show alert and close plugin if WebRTC is not supported.
    tinymce_recordrtc.check_has_gum();
    // Show alert and redirect user if connection is not secure.
    tinymce_recordrtc.check_secure();

    // Run when user clicks on "record" button.
    startStopBtn.on('click', function() {
        startStopBtn.attr('disabled', true);

        // If button is displaying "Start Recording" or "Record Again".
        if ((startStopBtn.html() === recordrtc.startrecording) ||
            (startStopBtn.html() === recordrtc.recordagain) ||
            (startStopBtn.html() === recordrtc.recordingfailed)) {
            // Make sure the upload button is not shown.
            uploadBtn.parent().parent().addClass('hide');

            // Empty the array containing the previously recorded chunks.
            chunks = [];
            blobSize = 0;

            // Initialize common configurations.
            var commonConfig = {
                // When the stream is captured from the microphone/webcam.
                onMediaCaptured: function(stream) {
                    // Make video stream available at a higher level by making it a property of startStopBtn.
                    startStopBtn.stream = stream;

                    tinymce_recordrtc.start_recording(recType, startStopBtn.stream);
                },

                // Revert button to "Record Again" when recording is stopped.
                onMediaStopped: function(btnLabel) {
                    startStopBtn.html(btnLabel);
                    startStopBtn.attr('disabled', false);
                },

                // Handle recording errors.
                onMediaCapturingFailed: function(error) {
                    tinymce_recordrtc.handle_gum_errors(error, commonConfig);
                }
            };

            // Show video tag without controls to view webcam stream.
            player.parent().parent().removeClass('hide');
            player.attr('controls', false);

            // Capture audio+video stream from webcam/microphone.
            tinymce_recordrtc.capture_audio_video(commonConfig);
        } else { // If button is displaying "Stop Recording".
            // First of all clears the countdownTicker.
            window.clearInterval(countdownTicker);

            // Disable "Record Again" button for 1s to allow background processing (closing streams).
            window.setTimeout(function() {
                startStopBtn.attr('disabled', false);
            }, 1000);

            // Stop recording.
            tinymce_recordrtc.stop_recording(startStopBtn.stream);

            // Change button to offer to record again.
            startStopBtn.html(recordrtc.recordagain);
        }
    });
};

// Setup to get audio+video stream from microphone/webcam.
tinymce_recordrtc.capture_audio_video = function(config) {
    tinymce_recordrtc.capture_user_media(
        // Media constraints.
        {
            audio: true,
            video: {
                width: {
                    ideal: 640
                },
                height: {
                    ideal: 480
                }
            }
        },

        // Success callback.
        function(audioVideoStream) {
            // Set video player source to microphone+webcam stream, and play it back as it's recording.
            playerDOM.srcObject = audioVideoStream;
            playerDOM.play();

            config.onMediaCaptured(audioVideoStream);
        },

        // Error callback.
        function(error) {
            config.onMediaCapturingFailed(error);
        }
    );
};
