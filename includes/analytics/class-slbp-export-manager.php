<?php
/**
 * Enhanced export manager for analytics data.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/analytics
 */

/**
 * Enhanced export manager for analytics data.
 *
 * Handles export of analytics data in multiple formats (CSV, PDF, XLSX).
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/analytics
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Export_Manager {

	/**
	 * Supported export formats.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $supported_formats    Supported export formats.
	 */
	private $supported_formats = array( 'csv', 'pdf', 'xlsx' );

	/**
	 * Export directory path.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $export_dir    Export directory path.
	 */
	private $export_dir;

	/**
	 * Export directory URL.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $export_url    Export directory URL.
	 */
	private $export_url;

	/**
	 * Initialize the class.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$upload_dir = wp_upload_dir();
		$this->export_dir = $upload_dir['basedir'] . '/slbp-exports/';
		$this->export_url = $upload_dir['baseurl'] . '/slbp-exports/';
		
		$this->ensure_export_directory();
	}

	/**
	 * Export data in specified format.
	 *
	 * @since    1.0.0
	 * @param    array     $data      Data to export.
	 * @param    string    $format    Export format.
	 * @param    string    $type      Data type.
	 * @param    array     $options   Export options.
	 * @return   string|WP_Error     File URL or error.
	 */
	public function export_data( $data, $format, $type, $options = array() ) {
		if ( ! in_array( $format, $this->supported_formats, true ) ) {
			return new WP_Error( 'invalid_format', __( 'Unsupported export format.', 'skylearn-billing-pro' ) );
		}

		$filename = $this->generate_filename( $type, $format );
		$file_path = $this->export_dir . $filename;

		switch ( $format ) {
			case 'csv':
				$result = $this->export_to_csv( $data, $file_path, $options );
				break;
			case 'pdf':
				$result = $this->export_to_pdf( $data, $file_path, $type, $options );
				break;
			case 'xlsx':
				$result = $this->export_to_xlsx( $data, $file_path, $options );
				break;
			default:
				return new WP_Error( 'export_failed', __( 'Export format not implemented.', 'skylearn-billing-pro' ) );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Return the file URL
		return $this->export_url . $filename;
	}

	/**
	 * Export data to CSV format.
	 *
	 * @since    1.0.0
	 * @param    array     $data       Data to export.
	 * @param    string    $file_path  File path.
	 * @param    array     $options    Export options.
	 * @return   bool|WP_Error        Success status or error.
	 */
	private function export_to_csv( $data, $file_path, $options = array() ) {
		$file_handle = fopen( $file_path, 'w' );

		if ( false === $file_handle ) {
			return new WP_Error( 'file_creation_failed', __( 'Failed to create CSV file.', 'skylearn-billing-pro' ) );
		}

		// Set UTF-8 BOM for better Excel compatibility
		fwrite( $file_handle, "\xEF\xBB\xBF" );

		// Write headers
		if ( ! empty( $data ) ) {
			if ( is_object( $data[0] ) ) {
				$headers = array_keys( get_object_vars( $data[0] ) );
			} else {
				$headers = array_keys( $data[0] );
			}
			fputcsv( $file_handle, $headers );

			// Write data rows
			foreach ( $data as $row ) {
				if ( is_object( $row ) ) {
					$row = get_object_vars( $row );
				}
				fputcsv( $file_handle, $row );
			}
		}

		fclose( $file_handle );
		return true;
	}

	/**
	 * Export data to PDF format.
	 *
	 * @since    1.0.0
	 * @param    array     $data       Data to export.
	 * @param    string    $file_path  File path.
	 * @param    string    $type       Data type.
	 * @param    array     $options    Export options.
	 * @return   bool|WP_Error        Success status or error.
	 */
	private function export_to_pdf( $data, $file_path, $type, $options = array() ) {
		// Check if we can use a PDF library
		if ( class_exists( 'TCPDF' ) ) {
			return $this->export_to_pdf_tcpdf( $data, $file_path, $type, $options );
		} elseif ( class_exists( 'Dompdf\\Dompdf' ) ) {
			return $this->export_to_pdf_dompdf( $data, $file_path, $type, $options );
		} else {
			// Fallback: Generate HTML and convert using built-in tools or suggest library installation
			return $this->export_to_pdf_html( $data, $file_path, $type, $options );
		}
	}

	/**
	 * Export to PDF using TCPDF library.
	 *
	 * @since    1.0.0
	 * @param    array     $data       Data to export.
	 * @param    string    $file_path  File path.
	 * @param    string    $type       Data type.
	 * @param    array     $options    Export options.
	 * @return   bool|WP_Error        Success status or error.
	 */
	private function export_to_pdf_tcpdf( $data, $file_path, $type, $options = array() ) {
		try {
			$pdf = new TCPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );
			
			// Set document information
			$pdf->SetCreator( 'SkyLearn Billing Pro' );
			$pdf->SetAuthor( get_bloginfo( 'name' ) );
			$pdf->SetTitle( ucfirst( $type ) . ' Report' );
			
			// Set margins
			$pdf->SetMargins( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
			$pdf->SetHeaderMargin( PDF_MARGIN_HEADER );
			$pdf->SetFooterMargin( PDF_MARGIN_FOOTER );
			
			// Add a page
			$pdf->AddPage();
			
			// Set font
			$pdf->SetFont( 'helvetica', '', 12 );
			
			// Generate HTML content
			$html = $this->generate_pdf_html( $data, $type, $options );
			
			// Output HTML content
			$pdf->writeHTML( $html, true, false, true, false, '' );
			
			// Save the PDF
			$pdf->Output( $file_path, 'F' );
			
			return true;
		} catch ( Exception $e ) {
			return new WP_Error( 'pdf_generation_failed', $e->getMessage() );
		}
	}

	/**
	 * Export to PDF using DomPDF library.
	 *
	 * @since    1.0.0
	 * @param    array     $data       Data to export.
	 * @param    string    $file_path  File path.
	 * @param    string    $type       Data type.
	 * @param    array     $options    Export options.
	 * @return   bool|WP_Error        Success status or error.
	 */
	private function export_to_pdf_dompdf( $data, $file_path, $type, $options = array() ) {
		try {
			$dompdf = new Dompdf\Dompdf();
			
			// Generate HTML content
			$html = $this->generate_pdf_html( $data, $type, $options );
			
			$dompdf->loadHtml( $html );
			$dompdf->setPaper( 'A4', 'portrait' );
			$dompdf->render();
			
			// Save the PDF
			file_put_contents( $file_path, $dompdf->output() );
			
			return true;
		} catch ( Exception $e ) {
			return new WP_Error( 'pdf_generation_failed', $e->getMessage() );
		}
	}

	/**
	 * Export to PDF using HTML fallback.
	 *
	 * @since    1.0.0
	 * @param    array     $data       Data to export.
	 * @param    string    $file_path  File path.
	 * @param    string    $type       Data type.
	 * @param    array     $options    Export options.
	 * @return   bool|WP_Error        Success status or error.
	 */
	private function export_to_pdf_html( $data, $file_path, $type, $options = array() ) {
		// For now, create an HTML file that can be printed to PDF
		$html = $this->generate_pdf_html( $data, $type, $options );
		
		// Change extension to HTML for fallback
		$html_path = str_replace( '.pdf', '.html', $file_path );
		
		$result = file_put_contents( $html_path, $html );
		
		if ( false === $result ) {
			return new WP_Error( 'file_creation_failed', __( 'Failed to create PDF file.', 'skylearn-billing-pro' ) );
		}
		
		return true;
	}

	/**
	 * Generate HTML content for PDF.
	 *
	 * @since    1.0.0
	 * @param    array     $data     Data to format.
	 * @param    string    $type     Data type.
	 * @param    array     $options  Options.
	 * @return   string             HTML content.
	 */
	private function generate_pdf_html( $data, $type, $options = array() ) {
		$html = '<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<title>' . ucfirst( $type ) . ' Report</title>
			<style>
				body { font-family: Arial, sans-serif; font-size: 12px; }
				h1 { color: #333; border-bottom: 2px solid #0073aa; }
				table { width: 100%; border-collapse: collapse; margin: 20px 0; }
				th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
				th { background-color: #f5f5f5; font-weight: bold; }
				.summary { background-color: #f9f9f9; padding: 15px; margin: 20px 0; }
				.footer { margin-top: 50px; font-size: 10px; color: #666; }
			</style>
		</head>
		<body>';

		$html .= '<h1>' . ucfirst( $type ) . ' Report</h1>';
		$html .= '<p>Generated on: ' . date( 'F j, Y g:i a' ) . '</p>';

		if ( ! empty( $data ) ) {
			$html .= '<table>';
			
			// Headers
			if ( is_object( $data[0] ) ) {
				$headers = array_keys( get_object_vars( $data[0] ) );
			} else {
				$headers = array_keys( $data[0] );
			}
			
			$html .= '<thead><tr>';
			foreach ( $headers as $header ) {
				$html .= '<th>' . esc_html( ucwords( str_replace( '_', ' ', $header ) ) ) . '</th>';
			}
			$html .= '</tr></thead>';
			
			// Data rows
			$html .= '<tbody>';
			foreach ( $data as $row ) {
				if ( is_object( $row ) ) {
					$row = get_object_vars( $row );
				}
				$html .= '<tr>';
				foreach ( $row as $cell ) {
					$html .= '<td>' . esc_html( $cell ) . '</td>';
				}
				$html .= '</tr>';
			}
			$html .= '</tbody>';
			
			$html .= '</table>';
		} else {
			$html .= '<p>No data available for the selected criteria.</p>';
		}

		$html .= '<div class="footer">';
		$html .= '<p>Report generated by SkyLearn Billing Pro | ' . get_bloginfo( 'name' ) . '</p>';
		$html .= '</div>';

		$html .= '</body></html>';

		return $html;
	}

	/**
	 * Export data to XLSX format.
	 *
	 * @since    1.0.0
	 * @param    array     $data       Data to export.
	 * @param    string    $file_path  File path.
	 * @param    array     $options    Export options.
	 * @return   bool|WP_Error        Success status or error.
	 */
	private function export_to_xlsx( $data, $file_path, $options = array() ) {
		// Check if PhpSpreadsheet is available
		if ( class_exists( 'PhpOffice\\PhpSpreadsheet\\Spreadsheet' ) ) {
			return $this->export_to_xlsx_phpspreadsheet( $data, $file_path, $options );
		} else {
			// Fallback: Create a simple XML-based Excel file
			return $this->export_to_xlsx_simple( $data, $file_path, $options );
		}
	}

	/**
	 * Export to XLSX using PhpSpreadsheet.
	 *
	 * @since    1.0.0
	 * @param    array     $data       Data to export.
	 * @param    string    $file_path  File path.
	 * @param    array     $options    Export options.
	 * @return   bool|WP_Error        Success status or error.
	 */
	private function export_to_xlsx_phpspreadsheet( $data, $file_path, $options = array() ) {
		try {
			$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();
			
			if ( ! empty( $data ) ) {
				// Headers
				if ( is_object( $data[0] ) ) {
					$headers = array_keys( get_object_vars( $data[0] ) );
				} else {
					$headers = array_keys( $data[0] );
				}
				
				$col = 1;
				foreach ( $headers as $header ) {
					$sheet->setCellValueByColumnAndRow( $col, 1, ucwords( str_replace( '_', ' ', $header ) ) );
					$col++;
				}
				
				// Data rows
				$row = 2;
				foreach ( $data as $data_row ) {
					if ( is_object( $data_row ) ) {
						$data_row = get_object_vars( $data_row );
					}
					
					$col = 1;
					foreach ( $data_row as $cell ) {
						$sheet->setCellValueByColumnAndRow( $col, $row, $cell );
						$col++;
					}
					$row++;
				}
				
				// Style the header row
				$header_range = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex( count( $headers ) ) . '1';
				$sheet->getStyle( $header_range )->getFont()->setBold( true );
				$sheet->getStyle( $header_range )->getFill()
					->setFillType( \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID )
					->getStartColor()->setARGB( 'FFE9E9E9' );
			}
			
			$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
			$writer->save( $file_path );
			
			return true;
		} catch ( Exception $e ) {
			return new WP_Error( 'xlsx_generation_failed', $e->getMessage() );
		}
	}

	/**
	 * Export to XLSX using simple XML.
	 *
	 * @since    1.0.0
	 * @param    array     $data       Data to export.
	 * @param    string    $file_path  File path.
	 * @param    array     $options    Export options.
	 * @return   bool|WP_Error        Success status or error.
	 */
	private function export_to_xlsx_simple( $data, $file_path, $options = array() ) {
		// For simplicity, fallback to CSV format when XLSX libraries aren't available
		$csv_path = str_replace( '.xlsx', '.csv', $file_path );
		return $this->export_to_csv( $data, $csv_path, $options );
	}

	/**
	 * Generate filename for export.
	 *
	 * @since    1.0.0
	 * @param    string    $type      Data type.
	 * @param    string    $format    Export format.
	 * @return   string              Generated filename.
	 */
	private function generate_filename( $type, $format ) {
		$timestamp = date( 'Y-m-d-H-i-s' );
		return "slbp-{$type}-{$timestamp}.{$format}";
	}

	/**
	 * Ensure export directory exists.
	 *
	 * @since    1.0.0
	 */
	private function ensure_export_directory() {
		if ( ! file_exists( $this->export_dir ) ) {
			wp_mkdir_p( $this->export_dir );
			
			// Create .htaccess to protect exports
			$htaccess_content = "Options -Indexes\n<Files *.php>\nDeny from all\n</Files>";
			file_put_contents( $this->export_dir . '.htaccess', $htaccess_content );
		}
	}

	/**
	 * Clean up old export files.
	 *
	 * @since    1.0.0
	 * @param    int    $days_old    Delete files older than this many days.
	 */
	public function cleanup_old_exports( $days_old = 7 ) {
		$files = glob( $this->export_dir . 'slbp-*' );
		$cutoff_time = time() - ( $days_old * 24 * 60 * 60 );
		
		foreach ( $files as $file ) {
			if ( is_file( $file ) && filemtime( $file ) < $cutoff_time ) {
				unlink( $file );
			}
		}
	}

	/**
	 * Get supported formats.
	 *
	 * @since    1.0.0
	 * @return   array    Supported export formats.
	 */
	public function get_supported_formats() {
		return $this->supported_formats;
	}
}