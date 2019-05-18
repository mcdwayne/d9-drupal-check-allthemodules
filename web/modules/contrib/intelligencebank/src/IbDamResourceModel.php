<?php

namespace Drupal\ib_dam;

/**
 * Class IbDamResourceModel.
 *
 * Data class for iframe app response items.
 *
 * @package Drupal\ib_dam
 */
class IbDamResourceModel {

  private $action;
  private $mimetype;
  private $name;
  private $type;
  private $url;
  private $width;
  private $height;
  private $fileName;
  private $fileType;
  private $authKey;
  private $resourceType;
  private $description;

  /**
   * IbDamResourceModel constructor.
   *
   * @param mixed $properties
   *   Response item, object of array.
   */
  public function __construct($properties) {
    $this->url          = self::extractVar($properties, 'url');
    $this->mimetype     = self::extractVar($properties, 'type');
    list($this->type)   = explode('/', $this->mimetype);
    $this->name         = self::extractVar($properties, 'name');
    $this->width        = self::extractVar($properties, 'width');
    $this->height       = self::extractVar($properties, 'height');
    $this->fileName     = self::extractVar($properties, 'origfilename');
    $this->fileType     = strtolower(self::extractVar($properties, 'filetype'));
    $this->authKey      = self::extractVar($properties, '_aid');
    $this->action       = self::extractVar($properties, 'action');
    $this->description  = !empty($properties->description) ? $properties->description : NULL;
    $this->resourceType = self::extractVar($properties, 'action') == 'resource_link'
      ? 'embed'
      : 'local';
  }

  /**
   * Factory method.
   *
   * @param \stdClass $options
   *   Response item.
   *
   * @return \Drupal\ib_dam\IbDamResourceModel
   *   Resource item model.
   */
  public static function buildModel(\stdClass $options) {
    return new static($options);
  }

  /**
   * Returns resource type.
   */
  public function getResourceType() {
    return $this->resourceType;
  }

  /**
   * Returns name of the item.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Returns item's description.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Returns item's action type.
   */
  public function getAction() {
    return $this->action;
  }

  /**
   * Returns item's url.
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Returns item's asset type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Set item's asset type.
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * Returns item's file extension.
   */
  public function getFileType() {
    return strtolower($this->fileType);
  }

  /**
   * Returns item's file name.
   */
  public function getFileName() {
    return $this->fileName;
  }

  /**
   * Returns item's mime type.
   */
  public function getMimetype() {
    return $this->mimetype;
  }

  /**
   * Returns item's auth key.
   */
  public function getAuthKey() {
    return $this->authKey;
  }

  /**
   * Unset auth key.
   */
  public function removeAuthKey() {
    $this->authKey = FALSE;
  }

  /**
   * Helper function to extract correctly item value.
   */
  private static function extractVar($item, $name) {
    $item = (array) $item;
    return isset($item[$name]) ? trim($item[$name]) : NULL;
  }

}
