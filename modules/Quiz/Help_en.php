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
 * @package Quiz module
 * @subpackage Help
 */

// QUIZ ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['Quiz/Quizzes.php'] = '<p>' . _help( '<i>Quizzes</i> lets you consult quizzes created by teachers, for the current quarter.', 'Quiz' ) . '</p>

	<p>' . _help( 'To consult Quizzes of another quarter, select the right Quarter from the dropdown list in the left menu.', 'Quiz' ) . '</p>

	<p>' . _help( 'First, select a Quiz by clicking on its title in the Quizzes list. The Quiz information and options are now displayed above the list.', 'Quiz' ) . '</p>

	<p>' . _help( 'Just below the Quiz information, you will notice two links:', 'Quiz' ) . '</p>

	<ul>
		<li>' . _help( 'The "Preview" link to display the Quiz and its Questions, just as students will see it.', 'Quiz' ) . '</li>
		<li>' . _help( 'The "Teacher" link will bring you to the <i>User Info</i> program.', 'Quiz' ) . '</li>
	</ul>

	<p>' . _help( 'Questions belonging to the selected Quiz are shown in the list next to the Quizzes list.
		Clicking on a Question title will bring you to the <i>Questions</i> program.', 'Quiz' ) . '</p>';


	$help['Quiz/Questions.php'] = '<p>' . _help( '<i>Questions</i> lets you add, edit, and browse Questions, organized in Categories.', 'Quiz' ) . '</p>

	<p>' . _help( 'You can add Categories to organize your Questions and easily browse other teachers\' Questions. To create a new Category, click on the "+" icon below the existing Categories.', 'Quiz' ) . '</p>

	<p>' . _help( 'You can now type in the name of the new Category in the "Title" field provided. Add a sort order (order in which the categories will appear in the list). Click "Save" when you have finished.', 'Quiz' ) . '</p>

	<p>' . _help( 'Add a new Question', 'Quiz' ) . '</p>

	<p>' . _help( 'Click on the "+" icon below the "No Questions were found" text. Fill in the Question field(s), and then choose what type of field you wish with the "Type" pull-down.', 'Quiz' ) . '</p>

	<ul>
		<li>' . _help( '"Select One from Options" fields create radio buttons from which you can select one option. To create this type of field, click on "Select One from Options" and then add your options (one per line) in the "Options" text box. Mark the correct answer with an asterisk "*".', 'Quiz' ) . '</li>

		<li>' . _help( '"Select Multiple from Options" fields create multiple checkboxes to choose one or more options. Mark the correct answers with an asterisk "*".', 'Quiz' ) . '</li>

		<li>' . _help( '"Text" fields create alphanumeric text fields with a maximum capacity of 255 characters.', 'Quiz' ) . '</li>

		<li>' . _help( '"Long Text" fields create large alphanumeric text boxes with rich text and multimedia capabilities.', 'Quiz' ) . '</li>

		<li>' . _help( '"Gap Fill" fields create text fields inserted in between sentences. Delimit gaps with double underscores "__"', 'Quiz' ) . '</li>
	</ul>

	<p>' . _help( 'The "Description" is a rich text that will be displayed after the Question title (optional).', 'Quiz' ) . '</p>

	<p>' . _help( 'The "Sort Order" determines the order in which the fields will be displayed in the Student Info tab.', 'Quiz' ) . '</p>

	<p>' . _help( 'Delete a Question', 'Quiz' ) . '</p>

	<p>' . _help( 'You can delete any Question simply by clicking on the "Delete" button in the upper right corner. Please note that both the "Delete" and "Save" buttons will only show up if the Question has not been added to a Quiz yet.', 'Quiz' ) . '</p>';

endif;


// Teacher help.
if ( User( 'PROFILE' ) === 'teacher' ) :

	$help['Quiz/Quizzes.php'] = '<p>' . _help( '<i>Quizzes</i> lets you view, add, and edit your quizzes, for the current quarter.', 'Quiz' ) . '</p>

	<p>' . _help( 'To consult Quizzes of another quarter, select the right Quarter from the dropdown list in the left menu.', 'Quiz' ) . '</p>

	<p>' . _help( 'First, select a Quiz by clicking on its title in the Quizzes list. The Quiz information and options are now displayed above the list.', 'Quiz' ) . '</p>

	<p>' . _help( 'To add a new Quiz, click on the "+" icon. The form will appear above the list.', 'Quiz' ) . '</p>

	<p>' . _help( 'Fill in the required form fields such as Title, Assignment and Points.', 'Quiz' ) . '</p>

	<p>' . _help( 'An Assignment must be associated to the Quiz. In case no Assignments were found for the current Quarter, you must add one beforehand. By default, the Assignment title and description are the ones used for the Quiz. Also, the Quiz opening dates will be defined by the Assignment\'s Assigned and Due dates.', 'Quiz' ) . '</p>

	<p>' . _help( 'The "Random Question Order" option will shuffle questions before displaying them.', 'Quiz' ) . '</p>

	<p>' . _help( 'The "Show Correct Answers" option will show to students which answers are correct (green tick icon) or incorrect (red "x" icon). This happens right after the student submits the Quiz. Only Questions of the following types can be automatically corrected: Text, Select, Multiple and Gap Fill.', 'Quiz' ) . '</p>

	<p>' . _help( 'To save the Quiz, click on the "Save" button. A Quiz can be deleted by clicking on the "Delete" button. Please note that the "Delete" button will only show up if the Quiz has not been answered yet.', 'Quiz' ) . '</p>

	<p>' . _help( 'Questions belonging to the selected Quiz will appear in the list next to the Quizzes list.', 'Quiz' ) . '</p>
	<p>' . _help( 'In order to add a Question to your Quiz, click on the "+" icon. Clicking on a Question title or on the "+" icon will bring you to the <i>Questions</i> program.', 'Quiz' ) . '</p>

	<p>' . _help( 'To remove a Question from your Quiz, click on the "-" icon next to the Question title in the list.', 'Quiz' ) . '</p>

	<p>' . _help( 'Just below the Quiz information, you will notice up to 3 links:', 'Quiz' ) . '</p>

	<ul>
		<li>' . _help( 'The "Preview" link to display the Quiz and its Questions, just as students will see it.', 'Quiz' ) . '</li>

		<li>' . _help( 'The "Grades" link will bring you to the <i>Grades > Gradebook</i> program. From there you can consult student Submissions / answers and grade them.', 'Quiz' ) . '</li>

		<li>' . _help( 'The "Assignment" link will bring you to the <i>Grades > Assignments</i> program.', 'Quiz' ) . '</li>
	</ul>';


	$help['Quiz/Questions.php'] = '<p>' . _help( '<i>Questions</i> lets you add, edit, and browse Questions, organized in Categories.', 'Quiz' ) . '</p>

	<p>' . _help( 'You can add Categories to organize your Questions and easily browse other teachers\' Questions. To create a new Category, click on the "+" icon below the existing Categories.', 'Quiz' ) . '</p>

	<p>' . _help( 'You can now type in the name of the new Category in the "Title" field provided. Add a sort order (order in which the categories will appear in the list). Click "Save" when you have finished.', 'Quiz' ) . '</p>

	<p>' . _help( 'Add a new Question' ) . '</p>

	<p>' . _help( 'Click on the "+" icon below the "No Questions were found" text. Fill in the Question field(s), and then choose what type of field you wish with the "Type" pull-down.', 'Quiz' ) . '</p>
	<ul>
	<li>' . _help( '"Select One from Options" fields create radio buttons from which you can select one option. To create this type of field, click on "Select One from Options" and then add your options (one per line) in the "Options" text box. Mark the correct answer with an asterisk "*".', 'Quiz' ) . '</li>

	<li>' . _help( '"Select Multiple from Options" fields create multiple checkboxes to choose one or more options. Mark the correct answers with an asterisk "*".', 'Quiz' ) . '</li>

	<li>' . _help( '"Text" fields create alphanumeric text fields with a maximum capacity of 255 characters.', 'Quiz' ) . '</li>
	<li>' . _help( '"Long Text" fields create large alphanumeric text boxes with rich text and multimedia capabilities.', 'Quiz' ) . '</li>
	<li>' . _help( '"Gap Fill" fields create text fields inserted in between sentences. Delimit gaps with double underscores "__"', 'Quiz' ) . '</li>
	</ul>

	<p>' . _help( 'The "Description" is a rich text that will be displayed after the Question title (optional).', 'Quiz' ) . '</p>

	<p>' . _help( 'The "Sort Order" determines the order in which the fields will be displayed in the Student Info tab.', 'Quiz' ) . '</p>

	<p>' . _help( 'Delete a Question', 'Quiz' ) . '</p>

	<p>' . _help( 'You can delete any Question simply by clicking on the "Delete" button in the upper right corner. Please note that both the "Delete" and "Save" buttons will only show up if the Question has not been added to a Quiz yet.', 'Quiz' ) . '</p>

	<p>' . _help( 'Just below the Question information, you will be able to consult the list of associated Quizzes, for the current quarter.', 'Quiz' ) . '</p>

	<p>' . _help( 'To add the Question to an available Quiz, select it from the dropdown, assign it a number of Points and a Sort Order. Then, click the "Add Question" button. You will then be redirected to the <i>Quizzes</i> program.', 'Quiz' ) . '</p>';

endif;


// Parent & student help.
if ( User( 'PROFILE' ) === 'parent' ) :


endif;
