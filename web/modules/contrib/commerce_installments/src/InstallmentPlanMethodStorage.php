<?php

namespace Drupal\commerce_installments;

use Drupal\commerce_installments\Event\FilterInstallmentPlanMethodsEvent;
use Drupal\commerce_installments\Event\InstallmentPlanMethodsEvents;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the storage handler class for Installment Plan Method entities.
 *
 * This extends the base storage class, adding required special handling for
 * Installment Plan entities.
 *
 * @ingroup commerce_installments
 */
class InstallmentPlanMethodStorage extends ConfigEntityStorage implements InstallmentPlanMethodStorageInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a PaymentGatewayStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadEligible(OrderInterface $order = NULL) {
    /** @var \Drupal\commerce_installments\Entity\InstallmentPlanMethodInterface[] $methods */
    $methods = $this->loadByProperties(['status' => TRUE]);
    // Allow the list of payment gateways to be filtered via code.
    $event = new FilterInstallmentPlanMethodsEvent($methods, $order);
    $this->eventDispatcher->dispatch(InstallmentPlanMethodsEvents::FILTER_PLAN_METHODS, $event);
    $methods = $event->getMethods();


    // Evaluate conditions for the remaining ones.
    if ($order) {
      foreach ($methods as $method_id => $method) {
        if (!$method->applies($order)) {
          unset($methods[$method_id]);
        }
      }
    }
    uasort($methods, [$this->entityType->getClass(), 'sort']);

    return $methods;
  }

}
