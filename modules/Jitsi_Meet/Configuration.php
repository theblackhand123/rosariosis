<?php
/**
 * Jitsi Meet Configuration
 *
 * @package Jitsi Meet module
 */

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( ! empty( $_REQUEST['values']['config'] )
		&& $_POST['values']
		&& AllowEdit() )
	{
		foreach ( (array) $_REQUEST['values']['config'] as $column => $value )
		{
			// Update config value.
			Config( $column, $value );
		}

		$note[] = button( 'check' ) . '&nbsp;' . dgettext( 'Jitsi_Meet', 'The module configuration has been modified.' );
	}

	// Unset modfunc & values & redirect URL.
	RedirectURL( [ 'modfunc', 'values' ] );
}

if ( ! $_REQUEST['modfunc'] )
{
	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) ) . '" method="POST">';

	DrawHeader( '', SubmitButton() );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	PopTable( 'header', _( 'Configuration' ) );

	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'The domain the Jitsi Meet server runs. Defaults to their free hosted service.' ) . '</i></div>';

	echo '<table class="width-100p"><tr><td>' . TextInput(
		Config( 'JITSI_MEET_DOMAIN' ),
		'values[config][JITSI_MEET_DOMAIN]',
		dgettext( 'Jitsi_Meet', 'Domain' ) . $tooltip,
		'required'
	) . '</td></tr>';

	if ( Config( 'JITSI_MEET_DOMAIN' ) === '8x8.vc' )
	{
		$tooltip = ' <div class="tooltip"><i>' .
			sprintf(
				dgettext( 'Jitsi_Meet', 'Create an account at %s to get the AppID and the JWT.' ),
				'https://jaas.8x8.vc/'
			) . '</i></div>';

		echo '<tr><td>' . TextInput(
			Config( 'JITSI_MEET_JAAS_APP_ID' ),
			'values[config][JITSI_MEET_JAAS_APP_ID]',
			dgettext( 'Jitsi_Meet', 'JaaS AppID' ) . $tooltip,
			'size="50"'
		) . '</td></tr>';

		$jwt_value = Config( 'JITSI_MEET_JAAS_JWT' );

		if ( $jwt_value )
		{
			$jwt_value = [ $jwt_value, mb_substr( $jwt_value, 0, 100 ) . '...' ];
		}

		echo '<tr><td>' . TextInput(
			$jwt_value,
			'values[config][JITSI_MEET_JAAS_JWT]',
			dgettext( 'Jitsi_Meet', 'JWT' ) . $tooltip,
			'size="100"'
		) . '</td></tr>';
	}

	/**
	 * @todo Update params to new Jitsi Meet version, see jitsi.js
	 * @link https://wordpress.org/plugins/webinar-and-video-conference-with-jitsi-meet/
	 **/
	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'The toolbar buttons to display in comma separated format. For more information refer to <a target="_blank" href="https://github.com/jitsi/jitsi-meet/blob/master/interface_config.js#L49">TOOLBAR_BUTTONS</a>.' ) . '</i></div>';

	echo '<tr><td>' . TextInput(
		Config( 'JITSI_MEET_TOOLBAR' ),
		'values[config][JITSI_MEET_TOOLBAR]',
		dgettext( 'Jitsi_Meet', 'Toolbar' ) . $tooltip,
		'size="30"'
	) . '</td></tr>';

	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'The settings available in comma separated format. For more information refer to <a target="_blank" href="https://github.com/jitsi/jitsi-meet/blob/master/interface_config.js#L146">SETTINGS_SECTION</a>.' ) . '</i></div>';

	echo '<tr><td>' . TextInput(
		Config( 'JITSI_MEET_SETTINGS' ),
		'values[config][JITSI_MEET_SETTINGS]',
		dgettext( 'Jitsi_Meet', 'Settings' ) . $tooltip
	) . '</td></tr>';

	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'The width in pixels or percentage of the embedded window.' ) . '</i></div>';

	echo '<tr><td>' . TextInput(
		Config( 'JITSI_MEET_WIDTH' ),
		'values[config][JITSI_MEET_WIDTH]',
		dgettext( 'Jitsi_Meet', 'Width' ) . $tooltip,
		'required'
	) . '</td></tr>';

	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'The height in pixels or percentage of the embedded window.' ) . '</i></div>';

	echo '<tr><td>' . TextInput(
		Config( 'JITSI_MEET_HEIGHT' ),
		'values[config][JITSI_MEET_HEIGHT]',
		dgettext( 'Jitsi_Meet', 'Height' ) . $tooltip,
		'required'
	) . '</td></tr>';

	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'The link for the brand watermark.' ) . '</i></div>';

	echo '<tr><td>' . TextInput(
		Config( 'JITSI_MEET_BRAND_WATERMARK_LINK' ),
		'values[config][JITSI_MEET_BRAND_WATERMARK_LINK]',
		dgettext( 'Jitsi_Meet', 'Brand Watermark Link' ) . $tooltip,
		'size="30"'
	) . '</td></tr>';

	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'Hide/Show the video quality indicator.' ) . '</i></div>';

	echo '<tr><td>' . CheckboxInput(
		Config( 'JITSI_MEET_DISABLE_VIDEO_QUALITY_LABEL' ),
		'values[config][JITSI_MEET_DISABLE_VIDEO_QUALITY_LABEL]',
		dgettext( 'Jitsi_Meet', 'Disable Video Quality Indicator' ) . $tooltip
	) . '</td></tr>';

	echo '</table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton() . '</div>';

	echo '</form>';
}
