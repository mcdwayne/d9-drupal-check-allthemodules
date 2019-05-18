<?php

namespace Drupal\chatbot\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a route subscriber to register urls for serving chatbots.
 */
class ChatbotRoutes implements ContainerInjectionInterface {

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a new chatbot route subscriber.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager service.
   */
  public function __construct(StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = array();

    $chatbots = \Drupal::entityTypeManager()->getStorage('chatbot')->loadMultiple();
    foreach ($chatbots as $entity_id => $chatbot) {
      $routes["chatbot.webhook_$entity_id"] = new Route(
        $chatbot->getWebhookPath(),
        [
          '_controller' => 'Drupal\chatbot\Controller\ChatbotController::webhook_process',
          '_title' => 'Webhook',
          'entity' => $entity_id,
        ],
        [
          '_access' => 'TRUE'
        ],
        [
          'parameters' => [
            'entity' => [
              'type' => 'entity:chatbot',
            ],
          ],
        ]
      );
    }

    return $routes;
  }

}
