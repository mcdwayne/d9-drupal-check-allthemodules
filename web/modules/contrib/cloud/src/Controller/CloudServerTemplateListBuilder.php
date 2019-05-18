<?php

namespace Drupal\cloud\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;

/**
 * Provides a list controller for CloudServerTemplate entity.
 *
 * @ingroup cloud_server_template
 */
class CloudServerTemplateListBuilder extends CloudContentListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header = [
      // The header gives the table the information it needs in order to make
      // the query calls for ordering. TableSort uses the field information
      // to know what database column to sort by.
      ['data' => t('Name'), 'specifier' => 'name', 'field' => 'name'],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $header = $this->buildHeader();
    $query = $this->getStorage()->getQuery();

    // Get cloud_context from a path.
    $cloud_context = $this->routeMatch->getParameter('cloud_context');

    if (isset($cloud_context)) {
      $query->tableSort($header)
        ->condition('cloud_context', $cloud_context);
    }
    else {
      $query->tableSort($header);
    }

    // Only return templates the current user owns.
    if (!$this->currentUser->hasPermission('view any published cloud server template entities')) {
      if ($this->currentUser->hasPermission('view own published cloud server template entities')) {
        $query->condition('uid', $this->currentUser->id());
      }
      else {
        // Don't return any results if the user does not have any of
        // the above conditions.
        return [];
      }
    }

    $keys = $query->execute();
    return $this->storage->loadMultiple($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];

    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.cloud_server_template.canonical',
      [
        'cloud_server_template' => $entity->id(),
        'cloud_context' => $entity->getCloudContext(),
      ]
    );

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->hasLinkTemplate('launch')) {
      if ($this->currentUser->hasPermission('launch server template')) {
        $operations['launch'] = [
          'title' => t('Launch'),
          'url' => $entity->toUrl('launch'),
          'weight' => 100,
        ];
      }
    }
    if ($entity->hasLinkTemplate('copy')) {
      if ($entity->access('update')) {
        $operations['copy'] = [
          'title' => t('Copy'),
          'url' => $entity->toUrl('copy'),
          'weight' => 100,
        ];
      }
    }
    return $operations;
  }

}
