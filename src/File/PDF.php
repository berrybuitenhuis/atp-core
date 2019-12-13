<?php

namespace AtpCore\File;

use \Dompdf\Dompdf;

class PDF
{

    /**
     * Generate PDF-document by HTML
     *
     * @param $html
     * @param string $filename
     * @return string|boolean
     */
    public function generate($html, $filename = null)
    {
        // Instantiate
        $dompdf = new Dompdf();

        // Set HTML
        $dompdf->loadHtml($html);

        // Render the HTML as PDF
        $dompdf->render();

        // Generate PDF-data
        $data = $dompdf->output();

        if (!empty($filename)) {
            // Save PDF-document
            $result = file_put_contents($filename, $data);

            // Return
            if ($result !== false) $result = true;
            return $result;
        } else {
            // Return
            return $data;
        }
    }

}