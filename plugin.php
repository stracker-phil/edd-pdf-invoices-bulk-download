<?php
/**
 * Plugin name: EDD - PDF Invoices Bulk Download
 * Author:      Philipp Stracker
 * Author URI:  https://philippstracker.com
 * Description: Adds a Bulk-Download button to the "Easy Digital Downloads - PDF Invoices" plugin.
 * Version:     1.0.0
 *
 * ----------------------------------------------------------------------------
 *
 * Copyright (C) 2021 Philipp Stracker
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ----------------------------------------------------------------------------
 */

// Registers a "Bulk Download" action in the payment history table.
add_action( 'edd_payments_table_bulk_actions', 'edd_pdf_bulk_register' );

// Processes the "Bulk Download" action and prepares the zip archive.
add_action( 'edd_payments_table_do_bulk_action', 'edd_pdf_bulk_process', 10, 2 );

/**
 * Adds the "Download PDF Invoices" bulk action.
 *
 * @param array $actions Bulk actions for the payment history table.
 *
 * @return array List of bulk actions.
 */
function edd_pdf_bulk_register( $actions ) {
	if ( class_exists( 'ZipArchive' ) ) {
		$actions['pdf_bulk_download'] = __( 'Download PDF Invoices', 'edd_pdf_bulk' );
	}

	return $actions;
}

/**
 * Creates the PDF Invoices for all selected payments.
 *
 * @param int    $payment_id This parameter is ignored.
 * @param string $action     The action to perform.
 */
function edd_pdf_bulk_process( $payment_id, $action ) {
	if (
		'pdf_bulk_download' !== $action
		|| ! defined( 'EDDPDFI_PLUGIN_DIR' )
		|| ! class_exists( 'ZipArchive' )
	) {
		return;
	}

	// This function processes all payments at once, no need to call it again.
	remove_action( 'edd_payments_table_do_bulk_action', 'edd_pdf_bulk_process' );

	$ids      = isset( $_GET['payment'] ) ? (array) $_GET['payment'] : false;
	$invoices = [];

	foreach ( $ids as $id ) {
		$invoice = edd_pdf_bulk_prepare_invoice( $id );

		if ( $invoice ) {
			$invoices[] = $invoice;
		}
	}

	// Create a zip archive with all Invoices.
	$zip     = new ZipArchive();
	$archive = edd_pdf_bulk_basedir(
		apply_filters( 'edd_pdf_bulk_file_name', 'bulk-invoices.zip' )
	);

	if ( file_exists( $archive ) ) {
		wp_delete_file( $archive );
	}

	// Stop here if no invoices were created.
	if ( ! count( $invoices ) ) {
		return;
	}

	if ( true === $zip->open( $archive, ZipArchive::CREATE ) ) {
		foreach ( $invoices as $invoice ) {
			$zip->addFile( $invoice, basename( $invoice ) );
		}

		$zip->close();
	}

	// Delete the temp files.
	foreach ( $invoices as $invoice ) {
		wp_delete_file( $invoice );
	}

	if ( ob_get_length() ) {
		ob_end_clean();
	}

	// Download the zip archive.
	header( 'Content-Description: File Transfer' );
	header( 'Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1' );
	header( 'Pragma: public' );
	header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' ); // Date in the past
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );

	// force download dialog
	if ( strpos( php_sapi_name(), 'cgi' ) === false ) {
		header( 'Content-Type: application/force-download' );
		header( 'Content-Type: application/octet-stream', false );
		header( 'Content-Type: application/download', false );
		header( 'Content-Type: application/zip', false );
	} else {
		header( 'Content-Type: application/zip' );
	}

	// use the Content-Disposition header to supply a recommended filename
	header( 'Content-Disposition: attachment; filename="' . basename( $archive ) . '"' );
	header( 'Content-Transfer-Encoding: binary' );
	readfile( $archive );
}

/**
 * Returns the absolute path to a sub-folder in the uploads directory where
 * temporary PDF files and the zip archive is stored.
 *
 * @param string $file A filename to include in the returned path.
 *
 * @return string Absolute path to an uploads folder.
 */
function edd_pdf_bulk_basedir( $file = '' ) {
	static $base_dir = '';

	if ( ! $base_dir ) {
		$uploads_dir = trailingslashit( wp_upload_dir()['basedir'] );
		$base_dir    = trailingslashit( $uploads_dir . 'edd-pdf-bulk' );
		wp_mkdir_p( $base_dir );
	}

	return $base_dir . trim( $file, '\\/' );
}

/**
 * Generates the PDF Invoice for a single payment.
 *
 * @param int $payment_id The payment post ID.
 *
 * @return string Path to the PDF file, or empty string on failure.
 */
function edd_pdf_bulk_prepare_invoice( $payment_id ) {
	global $edd_options;

	if ( ! edd_pdf_bulk_is_invoice_link_allowed( $payment_id ) ) {
		return '';
	}

	// EDD_PDF_Invoice has some hardcoded $_GET accesses that we need to work around.
	$orig_get            = isset( $_GET['purchase_id'] ) ? $_GET['purchase_id'] : null;
	$_GET['purchase_id'] = $payment_id;

	include_once( EDDPDFI_PLUGIN_DIR . '/tcpdf/tcpdf.php' );
	include_once( EDDPDFI_PLUGIN_DIR . '/includes/EDD_PDF_Invoice.php' );

	/*
	 * The following code is taken from file "edd-pdf-invoices.php", in method
	 * EDD_PDF_Invoices::generate_pdf_invoice().
	 */

	do_action( 'edd_pdfi_generate_pdf_invoice', $payment_id );

	$eddpdfi_payment         = edd_get_payment( $payment_id );
	$eddpdfi_payment_meta    = $eddpdfi_payment->payment_meta;
	$eddpdfi_buyer_info      = $eddpdfi_payment_meta['user_info'];
	$eddpdfi_payment_gateway = $eddpdfi_payment->gateway;
	$eddpdfi_payment_method  = edd_get_gateway_admin_label( $eddpdfi_payment_gateway );

	$company_name = isset( $edd_options['eddpdfi_company_name'] )
		? apply_filters( 'eddpdfi_company_name', $edd_options['eddpdfi_company_name'] )
		: '';

	$eddpdfi_payment_date   = date_i18n( get_option( 'date_format' ), strtotime( $eddpdfi_payment->date ) );
	$eddpdfi_payment_status = edd_get_payment_status( $eddpdfi_payment, true );

	// WPML Support
	if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
		$lang = ! empty( $eddpdfi_payment->payment_meta['wpml_language'] )
			? $eddpdfi_payment->payment_meta['wpml_language']
			: false;

		if ( ! empty( $lang ) ) {
			global $sitepress;
			$sitepress->switch_lang( $lang );
		}
	}

	$eddpdfi_pdf = new EDD_PDF_Invoice( 'P', 'mm', 'A4', true, 'UTF-8', false );
	$eddpdfi_pdf->SetDisplayMode( 'real' );
	$eddpdfi_pdf->setJPEGQuality( 100 );

	$eddpdfi_pdf->SetTitle( __( 'Invoice ' . eddpdfi_get_payment_number( $eddpdfi_payment->ID ), 'eddpdfi' ) );
	$eddpdfi_pdf->SetCreator( __( 'Easy Digital Downloads', 'eddpdfi' ) );
	$eddpdfi_pdf->SetAuthor( get_option( 'blogname' ) );

	$address_line_2_line_height = isset( $edd_options['eddpdfi_address_line2'] ) ? 6 : 0;

	if ( ! isset( $edd_options['eddpdfi_templates'] ) ) {
		$edd_options['eddpdfi_templates'] = 'default';
	}

	// Generate the PDF invoice using the defined PDF template.
	do_action(
		'eddpdfi_pdf_template_' . $edd_options['eddpdfi_templates'],
		$eddpdfi_pdf,
		$eddpdfi_payment,
		$eddpdfi_payment_meta,
		$eddpdfi_buyer_info,
		$eddpdfi_payment_gateway,
		$eddpdfi_payment_method,
		$address_line_2_line_height,
		$company_name,
		$eddpdfi_payment_date,
		$eddpdfi_payment_status
	);

	$path = edd_pdf_bulk_basedir(
		apply_filters( 'eddpdfi_invoice_filename_prefix', 'Invoice-' ) . eddpdfi_get_payment_number( $eddpdfi_payment->ID ) . '.pdf'
	);

	// Delete existing invoice with the same name.
	if ( file_exists( $path ) ) {
		wp_delete_file( $path );
	}

	$eddpdfi_pdf->Output( $path, 'F' );

	$_GET['purchase_id'] = $orig_get;

	return $path;
}

/**
 * Check is invoice link is allowed.
 *
 * Based on EDD_PDF_Invoices::is_invoice_link_allowed().
 *
 * @global    $edd_options
 *
 * @param int $id Payment ID to verify.
 *
 * @return bool
 */
function edd_pdf_bulk_is_invoice_link_allowed( $id ) {
	global $edd_options;

	$ret = true;

	if ( ! $id ) {
		$ret = false;
	} elseif ( isset( $edd_options['eddpdfi_disable_invoices_on_free_downloads'] ) ) {
		$amount = edd_get_payment_amount( $id );
		$ret    = $amount > 0;
	}

	if ( $ret && ! edd_is_payment_complete( $id ) ) {
		$ret = false;
	}

	return apply_filters( 'eddpdfi_is_invoice_link_allowed', $ret, $id );
}
