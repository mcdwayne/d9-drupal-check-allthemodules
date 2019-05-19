<?php

namespace Drupal\vfd\Controller;

use Drupal\Core\Controller\ControllerBase;
use PclZip;

/**
 *
 */
class DownloadViewController extends ControllerBase {

  /**
   *
   */
  public function download($path) {

    $view_files = [];
    $fileName = "ViewFiles.zip";
    foreach ((views_get_view_result($path)) as $key => $value) {
      $files[$key] = files_node($value->nid);
    }
    $i = 0;
    foreach ($files as $key => $value) {
      foreach ($value as $key2 => $value2) {
        $view_files[$i] = \Drupal::service('file_system')->realpath($value2);
        $i++;
      }
    }
    $tmp_file = file_save_data('', 'temporary://' . $fileName, FILE_EXISTS_REPLACE);
    $tmp_file->status = 0;
    $tmp_file->save();
    $file = $tmp_file;
    $tmp_path = \Drupal::service('file_system')->realpath($file->getFileUri());
    $archive = new PclZip($tmp_path);
    $archive->add($view_files, PCLZIP_OPT_REMOVE_ALL_PATH);
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"" . $fileName . "\"");
    header("Content-Transfer-Encoding: binary");
    readfile($tmp_path);
    unlink($tmp_path);
    exit();
  }

}
