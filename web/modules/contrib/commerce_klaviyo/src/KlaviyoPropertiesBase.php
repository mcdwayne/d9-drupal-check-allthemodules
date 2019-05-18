<?php

namespace Drupal\commerce_klaviyo;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\commerce_klaviyo\Util\KlaviyoRequestPropertiesInterface;

/**
 * The base class for Klaviyo properties.
 *
 * @package Drupal\commerce_klaviyo
 */
abstract class KlaviyoPropertiesBase implements KlaviyoRequestPropertiesInterface {

  use DependencySerializationTrait {
    __sleep as protected trait_sleep;
    __wakeup as protected trait_wakeup;
  }

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The array of properties to send to Klaviyo.
   *
   * @var array
   */
  protected $properties = [];

  /**
   * The source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * Serialized source entity type.
   *
   * @var string
   */
  // @codingStandardsIgnoreLine
  protected $_sourceEntityType;

  /**
   * Serialized source entity ID.
   *
   * @var string
   */
  // @codingStandardsIgnoreLine
  protected $_sourceEntityId;

  /**
   * Constructs the KlaviyoPropertiesBase.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The source entity.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityInterface $source_entity) {
    $this->configFactory = $config_factory;
    $this->sourceEntity = $source_entity;
  }

  /**
   * Sets a property.
   *
   * @param string $name
   *   The property name.
   * @param mixed $value
   *   The property value.
   */
  public function setProperty($name, $value) {
    $this->properties[$name] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties() {
    return $this->properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity() {
    return $this->sourceEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntity(EntityInterface $source_entity) {
    $this->sourceEntity = $source_entity;
    return $this;
  }

  /**
   * Save source entity type and ID before serialization.
   *
   * {@inheritdoc}
   */
  public function __sleep() {
    $fields = $this->trait_sleep();
    $source_entity_key = array_search('sourceEntity', $fields);
    if ($source_entity_key !== FALSE) {
      unset($fields[$source_entity_key]);
    }
    if (!empty($this->sourceEntity)) {
      $this->_sourceEntityId = $this->sourceEntity->id();
      $this->_sourceEntityType = $this->sourceEntity->getEntityTypeId();
    }

    return $fields;
  }

  /**
   * Restore source entity after unserialize().
   */
  public function __wakeup() {
    if (!empty($this->_sourceEntityId)
      && !empty($this->_sourceEntityType)) {
      $this->sourceEntity = \Drupal::entityTypeManager()
        ->getStorage($this->_sourceEntityType)
        ->load($this->_sourceEntityId);
    }
    $this->_sourceEntityId = NULL;
    $this->_sourceEntityType = NULL;
    $this->trait_wakeup();
  }

}
