<?php
/**
 * English Help texts - Billing Elements module
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
 * @package Billing Elements module
 */

// BILLING ELEMENTS ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['Billing_Elements/Elements.php'] = '<p>' . _help( '<i>Elements</i> lets you create Billing Elements and organize them into categories. To create a new Category, click on the "+" icon. Then, enter the Category Title and click the "Save" button.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'To create a new Element, first select a Category in the list. Then, click on the "+" icon in the list at the right of categories. Then, enter the Element Title, Amount, Reference and Description. Optionally select the Grade Level the Element applies to. Uncheck the Rollover checkbox if you do not wish to roll the Element to the next school year. After entering the Element details, click the "Save" button.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'Below the Element details, you will notice the "Assign" button. It will redirect you to the <i>Mass Assign Elements</i> program and will prefill the form with the Element details.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'Note: only elements not assigned to students can be deleted.', 'Billing_Elements' ) . '</p>';

	$help['Billing_Elements/MassAssignElements.php'] = '<p>' . _help( '<i>Mass Assign Elements</i> allows you to assign a Billing Element and the corresponding Fee to various students at once.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'You must first select a (group of) student(s) by using the "Find a Student" search screen.' ) . '</p>
	<p>' . _help( 'Then, select an Element from the dropdown list.', 'Billing_Elements' ) . ' ' .
	_help( 'The fee Title and Amount fields will be automatically filled with the Element details. You can also set the Due Date and Comment.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'From the search result, you can select any number of students. You can select all the students in the list by checking the checkbox in the column headings above the list.' ) . '</p>
	<p>' . _help( 'Finally, click the "Add Element and Fee to Selected Students" button.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'Alternatively, you can click the "Assign Elements by Grade Level" link at the top of the screen to semi automatically assign the selected Billing Elements to students enrolled in the Grade Levels associated to each element.', 'Billing_Elements' ) . '</p>';

	$help['Billing_Elements/StudentElements.php'] = '<p>' . _help( '<i>Student Elements</i> allows you to consult, assign, or remove Billing Elements and their corresponding Fee for a single student.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'You must first select a student by using the "Find a Student" search screen.' ) . '</p>
	<p>' . _help( 'Then, the Billing Elements and their corresponding Fee will be displayed in the list.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'To add an Element, select it from the dropdown list at the bottom of the list.', 'Billing_Elements' ) . ' ' .
	_help( 'The fee Title and Amount fields will be automatically filled with the Element details. You can also set the Due Date and Comment.', 'Billing_Elements' ) . ' ' .
	_help( 'Then, click the "Save" button.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'To remove an Element and its corresponding Fee, click the "Delete" link next to the desired fee in the list. You will be asked to confirm the deletion.', 'Billing_Elements' ) . '</p>';

	$help['Billing_Elements/CategoryBreakdown.php'] = '<p>' . _help( '<i>Category Breakdown</i> is a report showing Billing Elements breakdown per Category. You must first select a Category from the dropdown list at the top of the screen.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'A column chart listing the Elements belonging to the category will be displayed. To display a pie chart, click on the "Pie" tab. To list the Elements, click on the "List" tab.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'To display the total amount for each Element, click the "Amount" radio button at the top of the screen.', 'Billing_Elements' ) . ' ' .
	_help( 'To display breakdown by grade level instead, check the "Breakdown by Grade Level" checkbox at the top right corner.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'You can adjust the Report Timeframe selecting the start and end date from the dropdown menus. Then, click on the "Go" button to update the chart.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'To save the chart as an image file, click on the download icon below the chart.', 'Billing_Elements' ) . '</p>
	<p>' . _help( 'Note: charts will display at most 25 elements per category.', 'Billing_Elements' ) . '</p>';

endif;
