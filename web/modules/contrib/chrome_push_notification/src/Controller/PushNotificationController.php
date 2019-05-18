<?php

namespace Drupal\chrome_push_notification\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\chrome_push_notification\Model\ChromeApiCall;

/**
 * Controller routines for chrome push notification.
 */
class PushNotificationController extends ControllerBase {

  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function indexRoute($register_id) {
    $data = $this->database->select(ChromeApiCall::$chromeNotificationTable)
      ->fields(ChromeApiCall::$chromeNotificationTable, ['id'])
      ->condition('register_id', $register_id, '=')->execute();
    $results = $data->fetchAll(\PDO::FETCH_OBJ);
    if (empty($results)) {
      $field = [
        'register_id' => $register_id,
        'registered_on' => time(),
      ];
      $this->database->insert(ChromeApiCall::$chromeNotificationTable)->fields($field)->execute();
    }
    return new JsonResponse(['status' => TRUE]);
  }

  /**
   * {@inheritdoc}
   */
  public function notificationRoute() {
    $config_gpn = $this->config('chrome_push_notification.sendMessage');
    $data = [
      'icon' => $config_gpn->get('chrome_notification_image_url'),
      'url' => $config_gpn->get('chrome_notification_link'),
      'title' => $config_gpn->get('chrome_notification_title'),
      'message' => $config_gpn->get('chrome_notification_message'),
    ];
    return new JsonResponse($data);
  }

  /**
   * {@inheritdoc}
   */
  public function userList() {
    $header = [
      // We make it sortable by ID or Created Date.
      [
        'data' => $this->t('Id'),
        'field' => 'id',
        'sort' => 'asc',
      ],
      ['data' => $this->t('Register Id')],
      [
        'data' => $this->t('Register Date'),
        'field' => 'registered_on',
        'sort' => 'asc',
      ],
    ];

    $getFields = [
      'id',
      'register_id',
      'registered_on',
    ];
    $query = $this->database->select(ChromeApiCall::$chromeNotificationTable);
    $query->fields(ChromeApiCall::$chromeNotificationTable, $getFields);
    // The actual action of sorting the rows is here.
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header);
    // Limit the rows to 20 for each page.
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(ChromeApiCall::$chromeNotificationViewNumber);
    $result = $pager->execute();

    // Populate the rows.
    $rows = [];
    foreach ($result as $row) {
      $rows[] = [
        'data' => [
          'id' => $row->id,
          'register_id' => $row->register_id,
          'date' => $row->registered_on,
        ],
      ];
    }

    // The table description.
    $build = [
      '#markup' => $this->t('List of All Registered Device'),
    ];

    // Generate the table.
    $build['config_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    // Finally add the pager.
    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

}
