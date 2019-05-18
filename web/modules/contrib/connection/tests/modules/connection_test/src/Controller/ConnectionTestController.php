<?php

namespace Drupal\connection_test\Controller;

use Drupal\connection\Plugin\ConnectionManager;
use Drupal\connection\ConnectionBridgeManager;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;


/**
 * Class ConnectionTestController.
 */
class ConnectionTestController extends ControllerBase {

  /**
   * Drupal\connection\Plugin\ConnectionManager definition.
   *
   * @var \Drupal\connection\Plugin\ConnectionManager
   */
  protected $connectionManager;

  /**
   * Drupal\connection\ConnectionBridgeManager definition.
   *
   * @var \Drupal\connection\ConnectionBridgeManager
   */
  protected $connectionBridgeManager;

  /**
   * ConnectionTestController constructor.
   *
   * @param \Drupal\connection\Plugin\ConnectionManager $connection_manager
   * @param \Drupal\connection\ConnectionBridgeManager $connection_bridge_manager
   */
  public function __construct(ConnectionManager $connection_manager, ConnectionBridgeManager $connection_bridge_manager) {
    $this->connectionManager = $connection_manager;
    $this->connectionBridgeManager = $connection_bridge_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.connection'),
      $container->get('plugin.manager.connection_bridge')
    );
  }

  /**
   * @param \Drupal\connection_test\Controller\string $plugin_id
   *
   * @return array
   */
  public function output(string $plugin_id) {
    if ($plugin = $this->connectionBridgeManager->getDefinition($plugin_id)) {
      try {
        $connection = $this->connectionManager->createInstance($plugin['type']);
      }
      catch (PluginException $e) {
        throw new PreconditionFailedHttpException();
      }
      $params = ['url' => $plugin['base_url'] . $plugin['endpoint']];
      $body = $connection->request($params);

      return [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $plugin['label'],
        '#items' => [
          $params['url'],
          $plugin['type'],
          $body,
        ]
      ];
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
