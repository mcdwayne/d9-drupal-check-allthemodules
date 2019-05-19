<?php

namespace Drupal\vfd\Controller;

use Drupal\Core\Controller\ControllerBase;
use PclZip;

/**
 *
 */
class DownloadNodeController extends ControllerBase {

  /**
   *
   */
  public function download($type, $nid) {
    $node_files = [];
    if ($type == 'node' && is_numeric($nid)) {
      $nodeID = $nid;
    }

    $node_files = files_node($nodeID);
    $fileName = 'NodeFiles.zip';
    $tmp_file = file_save_data('', 'temporary://' . $fileName, FILE_EXISTS_REPLACE);
    $tmp_file->status = 0;
    $tmp_file->save();
    $file = $tmp_file;
    $path = \Drupal::service('file_system')->realpath($file->getFileUri());
    $archive = new PclZip($path);
    $archive->add($node_files, PCLZIP_OPT_REMOVE_ALL_PATH);
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"" . $fileName . "\"");
    header("Content-Transfer-Encoding: binary");
    readfile($path);
    unlink($path);
    exit();
  }

}
