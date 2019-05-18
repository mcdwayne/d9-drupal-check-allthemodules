<?php

namespace Drupal\commerce_dibs\PluginForm\OffsiteRedirect;

use CommerceGuys\Intl\Currency\CurrencyRepositoryInterface;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Off-site Redirect payment gateway form.
 */
class FlexWinPaymentOffsiteForm extends BasePaymentOffsiteForm implements ContainerInjectionInterface {

  /**
   * DIBS payment window URL.
   */
  const REDIRECT_URL = 'https://payment.architrade.com/paymentweb/start.action';

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Currency repository.
   *
   * @var \Drupal\commerce_price\Repository\CurrencyRepository
   */
  protected $currencyRepository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * PaymentOffsiteForm constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \CommerceGuys\Intl\Currency\CurrencyRepositoryInterface $currencyRepository
   *   Currency repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, CurrencyRepositoryInterface $currencyRepository, LanguageManagerInterface $language_manager) {
    $this->moduleHandler = $moduleHandler;
    $this->currencyRepository = $currencyRepository;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('commerce_price.currency_repository'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    // DIBS is settings the base amount value as cents.
    $amount = $payment->getAmount()->multiply("100")->getNumber();
    $order_id = $payment->getOrderId();
    $order = $payment->getOrder();

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $plugin_configuration = $payment_gateway_plugin->getConfiguration();
    $redirect_method = 'post';
    $redirect_url = static::REDIRECT_URL;
    $dibs_merchant_id = $plugin_configuration['merchant'];
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $pay_type = $plugin_configuration['pay_type'];
    $account = $plugin_configuration['account'];
    $decorator = $plugin_configuration['decorator'];

    // We need the numeric currency code.
    $currency = $this->currencyRepository->get($payment->getAmount()
      ->getCurrencyCode());
    $currency_code = $currency->getNumericCode();

    $data = [
      'amount' => $amount,
      'currency' => $currency_code,
      'merchant' => $dibs_merchant_id,
      'lang' => $language,
      'decorator' => $decorator,
    ];

    if ($plugin_configuration['md5_key']) {
      $md5_params = [
        'merchant' => $dibs_merchant_id,
        'orderid' => $order_id,
        'currency' => $currency_code,
        'amount' => $amount,
      ];

      $md5key = md5($plugin_configuration['md5_key2'] . md5($plugin_configuration['md5_key1'] . http_build_query($md5_params)));

      $data['md5key'] = $md5key;
    }

    $data['accepturl'] = $form['#return_url'];
    $data['cancelurl'] = $form['#cancel_url'];
    $data['orderid'] = $order_id;
    if (!empty($pay_type)) {
      $data['paytype'] = $pay_type;
    }
    if ($form['#capture']) {
      $data['capturenow'] = 1;
    }

    if (!empty($account)) {
      $data['account'] = $account;
    }
    if ($plugin_configuration['mode'] == 'test') {
      $data['test'] = 1;
    }

    // Allows other modules to alter the payment data sent to DIBS.
    $this->moduleHandler->alter('commerce_dibs_payment_data', $data, $order, $payment);

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
  }

}
