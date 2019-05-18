<?php

namespace Drupal\representative_image\Plugin\migrate\source;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fetches representative image fields from the source database.
 *
 * @MigrateSource(
 *   id = "d7_representative_image_field_config",
 *   source_module = "representative_image",
 * )
 */
class FieldConfig extends FieldStorageConfig implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $entity_definitions = $this->entityTypeManager->getDefinitions();
    $name = $row->getSourceProperty('name');
    // Variables are made of representative_image_field_[entity type]_[bundle].
    // First let's find a matching entity type from the variable name.
    foreach ($entity_definitions as $entity_type => $definition) {
      if (strpos($name, 'representative_image_field_' . $entity_type . '_') === 0) {
        // Extract the bundle out of the variable name.
        preg_match('/^representative_image_field_' . $entity_type . '_([a-zA-z0-9_]+)$/', $name, $matches);
        $bundle = $matches[1];

        // Check that the bundle exists.
        $bundles = $this->entityManager->getBundleInfo($entity_type);
        if (!in_array($bundle, array_keys($bundles))) {
          // No matching bundle found in destination.
          return FALSE;
        }

        $row->setSourceProperty('entity_type', $entity_type);
        $row->setSourceProperty('bundle', $bundle);
        $row->setSourceProperty('settings', [
          'representative_image_field_name' => unserialize($row->getSourceProperty('value')),
        ]);
        return DrupalSqlBase::prepareRow($row);
      }
    }

    // No matching entity type found in destination for this variable.
    return FALSE;
  }

}
