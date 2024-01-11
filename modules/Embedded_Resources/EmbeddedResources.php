<?php
/**
 * Embedded Resources
 *
 * @package Embedded Resources module
 */

require_once 'modules/Embedded_Resources/includes/EmbeddedResources.fnc.php';

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] )
		&& AllowEdit() )
	{
		foreach ( (array) $_REQUEST['values'] as $id => $columns )
		{
			if ( isset( $columns['PUBLISHED_GRADE_LEVELS'] ) )
			{
				$published_grade_levels = implode( ',', $columns['PUBLISHED_GRADE_LEVELS'] );

				$columns['PUBLISHED_GRADE_LEVELS'] = '';

				if ( $published_grade_levels )
				{
					$columns['PUBLISHED_GRADE_LEVELS'] = ',' . $published_grade_levels;
				}
			}

			if ( $id !== 'new' )
			{
				$sql = "UPDATE resources_embedded SET ";

				foreach ( (array) $columns as $column => $value )
				{
					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";
				DBQuery( $sql );
			}

			// New: check for Title & Link.
			elseif ( $columns['TITLE']
				&& $columns['LINK'] )
			{
				$sql = "INSERT INTO resources_embedded ";

				$fields = '';
				$values = "";

				$go = 0;

				foreach ( (array) $columns as $column => $value )
				{
					if ( ! empty( $value ) || $value == '0' )
					{
						$fields .= DBEscapeIdentifier( $column ) . ',';
						$values .= "'" . $value . "',";
						$go = true;
					}
				}

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

				if ( $go )
				{
					DBQuery( $sql );

					if ( function_exists( 'DBLastInsertID' ) )
					{
						$resource_id = DBLastInsertID();
					}
					else
					{
						// @deprecated since RosarioSIS 9.2.1.
						$resource_id = DBGetOne( "SELECT LASTVAL();" );
					}

					$modname = 'Embedded_Resources/EmbedResource.php&id=' . $resource_id;

					// Admin,Teacher,Parent,Student can Use Resource.
					DBQuery( "INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
						values('1','" . $modname . "','Y','Y');
						INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
						values('2','" . $modname . "','Y',NULL);
						INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
						values('3','" . $modname . "','Y',NULL);
						INSERT INTO profile_exceptions (PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT)
						values('0','" . $modname . "','Y',NULL);" );

					$profiles_link = _( 'User Profiles' );

					if ( AllowUse( 'Users/Profiles.php' ) )
					{
						$profiles_link = '<a href="Modules.php?modname=Users/Profiles.php">' . $profiles_link . '</a>';
					}

					$note[] = sprintf(
						dgettext( 'Embedded_Resources', 'The link has been added to the <em>Resources</em> menu. Access right has been granted to the Administrator, Teacher, Parent and Student profiles. Go to %s for configuration.' ),
						$profiles_link
					);
				}
			}

			// Reload left menu so Resource appears.
			EmbeddedResourcesReloadMenu();
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( dgettext( 'Embedded_Resources', 'Embedded Resource' ) ) )
	{
		DBQuery( "DELETE FROM resources_embedded
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		$modname = 'Embedded_Resources/EmbedResource.php&id=' . $_REQUEST['id'];

		DBQuery( "DELETE FROM profile_exceptions
			WHERE MODNAME='" . $modname . "'" );

		// Reload left menu so Resource disappears.
		EmbeddedResourcesReloadMenu();

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $note, 'note' );

	$resources_RET = DBGet( "SELECT ID,TITLE,LINK,PUBLISHED_GRADE_LEVELS
		FROM resources_embedded
		ORDER BY TITLE,ID",
	[
		'TITLE' => 'EmbeddedResourcesMakeTextInput',
		'LINK' => 'EmbeddedResourcesMakeLink',
		'PUBLISHED_GRADE_LEVELS' => 'EmbeddedResourcesLimitToGradeLevels',
	] );

	$columns = [
		'TITLE' => _( 'Title' ),
		'LINK' => _( 'Link' ),
		'PUBLISHED_GRADE_LEVELS' => _( 'Limit to Grade Levels' ),
	];

	$link['add']['html'] = [
		'TITLE' => EmbeddedResourcesMakeTextInput( '', 'TITLE' ),
		'LINK' => EmbeddedResourcesMakeLink( '', 'LINK' ),
		'PUBLISHED_GRADE_LEVELS' => EmbeddedResourcesLimitToGradeLevels( '', 'PUBLISHED_GRADE_LEVELS' ),
	];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
	$link['remove']['variables'] = [ 'id' => 'ID' ];

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) . '" method="POST">';
	DrawHeader( '', SubmitButton() );

	ListOutput(
		$resources_RET,
		$columns,
		dgettext( 'Embedded_Resources', 'Embedded Resource' ),
		dgettext( 'Embedded_Resources', 'Embedded Resources' ),
		$link
	);

	echo '<div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}
