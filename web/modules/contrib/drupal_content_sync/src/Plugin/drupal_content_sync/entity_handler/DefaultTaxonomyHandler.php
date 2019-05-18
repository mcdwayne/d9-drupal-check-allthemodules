<?php

namespace Drupal\drupal_content_sync\Plugin\drupal_content_sync\entity_handler;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\drupal_content_sync\Exception\SyncException;
use Drupal\drupal_content_sync\ExportIntent;
use Drupal\drupal_content_sync\ImportIntent;
use Drupal\drupal_content_sync\Plugin\EntityHandlerBase;
use Drupal\drupal_content_sync\SyncIntent;

/**
 * Class DefaultTaxonomyHandler, providing proper file handling capabilities.
 *
 * @EntityHandler(
 *   id = "drupal_content_sync_default_taxonomy_handler",
 *   label = @Translation("Default Taxonomy"),
 *   weight = 90
 * )
 *
 * @package Drupal\drupal_content_sync\Plugin\drupal_content_sync\entity_handler
 */
class DefaultTaxonomyHandler extends EntityHandlerBase {

  /**
   * @inheritdoc
   */
  public static function supports($entity_type, $bundle) {
    return $entity_type == 'taxonomy_term';
  }

  /**
   * @inheritdoc
   */
  public function getAllowedExportOptions() {
    return [
      ExportIntent::EXPORT_DISABLED,
      ExportIntent::EXPORT_AUTOMATICALLY,
      ExportIntent::EXPORT_AS_DEPENDENCY,
      ExportIntent::EXPORT_MANUALLY,
    ];
  }

  /**
   * @inheritdoc
   */
  public function getAllowedPreviewOptions() {
    return [
      'table' => 'Table',
      'preview_mode' => 'Preview mode',
    ];
  }

  /**
   * @inheritdoc
   */
  public function updateEntityTypeDefinition(&$definition) {
    parent::updateEntityTypeDefinition($definition);
    $definition['new_properties']['parent'] = [
      'type' => 'object',
      'default_value' => NULL,
    ];
    $definition['new_property_lists']['details']['parent'] = 'value';
    $definition['new_property_lists']['modifiable']['parent'] = 'value';
    $definition['new_property_lists']['database']['parent'] = 'value';
  }

  /**
   * @inheritdoc
   */
  public function getForbiddenFields() {
    return array_merge(
      parent::getForbiddenFields(),
      [
        'parent',
      ]
    );
  }

  /**
   * @inheritdoc
   */
  public function import(ImportIntent $intent) {
    $action = $intent->getAction();

    if ($this->ignoreImport($intent)) {
      return FALSE;
    }

    /**
     * @var \Drupal\Core\Entity\FieldableEntityInterface $entity
     */
    $entity = $intent->getEntity();

    if ($action == SyncIntent::ACTION_DELETE) {
      if ($entity) {
        return $this->deleteEntity($entity);
      }
      return FALSE;
    }

    if (!$entity) {
      $entity_type = \Drupal::entityTypeManager()->getDefinition($intent->getEntityType());

      $base_data = [
        $entity_type->getKey('bundle') => $intent->getBundle(),
        $entity_type->getKey('label') => $intent->getField('title'),
      ];

      $base_data[$entity_type->getKey('uuid')] = $intent->getUuid();

      $storage = \Drupal::entityTypeManager()->getStorage($intent->getEntityType());
      $entity = $storage->create($base_data);

      if (!$entity) {
        throw new SyncException(SyncException::CODE_ENTITY_API_FAILURE);
      }

      $intent->setEntity($entity);
    }

    $parent_reference = $intent->getField('parent');
    if ($parent_reference) {
      $parent = $intent->loadEmbeddedEntity($parent_reference);
      $entity->set('parent', ['target_id' => $parent->id()]);
    }
    else {
      $entity->set('parent', ['target_id' => 0]);
    }

    if (!$this->setEntityValues($intent)) {
      return FALSE;
    }

    // Make sure that menu items that were created for this entity before
    // the entity was available now reference this entity correctly by ID
    // {@see DefaultLinkHandler}.
    $menu_links = \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->loadByProperties(['link.uri' => 'internal:/' . $this->entityTypeName . '/' . $entity->uuid()]);
    foreach ($menu_links as $item) {
      /**
       * @var \Drupal\menu_link_content\Entity\MenuLinkContent $item
       */
      $item->set('link', 'entity:' . $this->entityTypeName . '/' . $entity->id());
      $item->set('enabled', 1);
      $item->save();
    }

    return TRUE;
  }

  /**
   * @inheritdoc
   */
  public function export(ExportIntent $intent, FieldableEntityInterface $entity = NULL) {
    /**
     * @var \Drupal\file\FileInterface $entity
     */
    if (!$entity) {
      $entity = $intent->getEntity();
    }

    if (!parent::export($intent)) {
      return FALSE;
    }

    $query = \Drupal::database()->select('taxonomy_term_hierarchy', 'tth');
    $query->addField('tth', 'parent');
    $query->condition('tth.tid', $entity->id());
    $results = $query->execute()->fetchAll();
    $parent = reset($results);

    if ($parent && $parent->parent) {
      $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $parent_term = $term_storage->load($parent->parent);

      if ($parent_term) {
        $parent = $intent->embedEntity($parent_term, TRUE);
        $intent->setField('parent', $parent);
      }
    }

    return TRUE;
  }

}
