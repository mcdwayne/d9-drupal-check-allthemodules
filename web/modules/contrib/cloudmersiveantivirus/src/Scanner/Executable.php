<?php

namespace Drupal\cloudmersiveantivirus\Scanner;

use Drupal\file\FileInterface;
use Drupal\cloudmersiveantivirus\ScannerInterface;
use Drupal\cloudmersiveantivirus\Scanner;
use Drupal\cloudmersiveantivirus\Config;

class Executable implements ScannerInterface {
  private $_executable_path = '';
  private $_executable_parameters = '';
  private $_file = '';
  protected $_virus_name = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(Config $config) {
    $this->_executable_path       = $config->get('mode_executable.executable_path');
    $this->_executable_parameters = $config->get('mode_executable.executable_parameters');
  }

  /**
   * {@inheritdoc}
   */
  public function scan(FileInterface $file) {
    // Verify that the executable exists.
/*    if (!file_exists($this->_executable_path)) {
      \Drupal::logger('Cloudmersive Antivirus')->warning('Unable to find CloudmersiveAntivirus executable at @executable_path', array('@executable_path' => $this->_executable_path));
      return Scanner::FILE_IS_UNCHECKED;
    }*/

    // Redirect STDERR to STDOUT to capture the full output of the CloudmersiveAntivirus script.
    $script = "{$this->_executable_path} {$this->_executable_parameters}";
    $filename = drupal_realpath($file->getFileUri());
    $cmd = escapeshellcmd($script) . ' ' . escapeshellarg($filename) . ' 2>&1';

    // Text output from the executable is assigned to: $output
    // Return code from the executable is assigned to: $return_code.

    // Possible return codes (see `man clamscan`):
    // - 0 = No virus found.
    // - 1 = Virus(es) found.
    // - 2 = Some error(s) occured.

    // Note that older versions of clamscan (prior to 0.96) may have return
    // values greater than 2. Any value of 2 or greater means that the scan
    // failed, and the file has not been checked.
//    exec($cmd, $output, $return_code);
//    $output = implode("\n", $output);

//$cFile = curl_file_create($filename);

//\Drupal::logger('Cloudmersive Antivirus')->warning("File: " . $filename  );


$curl = curl_init();

$key = $this->_executable_path;

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.cloudmersive.com/virus/scan/file",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_HTTPHEADER => array(
    "cache-control: no-cache",
    "Apikey: " . $key,
    "content-type: application/x-www-form-urlencoded"
  ),
  CURLOPT_POSTFIELDS => array(
      'inputFile' => new \CURLFile($filename)
    )
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
//  echo "cURL Error #:" . $err;
} else {
//  echo $response;
}

$strResponse = (string) $response;

//\Drupal::logger('Cloudmersive Antivirus')->warning("Got response: " . $strResponse . "Err: " . $err  );



if (strpos($strResponse, '"CleanResult":true') !== false) {
	// \Drupal::logger('Cloudmersive Antivirus')->warning("Scanned uploaded file, clean result."  );
    return Scanner::FILE_IS_CLEAN;
}
else
{
	//\Drupal::logger('Cloudmersive Antivirus')->warning("Scanned uploaded file, INFECTED result - BLOCKED."  );
	return Scanner::FILE_IS_INFECTED;
}



  /*  switch ($return_code) {
      case 0:
        return Scanner::FILE_IS_CLEAN;
        // return array(Scanner::FILE_IS_CLEAN, $return_code, $output);

      case 1:
        return Scanner::FILE_IS_INFECTED;
        // return array(Scanner::FILE_IS_INFECTED, $return_code, $output);

      default:
        return Scanner::FILE_IS_UNCHECKED;
        // return array(Scanner::FILE_IS_UNCHECKED, $return_code, $output);
    }*/
  }

  /**
   * {@inheritdoc}
   */
  public function virus_name() {
    return $this->_virus_name;
  }

  /**
   * {@inheritdoc}
   */
  public function version() {
  
  	return "1";
  
    // if (file_exists($this->_executable_path)) {
      // return exec(escapeshellcmd($this->_executable_path) . ' -V');
    // }
    // else {
      // return NULL;
    // }
  }
}
