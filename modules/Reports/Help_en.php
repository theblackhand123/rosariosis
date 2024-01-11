<?php
/**
 * English Help texts
 *
 * Texts are organized by:
 * - Module
 * - Profile
 *
 * Please use this file as a reference to generate the Gettext [My_Module]_help.po files
 * and translate Help texts to your language.
 * The Catalog should only reference the Help_en.php file
 * and detect the `_help` function / source keyword.
 *
 * @author François Jacquet
 *
 * @package Reports
 * @subpackage Help
 */

// REPORTS ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['Reports/SavedReports.php'] = '<p>' . _help( '<i>Saved Reports</i> allows you to save, rename and delete reports. Those reports can virtually consist of any page within RosarioSIS. The "Save Report" button allowing you to save a report will appear in the Bottom frame whenever a Student / User list is displayed.', 'Reports' ) . '</p>
	<p>' . _help( 'Once saved, the report will appear under the Reports module\'s menu. This will allow you to easily run the report again and can act as a shortcut to any page.', 'Reports' ) . '</p>';

	$help['Reports/Calculations.php'] = '<p>' . _help( '<i>Calculations</i> allows you to perform calculations by combinating some basic functions, RosarioSIS fields, breakdown and search screens into an equation.', 'Reports' ) . '</p>
	<p>' . _help( 'The top header will give you hints while writing your calculation. The left box will provide you with functions and mathematical operators. The right box will provide you with Time values, RosarioSIS fields and constants.', 'Reports' ) . '</p>
	<p>' . _help( 'Clicking on one of those function / operator / field will add it to the Equation box below.', 'Reports' ) . '</p>
	<p>' . _help( 'The Equation box features 3 icons and a dropdown list:', 'Reports' ) . '</p>
	<ul>
		<li>' . _help( 'the "Breakdown" dropdown list to apply to the equation results', 'Reports' ) . '</li>
		<li>' . _help( 'the Backspace icon to erase the last member of the equation', 'Reports' ) . '</li>
		<li>' . _help( 'the Run icon to run the equation', 'Reports' ) . '</li>
		<li>' . _help( 'and the Floppy icon to save the equation', 'Reports' ) . '</li>
	</ul>
	<p>' . _help( 'When adding a function to the equation, you will notice a Search screen popping up: the dropdown lists will allow you to filter the results of the function. You can add another filter by clicking on the plus icon (+). Please note that the same type of filter cannot be repeated for the same function. Each search screen corresponds to a function. If your equation contains more than one function, a new search screen will pop up.', 'Reports' ) . '</p>
	<p>' . _help( 'At the bottom of the screen, a list of Saved Equations may appear. Saved equations can be used within the <i>Calculations Reports</i> program.', 'Reports' ) . '</p>';

endif;
