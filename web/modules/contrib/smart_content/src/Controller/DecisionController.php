<?php

namespace Drupal\smart_content\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\smart_content\Entity\SmartVariationSetInterface;
use Drupal\smart_content\Event\DecisionEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\smart_content\Condition\ConditionManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DecisionController.
 *
 * @package Drupal\smart_content\Controller
 */
class DecisionController extends ControllerBase {

  /**
   * Drupal\smart_content\Condition\ConditionManager definition.
   *
   * @var \Drupal\smart_content\Condition\ConditionManager
   */
  protected $pluginManagerSmartContentCondition;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConditionManager $plugin_manager_smart_content_condition,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->pluginManagerSmartContentCondition = $plugin_manager_smart_content_condition;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.smart_content.condition'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Client side processing.
   * Returns the winning variation from a client side processed decision.
   *
   * @param \Drupal\smart_content\Entity\SmartVariationSetInterface $entity
   *
   * @param $variation_id
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function reaction(SmartVariationSetInterface $entity, $variation_id) {
    $response = new AjaxResponse();
    $context = \Drupal::request()->query->all();
    if($entity->validateReactionRequest($variation_id, $context)) {
      $response = $entity->getVariationResponse($variation_id, $context);
    }
    // Dispatch an event with the winning variation so other modules can attach
    // data to the response.
    $winner = $entity->getVariation($variation_id);
    $event = new DecisionEvent($response, $winner);
    $this->eventDispatcher->dispatch(DecisionEvent::EVENT_NAME, $event);
    return $response;
  }

}
