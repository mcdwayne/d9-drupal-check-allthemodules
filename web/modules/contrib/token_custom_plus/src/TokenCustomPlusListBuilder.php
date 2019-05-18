<?php

namespace Drupal\token_custom_plus;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\token_custom\TokenCustomListBuilder;

/**
 * Defines a class to build a listing of custom token (plus) entities.
 *
 * @see \Drupal\token_custom\Entity\TokenCustom
 */
class TokenCustomPlusListBuilder extends TokenCustomListBuilder {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);
    $this->limit = token_custom_plus_get_setting('custom_token_list_limit');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    if (token_custom_plus_get_setting('custom_token_list_sort_by_type')) {
      $query = $this->getStorage()->getQuery()
        ->sort($this->entityType->getKey('bundle'))
        ->sort($this->entityType->getKey('id'));

      if (!empty($this->limit)) {
        $query->pager($this->limit);
      }
      return $query->execute();
    }
    return parent::getEntityIds();
  }

  /**
   * {@inheritdoc}
   *
   * Adds Clone to the Operations drop-down.
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $operations['clone'] = [
      'title' => $this->t('Clone'),
      'url' => $this->ensureDestination($entity->toUrl('add-form')),
      'weight' => isset($operations['edit']['weight']) ? $operations['edit']['weight'] + 1 : 20,
    ];
    // See token_custom_plus_entity_prepare_form().
    $custom_token_clone_suffix = token_custom_plus_get_setting('custom_token_clone_suffix');
    // Suffix may be overridden to be empty string '' -- but not NULL.
    if (is_null($custom_token_clone_suffix)) {
      $custom_token_clone_suffix = '*';
    }
    // Note: type is already tranferred via URL parameter token_custom_type.
    $query_pars = [
      'name' => $entity->name->value . $custom_token_clone_suffix,
      'machine_name' => $entity->id() . $custom_token_clone_suffix,
      'description' => $entity->getDescription(),
      'content' => $entity->getRawContent(),
    ];
    $operations['clone']['url']->mergeOptions(['query' => $query_pars]);

    return $operations;
  }

}
