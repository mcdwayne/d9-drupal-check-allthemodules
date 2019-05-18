<?php

namespace Drupal\payex_commerce\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payex\Service\PayExApiFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the offsite payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payex",
 *   label = "PayEx",
 *   display_label = "PayEx",
 *   forms = {
 *     "add-payment-method" = "Drupal\payex_commerce\PluginForm\PayEx\PaymentMethodAddForm",
 *     "edit-payment-method" = "Drupal\payex_commerce\PluginForm\PayEx\PaymentMethodEditForm",
 *     "off-site" = "Drupal\payex_commerce\PluginForm\PayEx\OffSiteForm",
 *   },
 *   modes = {"payex": "PayEx Setting"},
 *   payment_method_types = {"payex"},
 * )
 */
class PayEx extends PaymentGatewayBase implements PayExInterface, OnsitePaymentGatewayInterface {

  /**
   * @var \Drupal\payex\Service\PayExApi|FALSE
   */
  protected $payexApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, PayExApiFactory $payExApiFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->payexApi = $payExApiFactory->get($configuration['payex_setting_id']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('payex.api_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'payex_setting_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $options = [];
    $payex_settings = $this->entityTypeManager->getStorage('payex_setting')->loadByProperties();
    foreach ($payex_settings as $payex_setting) {
      $options[$payex_setting->id()] = $payex_setting->label();
    }

    $form['payex_setting_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select configuration'),
      '#default_value' => $this->configuration['payex_setting_id'],
      '#options' => $options,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['payex_setting_id'] = $values['payex_setting_id'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    // Payment method should never be reused.
    $payment_method->setReusable(FALSE);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method){
    $payment_method->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    // We fake to use OnsitePaymentGatewayInterface in order for this to be
    // usable by commerce.
  }


}
