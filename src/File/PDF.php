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
     * @param array $options
     * @return string|boolean
     */
    public function generate($html, $options = [], $filename = null)
    {
        // Instantiate Dom-pdf
        if (is_array($options) && !empty($options)) {
            // Set options
            $options = new Options();
            foreach ($options AS $key => $value) {
                $options->set($key, $value);
            }

            $dompdf = new Dompdf($options);
        } else {
            $dompdf = new Dompdf();
        }

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