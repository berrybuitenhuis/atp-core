<?php

namespace AtpCore\Encoding;

class Form {
    
    /**
    * Decode x-form-encoded data
    * 
    * @param string $dataString, example test=1&test2=3&data=1+2
    * @param boolean $urlEncode, url-encode value
    * @return array
    */
    public function decode($dataString, $urlEncode = false) {
        if (empty($dataString)) return array();
        
        $output = array();
        $dataElements = explode("&", $dataString);
        foreach ($dataElements AS $dataElement) {
            $data = explode("=", $dataElement);
            $output[$data[0]] = ($urlEncode === true) ? urlencode($data[1]) : $data[1];
        }
        
        return $data;
    }
    
}

?>