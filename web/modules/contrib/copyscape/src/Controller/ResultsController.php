<?php

namespace Drupal\copyscape\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Url;

/**
 * Returns responses for copyscape routes.
 */
class ResultsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The $entityTypeManager definition.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a ResultsController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeM $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    DateFormatterInterface $dateFormatter
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * Provides the copyscape results.
   *
   * @return array
   */
  public function results() {

    $table = [];

    // Build resultst table.
    $table['table'] = [
      '#type' => 'table',
      '#header' => [
        [
          'data' => $this->t('Node ID'),
          'specifier' => 'nid',
          'sort' => 'desc'
        ],
        [
          'data' => $this->t('Title'),
          'specifier' => 'name',
        ],
        [
          'data' => $this->t('User'),
          'specifier' => 'uid',
        ],
        [
          'data' => $this->t('Updated'),
          'specifier' => 'created',
        ],
        [
          'data' => $this->t('Response'),
          'specifier' => 'response',
        ],
        [
          'data' => t('Actions'),
        ]
      ],
      '#empty' => $this->t('There are no copyscape results in the database at the moment.'),
    ];

    // Get copyscape results entity storage.
    $storage = $this->entityTypeManager->getStorage('copyscape_result');

    // Load all Copyscape results.
    $results = \Drupal::entityQuery('copyscape_result')
      ->tableSort($table['table']['#header'])
      ->pager(10)
      ->execute();

    if (empty($results)) {
      // No copyscape results in the database.
      return $table;
    }

    $results = $storage->loadMultiple($results);

    // Build table rows.
    foreach ($results as $result) {
      $response = unserialize($result->get('response')->getString());
      $response = is_array($response) ? current($response) : $response;

      if (empty($response['result'])) {
        $percentage = $this->t('No results found');
      }
      else {
        $percentage = '';
        foreach ($response['result'] as $value) {
          if (!empty($value['percentmatched'])) {
            $percentage .= $value['url'] . ' ' . $value['percentmatched'] . '%<br />';
          }
        }

        $percentage = Xss::filter($percentage);
      }

      $row = [
        'nid' => [
          '#markup' => $result->get('nid')->getString()
        ],
        'name' => [
          '#markup' =>  $result->get('name')->getString(),
        ],
        'uid' => [
          '#markup' => $result->getOwner()->getUsername(),
        ],
        'created' => [
          '#markup' => $this->dateFormatter->format($result->getCreatedTime(), 'short'),
        ],
        'response' => [
          '#markup' => $percentage,
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => [],
        ],
      ];
      $row['operations']['#links'] = [
        'delete' => [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('entity.copyscape_result.delete_form', [
            'copyscape_result' => $result->id()
            ]),
        ],
      ];
      $table['table'][] = $row;
    }
    $table['pager'] = [
      '#type' => 'pager',
    ];

    return $table;
  }

}
