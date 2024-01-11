<?php
/**
 * Setup Assistant functions
 *
 * @package Setup Assistant plugin
 */


function SetupAssistantOutput( $user_id )
{
	echo '<br />';

	PopTable( 'header', dgettext( 'Setup_Assistant', 'Setup Assistant' ) );

	$steps = SetupAssistantProfileSteps( User( 'PROFILE' ) );

	$complete_steps = SetupAssistantCompletedSteps( $user_id );

	$steps_html = [];

	foreach ( $steps as $step )
	{
		if ( ! is_array( $step ) )
		{
			echo '<ul class="setup-assistant-steps"><li>' .
				implode( '</li><li>', $steps_html ) . '</li></ul>';

			// Step is module title.
			echo $step;

			$steps_html = [];

			continue;
		}

		$step['complete'] = in_array( $step['id'], $complete_steps );

		$step_html = SetupAssistantStepHTML( $step );

		if ( $step_html )
		{
			$steps_html[] = $step_html;
		}
	}

	if ( $steps_html )
	{
		echo '<ul class="setup-assistant-steps"><li>' .
			implode( '</li><li>', $steps_html ) . '</li></ul>';
	}

	echo SetupAssistantDeactivateForm( $user_id );

	PopTable( 'footer' );
}

function SetupAssistantActive( $user_id )
{
	if ( ! $user_id )
	{
		return false;
	}

	if ( ! empty( $_REQUEST['sa_deactivate'] ) )
	{
		// Save User config.
		ProgramUserConfig( 'SetupAssistant', $user_id, [ 'inactive' => 'Y' ] );

		return false;
	}

	$inactive_profile = ProgramConfig( 'setup_assistant', 'INACTIVE_' . User( 'PROFILE' ) );

	if ( $inactive_profile )
	{
		return false;
	}

	$user_config = ProgramUserConfig( 'SetupAssistant', $user_id );

	$inactive = ! empty( $user_config['inactive'] );

	return ! $inactive;
}

function SetupAssistantDeactivateForm( $user_id )
{
	$form = '<form method="POST" class="center">';

	$form .= '<input type="hidden" name="sa_deactivate" value="Y" />';

	$form .= Buttons( _( 'Done.' ) );

	$form .= '</form>';

	return $form;
}

/**
 * Get Steps
 *
 * @global $_ROSARIO['Setup_Assistant']['steps'] Add your own steps.
 */
function SetupAssistantProfileSteps( $profile )
{
	global $_ROSARIO,
		$locale;

	require_once 'plugins/Setup_Assistant/includes/SetupAssistantSteps.fnc.php';

	if ( !isset( $_ROSARIO['Setup_Assistant']['steps'] )
		|| ! is_array( $_ROSARIO['Setup_Assistant']['steps'] ) )
	{
		$_ROSARIO['Setup_Assistant']['steps'] = [];
	}

	$steps = [];

	// General steps for all profiles.
	$steps[] = [
		'id' => 'inline_help',
		'onclick' => 'toggleHelp();',
		'text' => dgettext( 'Setup_Assistant', 'Consult the inline Help' ),
	];

	$steps[] = [
		'id' => 'print_handbook',
		'link' => '"Help.php" target="_blank"',
		//'onclick' => SetupAssistantStepCompleteOnClick( 'print_handbook' ),
		'text' => dgettext( 'Setup_Assistant', 'Print my Handbook' ),
	];

	$helpful_tips_pdf_url = 'https://www.rosariosis.org/wp-content/uploads/2015/01/RosarioSIS_Helpful_Tips.pdf';

	if ( mb_strpos( $locale, 'fr' ) === 0 )
	{
		$helpful_tips_pdf_url = 'https://www.rosariosis.org/wp-content/uploads/2015/01/RosarioSIS_Conseils_Pratiques.pdf';
	}
	elseif ( mb_strpos( $locale, 'es' ) === 0 )
	{
		$helpful_tips_pdf_url = 'https://www.rosariosis.org/wp-content/uploads/2015/01/RosarioSIS_Consejos_Utiles.pdf';
	}

	if ( User( 'PROFILE' ) === 'admin' )
	{
		$steps[] = [
			'id' => 'get_helpful_tips_pdf',
			'link' => '"' . $helpful_tips_pdf_url . '" target="_blank"',
			'text' => dgettext( 'Setup_Assistant', 'Get the Helpful Tips PDF' ),
		];
	}

	$steps_module = SetupAssistantSchoolSetupSteps( $profile );

	$steps = array_merge( $steps, $steps_module );

	$steps_module = SetupAssistantGradesSteps( $profile );

	$steps = array_merge( $steps, $steps_module );

	$steps_module = SetupAssistantAttendanceSteps( $profile );

	$steps = array_merge( $steps, $steps_module );

	$steps_module = SetupAssistantStudentsSteps( $profile );

	$steps = array_merge( $steps, $steps_module );

	$steps_module = SetupAssistantUsersSteps( $profile );

	$steps = array_merge( $steps, $steps_module );

	$steps_module = SetupAssistantSchedulingSteps( $profile );

	$steps = array_merge( $steps, $steps_module );

	$_ROSARIO['Setup_Assistant']['steps'] = array_merge( $steps, $_ROSARIO['Setup_Assistant']['steps'] );

	return $_ROSARIO['Setup_Assistant']['steps'];
}


function SetupAssistantStepHTML( $step )
{
	global $locale;

	static $js_included = false;

	if ( ! $js_included )
	{
		SetupAssistantJSCSS();

		$js_included = true;
	}

	$link = 'javascript:void(0);';

	if ( ! empty( $step['link'] ) )
	{
		$link = $step['link'];
	}

	if ( isset( $step['modname'] )
		&& AllowUse( $step['modname'] ) )
	{
		$link = 'Modules.php?modname=' . $step['modname'];
	}

	if ( ! empty( $step['help'] ) )
	{
		$link .= '&sa_help=1';
	}

	if ( strpos( $link, '"' ) !== 0 )
	{
		if ( $link !== 'javascript:void(0);' )
		{
			$link = ( function_exists( 'URLEscape' ) ?
				URLEscape( $link ) : _myURLEncode( $link ) );
		}

		$link = '"' . $link . '"';
	}

	if ( ! empty( $step['onclick'] ) )
	{
		$link .= ' onclick="' . ( function_exists( 'AttrEscape' ) ?
			AttrEscape( $step['onclick'] ) : htmlspecialchars( $step['onclick'], ENT_QUOTES ) ) . '"';
	}

	$input_id = GetInputID( 'check_' . $step['id'] );

	$onclick_js = 'setupAssistantCompleteStep(' . json_encode( $step['id'] ) . ');';

	$step_html = button( 'check', '', '', 'check-button' ) .
		' <label for="' . $input_id . '" class="a11y-hidden">' . _( 'Completed' ) . '</label>
		<input type="checkbox" id="' . $input_id .
		'" value="Y" onclick="' . ( function_exists( 'AttrEscape' ) ?
			AttrEscape( $onclick_js ) : htmlspecialchars( $onclick_js, ENT_QUOTES ) ) .
		'" autocomplete="off" /> ';


	$step_html .= '<a href=' . $link . '>' . $step['text'] . '</a>';

	if ( ! empty( $step['quick_setup_guide'] ) )
	{
		$lang_2_chars = '';

		if ( strpos( $locale, 'fr' ) === 0 )
		{
			$lang_2_chars = substr( $locale, 0, 2 ) . '/';
		}

		$quick_setup_guide_url = 'https://www.rosariosis.org/' . $lang_2_chars . 'quick-setup-guide/' .
			$step['quick_setup_guide'];

		$step_html .= ' <a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( $quick_setup_guide_url ) :
			_myURLEncode( $quick_setup_guide_url ) ) . '" target="_blank" title="' .
			dgettext( 'Setup_Assistant', 'Quick Setup Guide' ) .
			'">' . button( 'help' ) . '</a>';
	}

	$class = 'setup-assistant-step';

	if ( ! empty( $step['complete'] ) )
	{
		$class .= ' complete';
	}

	$step_html = '<div class="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $class ) : htmlspecialchars( $class, ENT_QUOTES ) ) . '" id="' . $step['id'] . '">' . $step_html . '</div>';

	return $step_html;
}

function SetupAssistantJSCSS()
{
	?>
	<script>
		var setupAssistantCompleteStep = function( stepId ) {
			var complete_link = document.createElement("a");
			complete_link.href = 'Modules.php?modname=misc/Portal.php&step_id=' + stepId;
			complete_link.target = 'none';

			ajaxLink( complete_link );

			// Add 'complete' class to step.
			$( '.setup-assistant-step#' + stepId ).addClass( 'complete' );
		};
	</script>
	<style>
		.setup-assistant-steps {
			list-style-type: decimal;
		}
		/*.setup-assistant-step a {
			font-size: larger;
		}*/
		.setup-assistant-step a {
			line-height: 20px;
		}
		.setup-assistant-step .check-button {
			vertical-align: text-bottom;
			display: none;
		}
		.setup-assistant-step input[type="checkbox"] {
			margin-right: 2px;
		}
		.setup-assistant-step.complete input[type="checkbox"] {
			display: none;
		}
		.setup-assistant-step.complete .check-button {
			display: inline-block;
		}

		.setup-assistant-step.complete a {
			text-decoration: line-through;
		}
	</style>
	<?php
}

function SetupAssistantCompletedSteps( $user_id )
{
	$completed_steps = (array) ProgramUserConfig( 'SetupAssistant', $user_id );

	return array_keys( $completed_steps );
}

function SetupAssistantCompleteStep( $user_id, $step_id )
{
	$completed_steps = SetupAssistantCompletedSteps( $user_id );

	if ( in_array( $step_id, $completed_steps ) )
	{
		return false;
	}

	ProgramUserConfig( 'SetupAssistant', $user_id, [ $step_id => 'Y' ] );

	return true;
}

function SetupAssistantStepCompleteOnClick( $step_id )
{
	return 'setupAssistantCompleteStep(\'' . $step_id . '\');';
}

function SetupAssistantStepsModuleTitle( $module_dir )
{
	global $_ROSARIO;

	if ( empty( $_ROSARIO['Menu'] ) )
	{
		require_once 'Menu.php';
	}

	if ( empty( $_ROSARIO['Menu'][ $module_dir ]['title'] ) )
	{
		return '';
	}

	$title = $_ROSARIO['Menu'][ $module_dir ]['title'];

	return '<h3 class="dashboard-module-title">
			<span class="module-icon ' . $module_dir . '"></span> ' . $title . '</h3>';
}
