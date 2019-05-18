<?php

declare(strict_types = 1);

namespace Drupal\commerce_paytrail\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway\PaytrailBase;
use Drupal\commerce_paytrail\Repository\FormManager;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class FormInterfaceEvent.
 *
 * @package Drupal\commerce_paytrail\Event\PaymentReposityEvent
 */
class FormInterfaceEvent extends Event {

  /**
   * The PaytrailBase payment plugin.
   *
   * @var \Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway\PaytrailBase
   */
  protected $plugin;

  /**
   * The commerce order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The transaction repository.
   *
   * @var \Drupal\commerce_paytrail\Repository\FormManager
   */
  protected $form;

  /**
   * FormInterfaceEvent constructor.
   *
   * @param \Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway\PaytrailBase $plugin
   *   The PaytrailBase payment plugin.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_paytrail\Repository\FormManager $form
   *   The transaction repository.
   */
  public function __construct(PaytrailBase $plugin, OrderInterface $order, FormManager $form) {
    $this->plugin = $plugin;
    $this->order = $order;
    $this->form = $form;
  }

  /**
   * Set order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return $this
   */
  public function setOrder(OrderInterface $order) {
    $this->order = $order;
    return $this;
  }

  /**
   * Set transaction repository.
   *
   * @param \Drupal\commerce_paytrail\Repository\FormManager $form
   *   The transaction repository.
   *
   * @return $this
   */
  public function setFormInterface(FormManager $form) {
    $this->form = $form;
    return $this;
  }

  /**
   * Get transaction repository.
   *
   * @return \Drupal\commerce_paytrail\Repository\FormManager
   *   The transaction repository.
   */
  public function getFormInterface() {
    return $this->form;
  }

  /**
   * Get clone of commerce order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Get payment plugin.
   *
   * @return \Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway\PaytrailBase
   *   The paytrail payment plugin.
   */
  public function getPlugin() {
    return $this->plugin;
  }

}
