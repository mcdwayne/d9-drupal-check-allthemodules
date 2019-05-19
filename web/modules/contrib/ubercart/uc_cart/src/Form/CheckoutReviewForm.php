<?php

namespace Drupal\uc_cart\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Gives customers the option to finish checkout or revise their information.
 */
class CheckoutReviewForm extends FormBase {

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Form constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('session')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_checkout_review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $order = NULL) {
    if (!$form_state->has('uc_order')) {
      $form_state->set('uc_order', $order);
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#validate' => ['::skipValidation'],
      '#submit' => [[$this, 'back']],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit order'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $order = $form_state->get('uc_order');
    $this->session->remove('uc_checkout_review_' . $order->id());
    $this->session->set('uc_checkout_complete_' . $order->id(), TRUE);
    $form_state->setRedirect('uc_cart.checkout_complete');
  }

  /**
   * Ensures no validation is performed for the back button.
   */
  public function skipValidation(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Returns the customer to the checkout page to edit their information.
   */
  public function back(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('uc_cart.checkout');
  }

}
