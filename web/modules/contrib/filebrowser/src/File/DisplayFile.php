<?php

namespace Drupal\filebrowser\File;

use Drupal\Core\Controller\ControllerBase;

class DisplayFile extends ControllerBase {

  /**
   * @var string
   */
  public $id;

  /**
   * @var integer
   */
  public $nid;
  /**
   * @var integer
   */
  public $fid;
  /**
   * @var string
   */
  public $description;
  /**
   * @var string
   */
  public $displayName; //Test_test_test.jpg
  /**
   * @var string
   */
  public $fsRoot;
  /**
   * @var string
   */
  public $relativePath; //Test_test_test.jpg
  /**
   * @var string
   */
  public $fullPath; //Test_test_test.jpg
  /**
   * @var boolean
   */
  public $status; //0
  /**
   * @var  object
   */
  public $file; //stdClass Object

  /**
   * @var  object
   */
  public $fileData; //stdClass Object
  /**
   * @var string
   */
  public $uri; // public://Directory/Test_test_test.jpg
  /**
   * @var string
   */
  public $filename; // Test_test_test.jpg
  /**
   * @var string
   */
  public $name; // Test_test_test
  /**
   * @var string
   */
  public $url;  //http://drupalvm.dev/sites/default/files/Directory/Test_test_test.jpg
  /**
   * @var string
   */
  public $mimetype; // image/jpeg
  /**
   * @var integer
   */
  public $size; // 78527
  /**
   * @var string
   */
  public $type; // file
  /**
   * @var integer
   */
  public $timestamp; // 1467989263
  /**
   * @var \Drupal\Core\Link
   */
  public $link; // Drupal\Core\GeneratedLink Object

  /**
   * @var string
   */
  public $href;

  /**
   * @var array
   */
  public $metadata;

  public function __constructor($nid) {
    $this->nid = $nid;
  }

  /**
   * @param $file_relative_path
   * @param \stdClass $fs_file
   * @param $stats
   * @param $db_content
   * @param $root
   * @return array
   */
  public function fileSetData($file_relative_path, $fs_file, &$stats, $db_content, $root) {
    $this->fid = isset($db_content['fid']) ? $db_content['fid'] : null;
    $this->description = isset($db_content['description']) ? $db_content['description'] : null;
    $this->displayName = $db_content['display_name'];
    $this->name = $db_content['display_name'];
    $this->fsRoot = $root;
    $this->relativePath = $file_relative_path;
    $this->fullPath = rtrim($this->fsRoot, '/') . "/" . $fs_file->filename;
    $this->status = MARK_READ;
    $this->fileData = $fs_file;

    if($this->currentUser()->id() && isset($this->fileData->timestamp)) {
      // if ($this->user->getLastAccessedTime() <
      if ($this->currentUser()->getLastAccessedTime() < $this->fileData->timestamp) {
        $this->status = MARK_NEW;
      }
    }
    return $this;
  }

  /**
   * @function Creates a .. file for the abstracted file system.
   * @param  string $relative_path
   * @return object $this
   */

  public function createSubdir($relative_path) {

    function s3_create_subdir($nid, $fs_root){
      return [
        'nid' => $nid,
        'display-name' => '..',
        'relative-path' => '/',
        'full-path'  => $fs_root,
        'status' => MARK_READ,
        'kind' => 2,
        'mime-type' => 'folder/parent',
        'url' => url('node/' . $nid, ['absolute' => TRUE]),
      ];
    }


    $this->nid =
    $this->fid = null;
    $this->description =  null;
    $this->displayName = '..';
    $this->fsRoot = null;
    $this->relativePath = $relative_path;
    $this->fullPath = $this->fsRoot;
    //todo: mark logic
    $this->status = MARK_READ;
    $this->fileData = new \stdClass();
    $this->fileData->mimetype = 'folder/parent';
    $this->fileData->type = 'directory';
    return $this;
  }

  /**
   * @function Creates a . file for the abstracted file system.
   * @param  string $relative_path
   * @return object $this
   */
  public function createUpDir($relative_path) {
    $this->fid = null;
    $this->description =  null;
    $this->displayName = '.';
    $this->fsRoot = null;
    $this->relativePath = $relative_path;;
    $this->fullPath = $this->fsRoot;
    //todo: mark logic
    $this->status = MARK_READ;
    $this->fileData = new \stdClass();
    $this->fileData->mimetype = 'folder';
    $this->fileData->type = 'directory';
    return $this;
  }

}