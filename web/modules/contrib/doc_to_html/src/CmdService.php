<?php

namespace Drupal\doc_to_html;

use Drupal\Core\Config\ConfigFactory;
use Drupal\doc_to_html\FileService;
use Drupal\doc_to_html\MarkupService;

/**
 * Class CmdService.
 *
 * @package Drupal\doc_to_html
 */
class CmdService implements CmdServiceInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Drupal\doc_to_html\FileService definition.
   *
   * @var Drupal\doc_to_html\FileService
   */
  protected $fileService;

  /**
   * Drupal\doc_to_html\MarkupService definition.
   *
   * @var Drupal\doc_to_html\MarkupService
   */
  protected $markupService;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactory $configFactory,
    FileService $fileService,
    MarkupService $markupService) {

    $this->config = $configFactory;
    $this->fileService = $fileService;
    $this->markupService = $markupService;
  }

  /**
   * @return array|mixed|null
   */
  private function GetCommand(){
    $config = $this->config->get('doc_to_html.libreofficesettings');
    return $config->get('command');
  }

  /**
   * @return array|mixed|null
   */
  private function GetBasePathOfLibrary(){
    $config = $this->config->get('doc_to_html.libreofficesettings');
    return $config->get('base_path_application');
  }

  /**
   * @param $cmd
   * @return mixed
   */
  private function ExecuteCMD($cmd){
    exec($cmd,$output);
    return $output;
  }

  /**
   * @param $cmd
   * @return mixed
   */
  private function ExecuteSYSTEM($cmd){
    $output = shell_exec($cmd);
    return $output;
  }

  /**
   * @see GetCoomand().
   * @see GetBasePathOfLibrary().
   */
  public function GetLibreOfficeVersion(){

    // Prepare Empty String.
    $cmd = '';

    // Get Base Path of Libreoffice in system.
    $cmd .=$this->GetBasePathOfLibrary();

    // Get Main Command.
    $cmd .=$this->GetCommand();

    // Write command for extract Version.
    $cmd .=' --version';

    // Execute CMD.
    $output = $this->ExecuteCMD($cmd);
    if(!empty($output)){
      return implode('|',$output);
    }
    else{
      return FALSE;
    }
  }

  /**
   * @param $markup
   * @param $fid
   * @param bool $regex
   */
  public function convertTo(&$markup, $fid, $regex = FALSE){

    // Load file
    $file = \Drupal\file\Entity\File::load($fid);
    $config = $this->config->get('doc_to_html.basicsettings');

    // Get uri.
    $file_uri = $file->getFileUri();
    $source_path = $this->fileService->escapeSpaceRealPath($file_uri);
    $destination_uri = 'public://';
    $destination_uri .= $config->get('doc_to_html_folder');
    $destination_path = $this->fileService->realPath($destination_uri);

    // Prepare Empty String.
    $cmd = '';

    // Get Base Path of Libreoffice in system.
    $cmd .=$this->GetBasePathOfLibrary();

    // Get Main Command.
    $cmd .=$this->GetCommand();
    $cmd .= ' --headless --convert-to html '. $source_path . ' --outdir ' . $destination_path;
    $output = $this->ExecuteSYSTEM($cmd);
    if(!empty($output)) {
      $markup = $this->markupService->getContentFrom($file_uri, $regex);
    }
    else {
      // @TODO manage negative response.
    }
  }
}
