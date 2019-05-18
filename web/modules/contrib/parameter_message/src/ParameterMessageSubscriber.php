<?php

namespace Drupal\parameter_message;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Component\Utility\Xss;

/**
 * Default class for Subscriber.
 */
class ParameterMessageSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  public $queryFactory;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $request_stack, ConfigFactory $config_factory, QueryFactory $query_factory, EntityTypeManagerInterface $entity_manager) {
    $this->request = $request_stack->getCurrentRequest();
    $this->configFactory = $config_factory;
    $this->queryFactory = $query_factory;
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * Check if exists a parameter to show message.
   */
  public function processParameterMessage(GetResponseEvent $event) {

    $correct_version = $this->checkIsEntityVersion();

    if (empty($correct_version)) {
      return FALSE;
    }

    $parameters = $this->request->query->all();

    if (empty($parameters['message'])) {
      return FALSE;
    }

    $message_parameter = $parameters['message'];

    $query = $this->queryFactory->get('parameter_message_message');

    $query->condition('parameter', '%' . $message_parameter . '%', 'like');

    $message_ids = $query->execute();

    if (empty($message_ids)) {
      return FALSE;
    }

    $parameters = $this->request->query->all();

    $message_id = current($message_ids);
    $message_storage = $this->entityTypeManager->getStorage('parameter_message_message');
    $message = $message_storage->load($message_id);

    $parameter = $message->parameter->value;
    $parameter = trim($parameter);

    $text_message = $message->body->value;
    $text_message = Xss::filter($text_message);
    $text_message = trim($text_message);

    $type = $message->type->value;
    $type = Xss::filter($type);
    $type = trim($type);

    if (!empty($parameters['message']) && $parameters['message'] == $parameter) {
      drupal_set_message($text_message, $type);
    }
  }

  /**
   * Check if Modal Page use Entity Version.
   */
  public function checkIsEntityVersion() {

    $config = $this->configFactory->get('parameter_message.settings');

    $cache_clean = $config->get('cache_clear');

    if (empty($cache_clean)) {
      drupal_flush_all_caches();
      // @codingStandardsIgnoreLine
      \Drupal::configFactory()->getEditable('parameter_message.settings')->set('cache_clear', TRUE)->save();
    }

    $schema_version = drupal_get_installed_schema_version('parameter_message');

    if (!empty($schema_version) && $schema_version <= 8000) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['processParameterMessage'];
    return $events;
  }

}
