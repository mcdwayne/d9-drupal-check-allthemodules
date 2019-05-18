<?php

namespace Drupal\external_entities\Plugin\pathauto\AliasType;

use Drupal\pathauto\Plugin\pathauto\AliasType\EntityAliasTypeBase;
use Drupal\pathauto\PathautoState;

/**
 * A pathauto alias type plugin for external entities.
 */
class ExternalEntityAliasTypeBase extends EntityAliasTypeBase {

  /**
   * The maximum amount of items to process per update batch process.
   */
  const ITEMS_PER_UPDATE_PROCESS = 25;

  /**
   * The maximum amount of items to process per delete batch process.
   */
  const ITEMS_PER_DELETE_PROCESS = 100;

  /**
   * {@inheritdoc}
   */
  public function batchUpdate($action, &$context) {
    if (!isset($context['sandbox']['current'])) {
      $context['sandbox']['count'] = 0;
      $context['sandbox']['current'] = 0;
      $context['sandbox']['processed'] = 0;
    }

    $query = $this
      ->entityTypeManager
      ->getStorage($this->getEntityTypeId())
      ->getQuery();
    $query->addTag('pathauto_bulk_update');
    $query->addMetaData('entity', $this->getEntityTypeId());

    // Get the total amount of items to process.
    if (!isset($context['sandbox']['total'])) {
      $count_query = clone $query;
      $context['sandbox']['total'] = $count_query->count()->execute();

      // If there are no entities to update, then stop immediately.
      if (!$context['sandbox']['total']) {
        $context['finished'] = 1;
        return;
      }
    }

    $query->range($context['sandbox']['processed'], self::ITEMS_PER_UPDATE_PROCESS);
    $ids = $query->execute();

    $updates = $this->bulkUpdate($ids);
    $context['sandbox']['count'] += count($ids);
    $context['sandbox']['current'] = max($ids);
    $context['sandbox']['processed'] += self::ITEMS_PER_UPDATE_PROCESS;
    $context['results']['updates'] += $updates;
    $entity_type = $this->entityTypeManager->getDefinition($this->getEntityTypeId());
    $context['message'] = $this->t('Updated alias for %label @id.', [
      '%label' => $entity_type->getLabel(),
      '@id' => end($ids),
    ]);

    if ($context['sandbox']['processed'] != $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['processed'] / $context['sandbox']['total'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function batchDelete(&$context) {
    if (!isset($context['sandbox']['current'])) {
      $context['sandbox']['count'] = 0;
      $context['sandbox']['current'] = 0;
    }

    $query = $this->database->select('url_alias', 'ua');
    $query->addField('ua', 'pid');
    $query->addExpression("REPLACE(ua.source, '" . $this->getSourcePrefix() . "', '')", 'entity_id');
    $query->condition('ua.source', $this->getSourcePrefix() . '%', 'LIKE');
    $query->condition('ua.source', $this->getSourcePrefix() . '%/%', 'NOT LIKE');
    $query->condition('ua.pid', $context['sandbox']['current'], '>');
    $query->orderBy('ua.pid');
    $query->addTag('pathauto_bulk_delete');
    $query->addMetaData('entity', $this->getEntityTypeId());

    // Get the total amount of items to process.
    if (!isset($context['sandbox']['total'])) {
      $context['sandbox']['total'] = $query
        ->countQuery()
        ->execute()
        ->fetchField();

      // If there are no entities to delete, then stop immediately.
      if (!$context['sandbox']['total']) {
        $context['finished'] = 1;
        return;
      }
    }

    $query->range(0, self::ITEMS_PER_DELETE_PROCESS);
    $pids_by_id = $query->execute()->fetchAllKeyed(1, 0);

    PathautoState::bulkDelete($this->getEntityTypeId(), $pids_by_id);
    $context['sandbox']['count'] += count($pids_by_id);
    $context['sandbox']['current'] = max($pids_by_id);
    $context['results']['deletions'][] = $this->getLabel();

    if ($context['sandbox']['count'] != $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['count'] / $context['sandbox']['total'];
    }
  }

}
