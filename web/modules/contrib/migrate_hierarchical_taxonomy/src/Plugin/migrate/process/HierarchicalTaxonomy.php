<?php

namespace Drupal\migrate_hierarchical_taxonomy\Plugin\migrate\process;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityGenerate;

/**
 * Perform hierarchical taxonomy import.
 *
 * @MigrateProcessPlugin(
 *   id = "hierarchical_taxonomy"
 * )
 *
 * To import hierarchical taxonomy:
 *
 * @code
 * field_taxonomy:
 *   plugin: hierarchical_taxonomy
 *   bundle: vocabulary_machine_name
 *   ignore_case: true
 *   source:
 *     - level1
 *     - level2
 *     - level3
 *     - ...
 * @endcode
 */
class HierarchicalTaxonomy extends EntityGenerate {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, MigrationInterface $migration, EntityManagerInterface $entityManager, SelectionPluginManagerInterface $selectionPluginManager, MigratePluginManager $migratePluginManager) {
    parent::__construct($configuration, $pluginId, $pluginDefinition, $migration, $entityManager, $selectionPluginManager, $migratePluginManager);
    $this->lookupValueKey = 'name';
    $this->lookupBundleKey = 'vid';
    $this->lookupEntityType = 'taxonomy_term';
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destinationProperty) {
    // In case of subfields ('field_reference/target_id'), extract the field
    // name only.
    $parts = explode('/', $destinationProperty);
    $destinationProperty = reset($parts);
    $this->determineLookupProperties($destinationProperty);

    $levels = $this->configuration['source'];
    $parent = 0;
    foreach ($levels as $level => $name) {
      if ($value[$level] && !empty($value[$level])) {
        // vérification si terme existe dans sa hiérarchie sinon
        // on créé la hiérarchie en partant du niveau le plus bas.
        $searchTerm = $value[$level];
        $tree = $this->entityManager->getStorage('taxonomy_term')->loadTree(
          $this->configuration['bundle'],
          $parent,
          1,
          TRUE
        );
        $result = NULL;
        foreach ($tree as $term) {
          $termName = $term->getName();
          if ($searchTerm == $termName) {
            $result = $term->id();
          }
        }
        if (!$result) {
          $this->configuration['parent'] = $parent;
          $result = $this->generateEntity($searchTerm);
        }
        $parent = $result;
      }
    }

    return $result;
  }

  /**
   * Fabricate an entity.
   *
   * This is intended to be extended by implementing classes to provide for more
   * dynamic default values, rather than just static ones.
   *
   * @param mixed $value
   *   Primary value to use in creation of the entity.
   *
   * @return array
   *   Entity value array.
   */
  protected function entity($value) {
    $entity_values = [$this->lookupValueKey => $value];

    if ($this->lookupBundleKey) {
      $entity_values[$this->lookupBundleKey] = $this->lookupBundle;
    }

    $entity_values['parent'] = $this->configuration['parent'];

    return $entity_values;
  }

}
