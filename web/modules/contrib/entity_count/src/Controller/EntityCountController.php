<?php

namespace Drupal\entity_count\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class EntityCountController extends ControllerBase {
  public function entityCount() {
    $output = [
      '#type' => 'table',
      '#header' => [
        'entity_type' => $this->t('Entity type'),
        'count' => $this->t('Count'),
        'actions' => $this->t('Actions'),
      ],
    ];

    $entity_type_manager = \Drupal::service('entity_type.manager');
    $definitions = $entity_type_manager->getDefinitions();
    foreach ($definitions as $definition) {
      $entity_type = $definition->getLabel();

      $storage = $entity_type_manager->getStorage($definition->id());
      if (get_class($definition) == 'Drupal\Core\Config\Entity\ConfigEntityType') {
        $entities = $storage->loadMultiple();
        $count = count($entities);
      } else {
        $query = $storage->getAggregateQuery();
        $query->count();
        $count = $query->execute();
      }

      $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');
      $bundle_info = $entity_type_bundle_info->getBundleInfo($definition->id());
      if (count($bundle_info) > 1) {
        $renderer = \Drupal::service('renderer');
        $actions_array = [
          '#type' => 'dropbutton',
          '#links' => [
            'foo' => [
              'title' => $this->t('Per bundle'),
              'url' => Url::fromRoute('entity_count.per_bundle', ['entity_type' => $definition->id()]),
            ],
          ],
        ];
        $actions = $renderer->render($actions_array);
      } else {
        $actions = '';
      }

      $row = [
        'entity_type' => $entity_type,
        'count' => $count,
        'actions' => $actions,
      ];

      $rows[] = $row;
    }

    usort($rows, function($a, $b) {
      return strcmp($a['entity_type'], $b['entity_type']);
    });

    $output['#rows'] = $rows;

    return $output;
  }

  public function perBundle($entity_type) {
    $output = [
      '#type' => 'table',
      '#header' => [
        'bundle' => $this->t('Bundle'),
        'count' => $this->t('Count'),
      ],
    ];

    $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');
    $bundle_info = $entity_type_bundle_info->getBundleInfo($entity_type);
    foreach($bundle_info as $bundle_id => $bundle_data) {
      $bundle = $bundle_data['label'];

      $entity_type_manager = \Drupal::service('entity_type.manager');
      $definition = $entity_type_manager->getDefinition($entity_type);
      $keys = $definition->getKeys();

      $bundle_key = is_null($keys['bundle']) ? 'bundle' : $keys['bundle'];

      $query = \Drupal::entityQuery($entity_type);
      $query->condition($bundle_key, $bundle_id);
      $query->count();
      $count = $query->execute();

      $row = [
        'bundle' => $bundle,
        'count' => $count,
      ];

      $rows[] = $row;
    }

    usort($rows, function($a, $b) {
      return strcmp($a['bundle'], $b['bundle']);
    });

    $output['#rows'] = $rows;

    return $output;
  }
}