<?php

namespace Drupal\entity_counter\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\entity_counter\Entity\EntityCounterInterface;
use Drupal\entity_counter\EntityCounterSourceCardinality;
use Drupal\entity_counter\Plugin\EntityCounterSourceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for all entity counter sources.
 */
class EntityCounterPluginSourceController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity counter source plugin manager.
   *
   * @var \Drupal\entity_counter\Plugin\EntityCounterSourceManager
   */
  protected $pluginManager;

  /**
   * Constructs an EntityCounterPluginSourceController object.
   *
   * @param \Drupal\entity_counter\Plugin\EntityCounterSourceManager $plugin_manager
   *   The entity counter source plugin manager.
   */
  public function __construct(EntityCounterSourceManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_counter.source')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $definitions = $this->pluginManager->getDefinitions();
    $definitions = $this->pluginManager->getSortedDefinitions($definitions);
    $definitions = $this->pluginManager->removeExcludeDefinitions($definitions);

    $rows = [];
    foreach ($definitions as $plugin_id => $definition) {
      $rows[$plugin_id] = [
        'data' => [
          $plugin_id,
          $definition['label'],
          $definition['description'],
          ($definition['cardinality'] == -1) ? $this->t('Unlimited') : $definition['cardinality'],
          $definition['provider'],
        ],
      ];
    }
    ksort($rows);

    $build = [];

    // Display info.
    $build['info'] = [
      '#markup' => $this->t('@total sources', ['@total' => count($rows)]),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    // Handlers.
    $build['entity_counter_sources'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Label'),
        $this->t('Description'),
        $this->t('Cardinality'),
        $this->t('Provided by'),
      ],
      '#rows' => $rows,
      '#sticky' => TRUE,
    ];

    return $build;
  }

  /**
   * Shows a list of entity counter sources that can be added.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter
   *   The entity counter entity.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function listSources(Request $request, EntityCounterInterface $entity_counter) {
    $headers = [
      ['data' => $this->t('Handler'), 'width' => '20%'],
      ['data' => $this->t('Description'), 'width' => '60%'],
      ['data' => $this->t('Operations'), 'width' => '20%'],
    ];

    $definitions = $this->pluginManager->getDefinitions();
    $definitions = $this->pluginManager->getSortedDefinitions($definitions);
    $definitions = $this->pluginManager->removeExcludeDefinitions($definitions);

    $rows = [];
    foreach ($definitions as $plugin_id => $definition) {
      // Check cardinality.
      $cardinality = $definition['cardinality'];
      $is_cardinality_unlimited = ($cardinality == EntityCounterSourceCardinality::UNLIMITED);
      $is_cardinality_reached = ($entity_counter->getSources($plugin_id)->count() >= $cardinality);
      if (!$is_cardinality_unlimited && $is_cardinality_reached) {
        continue;
      }

      $row['title']['data'] = [
        '#type' => 'link',
        '#title' => $definition['label'],
        '#url' => Url::fromRoute('entity.entity_counter.source.add_form', [
          'entity_counter' => $entity_counter->id(),
          'entity_counter_source' => $plugin_id,
        ]),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'height' => 'auto',
            'width' => 'auto',
          ]),
        ],
      ];

      $row['description'] = [
        'data' => [
          '#markup' => $definition['description'],
        ],
      ];

      $links['add'] = [
        'title' => $this->t('Add source'),
        'url' => Url::fromRoute('entity.entity_counter.source.add_form', [
          'entity_counter' => $entity_counter->id(),
          'entity_counter_source' => $plugin_id,
        ]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'height' => 'auto',
            'width' => 'auto',
          ]),
        ],
      ];
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];

      $rows[] = $row;
    }

    $build['sources'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No source available.'),
    ];

    return $build;
  }

}
