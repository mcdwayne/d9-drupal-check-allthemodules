<?php

namespace Drupal\smartsheet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\smartsheet\SmartsheetClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * The Smartsheet client.
   *
   * @var \Drupal\smartsheet\SmartsheetClientInterface
   */
  protected $smartsheetClient;

  /**
   * Constructs a new DefaultController object.
   *
   * @param \Drupal\smartsheet\SmartsheetClientInterface $smartsheet_client
   *   The Smartsheet client.
   */
  public function __construct(SmartsheetClientInterface $smartsheet_client) {
    $this->smartsheetClient = $smartsheet_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('smartsheet.client')
    );
  }

  /**
   * Overview of existing sheets.
   *
   * @return array
   *   The list of sheets as a table render array.
   */
  public function overview() {
    $output = [
      '#type' => 'table',
      '#header' => [
        'title' => $this->t('Title'),
        'created' => $this->t('Created'),
        'updated' => $this->t('Updated'),
        'operations' => $this->t('Operations'),
      ],
      '#rows' => [],
      '#empty' => $this->t('There is no sheet available at the moment.'),
    ];

    if ($response = $this->smartsheetClient->get('/sheets')) {
      foreach ($response['data'] as $sheet) {
        $links = [
          'view' => [
            'title' => $this->t('View'),
            'url' => Url::fromUri($sheet['permalink']),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('smartsheet.delete', [
              'id' => $sheet['id'],
            ]),
          ],
        ];

        $output['#rows'][] = [
          'title' => $sheet['name'],
          'created' => $sheet['createdAt'],
          'updated' => $sheet['modifiedAt'],
          'operations' => [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ],
        ];
      }
    }

    return $output;
  }

}
