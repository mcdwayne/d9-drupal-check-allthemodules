<?php

namespace Drupal\stripe\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\stripe\StripeService;

/**
 * StripeProductsForm class.
 */
class StripeProductsForm extends ConfigFormBase {

  /**
   * Drupal\stripe\StripeService definition.
   *
   * @var StripeService $stripeService
   */
  protected $stripeService;

  /**
   * {@inheritdoc}
   */
  public function __construct(StripeService $stripeService) {
    $this->stripeService = $stripeService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('stripe.stripe_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stripe_products_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    $form['stripe_create_product'] = array(
      '#type' => 'details',
      '#title' => t('Create new product'),
      '#open' => TRUE,
    );

    $form['stripe_create_product']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product name'),
      '#description' => $this->t('This will appear on Checkout, customer receipts, and invoices.'),
      '#required' => TRUE,
    ];

    $form['stripe_create_product']['unit_label '] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unit label '),
      '#description' => $this->t('This will represent a unit of this product, such as a seat or API call, on Checkout, customers\' receipts, and invoices.'),
    ];

    $form['stripe_create_product']['statement_descriptor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Statement descriptor'),
      '#description' => $this->t('This will appear on customers\' bank statements, so make sure it\'s clearly recognizable.'),
      '#required' => TRUE,
    ];

    // Header of the products table.
    $header = [
      'product_id' => t('Product ID'),
      'product_name' => t('Product name'),
    ];

    // Variable to hold the products.
    $products = [];

    foreach ($this->stripeService->getProducts() as $product) {
      $products[$product->id] = [
        'product_id' => $product->id,
        'product_name' => $product->name,
      ];
    }

    $form['stripe_products'] = array(
      '#type' => 'details',
      '#title' => t('List of products'),
      '#open' => TRUE,
    );

    $form['stripe_products']['products'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $products,
      '#empty' => t('No products found'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->stripeService->createServiceProduct(
      $form_state->getValue('name'),
      $form_state->getValue('unit_label'),
      $form_state->getValue('statement_descriptor')
    );

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // This function returns the name of the settings files we will
    // create / use.
    return [
      'stripe.settings',
    ];
  }

}
