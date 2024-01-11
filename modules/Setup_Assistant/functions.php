<?php
/**
 * Setup Assistant functions
 *
 * @package Setup Assistant plugin
 */

add_action( 'misc/Portal.php|portal_alerts', 'SetupAssistantDo' );

function SetupAssistantDo()
{
	require_once 'plugins/Setup_Assistant/includes/SetupAssistant.fnc.php';

	$user_id = User( 'STAFF_ID' ) ? User( 'STAFF_ID' ) : ( UserStudentID() * -1 );

	if ( ! SetupAssistantActive( $user_id ) )
	{
		return;
	}

	if ( ! empty( $_REQUEST['step_id'] ) )
	{
		SetupAssistantCompleteStep( $user_id, $_REQUEST['step_id'] );

		// Step was completed by AJAX. No Output.
		Warehouse( 'footer' );

		exit;
	}

	SetupAssistantOutput( $user_id );
}


if ( ! empty( $_REQUEST['sa_help'] ) )
{
	SetupAssistantShowHelp();
}

function SetupAssistantShowHelp()
{
	?>
	<script>
		window.setTimeout(function() {
			showHelp();
		}, 1500);
	</script>
	<?php
}
