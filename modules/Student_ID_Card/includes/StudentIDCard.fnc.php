<?php
/**
 * Student ID Card functions
 *
 * @package Student ID Card module
 */

/**
 * Save Custom CSS options (JSON)
 *
 * @param array $custom_css Custom CSS options.
 */
function StudentIDCardSaveCustomCSS( $custom_css )
{
	return ProgramConfig( 'student_id_card', 'custom_css', json_encode( $custom_css ) );
}

if ( ! function_exists( 'StudentIDCardGetCustomCSS' ) ) :
/**
 * Get Custom CSS options, decode them, and set default values
 * Format combined values such as padding (add px unit).
 *
 * Function can be redefined in your custom module or plugin.
 *
 * @return array Formatted Custom CSS options
 */
function StudentIDCardGetCustomCSS()
{
	$custom_css = ProgramConfig( 'student_id_card', 'custom_css' );

	$custom_css = json_decode( (string) $custom_css, true );

	if ( empty( $custom_css['background_image'] )
		|| ! file_exists( $custom_css['background_image'] ) )
	{
		$custom_css['background_image'] = 'modules/Student_ID_Card/img/student-id-card-background.jpg';
	}

	$custom_css['card_top_bottom_padding'] = (int) issetVal( $custom_css['card_top_bottom_padding'], '46' );
	$custom_css['card_left_right_padding'] = (int) issetVal( $custom_css['card_left_right_padding'], '13' );

	$custom_css['text_top_bottom_padding'] = (int) issetVal( $custom_css['text_top_bottom_padding'], '18' );
	$custom_css['text_left_right_padding'] = (int) issetVal( $custom_css['text_left_right_padding'], '13' );

	$custom_css['photo_top_bottom_padding'] = (int) issetVal( $custom_css['photo_top_bottom_padding'], '32' );
	$custom_css['photo_left_right_padding'] = (int) issetVal( $custom_css['photo_left_right_padding'], '13' );

	if ( empty( $custom_css['photo_float'] )
		|| ! in_array( $custom_css['photo_float'], [ 'left', 'right', 'none' ] ) )
	{
		$custom_css['photo_float'] = 'left';
	}

	$custom_css['photo_max_width'] = (int) issetVal( $custom_css['photo_max_width'], '132' );

	$custom_css['photo_max_width_px'] = $custom_css['photo_max_width'] . 'px';

	$custom_css['card_padding'] = $custom_css['card_top_bottom_padding'] . 'px ' . $custom_css['card_left_right_padding'] . 'px';

	$custom_css['text_padding'] = $custom_css['text_top_bottom_padding'] . 'px ' . $custom_css['text_left_right_padding'] . 'px';

	$custom_css['photo_padding'] = $custom_css['photo_top_bottom_padding'] . 'px ' . $custom_css['photo_left_right_padding'] . 'px';

	return $custom_css;
}
endif;

/**
 * Get custom CSS variables --my-var: my-value
 *
 * @param array $custom_css Formatted custom CSS options.
 *
 * @return array Custom CSS variables
 */
function StudentIDCardGetCustomCSSVariables( $custom_css )
{
	$custom_css_vars = [];

	foreach ( $custom_css as $custom_css_var => $custom_css_val )
	{
		if ( file_exists( $custom_css_val ) )
		{
			// Fix background image in cache, add dynamic param to URL to force reload.
			$custom_css_val = 'url(../../../' . $custom_css_val . '?t=' . time() . ')';
		}

		$custom_css_vars[ '--' . $custom_css_var ] = $custom_css_val;
	}

	return $custom_css_vars;
}

if ( ! function_exists( 'StudentIDCardOptionsFormHTML' ) ) :
/**
 * Options form HTML & inputs (custom CSS)
 *
 * Function can be redefined in your custom module or plugin.
 *
 * @param array $custom_css Formatted custom CSS options.
 *
 * @return string Options form HTML & inputs (custom CSS)
 */
function StudentIDCardOptionsFormHTML( $custom_css )
{
	// Upload background image.
	$html = '<tr class="st"><td>' .
		'<div class="student-id-card"></div><br />' .
	FileInput(
		'background_image',
		dgettext( 'Student_ID_Card', 'Background Image' ) . ' (.jpg, .png, .gif)',
		'accept="image/*"'
	) . '</td></tr>';

	// Card Padding.
	$html .= '<tr class="st"><td><fieldset><legend>' .
		dgettext( 'Student_ID_Card', 'Card Padding' ) . '</legend><table><tr class="st"><td>' .
	TextInput(
		$custom_css['card_top_bottom_padding'],
		'custom_css[card_top_bottom_padding]',
		dgettext( 'Student_ID_Card', 'Top and bottom (pixels)' ),
		'type="number" min="0" max="243"',
		false
	) . '</td><td>' .
	TextInput(
		$custom_css['card_left_right_padding'],
		'custom_css[card_left_right_padding]',
		dgettext( 'Student_ID_Card', 'Left and right (pixels)' ),
		'type="number" min="0" max="243"',
		false
	) . '</td></tr></table></fieldset></td></tr>';

	// Text Padding.
	$html .= '<tr class="st"><td><fieldset><legend>' .
		dgettext( 'Student_ID_Card', 'Text Padding' ) . '</legend><table><tr class="st"><td>' .
	TextInput(
		$custom_css['text_top_bottom_padding'],
		'custom_css[text_top_bottom_padding]',
		dgettext( 'Student_ID_Card', 'Top and bottom (pixels)' ),
		'type="number" min="0" max="243"',
		false
	) . '</td><td>' .
	TextInput(
		$custom_css['text_left_right_padding'],
		'custom_css[text_left_right_padding]',
		dgettext( 'Student_ID_Card', 'Left and right (pixels)' ),
		'type="number" min="0" max="243"',
		false
	) . '</td></tr></table></fieldset></td></tr>';

	// Photo Padding.
	$html .= '<tr class="st"><td><fieldset><legend>' .
		dgettext( 'Student_ID_Card', 'Photo Padding' ) . '</legend><table><tr class="st"><td>' .
	TextInput(
		$custom_css['photo_top_bottom_padding'],
		'custom_css[photo_top_bottom_padding]',
		dgettext( 'Student_ID_Card', 'Top and bottom (pixels)' ),
		'type="number" min="0" max="243"',
		false
	) . '</td><td>' .
	TextInput(
		$custom_css['photo_left_right_padding'],
		'custom_css[photo_left_right_padding]',
		dgettext( 'Student_ID_Card', 'Left and right (pixels)' ),
		'type="number" min="0" max="243"',
		false
	) . '</td></tr></table></fieldset></td></tr>';

	$float_options = [
		'left' => dgettext( 'Student_ID_Card', 'Left' ),
		'right' => dgettext( 'Student_ID_Card', 'Right' ),
		'none' => _( 'None' ),
	];

	// Photo Position & Max. Width.
	$html .= '<tr class="st"><td><fieldset><legend>' .
		dgettext( 'Student_ID_Card', 'Photo' ) . '</legend><table><tr class="st"><td>' .
	TextInput(
		$custom_css['photo_max_width'],
		'custom_css[photo_max_width]',
		dgettext( 'Student_ID_Card', 'Max. width (pixels)' ),
		'type="number" min="0" max="243"',
		false
	) . '</td><td>' .
	SelectInput(
		$custom_css['photo_float'],
		'custom_css[photo_float]',
		dgettext( 'Student_ID_Card', 'Position' ),
		$float_options,
		false,
		'',
		false
	) . '</td></tr></table></fieldset></td></tr>';

	return $html;
}
endif;


if ( ! function_exists( 'StudentIDCardHTML' ) ) :
/**
 * Student ID Card HTML
 *
 * Function can be redefined in your custom module or plugin.
 *
 * @param int    $student_id           Student ID.
 * @param string $student_id_card_text Student ID Card text (with substitutions done).
 *
 * @return string Student ID Card HTML
 */
function StudentIDCardHTML( $student_id, $student_id_card_text )
{
	global $StudentPicturesPath;

	// @since 9.0 Fix Improper Access Control security issue: add random string to photo file name.
	$picture_path = (array) glob( $StudentPicturesPath . '*/' . $student_id . '.*jpg' );

	$picture_path = end( $picture_path );

	$student_photo_img = '';

	if ( ! $picture_path )
	{
		$picture_path = 'modules/Student_ID_Card/img/student-photo-placeholder.jpg';
	}

	$student_photo_img = '<img src="' . URLEscape( $picture_path ) . '" />';

	$html = '<div id="student-id-card-' . $student_id . '" class="student-id-card">';

	$html .= '<div class="student-id-card-photo">' . $student_photo_img . '</div>';

	$html .= '<div class="student-id-card-text">' . $student_id_card_text . '</div>';

	$html .= '</div><div id="output-' . $student_id . '" class="output"></div>';

	return $html;
}
endif;
