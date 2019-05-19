<?php

namespace Drupal\tag1quo\Adapter\Extension;

/**
 * Class Extension8.
 *
 * @internal This class is subject to change.
 *
 * @property \Drupal\Core\Extension\Extension $extension
 */
class Extension8 extends Extension {

  protected $infoExtension = '.info.yml';

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected static $schemaStore;

  /**
   * {@inheritdoc}
   */
  public function __construct($name, $extension) {
    /** @var \Drupal\Core\Extension\Extension $extension */
    $item = (object) get_object_vars($extension);
    $item->schema_version = $this->schemaStore()->get($name);
    $item->pathname = $extension->getPathname();
    $item->filename = $extension->getPathname();
    $item->type = $extension->getType();
    $item->info_comments = $this->parseInfoComments($item->pathname . '/' . $extension->getName() . '.info.yml');
    $item->owner = ""; // Unknown why this is used by server
    parent::__construct($name, $item);
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return dirname($this->extension->getPathname());
  }

  /**
   * Retrieves the schema store.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected function schemaStore() {
    if (static::$schemaStore === NULL) {
      static::$schemaStore = \Drupal::keyValue('system.schema');
    }
    return static::$schemaStore;
  }

}
