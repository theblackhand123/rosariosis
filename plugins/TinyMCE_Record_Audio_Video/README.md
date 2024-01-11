# TinyMCE Record Audio Video plugin

![screenshot](https://gitlab.com/francoisjacquet/TinyMCE_Record_Audio_Video/raw/master/screenshot.png?inline=false)

https://www.rosariosis.org/plugins/tinymce-record-audio-video/

Version 10.1 - June, 2023

Author François Jacquet

Based on [RecordRTC TinyMCE plugin for Moodle](https://github.com/blindsidenetworks/moodle-tinymce_recordrtc/)

License Gnu GPL v2

## Description

TinyMCE Record Audio Video plugin for RosarioSIS. Add audio and video annotations to text, anywhere TinyMCE (rich text editor) is present. This plugin adds 2 buttons for recording audio or video (with audio) to the editor's toolbar. Using WebRTC technologies, all recording is done instantly in the browser. After recording, users can embed the annotation directly into the text they are currently editing. The recording will appear as an audio or video player in the published writing.

Translated in [French](https://www.rosariosis.org/fr/plugins/tinymce-record-audio-video/), [Spanish](https://www.rosariosis.org/es/plugins/tinymce-record-audio-video/) and Portuguese (Brazil).

### Common problems

- If nothing is displayed in the popup after clicking one of the buttons in the TinyMCE toolbar, it is likely an issue with the `X-Frame-Options` header. To fix this, change the server configuration to set the header to `SAMEORIGIN`. Also, make sure that the header is not set twice as the browser will default the value to `DENY` (sometimes individual web apps also set the header, or there is some conflicting server configuration)
- The default maximum size of uploads in PHP is very small, it is recommended to set the `upload_max_filesize` setting to `40M` and the `post_max_size` setting to `50M` for a time limit of 2:00 to avoid getting an alert while recording
- The filesize of recorded video for Firefox will likely be twice that of other browsers, even with the same settings; this is expected as it uses a different writing library for recording video. The audio filesize should be similar across all browsers


## Content

Plugin configuration:

- Set the recording time limit (seconds), to control maximum recording size


## Install

Copy the `TinyMCE_Record_Audio_Video/` folder (if named `TinyMCE_Record_Audio_Video-master`, rename it) and its content inside the `plugins/` folder of RosarioSIS.

Go to _School > Configuration > Plugins_ and click "Activate".

Requires: RosarioSIS 6.0+.

