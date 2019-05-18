<?php

namespace Drupal\chatbot\Controller;

use Drupal\chatbot\Entity\ChatbotInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handle incoming requests from Service.
 *
 * @package Drupal\chatbot\Controller
 */
class ChatbotController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request $request
   */
  public $request;

  /**
   * Logging channel to use for writing log events.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Bot.
   *
   * @var \Drupal\chatbot\Plugin\ChatbotPluginInterface
   */
  protected $bot;

  /**
   * The incoming messages queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $incomingMessageQueue;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $request, LoggerChannelInterface $logger, QueueFactory $queueFactory) {
    $this->request = $request->getCurrentRequest();
    $this->logger = $logger;

    // Instantiate reliable queue.
    $this->incomingMessageQueue = $queueFactory->get('incoming_messages', TRUE);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('logger.factory')->get('chatbot'),
      $container->get('queue')
    );
  }

  /**
   * Route responder for all webhook paths.
   *
   * @param $entity ChatbotInterface
   *   Chatbot entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An HTTP response object.
   */
  public function webhook_process(ChatbotInterface $entity) {
    $this->bot = $entity->getPlugin();
    // Pass workflow entity to the workflow service.
    $workflows = $entity->get('workflow')->referencedEntities();

    $workflow = reset($workflows);
    $this->bot->getWorkflow()->configure($workflow);

    $requestMethod = $this->request->server->get('REQUEST_METHOD');

    try {
      switch ($requestMethod) {
        case 'POST':
          $response = $this->parsePost();
          break;

        case 'GET':
          $response = $this->parseGet();
          $response->headers->set('Content-Type', 'application/json');
          break;

        default:
          $response = new Response('Method not allowed.');
          $response->setStatusCode(405);
      }
    }
    catch (\Exception $e) {
      $this->logger->error("Failed to process in-coming data from %method: @exception",
        [
          '%method' => $requestMethod,
          '@exception' => $e->getMessage(),
        ]);
      $this->logger->emergency("Failed to process in coming data: %method", ['%method' => $requestMethod]);

      // Based on testing, it looks like FB auto retries if we send them a 400.
      // I wonder if they auto retry based on other responses.
      $response = new Response('Invalid data received');

      $this->logHttpResponse($response);
    }

    $this->logHttpResponse($response);
    return $response;
  }

  /**
   * Process an incoming GET method request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An HTTP response object.
   */
  public function parseGet() {
    $response = $this->getChallenge();
    return $response;
  }

  private function getChallenge() {
    return $this->bot->challenge();
  }

  /**
   * Process an incoming POST method request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An HTTP response object.
   */
  public function parsePost() {
    $rawDataReceived = $this->bot->parsePostData($this->request);
    if ($rawDataReceived instanceof JsonResponse) {
      return $rawDataReceived;
    }

    $this->logIncomingPost($rawDataReceived);
    $this->queueIncoming($rawDataReceived);
    $this->processQueueItems();

    return new Response('Message(s) received.');
  }

  /**
   * When incoming post logging is enabled, log the decoded post body.
   *
   * @var $dataReceived string
   *
   * @return bool
   *   Returns TRUE if incoming post was logged, otherwise, returns FALSE.
   *
   * @todo: sanitize dataReceived before logging
   */
  public function logIncomingPost($dataReceived) {
    $this->logger->debug("Incoming POST received: @dataReceived",
      array(
        '@dataReceived' => print_r($dataReceived, TRUE),
      )
    );
    return TRUE;
  }

  /**
   * When http response logging is enabled, log response controller will return.
   *
   * @var $response Response
   *
   * @return bool
   *   Returns TRUE if response was logged, otherwise, returns FALSE.
   *
   * @todo: sanitize dataReceived before logging
   */
  public function logHttpResponse(Response $response) {
    $this->logger->debug("Returning HTTP Response @code: @message",
        [
          '@code' => $response->getStatusCode(),
          '@message' => $response->getContent(),
        ]
      );
    return TRUE;
  }

  /**
   * Queue the incoming posts from Service.
   *
   * @param string $rawDataReceived
   *   Raw post data received.
   */
  protected function queueIncoming($rawDataReceived) {
    $this->incomingMessageQueue->createItem($rawDataReceived);
  }

  /**
   * Claim and process items in the incoming_messages queue.
   */
  protected function processQueueItems() {
    // Continue to claim and process items as long as we don't exceed the
    // timeout setting specified in configuration, defaults to 30 seconds.
    $end = time() + 30;
    while ((time() < $end) && ($item = $this->incomingMessageQueue->claimItem())) {
      $this->bot->process($item->data);
      $this->incomingMessageQueue->deleteItem($item);
    }
  }

  public function chatbotsMenuItemListings() {
    $menu_tree = \Drupal::menuTree();
    $menu_name = 'admin';

    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
    $currentLinkId = reset($parameters->activeTrail);
    $parameters->setRoot($currentLinkId)->excludeRoot()->setTopLevelOnly()->onlyEnabledLinks();
    $tree = $menu_tree->load($menu_name, $parameters);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    $content = [];
    foreach ($tree as $key => $element) {
      if (!$element->access->isAllowed()) {
        continue;
      }
      // @var $link \Drupal\Core\Menu\MenuLinkInterface
      $link = $element->link;
      $content[$key]['title'] = $link->getTitle();
      $content[$key]['options'] = $link->getOptions();
      $content[$key]['description'] = $link->getDescription();
      $content[$key]['url'] = $link->getUrlObject();
    }
    return [
      '#theme' => 'admin_block_content',
      '#content' => $content,
    ];
  }

}
