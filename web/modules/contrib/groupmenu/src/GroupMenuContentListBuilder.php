<?php

namespace Drupal\groupmenu;

use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\Controller\GroupContentListBuilder;
use Drupal\group\Entity\GroupContentType;

/**
 * Provides a list controller for menus entities in a group.
 */
class GroupMenuContentListBuilder extends GroupContentListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $plugin_id = 'group_menu:menu';
    $group_content_types = GroupContentType::loadByContentPluginId($plugin_id);

    // If we don't have any group menu plugins, we don't have any group menu's.
    if (empty($group_content_types)) {
      return [];
    }

    $query = $this->getStorage()->getQuery();

    // Filter by group menu plugins.
    $query->condition('type', array_keys($group_content_types), 'IN');
    // Only show group content for the group on the route.
    $query->condition('gid', $this->group->id());

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    $query->sort($this->entityType->getKey('id'));
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => $this->t('ID'),
      'label' => $this->t('Menu'),
    ];
    $row = $header + parent::buildHeader();
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
    unset($row['entity_type'], $row['plugin']);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t("There are no menus related to this group yet.");
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

    // Slap on redirect destinations for the administrative operations.
    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }

    // Add operations to edit and delete the actual entity.
    if ($entity->getEntity()->access('update')) {
      $operations['edit-entity'] = [
        'title' => $this->t('Edit menu'),
        'weight' => 102,
        'url' => $entity->getEntity()->toUrl(),
      ];
    }
    if ($entity->getEntity()->access('delete')) {
      $operations['delete-entity'] = [
        'title' => $this->t('Delete menu'),
        'weight' => 103,
        'url' => $entity->getEntity()->toUrl('delete-form'),
      ];
    }

    return $operations;
  }

}
