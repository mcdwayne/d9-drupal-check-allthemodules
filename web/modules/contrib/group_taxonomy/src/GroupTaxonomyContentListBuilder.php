<?php

namespace Drupal\group_taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\Controller\GroupContentListBuilder;
use Drupal\group\Entity\GroupContentType;

/**
 * Provides a list controller for menus entities in a group.
 */
class GroupTaxonomyContentListBuilder extends GroupContentListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    $query->sort($this->entityType->getKey('id'));

    // Only show group content for the group on the route.
    $query->condition('gid', $this->group->id());

    // Filter by group menu plugins.
    $plugin_id = 'group_taxonomy';

    $group_content_types = GroupContentType::loadByContentPluginId($plugin_id);
    if (!empty($group_content_types)) {
      $query->condition('type', array_keys($group_content_types), 'IN');
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => $this->t('ID'),
      'label' => $this->t('Taxonomy Vocabulary'),
    ];
    $row = $header + parent::buildHeader();

    // Remove plugin and entity types columns
    unset($row['entity_type'], $row['plugin']);

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupContentInterface $entity */
    $row['id'] = $entity->id();
    $row['label']['data'] = $entity->getEntity()->toLink(NULL,'edit-form');
    $row = $row + parent::buildRow($entity);

    // Remove plugin and entity types data.
    unset($row['entity_type'], $row['plugin']);

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t("There are no taxonomies related to this group yet.");
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupContentInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    // Add view operation for the Group Content Relation.
    if (!isset($operations['view']) && $entity->access('view')) {
      $operations['view'] = [
        'title' => $this->t('View relation'),
        'weight' => 1,
        'url' => $entity->toUrl(),
      ];
    }

    // Add operations to edit and delete the actual entity.
    if ($entity->getEntity()->access('update')) {
      $operations['edit-entity'] = [
        'title' => $this->t('Edit taxonomy'),
        'weight' => 102,
        'url' => $entity->getEntity()->toUrl(),
      ];
    }
    if ($entity->getEntity()->access('delete')) {
      $operations['delete-entity'] = [
        'title' => $this->t('Delete taxonomy'),
        'weight' => 103,
        'url' => $entity->getEntity()->toUrl('delete-form'),
      ];
    }

    return $operations;
  }

}
