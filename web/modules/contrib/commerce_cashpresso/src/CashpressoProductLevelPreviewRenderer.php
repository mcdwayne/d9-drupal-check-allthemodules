<?php

namespace Drupal\commerce_cashpresso;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\PriceCalculatorInterface;
use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Default cashpresso product level preview renderer implementation.
 */
class CashpressoProductLevelPreviewRenderer implements CashpressoProductLevelPreviewRendererInterface {

  use StringTranslationTrait;

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The number formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface
   */
  protected $numberFormatter;

  /**
   * The price calculator.
   *
   * @var \Drupal\commerce_order\PriceCalculatorInterface
   */
  protected $priceCalculator;

  /**
   * The payment gateway storage.
   *
   * @var \Drupal\commerce_payment\PaymentGatewayStorageInterface
   */
  protected $paymentGatewayStorage;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The price rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * Constructs a new CashpressoProductLevelPreviewRenderer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\commerce_price\NumberFormatterFactoryInterface $number_formatter_factory
   *   The number formatter factory.
   * @param \Drupal\commerce_order\PriceCalculatorInterface $price_calculator
   *   The price calculator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The price rounder.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentStoreInterface $current_store, AccountInterface $current_user, LanguageManagerInterface $language_manager, NumberFormatterFactoryInterface $number_formatter_factory, PriceCalculatorInterface $price_calculator, RendererInterface $renderer, RounderInterface $rounder) {
    $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');
    $this->currentStore = $current_store;
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;
    $this->numberFormatter = $number_formatter_factory->createInstance();
    $this->numberFormatter->setMaximumFractionDigits(6);
    $this->priceCalculator = $price_calculator;
    $this->paymentGatewayStorage = $entity_type_manager->getStorage('commerce_payment_gateway');
    $this->renderer = $renderer;
    $this->rounder = $rounder;
  }

  /**
   * {@inheritdoc}
   */
  public function buildCashpressoPreview(PurchasableEntityInterface $purchasable_entity, array $adjustment_types = [], $minimum_price_amount = 0, $enable_direct_checkout = TRUE) {
    $cashpresso_gateway_entity = $this->getActiveCashpressoGateway();
    if (empty($cashpresso_gateway_entity)) {
      // Early exit, when no active cashpresso gateway is configured.
      return [];
    }
    /** @var \Drupal\commerce_cashpresso\Plugin\Commerce\PaymentGateway\CashpressoGatewayInterface $cashpresso_gateway */
    $cashpresso_gateway = $cashpresso_gateway_entity->getPlugin();

    $context = new Context($this->currentUser, $this->currentStore->getStore());
    $result = $this->priceCalculator->calculate($purchasable_entity, 1, $context, $adjustment_types);
    $calculated_price = $result->getCalculatedPrice();

    if ($minimum_price_amount && $minimum_price_amount > 0) {
      $minimum_price = new Price((string) $minimum_price_amount, $calculated_price->getCurrencyCode());
      if ($calculated_price->lessThan($minimum_price)) {
        return [];
      }
    }

    $partner_info = $cashpresso_gateway->fetchPartnerInfo();
    if ($partner_info && $total_limit = $partner_info->getTotalLimit()) {
      if ($calculated_price->greaterThan($total_limit)) {
        return [];
      }
    }

    $calculated_price = $this->rounder->round($calculated_price);

    $js_settings = [
      'url' => 'https://my.cashpresso.com/ecommerce/v2/label/c2_ecom_wizard.all.min.js',
      'data' => [
        'partnerApiKey' => $cashpresso_gateway->getApiKey(),
        'interestFreeDaysMerchant' => $cashpresso_gateway->getInterestFreeDaysMerchant(),
        'mode' => $cashpresso_gateway->getMode(),
        'locale' => $this->languageManager->getCurrentLanguage()->getId(),
      ],
    ];
    if ($enable_direct_checkout && $this->currentUser->hasPermission('access checkout')) {
      $js_settings['data']['checkoutCallback'] = 'true';
      $js_settings['directCheckoutUrl'] = Url::fromRoute('commerce_cashpresso.direct_checkout',
        ['entity_type' => $purchasable_entity->getEntityTypeId(), 'entity_id' => $purchasable_entity->id()])->setAbsolute(TRUE)->toString();
    }

    return [
      '#type' => 'inline_template',
      '#template' => '<div class="c2-financing-label cashpresso-product-label" data-c2-financing-amount="{{ amount }}" data-purchasable-entity="{{ purchasable_entity }}"></div>',
      '#context' => [
        'amount' => $calculated_price->getNumber(),
        'purchasable_entity' => sprintf('%s:%s', $purchasable_entity->getEntityTypeId(), $purchasable_entity->id()),
      ],
      '#cache' => [
        'contexts' => ['session'],
      ],
      '#attached' => [
        'drupalSettings' => ['commerce_cashpresso' => $js_settings],
        'library' => ['commerce_cashpresso/product'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderCashpressoPreview(PurchasableEntityInterface $purchasable_entity, array $adjustment_types = [], $minimum_price_amount = 0, $enable_direct_checkout = TRUE) {
    $build = $this->buildCashpressoPreview($purchasable_entity, $adjustment_types, $minimum_price_amount, $enable_direct_checkout);
    return !empty($build) ? $this->renderer->render($build) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildCashpressoStaticLabelPreview(PurchasableEntityInterface $purchasable_entity, array $adjustment_types = [], $minimum_price_amount = 0, $enable_direct_checkout = TRUE) {
    $cashpresso_gateway_entity = $this->getActiveCashpressoGateway();
    if (empty($cashpresso_gateway_entity)) {
      // Early exit, when no active cashpresso gateway is configured.
      return [];
    }
    /** @var \Drupal\commerce_cashpresso\Plugin\Commerce\PaymentGateway\CashpressoGatewayInterface $cashpresso_gateway */
    $cashpresso_gateway = $cashpresso_gateway_entity->getPlugin();

    $context = new Context($this->currentUser, $this->currentStore->getStore());
    $result = $this->priceCalculator->calculate($purchasable_entity, 1, $context, $adjustment_types);
    $calculated_price = $result->getCalculatedPrice();

    if ($minimum_price_amount && $minimum_price_amount > 0) {
      $minimum_price = new Price((string) $minimum_price_amount, $calculated_price->getCurrencyCode());
      if ($calculated_price->lessThan($minimum_price)) {
        return [];
      }
    }

    $partner_info = $cashpresso_gateway->fetchPartnerInfo();
    if ($partner_info && $total_limit = $partner_info->getTotalLimit()) {
      if ($calculated_price->greaterThan($total_limit)) {
        return [];
      }
    }

    $calculated_price = $this->rounder->round($calculated_price);
    $instalment_price = $partner_info->calculateInstalmentPrice($calculated_price);

    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
    $currency = $this->currencyStorage->load($instalment_price->getCurrencyCode());
    $instalment_price_formatted = $this->numberFormatter->formatCurrency($instalment_price->getNumber(), $currency);

    $js_settings = [
      'url' => 'https://my.cashpresso.com/ecommerce/v2/label/c2_ecom_wizard_static.all.min.js',
      'data' => [
        'partnerApiKey' => $cashpresso_gateway->getApiKey(),
        'interestFreeDaysMerchant' => $cashpresso_gateway->getInterestFreeDaysMerchant(),
        'mode' => $cashpresso_gateway->getMode(),
        'locale' => $this->languageManager->getCurrentLanguage()->getId(),
      ],
    ];
    if ($enable_direct_checkout && $this->currentUser->hasPermission('access checkout')) {
      $js_settings['data']['checkoutCallback'] = 'true';
      $js_settings['directCheckoutUrl'] = Url::fromRoute('commerce_cashpresso.direct_checkout',
        ['entity_type' => $purchasable_entity->getEntityTypeId(), 'entity_id' => $purchasable_entity->id()])->setAbsolute(TRUE)->toString();
    }

    return [
      '#type' => 'inline_template',
      '#template' => '<a href="#" data-amount="{{ amount }}" class="c2-static-label cashpresso-product-label" data-purchasable-entity="{{ purchasable_entity }}">{{ text }}</a>',
      '#context' => [
        'amount' => $calculated_price->getNumber(),
        'text' => $this->t('or from @instalment_price / month', ['@instalment_price' => $instalment_price_formatted]),
        'purchasable_entity' => sprintf('%s:%s', $purchasable_entity->getEntityTypeId(), $purchasable_entity->id()),
      ],
      '#cache' => [
        'contexts' => ['session'],
      ],
      '#attached' => [
        'drupalSettings' => ['commerce_cashpresso' => $js_settings],
        'library' => ['commerce_cashpresso/product_static'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderCashpressoStaticLabelPreview(PurchasableEntityInterface $purchasable_entity, array $adjustment_types = [], $minimum_price_amount = 0, $enable_direct_checkout = TRUE) {
    $build = $this->buildCashpressoStaticLabelPreview($purchasable_entity, $adjustment_types, $minimum_price_amount, $enable_direct_checkout);
    return !empty($build) ? $this->renderer->render($build) : '';
  }

  /**
   * Returns a single active cashpresso gateway, if available.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentGatewayInterface|null
   *   The cashpresso gateway.
   */
  protected function getActiveCashpressoGateway() {
    $query = $this->paymentGatewayStorage->getQuery();
    $query->condition('status', TRUE);
    $query->condition('plugin', 'cashpresso');
    $query->range(0, 1);
    $query_result = $query->execute();
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface|null $result */
    $result = !empty($query_result) ? $this->paymentGatewayStorage->load(reset($query_result)) : NULL;
    return $result;
  }

}
