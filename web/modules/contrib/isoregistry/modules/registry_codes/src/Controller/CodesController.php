<?php

namespace Drupal\registry_codes\Controller;

use Drupal\isoregistry\Controller\RegistryExceptions;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Description of Codes
 *
 * @author Balschmiter
 */


/*Beispiel aufrufe:
 *  http://localhost:8002/drupal8/registry/ex/code/Ebene10.xml
 * http://localhost:8002/drupal8/registry/ex/code/Ebene10
 * http://localhost:8002/drupal8/registry/ex/code/Ebene10.xml?format=json
 * http://localhost:8002/drupal8/registry/ex/code/Ebene10?format=xml
 * 
 * 
 * 
 */
class CodesController {
  
  private $defaultFormats = ['none','xml','json', 'exml'];
  private $format = null;
  private $code = null;
  private $lang = null;
  private $version = null;
  private $namespace = null;
  private $nids = null;
  private $node = null;
  
  public function showCode($namespace, $code) {
    $this->namespace = $namespace;
    $this->buildFormatandCode($_GET['format'], $code);
    $this->buildLang($_GET['lang']);
    $this->checkCodeExistens();
    if ($this->format == null || in_array(strtolower($this->format),$this->defaultFormats)) {
      $response = $this->changeResponse();
      return $response;
    } else {
      $response = new RegistryExceptions(t('angegebenes Format nicht unterstützt, unterstützt wird nur XML und Blank (Ausgabe über die Webseite)'));
      return $response->getDefaultException();
    }
  }
  
  private function buildLang($lang){
    if ($lang === null) {
      $this->lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    } else {
      $this->lang =$lang;
    }
  }
  
  private function buildFormatandCode($getFormat, $code) {
    $codeFormat_ = explode(".", $code);
    $codeFormat = $codeFormat_[count($codeFormat_) - 1];
    if(in_array($codeFormat, $this->defaultFormats) && in_array($getFormat, $this->defaultFormats)) {
      $this->format = $getFormat;
      $this->code = str_replace(".".$codeFormat,"",$code);
    } elseif (!in_array($codeFormat, $this->defaultFormats) && !in_array($getFormat, $this->defaultFormats)) {
      $this->code = $code;
    } elseif (!in_array($codeFormat, $this->defaultFormats) && in_array($getFormat, $this->defaultFormats)) {
      $this->format = $getFormat;
      $this->code = $code;
    } elseif (in_array($codeFormat, $this->defaultFormats) && !in_array($getFormat, $this->defaultFormats)) {
      $this->format = $codeFormat;
      $this->code = str_replace(".".$codeFormat,"",$code);
    }
  }
  
  private function checkCodeExistens() {
    //Check Namespace
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'namespaces')
      ->condition('field_shortcut', $this->namespace)
      ->execute();
    
    if(count($tids) < 1 ) {
      $response = new RegistryExceptions(t('keine Namensraum gefunden, der dem angegebenen entspricht'));
      return $response->getDefaultException();
    } 
    $tid = null;
    foreach ($tids as $key => $value) {
      $tid = $value;
    }
    //Check Node
    $config = \Drupal::config('registry_codes.settings');
    $this->nids = \Drupal::entityQuery('node')
      ->condition('type', $config->get('enabled_content_types'), 'IN')  //array mit entsprechenden Content types
      ->condition('field_codes_code', $this->code)
      ->condition('field_namespace', $tid)
      ->condition('langcode', $this->lang)
      ->execute();
  }
  
  public function changeResponse() {
    switch ($this->format) {
      case null:
        $response = new CodesNode();
        return $response->getResponse($this->nids, $this->lang);
      case 'xml':
        $response = new CodesXML();
        return $response->getResponse($this->nids, $this->namespace, $this->lang);
      case 'exml':
        $response = new CodesXML();
        return $response->getResponse($this->nids, $this->namespace, $this->lang, true);
      case 'json':
        $response = new CodesJSON();
        return $response->getResponse($this->nids, $this->namespace, $this->lang);
    }
  }
}

