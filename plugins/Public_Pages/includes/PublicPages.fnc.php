<?php
/**
 * Public Pages plugin functions
 *
 * @package Public Pages plugin
 */


function PublicPageDo( $page )
{
	global $error;

	$page = GetPublicPages( $page ) ? $page : '';

	// Build menu.
	echo PublicPagesMenu( $page );

	if ( ! $page )
	{
		return false;
	}

	echo PublicPagesSessionSchool( $page );

	echo PublicPageHead( $page );

	$public_page_function = 'PublicPage' . ucfirst( $page );

	if ( function_exists( $public_page_function ) )
	{
		$public_page_function();
	}
	else
	{
		$error[] = dgettext( 'Public_Pages', 'Page not found.' );

		echo ErrorMessage( $error );
	}

	echo PublicPageFoot( $page );

	return true;
}


if ( ! function_exists( 'PublicPagesSessionSchool' ) )
{
	function PublicPagesSessionSchool( $page )
	{
		global $_ROSARIO;

		// Not allowed to edit, obviously.
		$_ROSARIO['allow_edit'] = false;

		// Ever...
		$_ROSARIO['AllowEdit'] = [];

		//$_SESSION['USERNAME'] = 'PublicPages';
		//$_SESSION['STAFF_ID'] = '-1';

		// First school only.
		$_SESSION['UserSchool'] = DBGetOne( "SELECT ID
			FROM schools
			WHERE SYEAR='" . UserSyear() . "'
			ORDER BY ID
			LIMIT 1" );

		// For Menu.php to work.
		$_ROSARIO['User'][1]['PROFILE'] = 'admin';
		$_ROSARIO['User'][1]['PROFILE_ID'] = '1';

		// For ProgramTitle() to work.
		$_ROSARIO['User'][1]['SYEAR'] = UserSyear();

		if ( ! isset( $_REQUEST['modfunc'] ) )
		{
			$_REQUEST['modfunc'] = false;
		}

		return '';
	}
}

if ( ! function_exists( 'GetPublicPages' ) )
{
	function GetPublicPages( $page = '' )
	{
		$pages = GetPublicPagesAll();

		$pages_config = Config( 'PUBLIC_PAGES' );

		$pages_config = explode( '||', trim( $pages_config, '||' ) );

		// Only keep pages  which are in config.
		$pages = array_intersect_key( $pages, array_flip( $pages_config ) );

		if ( $page )
		{
			return isset( $pages[ $page ] ) ? $pages[ $page ] : '';
		}

		return $pages;
	}
}


if ( ! function_exists( 'GetPublicPagesAll' ) )
{
	function GetPublicPagesAll()
	{
		global $RosarioModules;

		// Add School, Calendar, Courses, & Marking Periods pages.
		$pages = [
			'school' => _( 'School' ),
			'calendar' => _( 'Calendar' ),
			'markingperiods' => _( 'Marking Periods' ),
			'courses' => _( 'Courses' ),
			// 'teachers' => _( 'Teachers' ),
		];

		if ( ! $RosarioModules['Scheduling'] )
		{
			unset( $pages['courses'] );
		}

		return $pages;
	}
}


if ( ! function_exists( 'PublicPagesMenu' ) )
{
	function PublicPagesMenu( $page )
	{
		if ( $page === 'calendar' && $_REQUEST['modfunc'] === 'detail' )
		{
			return '';
		}

		$pages = GetPublicPages();

		$url = '?public-page=';

		$menu_items = [];

		// Add Login to menu items.
		$menu_items[] = '<a href="index.php?public-page=login"' . ( $page ? '' : ' class="current"' ) . '>' .
			_( 'Login' ) . '</a>';

		foreach ( $pages as $id => $label )
		{
			$class = $page === $id ? ' class="current"' : '';

			$menu_items[] = '<a href="' . ( function_exists( 'URLEscape' ) ?
				URLEscape( $url . $id ) :
				_myURLEncode( $url . $id ) ) . '"' . $class . '>' . $label . '</a>';
		}

		$menu_html = '<nav role="navigation" class="header2 public-pages-menu"><ul><li>' .
			implode( '</li><li>', $menu_items ) . '</li></ul></nav>';

		return $menu_html;
	}
}

if ( ! function_exists( 'PublicPageHead' ) )
{
	function PublicPageHead( $page )
	{
		return '<div class="public-pages-module public-pages-' . $page . '">';
	}
}

if ( ! function_exists( 'PublicPageFoot' ) )
{
	function PublicPageFoot( $page )
	{
		return '</div>';
	}
}


if ( ! function_exists( 'PublicPageSchool' ) )
{
	function PublicPageSchool()
	{
		echo PublicPageGetModuleHTML( 'school', 'School_Setup/Schools.php' );
	}
}


if ( ! function_exists( 'PublicPageCalendar' ) )
{
	function PublicPageCalendar()
	{
		echo PublicPageGetModuleHTML( 'calendar', 'School_Setup/Calendar.php' );
	}
}


if ( ! function_exists( 'PublicPageMarkingperiods' ) )
{
	function PublicPageMarkingperiods()
	{
		echo PublicPageGetModuleHTML( 'markingperiods', 'School_Setup/MarkingPeriods.php' );
	}
}


if ( ! function_exists( 'PublicPageCourses' ) )
{
	function PublicPageCourses()
	{
		echo PublicPageGetModuleHTML( 'courses', 'Scheduling/Courses.php' );
	}
}


function PublicPageGetModuleHTML( $page, $modname )
{
	global $_ROSARIO,
		// For extra School Fields to show up.
		$field,
		$value,
		$error,
		$note,
		$warning;

	if ( empty( $modname )
		|| mb_substr( $modname, -4, 4 ) !== '.php'
		|| mb_strpos( $modname, '..' ) !== false
		|| ! is_file( 'modules/' . $modname ) )
	{
		return '';
	}

	$_REQUEST['modname'] = $modname;

	// Remove $_REQUEST['public-page'] var for PreparePHPSelf.
	unset( $_REQUEST['public-page'] );

	ob_start();

	require_once 'modules/' . $modname;

	$html = ob_get_clean();

	// Replace links.
	$html = str_replace(
		[
			'Modules.php?modname=' . $modname,
			'Modules.php?modname=' . urlencode( $modname ),
			mb_substr( json_encode( 'Modules.php?modname=' . $modname ), 1, -1 ),
		],
		'index.php?public-page=' . $page,
		$html
	);

	return $html;
}
