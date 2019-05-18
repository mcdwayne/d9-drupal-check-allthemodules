<?php

namespace Drupal\filebrowser;

use Drupal\filebrowser\Services\Common;
use Drupal\node\NodeInterface;

class ServerFileList {

  /**
   * @var array
   */
  protected $serverFileList;
  /**
   * @var NodeInterface
   */
  protected $node;

  /**
   * @var $string
   */
  protected $relativePath;

  /**
   * @var Common
   */
  protected $common;

  /**
   * @var Filebrowser
   */
  protected $filebrowser;

  /**
   * ServerFileList constructor.
   * @param \Drupal\node\NodeInterface $node
   * @param string $relative_path
   */
  public function __construct(NodeInterface $node, $relative_path) {
    $this->serverFileList = $this->createServerFileList($node, $relative_path);
    $this->common = \Drupal::service('filebrowser.common');
    $this->filebrowser = $node->filebrowser;
  }


  public function getList() {
    return $this->serverFileList;
  }

  /**
   * Retrieves files from the file system
   * @param NodeInterface $node
   * @param string $relative_path
   * @return array list of files filtered as per node settings and restrictions
   * returns an array of objects keyed to the uri:
   *   [public://directory/ic_autorenew_white_18px.svg] => stdClass Object(
   *     [uri] => public://directory/ic_autorenew_white_18px.svg // file_scan_directory
   *     [filename] => ic_autorenew_white_18px.svg               // file_scan_directory
   *     [name] => ic_autorenew_white_18px                       // file_scan_directory
   *     [url] => http://drupal8.dev/sites/default/files/NFTR/file.svg
   *     [mimetype] => application/octet-stream
   *     [size] => 394
   *     [type] => file
   *     [timestamp] => 1460968364)
   */
  protected function createServerFileList($node, $relative_path) {
    /** @var Filebrowser $folder_path */
    $folder_path = $node->filebrowser->folderPath;
    $directory = $folder_path . $relative_path;
    $files = file_scan_directory($directory, '/.*/', ['recurse' => false]);
    $validator = \Drupal::service('filebrowser.validator');

    foreach ($files as $key => $file) {

      $file->url = file_create_url($file->uri);
      // Complete the required file data

      $file->mimetype = \Drupal::service('file.mime_type.guesser')->guess($file->filename);

      $file->size = filesize($file->uri);
      $file->type = filetype($file->uri);
      $file->timestamp = filectime($file->uri);

      if (
        // filter whitelist and blacklist
        ($file->type != 'dir' && !$validator->whiteListed($file->filename, $node->filebrowser->whitelist)) ||
        $validator->blackListed($file->filename, $node->filebrowser->forbiddenFiles) ||
        // sub folder reading rights
        (!$node->filebrowser->exploreSubdirs && $file->type == 'dir')
      ) {
        unset($files[$key]);
      }
    }
    return $files;
  }

}