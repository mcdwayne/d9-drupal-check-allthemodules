<?php
/**
 * @file FrxAPI.incL
 * General Forena Reporting Class
 */
namespace Drupal\forena;
use Drupal\forena\Context\DataContext;
use Drupal\forena\File\ReportFileSystem;
use Drupal\forena\FrxPlugin\Document\DocumentBase;


/**
 * Implements FrxAPI Trait
 *
 * This trait provides access to common Forena API's. Use this trait in your
 * own classes to interact with the forena Toolbox.
 */
trait FrxAPI {

  /**
   * Returns the data manager service
   * @return DataManager
   */
  public function dataManager() {
    return DataManager::instance();
  }

  /**
   * Returns the fornea document manager
   * @return \Drupal\forena\DocManager
   */
  public function documentManager() {
    return DocManager::instance();
  }

  /**
   * Return Data Service
   * @return \Drupal\forena\Context\DataContext
   */
  public function dataService() {
    return DataManager::instance()->dataSvc;
  }

  /**
   * Report an error
   *
   * @param string $short_message
   *   Short Error message
   * @param string $log
   *   Log message for system logs
   */
  public function error($short_message, $log='') {
    AppService::instance()->error($short_message, $log);
  }

  /**
   * Returns containing application service
   * @return \Drupal\forena\AppService
   */
  public function app() {
    return AppService::instance();
  }

  /**
   * Get the current report file system.
   * @return \Drupal\forena\File\ReportFileSystem
   */
  public function reportFileSystem() {
    return ReportFileSystem::instance();
  }

  /**
   * Load the contents of a file in the report file system.
   * @param $filename
   * @return string
   */
  public function getReportFileContents($filename) {
    return ReportFileSystem::instance()->contents($filename);
  }

  /**
   * Get the current data context.
   * @return array|\SimpleXMLElement
   */
  public function currentDataContext() {
    return DataManager::instance()->dataSvc->currentContext();
  }

  public function currentDataContextArray() {
    return DataManager::instance()->dataSvc->currentContextArray();
  }

  /**
   * Get the context of a specific id.
   * @param $id
   * @return mixed
   */
  public function getDataContext($id) {
    return DataManager::instance()->dataSvc->getContext($id);
  }

  /**
   * Set Data context by id.
   * @param string $id
   *   ID of the context to set
   * @param object | array $data
   *   Data contents to set.
   */
  public function setDataContext($id, $data) {
    DataManager::instance()->dataSvc->setContext($id, $data);
  }

  /**
   * Push data onto the Stack
   * @param $data
   * @param string $id
   */
  public function pushData($data, $id='') {
    DataManager::instance()->dataSvc->push($data, $id);
  }

  /**
   * Pop data off of the stack.
   */
  public function popData() {
    DataManager::instance()->dataSvc->pop();
  }

  /**
   * Change to a specific document type.
   * @param string $type
   *   The document type you are trying to retrieve.
   */
  public function setDocument($type = 'drupal') {
    DocManager::instance()->setDocument($type);
  }

  /**
   * Get the current document
   * @return DocumentBase
   */
  public function getDocument() {
    return DocManager::instance()->getDocument();
  }

  /**
   * Enter description here...
   *
   * @param \SimpleXMLElement $xml
   *   XML to grab
   * @param string $tag
   *   The tag name of the inner XML
   * @return string
   */
  function innerXML(\SimpleXMLElement $xml, $tag = '') {
    if (is_object($xml) ) {
      if (!$tag) $tag = $xml->getName();
      $xml_data = $xml->asXML();
      $xml_data = preg_replace("/<\/?" . $tag . "(.|\s)*?>/", "", $xml_data);
    };
    return $xml_data;
  }

  /**
   * Run a report with a particular format.
   * @param $machine_name
   *   Name of report. 
   * @parms array $parms
   *   Parameter values to pass to report
   * @return array|string
   */
  public function report($machine_name, $parms = []) {
    return ReportManager::instance()->report($machine_name, $parms);
  }

  /**
   * Get list of skins.
   * @return array
   *   List of skins
   */
  public function skins() {
    return ReportManager::instance()->skins();
  }

}
