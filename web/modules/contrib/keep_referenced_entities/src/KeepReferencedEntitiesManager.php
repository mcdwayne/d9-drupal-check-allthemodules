<?php

namespace Drupal\keep_referenced_entities;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Keep referenced entities Manager.
 */
class KeepReferencedEntitiesManager {

  /**
   * Entity which have to be checked for references before the deletion.
   *
   * @var \Drupal\Core\Entity
   */
  protected $entity;

  /**
   * Array of related entities.
   *
   * @var array
   */
  protected $relations;

  /**
   * Constructs the KRE Manager.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Tries to find relations of given entity and formats them as list.
   */
  public function getReferencesList() {
    $results = FALSE;
    $table_names = [];

    $config = \Drupal::config('keep_referenced_entities.settings');
    $entity_types_config = unserialize($config->get('entity_types'));
    // If option is not enabled or entity is not protected or we have flag "delete_force".
    if (!$config->get('enabled')
      || empty($entity_types_config[$this->entity->getEntityTypeId()])
      || $this->entity->__get('delete_force') === TRUE) {
      return $results;
    }

    $entity_type_config = $entity_types_config[$this->entity->getEntityTypeId() . '_bundles'];

    foreach ($this->getFields() as $field) {
      // Default handler.
      if (strpos($field->getSetting('handler'), 'default:') === 0) {
        // If field references on current entity.
        if ($field->getSetting('handler') == 'default:' . $this->entity->getEntityTypeId()) {
          $settings = $field->getSetting('handler_settings');
          // If field referenced on current bundle.
          if (!empty($settings['target_bundles'][$this->entity->bundle()])) {
            // If bundle is protected
            if (!empty($entity_type_config[$this->entity->bundle()])) {
              $table_names[$field->get('field_name')] = [
                'entity_type' => $field->get('entity_type'),
                'table_name' => $field->get('entity_type') . '__' . $field->get('field_name')
              ];
            }
          }
        }
      }
      // Other cases (for example views).
      else {
        // @todo handle it.
        throw new \Exception('Cannot handle related entities.');
      }
    }

    // Build the set of queries.
    foreach ($table_names as $field_name => $entity_data) {
      if (empty($query)) {
        $query = \Drupal::database()->select($entity_data['table_name'], 't');
        $query->addExpression("'{$entity_data['entity_type']}'", 'entity_type');
        $query->fields('t', ['bundle', 'entity_id']);
        $query->condition($field_name . '_target_id', $this->entity->id());
      }
      else {
        $query2 = \Drupal::database()->select($entity_data['table_name'], 't');
        $query2->addExpression("'{$entity_data['entity_type']}'", 'entity_type');
        $query2->fields('t', ['bundle', 'entity_id']);
        $query2->condition($field_name . '_target_id', $this->entity->id());
        $query->union($query2);
      }
    }

    // Try to get results.
    if (!empty($query)) {
      $query->orderBy('entity_type');
      $query->orderBy('bundle');
      $data = $query->execute()->fetchAll();

      // Format results if we have them.
      if (!empty($data)) {
        $results = t(
          'Entity <a href="@entity-url">@entity-label</a> cannot be deleted
            because it is referenced with other entities. You should delete
            referenced entities before you can delete this entity.',
          [
            '@entity-url' => $this->getEntityUrl(),
            '@entity-label' => $this->entity->label()
          ]
        ) . $this->formatResults($data);
      }
    }

    return $results;
  }

  /**
   * Returns list of fields which have type "entity_reference".
   */
  private function getFields() {
    $field_query = \Drupal::entityQuery('field_config')
      ->condition('field_type', 'entity_reference');
    $fields_ids = $field_query->execute();
    $fields = FieldConfig::loadMultiple($fields_ids);
    return $fields;
  }

  /**
   * Formats the list of entities.
   */
  private function formatResults($data) {
    $entities_ids = [];
    foreach ($data as $item) {
      // Init an array.
      if (empty($entities_ids[$item->entity_type])) {
        $entities_ids[$item->entity_type] = [];
      }

      // Collect entity id.
      $entities_ids[$item->entity_type][] = $item->entity_id;
    }

    $result = [];
    // Prepare data for the list.
    foreach ($entities_ids as $entity_type => $ids) {
      $entities = \Drupal::entityTypeManager()
        ->getStorage($entity_type)
        ->loadMultiple($ids);

      foreach ($entities as $entity) {
        $link = $entity->toLink(
          $entity->label(),
          'edit-form',
          ['attributes' => ['target' => '_blank']]
        );
        $result[] = $link->toRenderable();
      }
      if (count($result) >= 10) {
        $result[] = '...';
        break;
      }
    }

    $list = [
      '#title' => t(
        'List of entities, which are referenced to this <a href="@entity-url">@entity-label</a>:',
        [
          '@entity-url' => $this->getEntityUrl(),
          '@entity-label' => $this->entity->label()
        ]
      ),
      '#theme' => 'item_list',
      '#empty' => t('This entity has no referenced entities.'),
      '#items' => $result
    ];
    return render($list)->__toString();
  }

  /**
   * Returns entity link as string.
   */
  private function getEntityUrl() {
    return $this->entity->toUrl()->toString();
  }
}
