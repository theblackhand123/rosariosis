<?php
/**
 * Functions
 *
 * @package Parent Agreement
 */

// Register plugin functions to be hooked.
add_action( 'index.php|login_check', 'ParentAgreementLoginCheck', 2, 11 ); // Priority 11, after Force Password Change plugin.

// Triggered function.
function ParentAgreementLoginCheck( $tag, $username )
{
	global $error,
		$login_status;

	if ( ! empty( $_SESSION['STUDENT_ID'] )
		&& ParentAgreementCheckStudentID( $_SESSION['STUDENT_ID'] ) )
	{
		// ParentAgreement not accepted by parent, Student cannot login.
		$error[] = dgettext( 'Parent_Agreement', 'Your parents must login first and accept the Parent Agreement so you can login.' );

		unset( $_SESSION['STUDENT_ID'] );

		$login_status = '';

		return;
	}

	if ( ! empty( $_SESSION['STUDENT_ID'] ) )
	{
		// Student can login.
		return;
	}

	if ( User( 'PROFILE' ) !== 'parent' )
	{
		// teacher or admin, can login.
		return;
	}

	if ( ! ParentAgreementAssociatedStudents( User( 'STAFF_ID' ) ) )
	{
		// Parent has no associated students, can login.
		return;
	}

	// Force First Login page.
	header( 'Location: index.php?locale=' . $_SESSION['locale'] . '&modfunc=first-login' );

	exit;
}

function ParentAgreementStudentIDsYearConfig( $student_ids_save = '' )
{
	$student_ids = Config( 'PARENT_AGREEMENT_STUDENT_IDS_' . Config( 'SYEAR' ) );

	if ( ! $student_ids )
	{
		// Set default value.
		$student_ids = Config( 'PARENT_AGREEMENT_STUDENT_IDS_' . Config( 'SYEAR' ), ',' );
	}

	if ( $student_ids_save )
	{
		// Save Student IDs.
		$student_ids = Config( 'PARENT_AGREEMENT_STUDENT_IDS_' . Config( 'SYEAR' ), $student_ids_save );
	}

	return $student_ids;
}


function ParentAgreementCheckStudentID( $student_id )
{
	if ( empty( $student_id ) )
	{
		return false;
	}

	if ( ! ParentAgreementCheckStudentHasAssociatedParents( $student_id ) )
	{
		// If Student has no associated parents, no agreement.
		return false;
	}

	$student_ids = ParentAgreementStudentIDsYearConfig();

	if ( strpos( $student_ids, ',' . $student_id . ',' ) !== false )
	{
		// Student ID found, agreement already accepted.
		return false;
	}

	// ParentAgreement.
	return true;
}

function ParentAgreementCheckStudentHasAssociatedParents( $student_id )
{
	// Check Student has Associated Parents for current School Year.
	return DBGetOne( "SELECT 1
		FROM students_join_users
		WHERE STUDENT_ID='" . (int) $student_id . "'
		AND STAFF_ID IN(SELECT STAFF_ID
			FROM staff
			WHERE SYEAR='" . Config( 'SYEAR' ) . "')" );
}

function ParentAgreementAssociatedStudents( $staff_id )
{
	// Check Student has Associated Parents for current School Year.
	$students_RET = DBGet( "SELECT STUDENT_ID
		FROM students_join_users
		WHERE STAFF_ID='" . (int) $staff_id . "'
		AND STUDENT_ID IN(SELECT STUDENT_ID
			FROM student_enrollment
			WHERE SYEAR='" . Config( 'SYEAR' ) . "'
			AND START_DATE IS NOT NULL
			AND CURRENT_DATE>=START_DATE
			AND (CURRENT_DATE<=END_DATE OR END_DATE IS NULL))" );

	if ( ! $students_RET )
	{
		return '';
	}

	$student_ids = [];

	foreach ( $students_RET as $student )
	{
		$student_ids[] = $student['STUDENT_ID'];
	}

	return implode( ',', $student_ids );
}


function ParentAgreementDone( $student_id )
{
	$student_ids = ParentAgreementStudentIDsYearConfig();

	// Remove existing student IDs.
	$student_id_array = explode( ',', $student_id );

	foreach ( $student_id_array as $student_id_i )
	{
		if ( strpos( $student_ids, ',' . $student_id_i . ',' ) !== false )
		{
			$student_ids = str_replace( ',' . $student_id_i . ',', ',', $student_ids );
		}
	}

	// Add Student ID to list of students whose parents agreed.
	$student_ids .= $student_id . ',';

	// Save Config value.
	ParentAgreementStudentIDsYearConfig( $student_ids );
}

add_action( 'index.php|before_first_login_form', 'ParentAgreementHasLoginForm' );

function ParentAgreementHasLoginForm()
{
	if ( ! User( 'STAFF_ID' ) && empty( $_SESSION['STUDENT_ID'] ) )
	{
		// User or student login failed.
		return false;
	}

	$student_ids = ParentAgreementAssociatedStudents( User( 'STAFF_ID' ) );

	if ( ! $student_ids )
	{
		// No student ID.
		return false;
	}

	$student_ids_array = explode( ',', $student_ids );

	$agreement = false;

	foreach ( $student_ids_array as $student_id )
	{
		if ( ! ParentAgreementCheckStudentID( $student_id ) )
		{
			continue;
		}

		$agreement = true;
	}

	if ( ! $agreement )
	{
		return false;
	}

	if ( ! empty( $_POST['PARENT_AGREEMENT'] ) )
	{
		// Add Student ID to list of students whose parents agreed.
		ParentAgreementDone( $student_ids );

		return false;
	}

	return ParentAgreementLoginForm();
}

if ( ! function_exists( 'ParentAgreementLoginForm' ) )
{
	/**
	 * ParentAgreement form on First Login.
	 *
	 * @since 5.3
	 *
	 * @return string Pop table with ParentAgreement form.
	 */
	function ParentAgreementLoginForm()
	{
		global $_ROSARIO;

		$_ROSARIO['page'] = 'first-login';

		Warehouse( 'header' );

		PopTable( 'header', dgettext( 'Parent_Agreement', 'Parent Agreement' ) ); ?>

		<form action="index.php?modfunc=first-login" method="POST" id="agreement-form" target="_top">
			<h3><?php echo Config( 'PARENT_AGREEMENT_TITLE' ); ?></h3>
			<div style="max-width: 640px; max-height: 400px; overflow-y: auto;">
				<?php echo Config( 'PARENT_AGREEMENT_TEXT' ); ?>
			</div>
			<input type="hidden" name="PARENT_AGREEMENT" value="1" />
			<p class="center">
				<?php echo Buttons( dgettext( 'Parent_Agreement', 'Accept' ), _( 'Logout' ) ); ?>
			</p>
			<script>
				$('#agreement-form input[type="reset"]').click(function(){
					// Logout.
					window.location.href = "index.php?modfunc=logout&token=" + <?php echo json_encode( issetVal( $_SESSION['token'], '' ) ); ?>;
				});
			</script>
		</form>

		<?php PopTable( 'footer' );

		Warehouse( 'footer' );

		exit;
	}
}
