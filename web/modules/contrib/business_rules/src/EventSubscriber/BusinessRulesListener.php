<?php

namespace Drupal\business_rules\EventSubscriber;

use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\Util\BusinessRulesProcessor;
use Drupal\business_rules\Util\BusinessRulesUtil;
use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class BusinessRulesListener.
 *
 * @package Drupal\business_rules\EventSubscriber
 */
class BusinessRulesListener implements EventSubscriberInterface {

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  private static $container;

  /**
   * The business rule processor.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesProcessor
   */
  private $processor;

  /**
   * The Business Rules Util.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  private $util;

  /**
   * BusinessRulesListener constructor.
   *
   * @param \Drupal\business_rules\Util\BusinessRulesProcessor $processor
   *   The business rule processor service.
   * @param \Drupal\business_rules\Util\BusinessRulesUtil $util
   *   The business rule util.
   */
  public function __construct(BusinessRulesProcessor $processor, BusinessRulesUtil $util) {
    $this->util      = $util;
    $this->processor = $processor;
  }

  /**
   * Sets the container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface|null $container
   *   A ContainerInterface instance or null.
   */
  public static function setContainer(ContainerInterface $container = NULL) {
    self::$container = $container;
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $return['business_rules.item_pos_delete'] = 'itemPosDelete';
    $return[KernelEvents::TERMINATE][]        = ['onTerminate', 100];

    // If there is no container service there is not possible to load any event.
    // As this method can be called before the container is ready, it might not
    // be available.
    // To avoid the necessity to manually clear all caches via user interface,
    // we are getting the plugin definition using this ugly way.
    if (!\Drupal::hasContainer() || !\Drupal::hasService('plugin.manager.business_rules.reacts_on')) {
      $query = Database::getConnection()
        ->query('SELECT value FROM {key_value} WHERE collection = :collection AND name = :name', [
          ':collection' => 'state',
          ':name'       => 'system.module.files',
        ])
        ->fetchCol();

      $modules = [];
      if (isset($query[0])) {
        $modules = unserialize($query[0]);
      }

      foreach ($modules as $name => $module) {
        $arr = explode('/', $module);
        unset($arr[count($arr) - 1]);
        $path = implode('/', $arr);

        // Skip core modules.
        if ($arr[0] != 'core') {
          $root_namespaces["Drupal\\$name"] = "$path/src";
        }
      }

      $root_namespaces['_serviceId'] = 'container.namespaces';

      $root_namespaces   = new \ArrayIterator($root_namespaces);
      $annotation        = new AnnotatedClassDiscovery('/Plugin/BusinessRulesReactsOn', $root_namespaces, 'Drupal\business_rules\Annotation\BusinessRulesReactsOn');
      $eventsDefinitions = $annotation->getDefinitions();
    }
    else {
      // If we have the container, we can get the definitions using the correct
      // process.
      $container         = \Drupal::getContainer();
      $reactionEvents    = $container->get('plugin.manager.business_rules.reacts_on');
      $eventsDefinitions = $reactionEvents->getDefinitions();
    }

    foreach ($eventsDefinitions as $event) {
      $return[$event['eventName']] = [
        'process',
        $event['priority'],
      ];
    }

    return $return;

  }

  /**
   * Process the rules.
   *
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The event to be processed.
   */
  public function process(BusinessRulesEvent $event) {
    $this->processor->process($event);
  }

  /**
   * Remove the item references.
   *
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The event.
   */
  public function itemPosDelete(BusinessRulesEvent $event) {
    $this->util->removeItemReferences($event);
  }

  /**
   * Run the necessary commands on terminate event.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The terminate event.
   */
  public function onTerminate(Event $event) {
    // $key_value = \Drupal::keyValueExpirable('business_rules.debug');
    // $key_value->deleteAll();
  }

}
