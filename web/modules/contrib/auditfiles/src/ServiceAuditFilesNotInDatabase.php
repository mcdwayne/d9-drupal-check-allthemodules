<?php

namespace Drupal\auditfiles;

use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Define all methods that are used on Files not in database functionality.
 */
class ServiceAuditFilesNotInDatabase {

  use StringTranslationTrait;

  /**
   * Define constructor for string translation.
   */
  public function __construct(TranslationInterface $translation) {
    $this->stringTranslation = $translation;
  }

  /**
   * Get the files that are not in database.
   */
  public function auditfilesNotInDatabaseGetReportsFiles() {
    $config = \Drupal::config('auditfiles.settings');
    $report_files = [];
    $reported_files = [];
    $this->auditfilesNotInDatabaseGetFilesForReport('', $report_files);
    if (!empty($report_files)) {
      // Get the static paths necessary for processing the files.
      $file_system_stream = $config->get('auditfiles_file_system_path') ? $config->get('auditfiles_file_system_path') : 'public';
      // The full file system path to the Drupal root directory.
      $real_files_path = drupal_realpath($file_system_stream . '://');
      // Get the chosen date format for displaying the file dates with.
      $date_format = $config->get('auditfiles_report_options_date_format') ? $config->get('auditfiles_report_options_date_format') : 'long';
      foreach ($report_files as $report_file) {
        // Check to see if the file is in the database.
        if (empty($report_file['path_from_files_root'])) {
          $file_to_check = $report_file['file_name'];
        }
        else {
          $file_to_check = $report_file['path_from_files_root'] . DIRECTORY_SEPARATOR . $report_file['file_name'];
        }
        $file_in_database = $this->auditfilesNotInDatabaseIsFileInDatabase($file_to_check);
        // If the file is not in the database, add to the list for displaying.
        if (!$file_in_database) {
          // Gets the file's information (size, date, etc.) and assempbles the.
          // array for the table.
          $reported_files += $this->auditfilesNotInDatabaseFormatRowData($report_file, $real_files_path, $date_format);
        }
      }
    }
    return $reported_files;
  }

  /**
   * Get files for report.
   */
  public function auditfilesNotInDatabaseGetFilesForReport($path, array &$report_files) {
    $config = \Drupal::config('auditfiles.settings');
    $file_system_stream = $config->get('auditfiles_file_system_path') ? $config->get('auditfiles_file_system_path') : 'public';
    $real_files_path = drupal_realpath($file_system_stream . '://');
    $maximum_records = $config->get('auditfiles_report_options_maximum_records') ? $config->get('auditfiles_report_options_maximum_records') : 250;
    if ($maximum_records > 0 && count($report_files) < $maximum_records) {
      $new_files = $this->auditfilesNotInDatabaseGetFiles($path);
      if (!empty($new_files)) {
        foreach ($new_files as $file) {
          // Check if the current item is a directory or a file.
          if (empty($file['path_from_files_root'])) {
            $item_path_check = $real_files_path . DIRECTORY_SEPARATOR . $file['file_name'];
          }
          else {
            $item_path_check = $real_files_path . DIRECTORY_SEPARATOR . $file['path_from_files_root'] . DIRECTORY_SEPARATOR . $file['file_name'];
          }
          if (is_dir($item_path_check)) {
            // The item is a directory, so go into it and get any files there.
            if (empty($path)) {
              $file_path = $file['file_name'];
            }
            else {
              $file_path = $path . DIRECTORY_SEPARATOR . $file['file_name'];
            }
            $this->auditfilesNotInDatabaseGetFilesForReport($file_path, $report_files);
          }
          else {
            // The item is a file, so add it to the list.
            $file['path_from_files_root'] = $this->auditfilesNotInDatabaseFixPathSeparators($file['path_from_files_root']);
            $report_files[] = $file;
          }
        }
      }
    }
  }

  /**
   * Checks if the specified file is in the database.
   *
   * @param string $filepathname
   *   The path and filename, from the "files" directory, of the file to check.
   *
   * @return bool
   *   Returns TRUE if the file was found in the database, or FALSE, if not.
   */
  public function auditfilesNotInDatabaseIsFileInDatabase($filepathname) {
    $file_uri = file_build_uri($filepathname);
    $connection = Database::getConnection();
    $query = $connection->select('file_managed', 'fm');
    $query->condition('fm.uri', $file_uri);
    $query->fields('fm', ['fid']);
    $fid = $query->execute()->fetchField();
    return empty($fid) ? FALSE : TRUE;
  }

  /**
   * Add files to record to display in reports.
   */
  public function auditfilesNotInDatabaseFormatRowData($file, $real_path, $date_format) {
    $filename = $file['file_name'];
    $filepath = $file['path_from_files_root'];
    if (empty($filepath)) {
      $filepathname = $filename;
    }
    else {
      $filepathname = $filepath . DIRECTORY_SEPARATOR . $filename;
    }
    $real_filepathname = $real_path . DIRECTORY_SEPARATOR . $filepathname;
    $filemime = \Drupal::service('file.mime_type.guesser')->guess($real_filepathname);
    $filesize = number_format(filesize($real_filepathname));
    if (!empty($date_format)) {
      $filemodtime = format_date(filemtime($real_filepathname), $date_format);
    }
    // Format the data for the table row.
    $row_data[$filepathname] = [
      'filepathname' => empty($filepathname) ? '' : $filepathname,
      'filemime' => empty($filemime) ? '' : $filemime,
      'filesize' => !isset($filesize) ? '' : $filesize,
      'filemodtime' => empty($filemodtime) ? '' : $filemodtime,
      'filename' => empty($filename) ? '' : $filename,
    ];
    return $row_data;
  }

  /**
   * Retrieves a list of files in the given path.
   *
   * @param string $path
   *   The path to search for files in.
   *
   * @return array
   *   The list of files and diretories found in the given path.
   */
  public function auditfilesNotInDatabaseGetFiles($path) {
    $config = \Drupal::config('auditfiles.settings');
    $file_system_stream = $config->get('auditfiles_file_system_path') ? $config->get('auditfiles_file_system_path') : 'public';
    $real_files_path = drupal_realpath($file_system_stream . '://');
    $exclusions = $this->auditfilesGetExclusions();
    // The variable to store the data being returned.
    $file_list = [];
    if (empty($path)) {
      $scan_path = $real_files_path;
    }
    else {
      $scan_path = $real_files_path . DIRECTORY_SEPARATOR . $path;
    }
    // Get the files in the specified directory.
    $files = scandir($scan_path);
    foreach ($files as $file) {
      if ($file != '.' && $file != '..') {
        // Check to see if this file should be included.
        $include_file = $this->auditfilesNotInDatabaseIncludeFile(
          $real_files_path . DIRECTORY_SEPARATOR . $path,
            $file,
            $exclusions
          );
        if ($include_file) {
          // The file is to be included, so add it to the data array.
          $file_list[] = [
            'file_name' => $file,
            'path_from_files_root' => $path,
          ];
        }
      }
    }
    return $file_list;
  }

  /**
   * Corrects the separators of a file system's file path.
   *
   * Changes the separators of a file path, so they are match the ones
   * being used on the operating system the site is running on.
   *
   * @param string $path
   *   The path to correct.
   *
   * @return string
   *   The corrected path.
   */
  public function auditfilesNotInDatabaseFixPathSeparators($path) {
    $path = preg_replace('@\/\/@', DIRECTORY_SEPARATOR, $path);
    $path = preg_replace('@\\\\@', DIRECTORY_SEPARATOR, $path);
    return $path;
  }

  /**
   * Creates an exclusion string.
   *
   * This function creates a list of file and/or directory exclusions to be used
   * with a preg_* function.
   *
   * @return string
   *   The excluions.
   */
  public function auditfilesGetExclusions() {
    $config = \Drupal::config('auditfiles.settings');
    $exclusions_array = [];
    $files = trim($config->get('auditfiles_exclude_files') ? $config->get('auditfiles_exclude_files') : '.htaccess');
    if ($files) {
      $exclude_files = explode(';', $files);
      array_walk($exclude_files, '\\Drupal\\auditfiles\\AuditFilesBatchProcess::auditfilesMakePreg', FALSE);
      $exclusions_array = array_merge($exclusions_array, $exclude_files);
    }
    $paths = trim($config->get('auditfiles_exclude_paths') ? $config->get('auditfiles_exclude_paths') : 'color;css;ctools;js');
    if ($paths) {
      $exclude_paths = explode(';', $paths);
      array_walk($exclude_paths, '\\Drupal\\auditfiles\\AuditFilesBatchProcess::auditfilesMakePreg', TRUE);
      $exclusions_array = array_merge($exclusions_array, $exclude_paths);
    }
    // Exclude other file streams that may be deinfed and in use.
    $exclude_streams = [];
    $auditfiles_file_system_path = $config->get('auditfiles_file_system_path') ? $config->get('auditfiles_file_system_path') : 'public';
    $file_system_paths = \Drupal::service("stream_wrapper_manager")->getWrappers(StreamWrapperInterface::LOCAL);
    foreach ($file_system_paths as $file_system_path_id => $file_system_path) {
      if ($file_system_path_id != $auditfiles_file_system_path) {
        $uri = $file_system_path_id . '://';
        if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($uri)) {
          $exclude_streams[] = $wrapper->realpath();
        }
      }
    }
    array_walk($exclude_streams, '\\Drupal\\auditfiles\\AuditFilesBatchProcess::auditfilesMakePreg', FALSE);
    $exclusions_array = array_merge($exclusions_array, $exclude_streams);
    // Create the list of requested extension exclusions. (This is a little more
    // complicated.)
    $extensions = trim($config->get('auditfiles_exclude_extensions') ? $config->get('auditfiles_exclude_extensions') : '');
    if ($extensions) {
      $exclude_extensions = explode(';', $extensions);
      array_walk($exclude_extensions, '\\Drupal\\auditfiles\\AuditFilesBatchProcess::auditfilesMakePreg', FALSE);
      $extensions = implode('|', $exclude_extensions);
      $extensions = '(' . $extensions . ')$';
      $exclusions_array[] = $extensions;
    }
    // Implode exclusions array to a string.
    $exclusions = implode('|', $exclusions_array);
    // Return prepared exclusion string.
    return $exclusions;
  }

  /**
   * Checks to see if the file is being included.
   *
   * @param string $path
   *   The complete file system path to the file.
   * @param string $file
   *   The name of the file being checked.
   * @param string $exclusions
   *   The list of files and directories that are not to be included in the
   *   list of files to check.
   *
   * @return bool
   *   Returns TRUE, if the path or file is being included, or FALSE,
   *   if the path or file has been excluded.
   *
   * @todo Possibly add other file streams that are on the system but not the one
   *   being checked to the exclusions check.
   */
  public function auditfilesNotInDatabaseIncludeFile($path, $file, $exclusions) {
    if (empty($exclusions)) {
      return TRUE;
    }
    elseif (!preg_match('@' . $exclusions . '@', $file) && !preg_match('@' . $exclusions . '@', $path . DIRECTORY_SEPARATOR . $file)) {
      return TRUE;
    }
    // This path and/or file are being excluded.
    return FALSE;
  }

  /**
   * Returns the header to use for the display table.
   *
   * @return array
   *   The header to use.
   */
  public function auditfilesNotInDatabaseGetHeader() {
    return [
      'filepathname' => [
        'data' => $this->t('File pathname'),
      ],
      'filemime' => [
        'data' => $this->t('MIME'),
      ],
      'filesize' => [
        'data' => $this->t('Size (in bytes)'),
      ],
      'filemodtime' => [
        'data' => $this->t('Last modified'),
      ],
    ];
  }

  /**
   * Creates the batch for adding files to the database.
   *
   * @param array $fileids
   *   The list of file IDs to be processed.
   *
   * @return array
   *   The definition of the batch.
   */
  public function auditfilesNotInDatabaseBatchAddCreateBatch(array $fileids) {
    $batch['title'] = $this->t('Adding files to Drupal file management');
    $batch['error_message'] = $this->t('One or more errors were encountered processing the files.');
    $batch['finished'] = "\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesNotInDatabaseBatchFinishBatch";
    $batch['progress_message'] = $this->t('Completed @current of @total operations.');
    $operations = [];
    $file_ids = [];
    foreach ($fileids as $file_id) {
      if (!empty($file_id)) {
        $file_ids[] = $file_id;
      }
    }
    foreach ($file_ids as $file_id) {
      $operations[] = [
        "\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesNotInDatabaseBatchAddProcessBatch",
        [$file_id],
      ];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Adds the specified file to the database.
   *
   * @param string $filepathname
   *   The full pathname to the file to add to the database.
   */
  public function auditfilesNotInDatabaseBatchAddProcessFile($filepathname) {
    $user = User::load(\Drupal::currentUser()->id());
    $file = new \StdClass();
    $file->uid = $user->get('uid')->value;
    $file->filename = trim(basename($filepathname));
    $file->uri = file_build_uri($filepathname);
    $real_filenamepath = drupal_realpath($file->uri);
    $file->filemime = \Drupal::service('file.mime_type.guesser')->guess($real_filenamepath);
    $file->filesize = filesize($real_filenamepath);
    $file->status = FILE_STATUS_PERMANENT;
    $file->timestamp = REQUEST_TIME;
    $uuid_service = \Drupal::service('uuid');
    $uuid = $uuid_service->generate();

    $connection = Database::getConnection();
    $query = $connection->select('file_managed', 'fm');
    $query->condition('fm.uri', $file->uri);
    $query->fields('fm', ['fid']);
    $existing_file = $query->execute()->fetchField();
    if (empty($existing_file)) {
      $results = \Drupal::database()->merge('file_managed')
        ->key(['fid' => NULL])
        ->fields([
          'fid' => NULL,
          'uuid' => $uuid,
          'langcode' => 'en',
          'uid' => $file->uid,
          'filename' => $file->filename,
          'uri' => $file->uri,
          'filemime' => $file->filemime,
          'filesize' => $file->filesize,
          'status' => $file->status,
          'created' => $file->timestamp,
          'changed' => $file->timestamp,
        ])->execute();
      if (empty($results)) {
        drupal_set_message($this->t('Failed to add %file to the database.', ['%file' => $filepathname]));
      }
      else {
        drupal_set_message($this->t('Sucessfully added %file to the database.', ['%file' => $filepathname]));
      }
    }
    else {
      drupal_set_message($this->t('The file %file is already in the database.', ['%file' => $filepathname]), 'error');
    }
  }

  /**
   * Creates the batch for deleting files from the server.
   *
   * @param array $file_names
   *   The list of file names to be processed.
   *
   * @return array
   *   The definition of the batch.
   */
  public function auditfilesNotInDatabaseBatchDeleteCreateBatch(array $file_names) {
    $batch['title'] = $this->t('Adding files to Drupal file management');
    $batch['error_message'] = $this->t('One or more errors were encountered processing the files.');
    $batch['finished'] = '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesNotInDatabaseBatchFinishBatch';
    $batch['progress_message'] = $this->t('Completed @current of @total operations.');
    $batch['title'] = $this->t('Deleting files from the server');
    $operations = [];
    $filenames = [];
    foreach ($file_names as $file_name) {
      if (!empty($file_name)) {
        $filenames[] = $file_name;
      }
    }
    foreach ($filenames as $filename) {
      $operations[] = [
        '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesNotInDatabaseBatchDeleteProcessBatch',
        [$filename],
      ];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Deletes the specified file from the server.
   *
   * @param string $filename
   *   The full pathname of the file to delete from the server.
   */
  public function auditfilesNotInDatabaseBatchDeleteProcessFile($filename) {
    $config = \Drupal::config('auditfiles.settings');
    $file_system_stream = $config->get('auditfiles_file_system_path') ? $config->get('auditfiles_file_system_path') : 'public';
    $real_files_path = drupal_realpath($file_system_stream . '://');
    if (file_unmanaged_delete($real_files_path . DIRECTORY_SEPARATOR . $filename)) {
      drupal_set_message($this->t('Sucessfully deleted %file from the server.', ['%file' => $filename]));
    }
    else {
      drupal_set_message($this->t('Failed to delete %file from the server.', ['%file' => $filename]));
    }
  }

}
