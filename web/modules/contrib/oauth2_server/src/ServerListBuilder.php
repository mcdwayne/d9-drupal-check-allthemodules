<?php

namespace Drupal\oauth2_server;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Builds a listing of oauth2 server entities.
 */
class ServerListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity instanceof ServerInterface) {
      $route_parameters['oauth2_server'] = $entity->id();
      $operations['scopes'] = [
        'title' => $this->t('Scopes'),
        'weight' => 20,
        'url' => new Url('entity.oauth2_server.scopes', $route_parameters),
      ];
      $operations['clients'] = [
        'title' => $this->t('Clients'),
        'weight' => 20,
        'url' => new Url('entity.oauth2_server.clients', $route_parameters),
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'label' => $this->t('Label'),
      'status' => [
        'data' => $this->t('Status'),
        'class' => ['checkbox'],
      ],
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $row = parent::buildRow($entity);

    $status_label = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    $status_icon = [
      '#theme' => 'image',
      '#uri' => $entity->status() ? 'core/misc/icons/73b355/check.svg' : 'core/misc/icons/ea2800/error.svg',
      '#width' => 18,
      '#height' => 18,
      '#alt' => $status_label,
      '#title' => $status_label,
    ];

    return [
      'data' => [
        'label' => [
          'data' => $this->getLabel($entity),
          'class' => ['oauth2-server-name'],
        ],
        'status' => [
          'data' => $status_icon,
          'class' => ['checkbox'],
        ],
        'operations' => $row['operations'],
      ],
      'title' => $this->t('ID: @name', ['@name' => $entity->id()]),
      'class' => [
        Html::cleanCssIdentifier($entity->getEntityTypeId() . '-' . $entity->id()),
        $entity->status() ? 'oauth2-server-list-enabled' : 'oauth2-server-list-disabled',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
      ],
      '#attributes' => [
        'id' => 'oauth2-server-entity-list',
      ],
    ];

    $build['table']['#empty'] = $this->t('No servers available. <a href="@link">Add server</a>.', [
      '@link' => Url::fromRoute('entity.oauth2_server.add_form')->toString(),
    ]);

    $servers = $this->storage->loadMultiple();
    $this->sortByStatusThenAlphabetically($servers);
    foreach ($servers as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * Sorts an array of entities by status and then alphabetically.
   *
   * Will preserve the key/value association of the array.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface[] $entities
   *   An array of config entities.
   */
  protected function sortByStatusThenAlphabetically(array &$entities) {
    uasort($entities, function (ConfigEntityInterface $a, ConfigEntityInterface $b) {
      if ($a->status() == $b->status()) {
        return strnatcasecmp($a->label(), $b->label());
      }
      else {
        return $a->status() ? -1 : 1;
      }
    });
  }

}
