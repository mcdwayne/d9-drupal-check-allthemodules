<?php

namespace Drupal\commerce_purchase_on_account\Form;

use Drupal\commerce_payment\PaymentGatewayManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure on which payment gateway the "purchase on account" payment method
 * works.
 */
class CommercePurchaseOnAccountSettingsForm extends ConfigFormBase {

  /**
   * The payment gateway plugin manager.
   *
   * @var \Drupal\commerce_payment\PaymentGatewayManager
   */
  protected $pluginManager;

  /**
   * Constructs a new PaymentGatewayForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\commerce_payment\PaymentGatewayManager $plugin_manager
   *   The payment gateway plugin manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    PaymentGatewayManager $plugin_manager
  ) {
    parent::__construct($config_factory);
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.commerce_payment_gateway')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_purchase_on_account_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_purchase_on_account.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_purchase_on_account.settings');

    $options = [];
    foreach ($this->pluginManager->getDefinitions() as $definition) {
      if ($definition['id'] != 'purchase_on_account' && !empty($definition['id'])) {
        $options[$definition['id']] = $definition['label'];
      }
    }

    $form['payment_gateways'] = array(
      '#type' => 'checkboxes',
      '#description' => $this->t('Inject the "Purchase on account" payment method to the following gateways.'),
      '#title' => $this->t('Payment gateways'),
      '#options' => $options,
      '#default_value' => $config->get('payment_gateways'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('commerce_purchase_on_account.settings')
      ->set('payment_gateways', $form_state->getValue('payment_gateways'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
