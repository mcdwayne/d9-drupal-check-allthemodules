<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 2/6/16
 * Time: 9:07 PM
 */

namespace Drupal\forena;

/**
 * Implements \Drupal\forena\DocManager
 * @package Drupal\forena
 */
class DocManager {
  private static $instance;
  private $writers = [];
  private $type;

  /** @var array  */
  protected $docTypes;

  /**
   * @return \Drupal\forena\DocManager
   */
  static public function instance() {
    if (static::$instance === null) {
      static::$instance = new static();
    }
    return static::$instance;
  }

  /**
   * Initilaize Document Manager
   */
  public function __construct() {
    // Determine plugins
    /** @var \Drupal\forena\FrxDocumentPluginManager $pm */
    $this->docTypes = AppService::instance()->getDocumentPlugins();
    $this->setDocument('drupal');
  }

  /**
   * @param string $type
   *   Document type
   * @return \Drupal\forena\FrxPlugin\Document\DocumentInterface
   *   Generated class
   */
  public function setDocument($type='') {
    if (!$type) $type = 'drupal';
    if (!isset($this->writers[$type])) {
      $class = $this->docTypes[$type];
      $this->writers[$type] = new $class();
    }
    $this->type = $type;
    return $this->writers[$this->type];
  }

  /**
   * Gets current document.
   * @return \Drupal\forena\FrxPlugin\Document\DocumentBase
   */
  public function getDocument() {
    return $this->writers[$this->type];
  }

  /**
   * @return string
   *   Document type of current document.
   */
  public function getDocumentType() {
    return $this->type;
  }

  /**
   * Return a list of document types.
   * @return array
   *   List of doctype extensions possible.
   */
  public function getDocTypes() {
    return array_keys($this->docTypes);
  }

  /**
   * @param string $base_name
   *   Name of file to set.
   */
  public function setFilename($base_name) {
    $ext = $this->getDocumentType();
    $this->getDocument()->setFilename("$base_name.$ext");
  }

}