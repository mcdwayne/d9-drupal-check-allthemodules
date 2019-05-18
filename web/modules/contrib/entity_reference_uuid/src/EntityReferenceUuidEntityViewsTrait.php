<?php

namespace Drupal\entity_reference_uuid;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Trait to add views relationships for entity_reference_uuid base fields.
 *
 * This trait is intended to be used in a subclass of
 * \Drupal\views\EntityViewsData.
 */
trait EntityReferenceUuidEntityViewsTrait {

  /**
   * Processes the views data for an entity reference UUID field.
   *
   * @param string $table
   *   The table the language field is added to.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function processViewsDataForEntityReferenceUuid($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {

    if ($target_entity_type_id = $field_definition->getItemDefinition()->getSetting('target_type')) {
      $target_entity_type = $this->getEntityManager()->getDefinition($target_entity_type_id);
      if ($target_entity_type instanceof ContentEntityType) {
        $views_field['relationship'] = [
          'base' => $this->getViewsTableForEntityType($target_entity_type),
          'base field' => $target_entity_type->getKey('id'),
          'entity base table' => $target_entity_type->getBaseTable(),
          'entity uuid field' => $target_entity_type->getKey('uuid'),
          'label' => $target_entity_type->getLabel(),
          'title' => $target_entity_type->getLabel(),
          'id' => 'entity_standard_uuid',
        ];
        $views_field['field']['id'] = 'field';
        $views_field['argument']['id'] = 'string';
        $views_field['filter']['id'] = 'equality';
        $views_field['sort']['id'] = 'standard';
        $views_field['field']['click sortable'] = FALSE;
      }
    }
  }

  /**
   * Helper to be called from getViewsData() in subclass of EntityViewsData.
   *
   * @see \Drupal\views\EntityViewsData::getViewsData().
   *
   * @param array $data
   *   The Views data.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type whose base fields we are checking.
   * @param \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage
   *   The entity storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function addReverseEntityReferenceUuid(array &$data, EntityTypeInterface $entity_type, SqlEntityStorageInterface $storage) {

    $entity_type_id = $entity_type->id();
    $base_table = $entity_type->getBaseTable() ?: $entity_type_id;
    $views_revision_base_table = NULL;
    $revisionable = $entity_type->isRevisionable();

    $revision_table = '';
    if ($revisionable) {
      $revision_table = $entity_type->getRevisionTable() ?: $entity_type_id . '_revision';
    }

    $translatable = $entity_type->isTranslatable();
    $data_table = '';
    if ($translatable) {
      $data_table = $entity_type->getDataTable() ?: $entity_type_id . '_field_data';
    }

    // Load all typed data definitions of all fields. This should cover each of
    // the entity base, revision, data tables.
    $field_definitions = $this->getEntityManager()->getBaseFieldDefinitions($entity_type_id);
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    if ($table_mapping = $storage->getTableMapping($field_definitions)) {
      // Iterate over each table we have so far and collect field data for each.
      // Based on whether the field is in the field_definitions provided by the
      // entity manager.
      // @todo We should better just rely on information coming from the entity
      //   storage.
      // @todo https://www.drupal.org/node/2337511
      foreach ($table_mapping->getTableNames() as $table) {
        foreach ($table_mapping->getFieldNames($table) as $field_name) {
          $field_type = $field_definitions[$field_name]->getType();
          if ($field_type === 'entity_reference_uuid' && $table !== $revision_table) {
            $target_entity_type_id = $field_definitions[$field_name]->getItemDefinition()->getSetting('target_type');
            $target_entity_type = $this->getEntityManager()->getDefinition($target_entity_type_id);
            // The line below is technically wrong since the UUID is not copied
            // to the data table, but we use hook_views_data_alter() to make it
            // appear to be present.
            $target_base_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();
            // Provide a reverse relationship for the entity type that is referenced by
            // the field.
            $args['@entity'] = $entity_type->getLabel();
            $args['@label'] = $target_entity_type->getLowercaseLabel();
            $args['@field_name'] = $field_name;
            $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $field_name;
            $data[$target_base_table][$pseudo_field_name]['relationship'] = [
              'title' => t('@entity using @field_name', $args),
              'label' => t('@field_name', ['@field_name' => $field_name]),
              'group' => $target_entity_type->getLabel(),
              'help' => t('Relate each @entity with a @field_name set to the @label.', $args),
              'id' => 'entity_reverse_uuid',
              'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
              'entity base table' => $entity_type->getBaseTable(),
              'entity_type' => $entity_type_id,
              'base field' => $entity_type->getKey('id'),
              'target base' => $target_base_table,
              'target entity base table' => $target_entity_type->getBaseTable(),
              'target entity uuid field' => $target_entity_type->getKey('uuid'),
              'target entity base field' => $target_entity_type->getKey('id'),
              'field_name' => $field_name,
              'field table' => $data_table  ?: $base_table,
              'field field' => $field_name,
            ];
            // @todo - do we need to add the entity status as join extra?
          }
        }
      }
    }
  }

  /**
   * Don't fail if this trait is used someplace unexpected.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   The default entityManager.
   */
  protected function getEntityManager() {
    return $this->entityManager ?? \Drupal::entityManager();
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\views\EntityViewsData::getViewsTableForEntityType
   */
  public function getViewsTableForEntityType(EntityTypeInterface $entity_type) {
    return $entity_type->getDataTable() ?: $entity_type->getBaseTable();
  }

}
