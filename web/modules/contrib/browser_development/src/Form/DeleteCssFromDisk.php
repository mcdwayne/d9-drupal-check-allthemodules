<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 10/04/17
 * Time: 7:23 AM
 */

namespace Drupal\browser_development\Form;

/**
 * Class DeleteCssFromDisk
 * @package Drupal\browser_development\Form
 */
class DeleteCssFromDisk {

  /**
   * @var string
   */
  protected $globalFilePath = 'public://browser-development/';

  /**
   * @var string disk path
   */
  protected $path;

  /**
   * DeleteCssFromDisk constructor.
   */
  public function __construct() {

    $this->path = \Drupal::service('file_system')->realpath($this->globalFilePath);
  }


  /**
   * @param $fileToBeDeleted
   */
  protected function deleteFileFromDisk($fileToBeDeleted) {

    $file = "$this->path/css/$fileToBeDeleted";
    unlink($file);

    //-- So user can see what the have deleted to file
     drupal_flush_all_caches();

  }

  /**
   * @param $formStateInformation
   */
  public function setDeleteBackground($formStateInformation) {

    $this->deleteFileFromDisk('background.css');
  }

  /**
   * @param $formStateInformation
   */
  public function setDeleteMenus($formStateInformation) {

    $this->deleteFileFromDisk($formStateInformation,'alink.css');
  }

  /**
   * @param $formStateInformation
   */
  public function setTextColor($formStateInformation) {

    $this->deleteFileFromDisk($formStateInformation,'text.css');
  }

}