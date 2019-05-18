<?php

/**
 * @file Contains \Drupal\semantic_connector\PowerTaggingConfigListBuilder.
 */

namespace Drupal\powertagging;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\powertagging\Entity\PowerTaggingConfig;

/**
 * Provides a listing of PowerTagging entities.
 */
class PowerTaggingConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['server'] = t('PoolParty server');
    $header['project'] = t('Selected project');
    $header['available'] = t('Available in entity type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Get project label.
    /** @var PowerTaggingConfig $entity */
    $entity = PowerTaggingConfig::load($entity->id());
    $connection_config = $entity->getConnection()->getConfig();
    $project_label = t('Project label not found');
    if (isset($connection_config['projects'])) {
      foreach ($connection_config['projects'] as $project) {
        if ($project['id'] == $entity->getProjectId()) {
          $project_label = $project['title'];
          break;
        }
      }
    }

    // Get the entity types with PowerTagging field.
    if ($fields = $entity->getFields()) {
      $fields_list = $entity->renderFields('item_list', $fields);
    }
    else {
      $fields_list = new FormattableMarkup('<div class="semantic-connector-italic">@notyetset</div>', ['@notyetset' => t('not yet set')]);
    }

    $row['title'] = new FormattableMarkup('<div class="semantic-connector-led" data-server-id="@connectionid" data-server-type="pp-server" title="@servicetitle"></div>@entitytitle', [
      '@connectionid' => $entity->getConnection()->id(),
      '@servicetitle' => t('Checking service'),
      '@entitytitle' => $entity->getTitle(),
    ]);
    $row['server'] = Link::fromTextAndUrl($entity->getConnection()
      ->getTitle(), Url::fromUri($entity->getConnection()
        ->getUrl() . '/PoolParty'));
    $row['project'] = $project_label;
    $row['available'] = $fields_list;

    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = [];
    if ($entity->access('update') && $entity->hasLinkTemplate('edit-config-form')) {
      $operations['edit'] = [
        'title' => t('Edit'),
        'url' => Url::fromRoute('entity.powertagging.edit_config_form', ['powertagging' => $entity->id()]),
        'weight' => 10,
      ];
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $operations['delete'] = [
        'title' => t('Delete'),
        'url' => Url::fromRoute('entity.powertagging.delete_form', ['powertagging' => $entity->id()]),
        'weight' => 100,
      ];
    }
    if ($entity->access('create') && $entity->hasLinkTemplate('clone-form')) {
      $operations['clone'] = [
        'title' => t('Clone'),
        'url' => Url::fromRoute('entity.powertagging.clone_form', ['powertagging' => $entity->id()]),
        'weight' => 1000,
      ];
    }
    $destination = \Drupal::destination()->get();
    if ($entity->access('update')) {
      $operations['tag_content'] = [
        'title' => t('Tag content'),
        'url' => Url::fromRoute('entity.powertagging.tag_content', ['powertagging_config' => $entity->id()], ['query' => ['destination' => $destination]]),
        'weight' => 1000,
      ];
    }
    if ($entity->access('update')) {
      $operations['update_vocabulary'] = [
        'title' => t('Update vocabulary'),
        'url' => Url::fromRoute('entity.powertagging.update_vocabulary', ['powertagging_config' => $entity->id()], ['query' => ['destination' => $destination]]),
        'weight' => 1000,
      ];
    }

    return $operations;
  }
}
