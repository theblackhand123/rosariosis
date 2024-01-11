<?php
/**
 * Functions
 *
 * @package Public Pages
 */

// Register plugin functions to be hooked.
add_action( 'Warehouse.php|header', 'PublicPagesHead' );

add_action( 'Warehouse.php|header_head', 'PublicPagesJSCSS' );

// Triggered function.
function PublicPagesHead( $hook_tag )
{
	global $_ROSARIO;

	if ( $_ROSARIO['page'] !== 'login'
		&& $_ROSARIO['page'] !== 'login public-pages' )
	{
		// Not on login page, end.
		return false;
	}

	if ( ! isset( $_REQUEST['modfunc'] ) )
	{
		$_REQUEST['modfunc'] = false;
	}

	require_once 'plugins/Public_Pages/includes/PublicPages.fnc.php';

	$page = issetVal( $_REQUEST['public-page'] );

	$on_page = PublicPageDo( $page );

	if ( $on_page )
	{
		Warehouse( 'footer' );

		// Do not display Login screen.
		die();
	}

	return true;
}

// Triggered function.
function PublicPagesJSCSS( $hook_tag )
{
	global $_ROSARIO;

	if ( $_ROSARIO['page'] !== 'login' )
	{
		// Not on login page, end.
		return false;
	}

	$page = issetVal( $_REQUEST['public-page'] );

	if ( $page )
	{
		// @since 1.2 add .public-pages CSS class to body.
		$_ROSARIO['page'] .= ' public-pages';
	}

	// Load JS.
	if ( function_exists( 'WarehouseHeaderJS' ) )
	{
		// @since RosarioSIS 6.0.
		WarehouseHeaderJS();
	}
	else
	{
		// @deprecated since 6.0.
		$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

		?>
		<script src="assets/js/jquery.js"></script>
		<script src="assets/js/plugins.min.js?v=<?php echo ROSARIO_VERSION; ?>"></script>
		<script src="assets/js/warehouse.min.js?v=<?php echo ROSARIO_VERSION; ?>"></script>
		<script src="assets/js/jscalendar/lang/calendar-<?php echo file_exists( 'assets/js/jscalendar/lang/calendar-' . $lang_2_chars . '.js' ) ? $lang_2_chars : 'en'; ?>.js"></script>
		<?php
	}

	// Load our plugin CSS. Redefine ajaxLink() & ajaxForm(): no AJAX.
	?>
	<link rel="stylesheet" href="plugins/Public_Pages/css/stylesheet.css?v=10.1" />
	<script>
		var ajaxLink = function(link) {
			window.location = link;

			return false;
		}

		var ajaxPostForm = function(form, submit) {

			return true;
		}
	</script>
	<?php

	return true;
}
