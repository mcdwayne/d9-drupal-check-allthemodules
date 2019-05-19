<?php
namespace Drupal\wrappers_delight;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\wrappers_delight\Annotation\WrappersDelight;

/**
 * Plugin manager for entity wrapper classes
 *
 * @package Drupal\wrappers_delight
 */
class WrappersDelightPluginManager extends DefaultPluginManager {

  /**
   * @var array
   */
  protected $definitionsByType;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    $subdir = 'Plugin/WrappersDelight';
    $plugin_definition_annotation_name = '\Drupal\wrappers_delight\Annotation\WrappersDelight';
    $interface = NULL;

    parent::__construct($subdir, $namespaces, $module_handler, $interface, $plugin_definition_annotation_name);
    $this->alterInfo('wrappers_delight_info');
    $this->setCacheBackend($cache_backend, 'wrappers_delight_info');
  }

  /**
   * @param string $type
   *
   * @return array
   */
  public function getDefintionsByType($type) {
    if (!isset($this->definitionsByType[$type])) {
      $this->definitionsByType[$type] = [];
      $plugins = $this->getDefinitions();
      foreach ($plugins as $id => $plugin) {
        if ($plugin['type'] == $type) {
          $this->definitionsByType[$type][$id] = $plugin;
        }
      }
    }
    return $this->definitionsByType[$type];
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Field\FieldItemInterface $raw
   *
   * @return \Drupal\wrappers_delight\WrapperBase|\Drupal\wrappers_delight\FieldItemWrapper
   * 
   * @throws \InvalidArgumentException
   */
  public function wrap($raw) {
    if ($raw instanceof EntityInterface) {
      return $this->wrapEntity($raw);
    }
    else {
      return $this->wrapField($raw);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface
   * 
   * @return \Drupal\wrappers_delight\WrapperBase
   * 
   * @throws \InvalidArgumentException
   */
  public function wrapEntity($entity) {
    $class_name = $this->getWrapperClass($entity->getEntityTypeId(), $entity->bundle());
    if (!empty($class_name)) {
      $class = new \ReflectionClass($class_name);
      if ($class->hasMethod('wrap')) {
        return $class_name::wrap($entity);
      }
    }
    throw new \InvalidArgumentException("Wrapper class not found for entity of type " . $entity->getEntityTypeId() . ' and bundle ' . $entity->bundle());
  }

  /**
   * @param \Drupal\Core\Field\FieldItemInterface $raw
   *
   * @return \Drupal\wrappers_delight\FieldItemWrapper
   * 
   * @throws \InvalidArgumentException
   */
  public function wrapField(FieldItemInterface $item) {
    $definition = $item->getFieldDefinition();
    $class_name = $this->getFieldWrapperClass($definition->getType());
    if (!empty($class_name)) {
      $class = new \ReflectionClass($class_name);
      if ($class->hasMethod('wrap')) {
        return $class_name::wrap($item);
      }
    }
    throw new \InvalidArgumentException("Wrapper class not found for field of type " . $definition->getType());
  }

  /**
   * @param \Drupal\Core\Field\FieldItemInterface $raw
   *
   * @return \Drupal\wrappers_delight\FieldItemWrapper
   *
   * @throws \InvalidArgumentException
   */
  public function wrapFieldList(FieldItemListInterface $list) {
    $definition = $list->getFieldDefinition();
    $class_name = $this->getFieldListWrapperClass($definition->getType());
    if (!empty($class_name)) {
      $class = new \ReflectionClass($class_name);
      if ($class->hasMethod('wrap')) {
        return $class_name::wrap($list);
      }
    }
    throw new \InvalidArgumentException("Wrapper class not found for field list of type " . $definition->getType());
  }

  /**
   * @param string $entity_type
   * @param string $bundle
   * 
   * @return string
   */
  public function getWrapperClass($entity_type, $bundle) {
    foreach ($this->getDefinitions() as $plugin) {
      if ($plugin['type'] == WrappersDelight::TYPE_BUNDLE && $plugin['entity_type'] == $entity_type && $plugin['bundle'] == $bundle) {
        return $plugin['class'];
      }
    }
    // If no bundle wrapper exists, check for an entity wrapper
    foreach ($this->getDefinitions() as $plugin) {
      if ($plugin['type'] == WrappersDelight::TYPE_ENTITY && $plugin['entity_type'] == $entity_type) {
        return $plugin['class'];
      }
    }
    
    // If there is no wrapper, pass back the entity class
    return \Drupal::entityTypeManager()->getDefinition($entity_type)->getClass();
  }

  /**
   * @param string $field_type
   *
   * @return string
   */
  public function getFieldWrapperClass($field_type) {
    foreach ($this->getDefinitions() as $plugin) {
      if ($plugin['type'] == WrappersDelight::TYPE_FIELD_TYPE && $plugin['field_type'] == $field_type) {
        return '\\' . $plugin['class'];
      }
    }
    return '\Drupal\wrappers_delight\FieldItemWrapper';
  }

  /**
   * @param string $field_type
   *
   * @return string
   */
  public function getFieldListWrapperClass($field_type) {
    foreach ($this->getDefinitions() as $plugin) {
      if ($plugin['type'] == WrappersDelight::TYPE_FIELD_LIST && $plugin['field_type'] == $field_type) {
        return '\\' . $plugin['class'];
      }
    }
    return '\Drupal\wrappers_delight\FieldItemListWrapper';
  }

}
