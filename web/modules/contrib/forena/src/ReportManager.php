<?php
/**
 * @file
 * Implements \Drupal\forena\File\ReportFileSystem
 */

namespace Drupal\forena;


use Drupal\forena\File\ReportFileSystem;

/**
 * Access to Report rendering engine.
 */
class ReportManager {

  private static $instance;

  /**
   * Singleton Factory
   * @return \Drupal\forena\ReportManager
   */
  public static function instance() {
    if (static::$instance === NULL) {
      static::$instance = new static();
    }
    return static::$instance;
  }

  /**
   * Determine doucment format from path and extract it if necessary.
   * @param $report_name
   * @return string 
   *   Document type from path. 
   */
  public function formatFromPath(&$report_name) {
    $format = 'drupal';
    $parts = explode('.', $report_name);
    if ($parts) {
      $ext = strtolower(array_pop($parts));
      $document_types = DocManager::instance()->getDocTypes();
      if(array_search($ext, $document_types)!==FALSE) {
        $report_name = implode('.', $parts);
        $format = $ext;
      }
    };
    return $format;
  }

  /**
   * Check access on areport.
   * @param $security
   * @return bool
   */
  public function checkAccess($security) {
    $access = empty($security);
    foreach ($security as $provider => $rights) {
      $m = DataManager::instance()->repository($provider);
      foreach ($rights as $right) {
        if ($m && $m->access($right)) {
          $access = TRUE;
        };
      }
    }
    return $access;
  }

  /**
   * Generate a forena report
   * @param $base_name
   * @return array | string
   *   Report or doucment. 
   */
  public function report($report_name, $parms = []) {
    $format = $this->formatFromPath($report_name);
    $base_name = $report_name;
    //@TODO: report loading based on language
    $content = NULL;
    $file_name = str_replace('.', '/', $report_name) . '.frx';
    $r = ReportFileSystem::instance();
    // Find out if the report exists
    if ($r->exists($file_name)) {
      $metaData = $r->getMetaData($file_name);
      $frx = $r->contents($file_name);
      $r = new Report($frx);
      if ($this->checkAccess($r->access)) {

        DocManager::instance()->setDocument($format);
        $dataSvc = DataManager::instance()->dataSvc;
        $dataSvc->setContext('report', $metaData);
        $dataSvc->setContext('site', AppService::instance()->siteContext);
        if (isset($_COOKIE)) {
          $dataSvc->setContext('cookie', $_COOKIE);
        }
        $doc = DocManager::instance()->getDocument();
        $doc->clear();
        $doc->header();
        $parms = array_merge($r->getDefaultParameters(), $parms);
        $dataSvc->push($parms, 'parm');
        $doc->setSkin($r->skin);
        DocManager::instance()->setFilename($base_name);
        $r->render($format);
        $doc->title=$r->title;
        $doc->footer();
        $dataSvc->pop();
        $r->buildParametersForm();
        $content = $doc->flush();
      }
      else {
        $content = FALSE;
      }

    }

    return $content;
  }

  /**
   * Generate a forena report
   * @param $base_name
   */
  public function reportInclude($report_name) {
    $format = $this->formatFromPath($report_name);
    $base_name = $report_name;
    //@TODO: report loading based on language
    $file_name = str_replace('.', '/', $report_name) . '.frx';
    $r = ReportFileSystem::instance();
    // Find out if the report exists
    if ($r->exists($file_name)) {
      $frx = $r->contents($file_name);
      $r = new Report($frx);
      $r->render($format);
    }
  }

  /**
   * Return the list of skins available.
   */
  public function skins() {
    // Determine the list of skins.
    return ReportFileSystem::instance()->skins();
  }

}