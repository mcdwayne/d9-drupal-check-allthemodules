<?php
namespace Drupal\commerce_admin_checkout\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

class AdminCheckoutEvent extends Event {
  
  const CHECKOUT_ASSIGN = 'commerce_admin_checkout.order.assign';

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The user account the order is being assigned to.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The checkout form.
   * 
   * @var array
   */
  protected $form;

  /**
   * The checkout form state.
   * 
   * @var \Drupal\Core\Form\FormStateInterface 
   */
  protected $form_state;

  /**
   * AdminCheckoutEvent constructor.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param \Drupal\user\UserInterface $account
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function __construct(OrderInterface $order, UserInterface $account, array &$form, FormStateInterface &$form_state) {
    $this->order = $order;
    $this->account = $account;
    $this->form = $form;
    $this->form_state = $form_state;
  }

  /**
   * @return \Drupal\commerce_order\Entity\OrderInterface
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * @return \Drupal\user\UserInterface
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * @return array
   */
  public function getForm(): array {
    return $this->form;
  }

  /**
   * @return \Drupal\Core\Form\FormStateInterface
   */
  public function getFormState() {
    return $this->form_state;
  }

}
