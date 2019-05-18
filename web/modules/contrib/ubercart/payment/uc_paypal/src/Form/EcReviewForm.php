<?php

namespace Drupal\uc_paypal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\Plugin\PaymentMethodManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Returns the form for the custom Review Payment screen for Express Checkout.
 */
class EcReviewForm extends FormBase {

  /**
   * The order that is being reviewed.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order;

  /**
   * The payment method manager.
   *
   * @var \Drupal\uc_payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Form constructor.
   *
   * @param \Drupal\uc_payment\Plugin\PaymentMethodManager $payment_method_manager
   *   The payment method plugin manager.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(PaymentMethodManager $payment_method_manager, SessionInterface $session) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_payment.method'),
      $container->get('session')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_paypal_ec_review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $this->order = $order;
    $form = $this->paymentMethodManager
      ->createFromOrder($this->order)
      ->getExpressReviewForm($form, $form_state, $this->order);

    if (empty($form)) {
      $this->session->set('uc_checkout_review_' . $this->order->id(), TRUE);
      return $this->redirect('uc_cart.checkout_review');
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue checkout'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->paymentMethodManager
      ->createFromOrder($this->order)
      ->submitExpressReviewForm($form, $form_state, $this->order);

    $this->session->set('uc_checkout_review_' . $this->order->id(), TRUE);
    $form_state->setRedirect('uc_cart.checkout_review');
  }

}
