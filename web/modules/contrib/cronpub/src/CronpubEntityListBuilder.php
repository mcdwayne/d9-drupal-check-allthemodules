<?php

/**
 * @file
 * Contains \Drupal\cronpub\CronpubEntityListBuilder.
 */

namespace Drupal\cronpub;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\cronpub\Plugin\Cronpub\CronpubActionManager;

/**
 * Defines a class to build a listing of Cronpub Task entities.
 *
 * @ingroup cronpub
 */
class CronpubEntityListBuilder extends EntityListBuilder {

  /**
   * @var \Drupal\cronpub\Plugin\Cronpub\CronpubActionManager
   */
  private $plugin_manager;

  /**
   * Get the plugin manager for Cronpub plugins.
   * @return \Drupal\cronpub\Plugin\Cronpub\CronpubActionManager
   */
  public function getPluginManager() {
    if (!$this->plugin_manager instanceof CronpubActionManager) {
      $this->plugin_manager = \Drupal::service('plugin.manager.cronpub');
    }
    return $this->plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['bundle'] = $this->t('bundle');
    $header['target'] = $this->t('Target Title');
    $header['actions'] = $this->t('Actions');
    $header['operations'] = '';
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\cronpub\Entity\CronpubEntity */
    $title = ($entity->getTargetEntity()->label())
      ? $entity->getTargetEntity()->label()
      : $entity->label();

    $row['bundle'] = $entity->getTargetEntity()->bundle();
    $row['target'] = $title;

    $plugin_definition = $this->getPluginManager()->getDefinition($entity->getPlugin());
    $row['actions'] = isset($plugin_definition['label']) ? $plugin_definition['label'] : '';

    $row['operations']['data'] = [
      '#type' => 'operations',
      '#links' => [
        'view' => [
          'title' => $this->t('View Chronology'),
          'weight' => 10,
          'url' => new Url(
            'entity.cronpub_entity.view', [
              'cronpub_entity_id' => $entity->id(),
            ]
          )
        ],
        'delete' => [
          'title' => $this->t('Reset Chronology'),
          'weight' => 20,
          'url' => new Url(
            'entity.cronpub_entity.delete_form', [
              'cronpub_entity' => $entity->id(),
            ]
          )
        ],
      ],
    ];
    return $row;
  }

}
