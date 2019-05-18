<?php

namespace Drupal\doc_to_html;

use Drupal\Core\Config\ConfigFactory;
use Drupal\doc_to_html\FileService;


/**
 * Class MarkupService.
 *
 * @package Drupal\doc_to_html
 */
class MarkupService implements MarkupServiceInterface {

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
  protected $fileservice;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config_factory,FileService $file_service) {
    $this->config = $config_factory;
    $this->fileservice = $file_service;
  }

  /**
   * @param $uri
   * @param bool $regexFilter
   * @return array
   */
  public function getContentFrom($uri, $regexFilter = FALSE) {
    $html = '';

    // Uri to realpath
    $uri_html = $this->fileservice->getUriHTMLFrom($uri);

    // Check if file exist.
    if(file_exists($uri_html)){

      // Get contet of HTML.
      $html = file_get_contents($uri_html);

      // Try encode utf8 if option is set.
      $this->tryEncodeUTF8($html);

      // Extract conttent inner body tag.
      $this->getBodyFrom($html);

      // If regex filter exist apply it.
      if($regexFilter){

        /**
         * for execute command preg_replace and utf8 applayed, decode it after execute
         * end encode now at end proces
         */
        $this->tryDecodeUTF8($html);
        $html = preg_replace($regexFilter,'',$html);
        $this->tryEncodeUTF8($html);
      }

    }
    else {
      //@TODO manage file not exist.
    }
    return $html;
  }

  /**
   * @param $html
   */
  private function tryEncodeUTF8(&$html) {
    $config = $this->config->get('doc_to_html.basicsettings');
    if((int) $config->get('utf_8_encode') === 1 ) {
      $html = (is_array($html)) ? reset($html) : $html;
      if(is_string($html)) {
        if (!mb_check_encoding($html, 'UTF-8')) {
          $html = mb_convert_encoding($html, 'UTF-8', mb_detect_encoding($html));
        }
      }
    }
  }

  /**
   * @param $html
   */
  private function tryDecodeUTF8(&$html){
    $config = $this->config->get('doc_to_html.basicsettings');
    if($config->get('utf_8_encode') == 1 ){
      $html = (is_array($html)) ? reset($html): $html;
      if(is_string($html)){
        $html = utf8_decode($html);
      }


    }
  }

  /**
   * @param $html
   */
  private function getBodyFrom(&$html){
    $config = $this->config->get('doc_to_html.basicsettings');
    if($config->get('extract_content_of_html_body') == 1) {
      $filter = $config->get('regex_to_parse_body');

      if(isset($filter) && !empty($filter)){
        preg_match($filter, $html , $output);
        $html = $output;
      }
    }
  }
}
