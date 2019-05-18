<?php

/**
 * @file
 * Contains \Drupal\cronpub\Controller\CronpubEntityView.
 */

namespace Drupal\cronpub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\cronpub\Entity\CronpubEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class CronpubEntityView.
 *
 * @package Drupal\cronpub\Controller
 */
class CronpubEntityView extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entity_type_manager;
  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entity_type_manager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Index.
   *
   * @param $cronpub_entity_id
   * @return array
   */
  public function index($cronpub_entity_id) {
    $output = [];
    $cronpub_entity = $this->entity_type_manager
      ->getStorage('cronpub_entity')->load($cronpub_entity_id);

    if ($cronpub_entity instanceof CronpubEntity) {

      if ($target_entity = $cronpub_entity->getTargetEntity()) {
        $output['heading'] = [
          '#markup' => "<h2>".$target_entity->label()."</h2>",
        ];
      }

      $output['task_dates'] = [
        '#type' => 'table',
        '#caption' => 'Cron tasks',
        '#header' => [
          $this->t('Time'),
          $this->t('Action'),
          $this->t('Execution'),
        ],
        '#rows' => [],
      ];

      $plugin_definition = $cronpub_entity->getPluginDefinition();
      $chronoi = $cronpub_entity->getChronology();

      foreach ($chronoi as $timestamp => $chronos) {
        // destination time
        $time = \Drupal::service('date.formatter')->format($timestamp, $type = 'medium');

        if (
          (int) $chronos['state']
          && (int) $chronos['state'] > 1000000000
          && (int) $chronos['state'] < 1000000000000
        ) {
          $chronos['state'] = \Drupal::service('date.formatter')
            ->format((int) $chronos['state'], $type = 'long');
        }

        $output['task_dates']['#rows'][] = [
          $time,
          ($chronos['job'] == 'start')
            ? (string) $plugin_definition['start']['label']
            : (string) $plugin_definition['end']['label'],
          $chronos['state'],
        ];

      }
    }
    return $output;
  }

  /**
   * Index.
   *
   * @param $entity_type
   * @param $entity_id
   * @param string $field_name
   *
   * @return array
   */
  public function taskOverview($entity_type, $entity_id, $field_name = '') {
    $params = [
      'entity_type' => $entity_type,
      'entity_id'   => $entity_id,
    ];
    if ($field_name) {
      $params['field_name'] = $field_name;
    }

    $cronpub_entities = $this->entity_type_manager
      ->getStorage('cronpub_entity')->loadByProperties($params);

    $full_output = [];
    foreach ($cronpub_entities as $cronpub_entity) {
      /* @var $cronpub_entity \Drupal\cronpub\Entity\CronpubEntity */
      $field_name = $cronpub_entity->get('field_name')->getValue()[0]['value'];
      $full_output[$field_name] = [];

      if ($target_entity = $cronpub_entity->getTargetEntity()) {
        if ($link = $target_entity->toUrl()->toString()) {
          $full_output[$field_name]['heading'] = [
            '#markup' => sprintf("<h2><a href='%s'>%s</a> (%s)</h2>", $link, $target_entity->label(), $field_name),
          ];
        }
        else {
          $full_output[$field_name]['heading'] = [
            '#markup' => sprintf("<h2>%s (%s)</h2>", $target_entity->label(), $field_name),
          ];
        }

      }

      $full_output[$field_name]['task_dates'] = [
        '#type' => 'table',
        '#caption' => 'Cron tasks',
        '#header' => [
          $this->t('Time'),
          $this->t('Action'),
          $this->t('Execution'),
        ],
        '#rows' => [],
      ];

      $plugin_definition = $cronpub_entity->getPluginDefinition();
      $chronoi = $cronpub_entity->getChronology();

      foreach ($chronoi as $timestamp => $chronos) {
        // destination time
        $time = \Drupal::service('date.formatter')->format($timestamp, $type = 'medium');

        if (
          (int) $chronos['state']
          && (int) $chronos['state'] > 1000000000
          && (int) $chronos['state'] < 1000000000000
        ) {
          $chronos['state'] = \Drupal::service('date.formatter')
            ->format((int) $chronos['state'], $type = 'long');
        }

        $full_output[$field_name]['task_dates']['#rows'][] = [
          $time,
          ($chronos['job'] == 'start')
            ? (string) $plugin_definition['start']['label']
            : (string) $plugin_definition['end']['label'],
          $chronos['state'],
        ];
      }

    }
    return $full_output;
  }
}
