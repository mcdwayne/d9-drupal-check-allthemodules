<?php

namespace Drupal\Tests\chatbot_api_entities\Traits;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Defines a trait for common test functionality.
 */
trait ChatbotApiEntitiesTestTrait {

  /**
   * Creates a new entity test bundle and adds synonyms field.
   */
  protected function setupEntityTestBundle() {
    $field_name = 'field_synonyms';
    $entity_type = 'entity_test';
    $bundle = 'some_bundle';
    entity_test_create_bundle($bundle);
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'string',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => $field_name,
      'label' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'required' => FALSE,
    ]);
    $field_config->save();
  }

  /**
   * Runs cron.
   */
  protected function cronRun() {
    $this->container->get('cron')->run();
  }

}
