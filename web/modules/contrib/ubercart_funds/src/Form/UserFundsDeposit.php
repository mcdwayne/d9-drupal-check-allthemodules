<?php

namespace Drupal\ubercart_funds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\node\NodeStorageInterface;
use Drupal\uc_cart\CartManagerInterface;

/**
 * Form to deposit money on user account.
 */
class UserFundsDeposit extends ConfigFormBase {

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\node\NodeStorageInterface
   * @var \Drupal\Core\Database\Connection $connection
   * @var \Drupal\uc_cart\CartManagerInterface $cartManager
   */
  protected $nodeStorage;
  protected $connection;
  protected $cartManager;

  /**
   * Class constructor.
   */
  public function __construct(NodeStorageInterface $node_storage, Connection $connection, CartManagerInterface $cart_manager) {
    $this->nodeStorage = $node_storage;
    $this->connection = $connection;
    $this->cartManager = $cart_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('database'),
      $container->get('uc_cart.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_funds_deposit';
  }

  /**
   * {@inheritdoc}
   *
   * Https://www.drupal.org/docs/8/api/form-api/configformbase-with-simple-configuration-api.
   */
  protected function getEditableConfigNames() {
    return [
      'uc_funds.deposit',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $currency = $this->config('uc_store.settings')->get('currency.code');

    $form['amount'] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Deposit Amount (@currency)',
        ['@currency' => $currency]
      ),
      '#description' => $this->t('Please enter the amount you wish to deposit in @currency',
        ['@currency' => $currency]
      ),
      '#default_value' => 0,
      '#step' => 0.01,
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Create product or load product.
    $deposit_node = \Drupal::service('ubercart_funds.product_manager')->createProduct('deposit', $form_state->getValue('amount'), $this->config('uc_store.settings')->get('currency.code'));

    // Add the deposit product in the cart.
    $cart = $this->cartManager->get();
    $cart->emptyCart();
    $cart->addItem($deposit_node->id(), 1, NULL, TRUE);
    // Redirect to checkout.
    $form_state->setRedirect('uc_cart.checkout');
  }

}
