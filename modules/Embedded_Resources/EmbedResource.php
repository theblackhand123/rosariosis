<?php
/**
 * Embed Resource
 *
 * @package Embedded Resources module
 */

if ( ! empty( $_REQUEST['id'] ) )
{
	$embedded_resource_link = DBGetOne( "SELECT LINK
		FROM resources_embedded
		WHERE ID='" . (int) $_REQUEST['id'] . "'" );

	if ( $embedded_resource_link
		&& filter_var( $embedded_resource_link, FILTER_VALIDATE_URL ) !== false )
	{
		$embedded_resource_title = DBGetOne( "SELECT TITLE
			FROM resources_embedded
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		// Hide title, will still serve for browser tab title and accessibility.
		echo '<h2 class="a11y-hidden">' . $embedded_resource_title . '</h2>';

		// Full screen iframe
		echo '<iframe border="0" width="100%" style="padding-top: 12px; height: calc(100vh - 64px);" src="' .
			URLEscape( $embedded_resource_link ) . '"></iframe>';
	}
}
