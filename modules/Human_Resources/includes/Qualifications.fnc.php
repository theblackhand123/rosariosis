<?php
/**
 * Qualifications tab functions
 *
 * @package Human Resources module
 */

/**
 * Skills ListOutput
 *
 * @param int $staff_id Staff ID.
 */
function HumanResourcesSkillsListOutput( $staff_id )
{
	global $table;

	$table = 'human_resources_skills';

	$functions = [
		'TITLE' => 'HumanResourcesMakeQualificationsAutoSelect',
		'DESCRIPTION' => 'HumanResourcesMakeQualificationsText',
	];

	$human_resource_RET = DBGet( "SELECT ID,TITLE,DESCRIPTION
		FROM " . DBEscapeIdentifier( $table ) . "
		WHERE STAFF_ID='" . (int) $staff_id . "'
		ORDER BY CREATED_AT,TITLE", $functions );

	$columns = [
		'TITLE' => dgettext( 'Human_Resources', 'Skill' ),
		'DESCRIPTION' => _( 'Description' ),
	];

	$link['add']['html'] = [
		'TITLE' => HumanResourcesMakeQualificationsAutoSelect( '', 'TITLE' ),
		'DESCRIPTION' => HumanResourcesMakeQualificationsText( '', 'DESCRIPTION' ),
	];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=' . $_REQUEST['category_id'] .
		'&modfunc=delete_qualification&table=' . $table . '&title=' . dgettext( 'Human_Resources', 'Skill' );

	$link['remove']['variables'] = [ 'id' => 'ID' ];

	ListOutput(
		$human_resource_RET,
		$columns,
		dgettext( 'Human_Resources', 'Skill' ),
		dgettext( 'Human_Resources', 'Skills' ),
		$link,
		[],
		[ 'search' => false, 'save' => false ]
	);
}

/**
 * Education ListOutput
 *
 * @param int $staff_id Staff ID.
 */
function HumanResourcesEducationListOutput( $staff_id )
{
	global $table;

	$table = 'human_resources_education';

	$functions = [
		'QUALIFICATION' => 'HumanResourcesMakeQualificationsAutoSelect',
		'INSTITUTE' => 'HumanResourcesMakeQualificationsText',
		'START_DATE' => 'HumanResourcesMakeQualificationsDate',
		'COMPLETED_ON' => 'HumanResourcesMakeQualificationsDate',
	];

	$human_resource_RET = DBGet( "SELECT ID,QUALIFICATION,INSTITUTE,START_DATE,COMPLETED_ON
		FROM " . DBEscapeIdentifier( $table ) . "
		WHERE STAFF_ID='" . (int) $staff_id . "'
		ORDER BY CREATED_AT,QUALIFICATION", $functions );

	$columns = [
		'QUALIFICATION' => dgettext( 'Human_Resources', 'Qualification' ),
		'INSTITUTE' => dgettext( 'Human_Resources', 'Institute' ),
		'START_DATE' => _( 'Start Date' ),
		'COMPLETED_ON' => dgettext( 'Human_Resources', 'Completed on' ),
	];

	$link['add']['html'] = [
		'QUALIFICATION' => HumanResourcesMakeQualificationsAutoSelect( '', 'QUALIFICATION' ),
		'INSTITUTE' => HumanResourcesMakeQualificationsText( '', 'INSTITUTE' ),
		'START_DATE' => HumanResourcesMakeQualificationsDate( '', 'START_DATE' ),
		'COMPLETED_ON' => HumanResourcesMakeQualificationsDate( '', 'COMPLETED_ON' ),
	];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=' . $_REQUEST['category_id'] .
		'&modfunc=delete_qualification&table=' . $table . '&title=' . dgettext( 'Human_Resources', 'Qualification' );

	$link['remove']['variables'] = [ 'id' => 'ID' ];

	ListOutput(
		$human_resource_RET,
		$columns,
		dgettext( 'Human_Resources', 'Qualification' ),
		dgettext( 'Human_Resources', 'Qualifications' ),
		$link,
		[],
		[ 'search' => false, 'save' => false ]
	);
}

/**
 * Certifications ListOutput
 *
 * @param int $staff_id Staff ID.
 */
function HumanResourcesCertificationsListOutput( $staff_id )
{
	global $table;

	$table = 'human_resources_certifications';

	$functions = [
		'TITLE' => 'HumanResourcesMakeQualificationsAutoSelect',
		'INSTITUTE' => 'HumanResourcesMakeQualificationsText',
		'GRANTED_ON' => 'HumanResourcesMakeQualificationsDate',
		'VALID_THROUGH' => 'HumanResourcesMakeQualificationsDate',
	];

	$human_resource_RET = DBGet( "SELECT ID,TITLE,INSTITUTE,GRANTED_ON,VALID_THROUGH
		FROM " . DBEscapeIdentifier( $table ) . "
		WHERE STAFF_ID='" . (int) $staff_id . "'
		ORDER BY CREATED_AT,TITLE", $functions );

	$columns = [
		'TITLE' => dgettext( 'Human_Resources', 'Certification' ),
		'INSTITUTE' => dgettext( 'Human_Resources', 'Institute' ),
		'GRANTED_ON' => dgettext( 'Human_Resources', 'Granted on' ),
		'VALID_THROUGH' => dgettext( 'Human_Resources', 'Valid through' ),
	];

	$link['add']['html'] = [
		'TITLE' => HumanResourcesMakeQualificationsAutoSelect( '', 'TITLE' ),
		'INSTITUTE' => HumanResourcesMakeQualificationsText( '', 'INSTITUTE' ),
		'GRANTED_ON' => HumanResourcesMakeQualificationsDate( '', 'GRANTED_ON' ),
		'VALID_THROUGH' => HumanResourcesMakeQualificationsDate( '', 'VALID_THROUGH' ),
	];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=' . $_REQUEST['category_id'] .
		'&modfunc=delete_qualification&table=' . $table . '&title=' . dgettext( 'Human_Resources', 'Certification' );

	$link['remove']['variables'] = [ 'id' => 'ID' ];

	ListOutput(
		$human_resource_RET,
		$columns,
		dgettext( 'Human_Resources', 'Certification' ),
		dgettext( 'Human_Resources', 'Certifications' ),
		$link,
		[],
		[ 'search' => false, 'save' => false ]
	);
}

/**
 * Languages ListOutput
 *
 * @param int $staff_id Staff ID.
 */
function HumanResourcesLanguagesListOutput( $staff_id )
{
	global $table;

	$table = 'human_resources_languages';

	$functions = [
		'TITLE' => 'HumanResourcesMakeQualificationsAutoSelect',
		'READING' => 'HumanResourcesMakeQualificationsProficiencySelect',
		'SPEAKING' => 'HumanResourcesMakeQualificationsProficiencySelect',
		'WRITING' => 'HumanResourcesMakeQualificationsProficiencySelect',
	];

	$human_resource_RET = DBGet( "SELECT ID,TITLE,READING,SPEAKING,WRITING
		FROM " . DBEscapeIdentifier( $table ) . "
		WHERE STAFF_ID='" . (int) $staff_id . "'
		ORDER BY CREATED_AT,TITLE", $functions );

	$columns = [
		'TITLE' => dgettext( 'Human_Resources', 'Language' ),
		'READING' => dgettext( 'Human_Resources', 'Reading' ),
		'SPEAKING' => dgettext( 'Human_Resources', 'Speaking' ),
		'WRITING' => dgettext( 'Human_Resources', 'Writing' ),
	];

	$link['add']['html'] = [
		'TITLE' => HumanResourcesMakeQualificationsAutoSelect( '', 'TITLE' ),
		'READING' => HumanResourcesMakeQualificationsProficiencySelect( '', 'READING' ),
		'SPEAKING' => HumanResourcesMakeQualificationsProficiencySelect( '', 'SPEAKING' ),
		'WRITING' => HumanResourcesMakeQualificationsProficiencySelect( '', 'WRITING' ),
	];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=' . $_REQUEST['category_id'] .
		'&modfunc=delete_qualification&table=' . $table . '&title=' . dgettext( 'Human_Resources', 'Language' );

	$link['remove']['variables'] = [ 'id' => 'ID' ];

	ListOutput(
		$human_resource_RET,
		$columns,
		dgettext( 'Human_Resources', 'Language' ),
		dgettext( 'Human_Resources', 'Languages' ),
		$link,
		[],
		[ 'search' => false, 'save' => false ]
	);
}

/**
 * Make Qualifications Text Input
 *
 * @global array  $THIS_RET
 * @global string $table
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Text Input
 */
function HumanResourcesMakeQualificationsText( $value, $column )
{
	global $THIS_RET,
		$table;

	if ( empty( $THIS_RET['ID'] ) )
	{
		$THIS_RET['ID'] = 'new';
	}

	$extra = ' maxlength=100';

	$input_size = 12;

	if ( $column === 'DESCRIPTION' )
	{
		$extra = ' maxlength=255';

		$input_size = 20;
	}

	if ( $column === 'INSTITUTE'
		&& $THIS_RET['ID'] !== 'new' )
	{
		$extra .= ' required';
	}

	return TextInput(
		$value,
		'values[' . $table . '][' . $THIS_RET['ID'] . '][' . $column . ']',
		'',
		'size="' . $input_size . '"' . $extra
	);
}

/**
 * Make Qualifications Date Input
 *
 * @global array  $THIS_RET
 * @global string $table
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Text Input
 */
function HumanResourcesMakeQualificationsDate( $value, $column )
{
	global $THIS_RET,
		$table;

	if ( empty( $THIS_RET['ID'] ) )
	{
		$THIS_RET['ID'] = 'new';
	}

	return DateInput(
		$value,
		'values[' . $table . '][' . $THIS_RET['ID'] . '][' . $column . ']',
		''
	);
}

/**
 * Make Qualifications Auto Select Input
 *
 * @global array  $THIS_RET
 * @global string $table
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Auto Select Input
 */
function HumanResourcesMakeQualificationsAutoSelect( $value, $column )
{
	global $THIS_RET,
		$table;

	static $js_included = false;

	if ( empty( $THIS_RET['ID'] ) )
	{
		$THIS_RET['ID'] = 'new';
	}

	// Add the 'new' option, is also the separator.
	$options['---'] = '-' . _( 'Edit' ) . '-';

	$options_RET = DBGet( "SELECT DISTINCT " . DBEscapeIdentifier( $column ) .
		" FROM " . DBEscapeIdentifier( $table ) .
		" ORDER BY " . DBEscapeIdentifier( $column ) );

	foreach ( (array) $options_RET as $option )
	{
		if ( $option[$column] != ''
			&& ! isset( $options[$option[$column]] ) )
		{
			$options[$option[$column]] = [ $option[$column], $option[$column] ];
		}
	}

	if ( $value === '---'
		|| count( (array) $options ) <= 1 )
	{
		return HumanResourcesMakeQualificationsText( $value, $column );
	}

	$input_name = 'values[' . $table . '][' . $THIS_RET['ID'] . '][' . $column . ']';

	$extra = ' maxlength=100';

	// When -Edit- option selected, change the auto pull-down to text field.
	$return = '';

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] )
		&& ! $js_included )
	{
		$js_included = true;

		ob_start();?>
		<script>
		function HumanResourcesMaybeEditTextInput(el) {

			// -Edit- option's value is ---.
			if ( el.value === '---' ) {

				var $el = $( el );

				// Remove parent <div> if any
				if ( $el.parent('div').length ) {
					$el.unwrap();
				}
				// Remove the select input.
				$el.remove();

				// Show & enable the text input of the same name.
				$( '[name="' + el.name + '_text"]' ).prop('name', el.name).prop('disabled', false).show().focus();
			}
		}
		</script>
		<?php $return = ob_get_clean();
	}

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$extra .= $THIS_RET['ID'] === 'new' ? '' : ' required';

		// Add hidden & disabled Text input in case user chooses -Edit-.
		$return .= TextInput(
			'',
			$input_name . '_text',
			'',
			$extra . ' disabled style="display:none;"',
			false
		);
	}

	$return .= SelectInput(
		$value,
		$input_name,
		'',
		$options,
		( $THIS_RET['ID'] === 'new' ? 'N/A' : false ),
		'onchange="HumanResourcesMaybeEditTextInput(this);"',
		( $THIS_RET['ID'] !== 'new' )
	);

	return $return;
}

/**
 * Make Qualifications Proficiency Select Input
 *
 * @global array  $THIS_RET
 * @global string $table
 *
 * @param  string $value    Field value.
 * @param  string $column   Field column.
 *
 * @return string Proficiency Select Input
 */
function HumanResourcesMakeQualificationsProficiencySelect( $value, $column )
{
	global $THIS_RET,
		$table;

	if ( empty( $THIS_RET['ID'] ) )
	{
		$THIS_RET['ID'] = 'new';
	}

	$options = [
		'ILR_Level_1' => dgettext( 'Human_Resources', 'Elementary proficiency' ),
		'ILR_Level_2' => dgettext( 'Human_Resources', 'Limited working proficiency' ),
		'ILR_Level_3' => dgettext( 'Human_Resources', 'Professional working proficiency' ),
		'ILR_Level_4' => dgettext( 'Human_Resources', 'Full professional proficiency' ),
		'ILR_Level_5' => dgettext( 'Human_Resources', 'Native or bilingual proficiency' ),
	];

	return SelectInput(
		$value,
		'values[' . $table . '][' . $THIS_RET['ID'] . '][' . $column . ']',
		'',
		$options
	);
}
