<?php

/**
 * Download file via SFTP
 *
 * @param type $data_importer
 * @return string
 */
use Symfony\Component\HttpFoundation\RedirectResponse;

function data_import_get_file_sftp($data_importer) {
  try {
    $library = libraries_detect('sftp');
    if (isset($library['library path']) && !empty($library['library path'])) {       
      require_once $library['library path'] . '/vendor/autoload.php';
      $sftp = new phpseclib\Net\SFTP($data_importer['host']);
      data_import_log(DATA_IMPORT_INFO, t('Connect to @host host SFTP', array('@host' => $data_importer['host'])));
  
      if (!$sftp->login($data_importer['username'], $data_importer['password'])) {
        // Error Login
        data_import_log(DATA_IMPORT_ERROR, t('Error login to sftp server : @server', array('@server' => $data_importer['host'])));
      } else {
        $remote_file = $data_importer['remote_directory'] . '/' . $data_importer['file_name'];
        $local_file = drupal_realpath('public://') . $data_importer['local_directory'] . '/' . $data_importer['file_name'];
        // Get file
        $sftp->get($remote_file, $local_file);
        if (file_exists($local_file)) {
          data_import_log(DATA_IMPORT_INFO, t('Successfully downloaded @file', array('@file' => $remote_file)));
        } else {
          data_import_log(DATA_IMPORT_ERROR, t('Error during transfert File `@file` from Server :@server', array('@file' => $remote_file, '@server' => $data_importer['host'])));
          drupal_set_message(t('Error during transfert File `@file` from Server :@server', array('@file' => $remote_file, '@server' => $data_importer['host'])), 'error');
          $response = new RedirectResponse(\Drupal::url('data_import.settings'), 302);
          $response->send();
        }
        return $local_file;
      }
    }else{
      data_import_log(DATA_IMPORT_ERROR, t('SFTP library not found', array()));
      drupal_set_message(t("Couldn't find the sftp library. Import aborted! Please get it from <a href='https://github.com/phpseclib/phpseclib/tree/master/phpseclib'>https://github.com/phpseclib/phpseclib/tree/master/phpseclib</a>"), 'error');
      return FALSE;
    }
  } catch (Exception $e) {
    data_import_log(DATA_IMPORT_ERROR, t('Error getting file to sftp server', array()));
  }
}

/**
 * Download file via FTP
 *
 * @param type $data_importer
 * @return string
 */
function data_import_get_file_ftp($data_importer) {
  try {
    // Set up basic connection
    $conn_id = ftp_connect($data_importer['host']);
    // Login with username and password
    $login_result = ftp_login($conn_id, $data_importer['username'], $data_importer['password']);
    // Test fail connect ftp.
    if ((!$conn_id) || (!$login_result)) {
      if (!$conn_id) {
        data_import_log(DATA_IMPORT_ERROR, t('Could not connect to @host.', array('@host' => $data_importer['host'])));
      }
      if (!$login_result) {
        data_import_log(DATA_IMPORT_ERROR, t('Could not authenticate with username @username and password @password.', array('@username' => $data_importer['username'], '@password' => $data_importer['password'])));
      }
    } else {
      ftp_pasv($conn_id, TRUE);
      $remote_file = $data_importer['remote_directory'] . '/' . $data_importer['file_name'];
      $local_file = drupal_realpath('public://') .DIRECTORY_SEPARATOR. $data_importer['local_directory'] .DIRECTORY_SEPARATOR. $data_importer['file_name'];
      // Download file
      if (ftp_get($conn_id, $local_file, $remote_file, FTP_BINARY)) {
        data_import_log(DATA_IMPORT_INFO, t('Successfully downloaded @file.', array('@file' => $remote_file)));

        return $local_file;
      } else {
        data_import_log(DATA_IMPORT_ERROR, t('There was a problem while downloading @file.', array('@file' => $remote_file)));
      }
      ftp_close($conn_id);
    }
  } catch (Exception $e) {
    data_import_log(DATA_IMPORT_ERROR, t('Error getting file to ftp server.', array()));
  }
}
