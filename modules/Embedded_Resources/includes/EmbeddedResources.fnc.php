<?php
/**
 * Embedded Resources functions
 *
 * @package Embedded Resources module
 */

/**
 * Make Text Input
 *
 * DBGet() callback
 *
 * @param string $value
 * @param string $column
 *
 * @return string Text Input
 */
function EmbeddedResourcesMakeTextInput( $value, $column = 'TITLE' )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	if ( $column === 'LINK' )
	{
		$extra = 'size="32" maxlength="1000"';
	}

	if ( $column === 'TITLE' )
	{
		$extra = 'maxlength="30"';
	}

	if ( $id !== 'new' )
	{
		$extra .= ' required';
	}

	return TextInput( $value, 'values[' . $id . '][' . $column . ']', '', $extra );
}

/**
 * Make Link
 *
 * DBGet() callback
 *
 * @uses EmbeddedResourcesMakeTextInput()
 *
 * @param string $value
 * @param string $column
 *
 * @return string Clickable link or Link + Text Input
 */
function EmbeddedResourcesMakeLink( $value, $column = 'LINK' )
{
	if ( ! empty( $_REQUEST['LO_save'] ) )
	{
		// Export list.
		return $value;
	}

	if ( AllowEdit() )
	{
		if ( $value )
		{
			return '<div style="display:table-cell;"><a href="' . URLEscape( $value ) . '" target="_blank">' .
				_( 'Link' ) . '</a>&nbsp;</div>
				<div style="display:table-cell;">' . EmbeddedResourcesMakeTextInput( $value, $column ) . '</div>';
		}

		return EmbeddedResourcesMakeTextInput( $value, $column );
	}

	if ( ! $value )
	{
		return $value;
	}

	// Truncate links > 100 chars.
	$truncated_link = $value;

	if ( mb_strlen( $truncated_link ) > 100 )
	{
		$separator = '/.../';
		$separator_length = mb_strlen( $separator );
		$max_length = 100 - $separator_length;
		$start = (int) ( $max_length / 2 );
		$trunc = mb_strlen( $truncated_link ) - $max_length;
		$truncated_link = substr_replace( $truncated_link, $separator, $start, $trunc );
	}

	return '<a href="' . URLEscape( $value ) . '" target="_blank">' . $truncated_link . '</a>';
}

/**
 * Limit to Grade Levels (all schools) field
 *
 * DBGet() callback
 *
 * @param string $value
 * @param string $column
 *
 * @return string Limit to Grade Levels HTML field
 */
function EmbeddedResourcesLimitToGradeLevels( $value, $column = 'PUBLISHED_GRADE_LEVELS' )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	$grade_levels_RET = DBGet( "SELECT ID,TITLE FROM school_gradelevels
		ORDER BY SCHOOL_ID,SORT_ORDER IS NULL,SORT_ORDER" );

	$grade_level_options = [];

	foreach ( (array) $grade_levels_RET as $grade_level )
	{
		$grade_level_options[ $grade_level['ID'] ] = $grade_level['TITLE'];
	}

	// @since RosarioSIS 10.7 Use Select2 input instead of Chosen, fix overflow issue.
	$select_input_function = function_exists( 'Select2Input' ) ? 'Select2Input' : 'SelectInput';

	$value_array = explode( ',', trim( (string) $value, ',' ) );

	// Fix responsive rt td too large.
	return '<div id="divVisibleTo' . $id . '" class="rt2colorBox">' . $select_input_function(
		$value_array,
		'values[' . $id . '][PUBLISHED_GRADE_LEVELS][]',
		'',
		$grade_level_options,
		'multiple_save_NA', // Save when none selected, add hidden empty input
		'multiple style="width: 240px"', // Multiple select inputs.
		false
	) . '</div>';
}

/**
 * Reload left menu so Resource (dis)appears
 *
 * @return bool True
 */
function EmbeddedResourcesReloadMenu()
{
	?>
	<script>ajaxLink('Side.php');</script>
	<?php

	return true;
}
