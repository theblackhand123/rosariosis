// TinyMCE recordrtc library functions for checking browser compatibility.
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
/** global: navigator */
/** global: tinymce_recordrtc */
/** global: tinyMCEPopup */

var tinymce_recordrtc = tinymce_recordrtc || {};

// Show alert and close plugin if browser does not support WebRTC at all.
tinymce_recordrtc.check_has_gum = function() {
    if (!(navigator.mediaDevices && window.MediaRecorder)) {
        tinymce_recordrtc.show_alert(recordrtc.nowebrtc, function() {
            parent.tinymce.activeEditor.windowManager.close();
        });
    }
};

// Notify and redirect user if plugin is used from insecure location.
tinymce_recordrtc.check_secure = function() {
    var isSecureOrigin = (window.location.protocol === 'https:') ||
        (window.location.host.indexOf('localhost') !== -1);

    // @link https://stackoverflow.com/questions/4565112/javascript-how-to-find-out-if-the-user-browser-is-chrome/13348618#13348618
    var isChromium = window.chrome,
        winNav = window.navigator,
        vendorName = winNav.vendor,
        isOpera = typeof window.opr !== "undefined",
        isIEedge = winNav.userAgent.indexOf("Edge") > -1,
        isIOSChrome = winNav.userAgent.match("CriOS");

    if (!isSecureOrigin && (isOpera || isIOSChrome || (
      isChromium !== null &&
      typeof isChromium !== "undefined" &&
      vendorName === "Google Inc." &&
      isOpera === false &&
      isIEedge === false
    ))) {
        tinymce_recordrtc.show_alert(recordrtc.gumsecurity, function() {
            parent.tinymce.activeEditor.windowManager.close();
        });
    } else if (!isSecureOrigin) {
        $('div#alert-danger').parent().parent().removeClass('hide');
    }
};
