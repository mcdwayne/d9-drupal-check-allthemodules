<?php

namespace Drupal\loyalist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\loyalist\Event\LoyalistNewEvent;
use Drupal\loyalist\Event\LoyalistVisitEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class LoyalistSessionController.
 *
 * @ingroup loyalist
 */
class LoyalistSessionController extends ControllerBase {

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * LoyalistSessionController constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler interface.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   */
  public function __construct(ModuleHandlerInterface $module_handler, PrivateTempStoreFactory $temp_store_factory) {
    $this->moduleHandler = $module_handler;
    $this->tempStore = $temp_store_factory->get('loyalist');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('tempstore.private')
    );
  }

  /**
   * Loyalist session initialization.
   *
   * Because the visits log is stored local to the user and accessed only via
   * Javascript, this information is controlled in the session so this function
   * can be called as an AJAX request.
   *
   * If an event is queued, it will be dispatched in loyalist_page_build().
   *
   * @param string $op
   *   The operation to run. One of:
   *    - "new": This session is for a new loyalist.
   *    - "returning": This session is for a returning loyalist.
   *    - "non": This session is for a non-loyalist.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   An empty JSON response.
   *
   * @see loyalist_menu()
   * @see loyalist_page_build()
   */
  public function initSession($op) {
    switch ($op) {
      case 'new':
        $loyalist = TRUE;
        $event = new LoyalistNewEvent();
        break;

      case 'returning':
        $loyalist = TRUE;
        $event = new LoyalistVisitEvent();
        break;

      default:
        $loyalist = FALSE;
        $event = NULL;
    }

    try {
      $this->tempStore->set('loyalist', $loyalist);
      if (!empty($event)) {
        $this->tempStore->set('event', $event);
      }
    }
    catch (TempStoreException $e) {
      watchdog_exception('loyalist', $e);
    }

    return new JsonResponse();
  }

}
