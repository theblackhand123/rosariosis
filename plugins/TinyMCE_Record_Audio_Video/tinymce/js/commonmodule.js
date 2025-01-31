// TinyMCE recordrtc library functions.
// @package    tinymce_recordrtc.
// @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com).
// @author     Jacob Prud'homme (jacob [dt] prudhomme [at] blindsidenetworks [dt] com)
// @copyright  2016 onwards, Blindside Networks Inc.
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.

// ESLint directives.
/* global tinyMCE, tinyMCEPopup */
/* exported alertDanger, countdownTicker, playerDOM, mediaRecorder */
/* eslint-disable camelcase, no-alert */

// Scrutinizer CI directives.
/** global: navigator */
/** global: parent */
/** global: tinymce_recordrtc */
/** global: Y */
/** global: mediaRecorder */

var tinymce_recordrtc = tinymce_recordrtc || {};

// Extract plugin settings to params hash.
(function() {
    var params = {};
    var r = /([^&=]+)=?([^&]*)/g;

    var d = function(s) {
        return window.decodeURIComponent(s.replace(/\+/g, ' '));
    };

    var search = window.location.search;
    var match = r.exec(search.substring(1));
    while (match) {
        params[d(match[1])] = d(match[2]);

        if (d(match[2]) === 'true' || d(match[2]) === 'false') {
            params[d(match[1])] = d(match[2]) === 'true' ? true : false;
        }
        match = r.exec(search.substring(1));
    }

    window.params = params;
})();

// Initialize some variables.
var alertDanger = null;
var blobSize = null;
var chunks = null;
var countdownSeconds = null;
var countdownTicker = null;
var maxUploadSize = null;
var mediaRecorder = null;
var player = null;
var playerDOM = null;
var recType = null;
var startStopBtn = null;
var uploadBtn = null;

// Capture webcam/microphone stream.
tinymce_recordrtc.capture_user_media = function(mediaConstraints, successCallback, errorCallback) {
    window.navigator.mediaDevices.getUserMedia(mediaConstraints).then(successCallback).catch(errorCallback);
};

// Add chunks of audio/video to array when made available.
tinymce_recordrtc.handle_data_available = function(event) {
    // Push recording slice to array.
    chunks.push(event.data);
    // Size of all recorded data so far.
    blobSize += event.data.size;

    // If total size of recording so far exceeds max upload limit, stop recording.
    // An extra condition exists to avoid displaying alert twice.
    if (blobSize >= maxUploadSize) {
        if (!window.localStorage.getItem('alerted')) {
            window.localStorage.setItem('alerted', 'true');

            startStopBtn.click();

            tinymce_recordrtc.show_alert(recordrtc.nearingmaxsize);
        } else {
            window.localStorage.removeItem('alerted');
        }

        chunks.pop();
    }
};

tinymce_recordrtc.handle_stop = function() {
    // Set source of audio player.
    var blob = new window.Blob(chunks, {
        type: mediaRecorder.mimeType
    });
    player.attr('src', window.URL.createObjectURL(blob));

    // Show audio player with controls enabled, and unmute.
    player.attr('muted', false);
    player.attr('controls', true);
    player.parent().parent().removeClass('hide');

    // Show upload button.
    uploadBtn.parent().parent().removeClass('hide');
    uploadBtn.html(recordrtc.attachrecording);
    uploadBtn.attr('disabled', false);

    // Handle when upload button is clicked.
    uploadBtn.on('click', function() {
        // Trigger error if no recording has been made.
        if (chunks.length === 0) {
            tinymce_recordrtc.show_alert(recordrtc.norecordingfound);
        } else {
            uploadBtn.attr('disabled', true);

            // Upload recording to server.
            tinymce_recordrtc.upload_to_server(recType, function(progress, fileURLOrError) {
                if (progress === 'ended') { // Insert annotation in text.
                    uploadBtn.attr('disabled', false);
                    tinymce_recordrtc.insert_annotation(recType, fileURLOrError);
                } else if (progress === 'upload-failed') { // Show error message in upload button.
                    uploadBtn.attr('disabled', false);
                    uploadBtn.html(recordrtc.uploadfailed + ' ' + fileURLOrError);
                } else if (progress === 'upload-failed-404') { // 404 error = File too large in Moodle.
                    uploadBtn.attr('disabled', false);
                    uploadBtn.html(recordrtc.uploadfailed404);
                } else if (progress === 'upload-aborted') {
                    uploadBtn.attr('disabled', false);
                    uploadBtn.html(recordrtc.uploadaborted + ' ' + fileURLOrError);
                } else {
                    uploadBtn.html(progress);
                }
            });
        }
    });
};

// Get everything set up to start recording.
tinymce_recordrtc.start_recording = function(type, stream) {
    // The options for the recording codecs and bitrates.
    var options = tinymce_recordrtc.select_rec_options(type);
    mediaRecorder = new window.MediaRecorder(stream, options);

    // Initialize MediaRecorder events and start recording.
    mediaRecorder.ondataavailable = tinymce_recordrtc.handle_data_available;
    mediaRecorder.onstop = tinymce_recordrtc.handle_stop;
    mediaRecorder.start(1000); // Capture in 1s chunks. Must be set to work with Firefox.

    // Mute audio, distracting while recording.
    player.attr('muted', true);

    // Set recording timer to the time specified in the settings.
    countdownSeconds = recordrtc.timelimit;
    countdownSeconds++;
    var timerText = recordrtc.stoprecording;
    timerText += ' (<span id="minutes"></span>:<span id="seconds"></span>)';
    startStopBtn.html(timerText);
    tinymce_recordrtc.set_time();
    countdownTicker = window.setInterval(tinymce_recordrtc.set_time, 1000);

    // Make button clickable again, to allow stopping recording.
    startStopBtn.attr('disabled', false);
};

// Stop recording audio/video.
tinymce_recordrtc.stop_recording = function(stream) {
    // Stop recording microphone stream.
    mediaRecorder.stop();

    // Stop each individual MediaTrack.
    var tracks = stream.getTracks();
    for (var i = 0; i < tracks.length; i++) {
        tracks[i].stop();
    }
};

// Upload recorded audio/video to server.
tinymce_recordrtc.upload_to_server = function(type, callback) {
    var xhr = new window.XMLHttpRequest();

    // Get src media of audio/video tag.
    xhr.open('GET', player.attr('src'), true);
    xhr.responseType = 'blob';

    xhr.onload = function() {
        if (xhr.status === 200) { // If src media was successfully retrieved.
            // blob is now the media that the audio/video tag's src pointed to.
            var blob = this.response;

            // Generate filename with random ID and file extension.
            var fileName = (Math.random() * 1000).toString().replace('.', '');
            fileName += (type === 'audio') ? '-audio.ogg' :
                '-video.webm';

            // Create FormData to send to PHP filepicker-upload script.
            var formData = new window.FormData();

            formData.append('upload_file', blob, fileName);

            // Pass FormData to PHP script using XHR.
            var uploadEndpoint = recordrtc.uploadurl;
            tinymce_recordrtc.make_xmlhttprequest(uploadEndpoint, formData, function(progress, responseText) {
                if (progress === 'upload-ended') {
                    callback('ended', window.JSON.parse(responseText).url);
                } else {
                    callback(progress);
                }
            });
        }
    };

    xhr.send();
};

// Handle XHR sending/receiving/status.
tinymce_recordrtc.make_xmlhttprequest = function(url, data, callback) {
    var xhr = new window.XMLHttpRequest();

    xhr.onreadystatechange = function() {
        if ((xhr.readyState === 4) && (xhr.status === 200)) { // When request is finished and successful.
            callback('upload-ended', xhr.responseText);
        } else if (xhr.status === 404) { // When request returns 404 Not Found.
            callback('upload-failed-404');
        }
    };

    xhr.upload.onprogress = function(event) {
        callback(Math.round(event.loaded / event.total * 100) + "% " + recordrtc.uploadprogress);
    };

    xhr.upload.onerror = function(error) {
        callback('upload-failed', error);
    };

    xhr.upload.onabort = function(error) {
        callback('upload-aborted', error);
    };

    // POST FormData to PHP script that handles uploading/saving.
    xhr.open('POST', url);
    xhr.send(data);
};

// Makes 1min and 2s display as 1:02 on timer instead of 1:2, for example.
tinymce_recordrtc.pad = function(val) {
    var valString = val + "";

    if (valString.length < 2) {
        return "0" + valString;
    } else {
        return valString;
    }
};

// Functionality to make recording timer count down.
// Also makes recording stop when time limit is hit.
tinymce_recordrtc.set_time = function() {
    countdownSeconds--;

    startStopBtn.children('span#seconds').html(tinymce_recordrtc.pad(countdownSeconds % 60));
    startStopBtn.children('span#minutes').html(tinymce_recordrtc.pad(window.parseInt(countdownSeconds / 60, 10)));

    if (countdownSeconds === 0) {
        startStopBtn.click();
    }
};

// Generates link to recorded annotation to be inserted.
tinymce_recordrtc.create_annotation = function(type, recording_url) {
    var linkText = window.prompt(recordrtc.annotationprompt,
        recordrtc['annotation:' + type]);

    // Return HTML for annotation link, if user did not press "Cancel".
    if (!linkText) {
        return undefined;
    } else {
        var annotation = '<div><a target="_blank" href="' + recording_url + '">' + linkText + '</a></div>';
        return annotation;
    }
};

// Inserts link to annotation in editor text area.
tinymce_recordrtc.insert_annotation = function(type, recording_url) {
    var annotation = tinymce_recordrtc.create_annotation(type, recording_url);

    // Insert annotation link.
    // If user pressed "Cancel", just go back to main recording screen.
    if (!annotation) {
        uploadBtn.html(recordrtc.attachrecording);
    } else {
        parent.tinymce.activeEditor.execCommand('mceInsertContent', false, annotation);
        parent.tinymce.activeEditor.windowManager.close();
    }
};
