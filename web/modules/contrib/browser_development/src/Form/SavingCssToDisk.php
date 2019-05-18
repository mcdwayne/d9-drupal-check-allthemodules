<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 10/04/17
 * Time: 7:23 AM
 */

namespace Drupal\browser_development\Form;


/**
 * Class SavingCssToDisk
 * @package Drupal\browser_development\Form
 */
class SavingCssToDisk {

  /**
   * @var string
   */
  protected $globalFilePath = 'public://browser-development/';

  /**
   * @var
   */
  protected $path;

  /**
   * CssSavingProcess constructor.
   */
  public function __construct() {

    $this->path = \Drupal::service('file_system')->realpath($this->globalFilePath);

  }

  /**
   * @param $data
   * @param $fileName
   * @todo this is not using drupal api properly workout why (When it saves it won't save over old file it just renames it)
   */
  protected function writToDisk($data,$fileName) {

    // $file = file_save_data($form_state->getValue('css_text_field'), "public://browser-css/browser-css-update.css",  FILE_EXISTS_RENAME  );
    // $file->save();

    //-- Do this until I work out Drupals api above

    $file = fopen("$this->path/css/$fileName", "a");
    fwrite($file,$data);
    fclose($file);

    //-- So user can see what they have saved to file
    drupal_flush_all_caches();
  }

  /**
   * @param $formStateInformation
   */
  public function setBackgroundColor($formStateInformation) {

    $this->writToDisk($formStateInformation,'background.css');
  }

  /**
   * @param $formStateInformation
   */
  public function setBackgroundImage($formStateInformation) {

    $this->writToDisk($formStateInformation,'background-image.css');
  }

  /**
   * @param $formStateInformation
   */
  public function setALink($formStateInformation) {

    $this->writToDisk($formStateInformation,'alink.css');
  }

  /**
   * @param $formStateInformation
   */
  public function setText($formStateInformation) {

    $this->writToDisk($formStateInformation,'text.css');
  }

}