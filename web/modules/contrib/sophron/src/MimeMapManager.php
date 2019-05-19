<?php

namespace Drupal\sophron;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\sophron\Event\MapEvent;
use Drupal\sophron\Map\DrupalMap;
use FileEye\MimeMap\Extension;
use FileEye\MimeMap\Map\AbstractMap;
use FileEye\MimeMap\Map\DefaultMap;
use FileEye\MimeMap\MapHandler;
use FileEye\MimeMap\MalformedTypeException;
use FileEye\MimeMap\MappingException;
use FileEye\MimeMap\Type;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a sensible mapping between filename extensions and MIME types.
 */
class MimeMapManager {

  /**
   * Option to use Sophron's Drupal-compatible map.
   */
  const DRUPAL_MAP = 0;

  /**
   * Option to use MimeMap's default map.
   */
  const DEFAULT_MAP = 1;

  /**
   * Option to use a custom defined map.
   */
  const CUSTOM_MAP = 99;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module configuration settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $sophronSettings;

  /**
   * The FQCN of the map currently in use.
   *
   * @var string
   */
  protected $currentMapClass;

  /**
   * The array of initialized map classes.
   *
   * Keyed by FQCN, each value stores the array of initialization errors.
   *
   * @var array
   */
  protected $initializedMapClasses = [];

  /**
   * Constructs a MimeMapManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EventDispatcherInterface $dispatcher) {
    $this->configFactory = $config_factory;
    $this->sophronSettings = $this->configFactory->get('sophron.settings');
    $this->eventDispatcher = $dispatcher;
  }

  /**
   * Determines if a FQCN is a valid map class.
   *
   * Map classes muste extend from FileEye\MimeMap\Map\AbstractMap.
   *
   * @param string $map_class
   *   A FQCN.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function isMapClassValid($map_class) {
    if (class_exists($map_class) && in_array(AbstractMap::class, class_parents($map_class))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Gets the FQCN of map currently in use by the manager.
   *
   * @return string
   *   A FQCN.
   */
  public function getMapClass() {
    if (!$this->currentMapClass) {
      switch ($this->sophronSettings->get('map_option')) {
        case static::DRUPAL_MAP:
          $this->setMapClass(DrupalMap::class);
          break;

        case static::DEFAULT_MAP:
          $this->setMapClass(DefaultMap::class);
          break;

        case static::CUSTOM_MAP:
          $map_class = $this->sophronSettings->get('map_class');
          $this->setMapClass($this->isMapClassValid($map_class) ? $map_class : DrupalMap::class);
          break;

      }
    }
    return $this->currentMapClass;
  }

  /**
   * Sets the map class to use by the manager.
   *
   * @param string $map_class
   *   A FQCN.
   *
   * @return $this
   */
  public function setMapClass($map_class) {
    $this->currentMapClass = $map_class;
    if (!isset($this->initializedMapClasses[$map_class])) {
      $event = new MapEvent($map_class);
      $this->eventDispatcher->dispatch(MapEvent::INIT, $event);
      $this->initializedMapClasses[$map_class] = $event->getErrors();
    }
    return $this;
  }

  /**
   * Gets the initialization errors of a map class.
   *
   * @param string $map_class
   *   A FQCN.
   *
   * @return array
   *   The array of mapping errors.
   */
  public function getMappingErrors($map_class) {
    $this->setMapClass($map_class);
    return isset($this->initializedMapClasses[$map_class]) ? $this->initializedMapClasses[$map_class] : [];
  }

  /**
   * Gets the list of MIME types.
   *
   * @return string[]
   *   A simple array of MIME type strings.
   */
  public function listTypes() {
    return MapHandler::map($this->getMapClass())->listTypes();
  }

  /**
   * Gets a MIME type.
   *
   * @param string $type
   *   A MIME type string.
   *
   * @return \FileEye\MimeMap\Type
   *   A Type object.
   *
   * @see \FileEye\MimeMap\Type
   */
  public function getType($type) {
    try {
      return new Type($type, $this->getMapClass());
    }
    catch (MalformedTypeException $e) {
      return NULL;
    }
    catch (MappingException $e) {
      return NULL;
    }
  }

  /**
   * Gets the list of file extensions.
   *
   * @return string[]
   *   A simple array of file extension strings.
   */
  public function listExtensions() {
    return MapHandler::map($this->getMapClass())->listExtensions();
  }

  /**
   * Gets a file extension.
   *
   * @param string $extension
   *   A file extension string.
   *
   * @return \FileEye\MimeMap\Extension
   *   An Extension object.
   *
   * @see \FileEye\MimeMap\Extension
   */
  public function getExtension($extension) {
    try {
      return new Extension($extension, $this->getMapClass());
    }
    catch (MappingException $e) {
      return NULL;
    }
  }

}
