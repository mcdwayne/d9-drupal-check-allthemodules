<?php

namespace Drupal\entity_split\Entity;

use Drupal\views\EntityViewsData;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides Views data for entity split entities.
 */
class EntitySplitViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data_table = 'entity_split_field_data';

    $data[$data_table]['table']['base']['help'] = $this->t('Entity splits attached to entities.');
    $data[$data_table]['entity_id']['field']['id'] = 'master_entity';

    unset($data[$data_table]['entity_id']['relationship']);

    // Provide relationships for content entities type except for entity split.
    foreach (static::entityTypeService()->getDefinitions() as $type => $entity_type) {
      if ($type === 'entity_split' || !$entity_type->entityClassImplements(ContentEntityInterface::class) || !$entity_type->getBaseTable() || empty(EntitySplitType::getEntitySplitTypesForEntityType($type))) {
        continue;
      }

      $data[$data_table][$type] = [
        'relationship' => [
          'title' => $entity_type->getLabel(),
          'help' => $this->t('The @entity_type to which the entity split is attached.', ['@entity_type' => $entity_type->getLabel()]),
          'base' => $this->getViewsTableForEntityType($entity_type),
          'base field' => $entity_type->getKey('id'),
          'relationship field' => 'entity_id',
          'id' => 'standard',
          'label' => $entity_type->getLabel(),
          'extra' => [
            [
              'field' => 'entity_type',
              'value' => $type,
              'table' => $data_table,
            ],
          ],
        ],
      ];

      if ($entity_type->hasKey('langcode')) {
        // Use OR operator for language conditions.
        $data[$data_table][$type]['relationship']['join_id'] = 'field_or_language_join';

        $data[$data_table][$type]['relationship']['extra'][] = [
          'field' => 'langcode',
          'left_field' => $entity_type->getKey('langcode'),
        ];

        $data[$data_table][$type]['relationship']['extra'][] = [
          'field' => 'langcode',
          'value' => 'und',
          'table' => $data_table,
        ];
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function addEntityLinks(array &$data) {
    // Do not add entity links.
  }

  /**
   * Returns entity type service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   Entity type service.
   */
  private static function entityTypeService() {
    return \Drupal::service('entity_type.manager');
  }

}
