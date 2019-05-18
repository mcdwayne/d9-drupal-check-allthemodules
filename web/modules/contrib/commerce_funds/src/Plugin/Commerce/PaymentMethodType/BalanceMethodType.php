<?php

namespace Drupal\commerce_funds\Plugin\Commerce\PaymentMethodType;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_store\Resolver\DefaultStoreResolver;

/**
 * Provides the Balance payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "funds_wallet",
 *   label = @Translation("Funds balance"),
 * )
 */
class BalanceMethodType extends PaymentMethodTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The default store resolver.
   *
   * @var \Drupal\commerce_store\Resolver\DefaultStoreResolver
   */
  protected $defaultStoreResolver;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DefaultStoreResolver $default_store_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->defaultStoreResolver = $default_store_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_store.default_store_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $default_currency = $this->defaultStoreResolver->resolve()->getDefaultCurrencyCode();
    $currency = $payment_method->get('currency')->getValue() ? $payment_method->get('currency')->getValue()[0]['target_id'] : $default_currency;

    $args = [
      '@currency' => $currency,
    ];

    return $this->t('Wallet (@currency)', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['balance_id'] = BundleFieldDefinition::create('integer')
      ->setLabel(t('User balance'))
      ->setDescription(t('The balance id of the user.'))
      ->setRequired(TRUE);

    $fields['currency'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Currency'))
      ->setDescription(t('The currency of the transaction.'))
      ->setSetting('target_type', 'commerce_currency')
      ->setRequired(TRUE);

    return $fields;
  }

}
