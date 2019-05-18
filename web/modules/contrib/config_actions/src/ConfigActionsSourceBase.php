<?php

namespace Drupal\config_actions;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Base class for config_actions plugins.
 */
abstract class ConfigActionsSourceBase extends PluginBase implements ConfigActionsSourceInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\config_actions\ConfigActionsServiceInterface
   */
  protected $actionService;

  /**
   * The type of the plugin instance
   * @var string
   */
  protected $pluginType;

  /**
   * The cached config data for this source instance.
   * @var array
   */
  protected $sourceData = [];

  /**
   * The ID value of the source.  Plugin specific.
   * @var string
   */
  protected $sourceId = '';

  /**
   * The Base namespace for the source.  Plugin specific.
   * @var string
   */
  protected $sourceBase = '';

  /**
   * Determine if sourceData has been changed since last load/save.
   * @var bool
   */
  protected $changed = FALSE;

  protected $merge = FALSE;

  /** ---------------------------------------------- */
  /** ABSTRACT Functions to be implemented in Plugin */
  /** ---------------------------------------------- */

  /**
   * {@inheritdoc}
   */
  abstract public function doLoad();

  /**
   * {@inheritdoc}
   */
  abstract public function doSave($data);

  /**
   * {@inheritdoc}
   */
  abstract public function detect($source);

  /** ------------------------------------------- */
  /** GENERAL Functions implemented in Base class */
  /** ------------------------------------------- */

  /**
   * Constructs a new ConfigActionsSource plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param ConfigActionsServiceInterface $config_action_service
   *   The ConfigActionsService from the container.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              ConfigActionsServiceInterface $config_action_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->actionService = $config_action_service;
    $this->sourceId = !empty($configuration['source']) ? $configuration['source'] : '';
    $this->sourceBase = !empty($configuration['base']) ? $configuration['base'] : '';
    $this->pluginType = $plugin_id;
    $this->setData([], FALSE);
  }

  /**
   * Create a plugin instance from the container
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var ConfigActionsServiceInterface $config_action_service */
    $config_action_service = $container->get('config_actions');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $config_action_service
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    if ($this->isChanged()) {
      // If data has been changed, return it instead of loading fresh
      return $this->sourceData;
    }
    $data = $this->doLoad();
    return $this->setData($data, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function save($data) {
    if (!empty($this->sourceId)) {
      if ($this->getMerge() && !empty($data)) {
        $existing = $this->doLoad();
        if (!empty($existing)) {
          $data = NestedArray::mergeDeepArray([$existing, $data], TRUE);
        }
      }
      $this->setData($data, FALSE);
      return $this->doSave($data);
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->sourceData;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data = [], $changed = TRUE) {
    $this->sourceData = $data;
    $this->changed = $changed;
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function isChanged() {
    return $this->changed;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->pluginType;
  }

  /**
   * {@inheritdoc}
   */
  public function setMerge($merge) {
    $this->merge = $merge;
  }

  /**
   * {@inheritdoc}
   */
  public function getMerge() {
    return $this->merge;
  }

  }
