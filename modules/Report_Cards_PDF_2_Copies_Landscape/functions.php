<?php
/**
 * Report Cards PDF two Copies Landscape for RosarioSIS
 *
 * @author François Jacquet
 */


/**
 * Is printing Report Cards PDF?
 *
 * @return bool
 */
function RCPDF2CLIsReportCardPdf()
{
	if ( empty( $_REQUEST['modname'] ) || $_REQUEST['modname'] !== 'Grades/ReportCards.php' )
	{
		return false;
	}

	if ( empty( $_REQUEST['modfunc'] ) || $_REQUEST['modfunc'] !== 'save' )
	{
		return false;
	}

	return true;
}


/**
 * Report Cards PDF landscape orientation
 *
 * @uses functions/PDF.php|pdf_start action hook.
 *
 * @global $pdf_options
 *
 * @return bool
 */
function RCPDF2CLReportCardsPdfLandscape()
{
	global $pdf_options;

	if ( ! RCPDF2CLIsReportCardPdf() )
	{
		return false;
	}

	$pdf_options['orientation'] = 'landscape';

	return true;
}

add_action( 'functions/PDF.php|pdf_start', 'RCPDF2CLReportCardsPdfLandscape' );


/**
 * Report Cards PDF 2 copies on same page
 *
 * @uses Grades/ReportCards.php|report_cards_html_array Action hook.
 *
 * @global $report_cards
 *
 * @return bool
 */
function RCPDF2CLReportCardsPdfTwoCopies()
{
	global $report_cards;

	if ( ! RCPDF2CLIsReportCardPdf()
		|| ! $report_cards )
	{
		return false;
	}

	$two_copies_html = '<table class="width-100p valign-top fixed-col tembely-report-cards-pdf-two-copies" style="height: 100%;"><tr>
	<td style="padding-right: 5px;"><div>__REPORT_CARD__</div></td>
	<td style="padding-left: 5px;"><div>__REPORT_CARD__</div></td>
	</tr></table>';

	$report_cards_two_copies = [];

	foreach ( $report_cards as $report_card )
	{
		$report_cards_two_copies[] = str_replace( '__REPORT_CARD__', $report_card, $two_copies_html );
	}

	$report_cards = $report_cards_two_copies;

	return true;
}

add_action( 'Grades/ReportCards.php|report_cards_html_array', 'RCPDF2CLReportCardsPdfTwoCopies' );
