<?php

namespace Application\Services;

class Pdf_generator_service
{
    /**
     * Generates a PDF from an HTML string.
     *
     * @param string $html_string The string of HTML file.
     * @param string $title The title of the PDF file.
     * @throws \Mpdf\MpdfException If an error occurs during PDF generation.
     * @throws \Exception If the file does not exist.
     */
    public function generate_pdf_from_html_string(string $html_string, string $title): void
    {
        // Configure mPDF with A4 paper size
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4', // Set paper size to A4
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        // Write HTML content to PDF
        $mpdf->WriteHTML($html_string);

        // Output the PDF
        $mpdf->Output($title, \Mpdf\Output\Destination::DOWNLOAD);
    }
}
