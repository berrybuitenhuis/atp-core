<?php

namespace AtpCore\File;

class Excel
{

    /**
     * Get (excel) data of S3-object
     *
     * @param array $s3Object
     * @return \PhpOffice\PhpSpreadsheet\SpreadSheet
     */
    public static function getDataByS3Object($s3Object)
    {
        // Write to temporary file
        $tmpFileName = tempnam(sys_get_temp_dir(), "xlsx_S3");
        $handle = fopen($tmpFileName, "w");
        fwrite($handle, $s3Object['Body']->getContents());
        fclose($handle);

        // Retrieve data
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $spreadSheet = $reader->load($tmpFileName);

        // Remove temporary file
        unlink($tmpFileName);

        // Return
        return $spreadSheet;
    }

}