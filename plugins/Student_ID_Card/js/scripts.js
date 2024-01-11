/**
 * Javascripts
 * Warning: no jQuery!
 *
 * @package Student ID Card module
 */

// Convert HTML to PNG images, load spinner next to button.
function takeScreenshots(classSel) {
	let photos = document.getElementsByClassName(classSel);
	let spinner = document.querySelector( '.loading' );
	let convertImagesButton = document.getElementById( 'convert-images-button' );
	let photosCount = photos.length;

	// Loading spinner.
	convertImagesButton.disabled = 'disabled';

	spinner.style.visibility = 'visible';

	for (let photo of photos) {
		takeScreenshot(photo, photosCount);
	}
}

// Screenshot the div.
// When screenshots are done (asynchonously), remove spinner, and replace button with Download zip
function takeScreenshot(photo, photosCount) {
	// Check to see if the counter has been initialized
	if ( typeof takeScreenshot.counter == 'undefined' ) {
	    // It has not... perform the initialization
	    takeScreenshot.counter = 0;
	}

	let photoWidth = document.getElementsByClassName('student-id-card')[0].offsetWidth;
	let photoHeight = document.getElementsByClassName('student-id-card')[0].offsetHeight;

	// Use dom-to-image.min.js
	domtoimage.toPng(photo, { width: photoWidth, height: photoHeight}).then(function (dataUrl) {
		var img = new Image();
		img.src = dataUrl;
		// Add canvas to next .output
		document.querySelector('#' + photo.id + ' + .output').appendChild(img);
		// Remove HTML
		photo.remove();

		takeScreenshot.counter++;

		if ( takeScreenshot.counter === photosCount ) {
			let spinner = document.querySelector( '.loading' );
			let convertImagesButton = document.getElementById( 'convert-images-button' );
			let downloadZipButton = document.getElementById( 'download-zip-button' );

			// Remove loading spinner & Replace button with the one to download as zip.
			spinner.remove();

			convertImagesButton.classList.add( 'hide' );
			downloadZipButton.classList.remove( 'hide' );
		}
	}).catch(function (error) {
		console.error('domtoimage oops, something went wrong!', error);
	});
}

/* Download all .output img in a zip file */
function downloadZip(sel) {
	// Use jszip.min.js
	let zip = new JSZip();
	let zipFilename = 'Student_ID_Cards.zip';
	let images = document.querySelectorAll(sel);
	// console.log(images);

	images.forEach(function(image){
		// Get base64 data from img.
		let base64data = image.src.replace("data:image/png;base64,", "");

		let filename = 'student-id-card-' + image.parentNode.id.replace(/\D/g, "") + '.png';
		zip.file(filename, base64data, { base64: true });
	});

	zip.generateAsync({ type: 'blob' }).then(function(content) {
		// Use FileSave.min.js
		saveAs(content, zipFilename);
	});
}
