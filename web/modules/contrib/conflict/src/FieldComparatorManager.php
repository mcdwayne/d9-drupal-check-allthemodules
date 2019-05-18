<?php

namespace Drupal\conflict;

use Drupal\Component\Utility\SortArray;
use Drupal\conflict\Annotation\FieldComparator;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class FieldComparatorManager extends DefaultPluginManager implements FieldComparatorManagerInterface {

  /**
   * The field comparators.
   *
   * @var array
   */
  protected $fieldComparators;

  /**
   * Constructs a new FieldComparatorManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Conflict/FieldComparator',
      $namespaces,
      $module_handler,
      FieldComparatorInterface::class,
      FieldComparator::class
    );

    $this->setCacheBackend($cache_backend, 'conflict.field_comparator.plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function hasChanged(FieldItemListInterface $items_a, FieldItemListInterface $items_b, $langcode, $entity_type_id, $bundle, $field_type, $field_name) {
    $this->initFieldComparators();

    $entity_type_id_s = 'entity_type_' . $entity_type_id;
    $bundle_s = 'bundle_' . $bundle;
    $field_type_s = 'field_type_' . $field_type;
    $field_name_s = 'field_name_' . $field_name;

    $entity_type_id_g = 'entity_type_' . FieldComparatorInterface::APPLIES_TO_ALL;
    $bundle_g = 'bundle_' . FieldComparatorInterface::APPLIES_TO_ALL;
    $field_type_g = 'field_type_' . FieldComparatorInterface::APPLIES_TO_ALL;
    $field_name_g = 'field_name_' . FieldComparatorInterface::APPLIES_TO_ALL;

    /** @var \Drupal\conflict\FieldComparatorInterface[] $comparators */
    $comparators = [];

    // Entity type - specific
    // bundle      - specific
    // field type  - specific
    // field name  - specific
    if (isset($this->fieldComparators[$entity_type_id_s][$bundle_s][$field_type_s][$field_name_s]['comparators'])) {
      $comparators = &$this->fieldComparators[$entity_type_id_s][$bundle_s][$field_type_s][$field_name_s]['comparators'];
    }
    // Entity type - specific
    // bundle      - specific
    // field type  - specific
    // field name  - all
    elseif (isset($this->fieldComparators[$entity_type_id_s][$bundle_s][$field_type_s][$field_name_g]['comparators'])) {
      $comparators = &$this->fieldComparators[$entity_type_id_s][$bundle_s][$field_type_s][$field_name_g]['comparators'];
    }
    // Entity type - specific
    // bundle      - all
    // field type  - specific
    // field name  - all
    elseif (isset($this->fieldComparators[$entity_type_id_s][$bundle_g][$field_type_s][$field_name_g]['comparators'])) {
      $comparators = &$this->fieldComparators[$entity_type_id_s][$bundle_g][$field_type_s][$field_name_g]['comparators'];
    }
    // Entity type - specific
    // bundle      - all
    // field type  - specific
    // field name  - specific
    elseif (isset($this->fieldComparators[$entity_type_id_s][$bundle_g][$field_type_s][$field_name_s]['comparators'])) {
      $comparators = &$this->fieldComparators[$entity_type_id_s][$bundle_g][$field_type_s][$field_name_s]['comparators'];
    }
    // Entity type - specific
    // bundle      - all
    // field type  - all
    // field name  - all
    elseif (isset($this->fieldComparators[$entity_type_id_s][$bundle_g][$field_type_g][$field_name_g]['comparators'])) {
      $comparators = &$this->fieldComparators[$entity_type_id_s][$bundle_g][$field_type_g][$field_name_g]['comparators'];
    }
    // Entity type - all
    // bundle      - all
    // field type  - specific
    // field name  - all
    elseif (isset($this->fieldComparators[$entity_type_id_g][$bundle_g][$field_type_s][$field_name_g]['comparators'])) {
      $comparators = &$this->fieldComparators[$entity_type_id_g][$bundle_g][$field_type_s][$field_name_g]['comparators'];
    }
    // Entity type - all
    // bundle      - all
    // field type  - all
    // field name  - all
    elseif (isset($this->fieldComparators[$entity_type_id_g][$bundle_g][$field_type_g][$field_name_g]['comparators'])) {
      $comparators = &$this->fieldComparators[$entity_type_id_g][$bundle_g][$field_type_g][$field_name_g]['comparators'];
    }

    if (empty($comparators)) {
      throw new \Exception('There are no field comparators available.');
    }

    foreach ($comparators as &$comparator) {
      if (!is_object($comparator)) {
        $comparator = $this->createInstance($comparator);
      }
      if ($comparator->hasChanged($items_a, $items_b, $langcode, $entity_type_id, $bundle, $field_type, $field_name)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Initializes the field comparators.
   */
  protected function initFieldComparators() {
    if (!isset($this->fieldComparators)) {
      $this->fieldComparators = [];
      foreach ($this->getDefinitions() as $plugin_id => $definition) {
        $entity_type_id = 'entity_type_' . $definition['entity_type_id'];
        $bundle = 'bundle_' . $definition['bundle'];
        $field_type = 'field_type_' . $definition['field_type'];
        $field_name = 'field_name_' . $definition['field_name'];

        if (isset($this->fieldComparators[$entity_type_id][$bundle][$field_type][$field_name]['comparators'])) {
          $this->fieldComparators[$entity_type_id][$bundle][$field_type][$field_name]['comparators'] = [$plugin_id];
        }
        else {
          $this->fieldComparators[$entity_type_id][$bundle][$field_type][$field_name]['comparators'][] = $plugin_id;
        }
      }
    }
  }

  /**
   * Finds plugin definitions.
   *
   * @return array
   *   List of definitions to store in cache.
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    uasort($definitions, [SortArray::class, 'sortByWeightElement']);
    $definitions = array_reverse($definitions, TRUE);
    return $definitions;
  }

}
