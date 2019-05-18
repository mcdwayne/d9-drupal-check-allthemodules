<?php

namespace Drupal\commerce_opp;

/**
 * Default brand repository implementation.
 */
class BrandRepository implements BrandRepositoryInterface {

  /**
   * The brand definitions.
   *
   * Source: https://docs.payon.com/tutorials/integration-guide (2018-03-18)
   *
   * @var array
   */
  protected $brandDefinitions = [
    // (Credit) card account brands.
    'WORLD' => [
      'id' => 'WORLD',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'WORLD',
      'sync' => TRUE,
    ],
    'VPAY' => [
      'id' => 'VPAY',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'VPAY',
      'sync' => TRUE,
    ],
    'VISAELECTRON' => [
      'id' => 'VISAELECTRON',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'VISAELECTRON',
      'sync' => TRUE,
    ],
    'VISADEBIT' => [
      'id' => 'VISADEBIT',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'VISADEBIT',
      'sync' => TRUE,
    ],
    'VISA' => [
      'id' => 'VISA',
      'commerce_id' => 'visa',
      'type' => Brand::TYPE_CARD,
      'label' => 'Visa',
      'sync' => TRUE,
    ],
    'TARJETASHOPPING' => [
      'id' => 'TARJETASHOPPING',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'TARJETASHOPPING',
      'sync' => TRUE,
    ],
    'SERVIRED' => [
      'id' => 'SERVIRED',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'SERVIRED',
      'sync' => TRUE,
    ],
    'NATIVA' => [
      'id' => 'NATIVA',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'NATIVA',
      'sync' => TRUE,
    ],
    'NARANJA' => [
      'id' => 'NARANJA',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'NARANJA',
      'sync' => TRUE,
    ],
    'MERCADOLIVRE' => [
      'id' => 'MERCADOLIVRE',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'MERCADOLIVRE',
      'sync' => TRUE,
    ],
    'MASTERDEBIT' => [
      'id' => 'MASTERDEBIT',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'MASTERDEBIT',
      'sync' => TRUE,
    ],
    'MASTER' => [
      'id' => 'MASTER',
      'commerce_id' => 'mastercard',
      'type' => Brand::TYPE_CARD,
      'label' => 'MasterCard',
      'sync' => TRUE,
    ],
    'MAESTRO' => [
      'id' => 'MAESTRO',
      'commerce_id' => 'maestro',
      'type' => Brand::TYPE_CARD,
      'label' => 'Maestro',
      'sync' => TRUE,
    ],
    'JCB' => [
      'id' => 'JCB',
      'commerce_id' => 'jcb',
      'type' => Brand::TYPE_CARD,
      'label' => 'JCB',
      'sync' => TRUE,
    ],
    'HIPERCARD' => [
      'id' => 'HIPERCARD',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'HIPERCARD',
      'sync' => TRUE,
    ],
    'ELO' => [
      'id' => 'ELO',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'ELO',
      'sync' => TRUE,
    ],
    'DISCOVER' => [
      'id' => 'DISCOVER',
      'commerce_id' => 'discover',
      'type' => Brand::TYPE_CARD,
      'label' => 'Discover Card',
      'sync' => TRUE,
    ],
    'DINERS' => [
      'id' => 'DINERS',
      'commerce_id' => 'dinersclub',
      'type' => Brand::TYPE_CARD,
      'label' => 'Diners Club',
      'sync' => TRUE,
    ],
    'DANKORT' => [
      'id' => 'DANKORT',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'DANKORT',
      'sync' => TRUE,
    ],
    'CENCOSUD' => [
      'id' => 'CENCOSUD',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'CENCOSUD',
      'sync' => TRUE,
    ],
    'CARTEBLEUE' => [
      'id' => 'CARTEBLEUE',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'CARTEBLEUE',
      'sync' => TRUE,
    ],
    'CARTEBANCAIRE' => [
      'id' => 'CARTEBANCAIRE',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'CARTEBANCAIRE',
      'sync' => TRUE,
    ],
    'CABAL' => [
      'id' => 'CABAL',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'CABAL',
      'sync' => TRUE,
    ],
    'BCMC' => [
      'id' => 'BCMC',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'BCMC',
      'sync' => FALSE,
    ],
    'ARGENCARD' => [
      'id' => 'ARGENCARD',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'ARGENCARD',
      'sync' => TRUE,
    ],
    'APPLEPAY' => [
      'id' => 'APPLEPAY',
      'commerce_id' => '',
      'type' => Brand::TYPE_CARD,
      'label' => 'APPLEPAY',
      'sync' => TRUE,
    ],
    'AMEX' => [
      'id' => 'AMEX',
      'commerce_id' => 'amex',
      'type' => Brand::TYPE_CARD,
      'label' => 'American Express',
      'sync' => TRUE,
    ],
    // Virtual account brands.
    'YANDEX' => [
      'id' => 'YANDEX',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'YANDEX',
      'sync' => FALSE,
    ],
    'TRUSTLY' => [
      'id' => 'TRUSTLY',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'TRUSTLY',
      'sync' => FALSE,
    ],
    'TENPAY' => [
      'id' => 'TENPAY',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'TENPAY',
      'sync' => FALSE,
    ],
    'SIBS_MULTIBANCO' => [
      'id' => 'SIBS_MULTIBANCO',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'SIBS_MULTIBANCO',
      'sync' => TRUE,
    ],
    'SHETAB' => [
      'id' => 'SHETAB',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'SHETAB',
      'sync' => FALSE,
    ],
    'RATENKAUF' => [
      'id' => 'RATENKAUF',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'RATENKAUF',
      'sync' => FALSE,
    ],
    'QIWI' => [
      'id' => 'QIWI',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'QIWI',
      'sync' => FALSE,
    ],
    'PRZELEWY' => [
      'id' => 'PRZELEWY',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'PRZELEWY',
      'sync' => FALSE,
    ],
    'PF_KARTE_DIRECT' => [
      'id' => 'PF_KARTE_DIRECT',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'PF_KARTE_DIRECT',
      'sync' => FALSE,
    ],
    'PAYTRAIL' => [
      'id' => 'PAYTRAIL',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'PAYTRAIL',
      'sync' => FALSE,
    ],
    'PAYSAFECARD' => [
      'id' => 'PAYSAFECARD',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'paysafecard',
      'sync' => FALSE,
    ],
    'PAYPAL' => [
      'id' => 'PAYPAL',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'PayPal',
      'sync' => FALSE,
    ],
    'PAYOLUTION_INVOICE' => [
      'id' => 'PAYOLUTION_INVOICE',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'PAYOLUTION_INVOICE',
      'sync' => FALSE,
    ],
    'PAYOLUTION_INS' => [
      'id' => 'PAYOLUTION_INS',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'PAYOLUTION_INS',
      'sync' => FALSE,
    ],
    'PAYOLUTION_ELV' => [
      'id' => 'PAYOLUTION_ELV',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'PAYOLUTION_ELV',
      'sync' => FALSE,
    ],
    'PAYDIREKT' => [
      'id' => 'PAYDIREKT',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'PAYDIREKT',
      'sync' => FALSE,
    ],
    'PAYBOX' => [
      'id' => 'PAYBOX',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'PAYBOX',
      'sync' => FALSE,
    ],
    'ONECARD' => [
      'id' => 'ONECARD',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'ONECARD',
      'sync' => FALSE,
    ],
    'MONEYSAFE' => [
      'id' => 'MONEYSAFE',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'MONEYSAFE',
      'sync' => FALSE,
    ],
    'MONEYBOOKERS' => [
      'id' => 'MONEYBOOKERS',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'MONEYBOOKERS',
      'sync' => FALSE,
    ],
    'MBWAY' => [
      'id' => 'MBWAY',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'MBWAY',
      'sync' => FALSE,
    ],
    'MASTERPASS' => [
      'id' => 'MASTERPASS',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'MASTERPASS',
      'sync' => FALSE,
    ],
    'KLARNA_PAYMENTS_SLICEIT' => [
      'id' => 'KLARNA_PAYMENTS_SLICEIT',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'KLARNA_PAYMENTS_SLICEIT',
      'sync' => FALSE,
    ],
    'KLARNA_PAYMENTS_PAYLATER' => [
      'id' => 'KLARNA_PAYMENTS_PAYLATER',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'KLARNA_PAYMENTS_PAYLATER',
      'sync' => FALSE,
    ],
    'IKANOOI_SE' => [
      'id' => 'IKANOOI_SE',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'IKANOOI_SE',
      'sync' => FALSE,
    ],
    'DAOPAY' => [
      'id' => 'DAOPAY',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'DAOPAY',
      'sync' => FALSE,
    ],
    'CHINAUNIONPAY' => [
      'id' => 'CHINAUNIONPAY',
      'commerce_id' => 'unionpay',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'UnionPay',
      'sync' => FALSE,
    ],
    'CASHU' => [
      'id' => 'CASHU',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'CASHU',
      'sync' => FALSE,
    ],
    'ASTROPAY_STREAMLINE_OT' => [
      'id' => 'ASTROPAY_STREAMLINE_OT',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'ASTROPAY_STREAMLINE_OT',
      'sync' => FALSE,
    ],
    'ASTROPAY_STREAMLINE_CASH' => [
      'id' => 'ASTROPAY_STREAMLINE_CASH',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'ASTROPAY_STREAMLINE_CASH',
      'sync' => FALSE,
    ],
    'ALIPAY' => [
      'id' => 'ALIPAY',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'ALIPAY',
      'sync' => FALSE,
    ],
    'AFTERPAY' => [
      'id' => 'AFTERPAY',
      'commerce_id' => '',
      'type' => Brand::TYPE_VIRTUAL,
      'label' => 'AFTERPAY',
      'sync' => TRUE,
    ],
    // Bank account brands.
    'TRUSTPAY_VA' => [
      'id' => 'TRUSTPAY_VA',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'TRUSTPAY_VA',
      'sync' => FALSE,
    ],
    'SOFORTUEBERWEISUNG' => [
      'id' => 'SOFORTUEBERWEISUNG',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'SOFORT Überweisung',
      'sync' => FALSE,
    ],
    'SADAD' => [
      'id' => 'SADAD',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'SADAD',
      'sync' => FALSE,
    ],
    'PREPAYMENT' => [
      'id' => 'PREPAYMENT',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'PREPAYMENT',
      'sync' => FALSE,
    ],
    'POLI' => [
      'id' => 'POLI',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'POLI',
      'sync' => FALSE,
    ],
    'OXXO' => [
      'id' => 'OXXO',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'OXXO',
      'sync' => FALSE,
    ],
    'INTERAC_ONLINE' => [
      'id' => 'INTERAC_ONLINE',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'INTERAC_ONLINE',
      'sync' => FALSE,
    ],
    'IDEAL' => [
      'id' => 'IDEAL',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'iDEAL',
      'sync' => FALSE,
    ],
    'GIROPAY' => [
      'id' => 'GIROPAY',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'GIROPAY',
      'sync' => FALSE,
    ],
    'EPS' => [
      'id' => 'EPS',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'eps',
      'sync' => FALSE,
    ],
    'ENTERCASH' => [
      'id' => 'ENTERCASH',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'ENTERCASH',
      'sync' => FALSE,
    ],
    'DIRECTDEBIT_SEPA' => [
      'id' => 'DIRECTDEBIT_SEPA',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'DIRECTDEBIT_SEPA',
      'sync' => TRUE,
    ],
    'BOLETO' => [
      'id' => 'BOLETO',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'BOLETO',
      'sync' => TRUE,
    ],
    'BITCOIN' => [
      'id' => 'BITCOIN',
      'commerce_id' => '',
      'type' => Brand::TYPE_BANK,
      'label' => 'Bitcoin',
      'sync' => FALSE,
    ],
  ];

  /**
   * The instantiated brands.
   *
   * @var \Drupal\commerce_opp\Brand[]
   */
  protected $brands = [];

  /**
   * Constructs a new BrandRepository object.
   */
  public function __construct() {
    foreach ($this->brandDefinitions as $id => $definition) {
      $this->brands[$id] = new Brand($definition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBrand($id) {
    if (!isset($this->brands[$id])) {
      throw new \InvalidArgumentException(sprintf('Invalid brand "%s"', $id));
    }
    return $this->brands[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function getBrands() {
    return $this->brands;
  }

  /**
   * {@inheritdoc}
   */
  public function getBrandLabels() {
    $brands = $this->getBrands();
    $brand_labels = array_map(function ($brand) {
      /** @var \Drupal\commerce_opp\Brand $brand */
      return $brand->getLabel();
    }, $brands);
    return $brand_labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardAccountBrands() {
    $brands = $this->getBrands();
    $filtered_brands = array_filter($brands, function ($brand) {
      /** @var \Drupal\commerce_opp\Brand $brand */
      return $brand->getType() == Brand::TYPE_CARD;
    });
    return $filtered_brands;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardAccountBrandLabels() {
    $brands = $this->getBrands();
    $brand_labels = array_map(function ($brand) {
      /** @var \Drupal\commerce_opp\Brand $brand */
      return $brand->getLabel();
    }, $brands);
    return $brand_labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getBankAccountBrands() {
    $brands = $this->getBrands();
    $filtered_brands = array_filter($brands, function ($brand) {
      /** @var \Drupal\commerce_opp\Brand $brand */
      return $brand->getType() == Brand::TYPE_BANK;
    });
    return $filtered_brands;
  }

  /**
   * {@inheritdoc}
   */
  public function getBankAccountBrandLabels() {
    $brands = $this->getBankAccountBrands();
    $brand_labels = array_map(function ($brand) {
      /** @var \Drupal\commerce_opp\Brand $brand */
      return $brand->getLabel();
    }, $brands);
    return $brand_labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getVirtualAccountBrands() {
    $brands = $this->getBrands();
    $filtered_brands = array_filter($brands, function ($brand) {
      /** @var \Drupal\commerce_opp\Brand $brand */
      return $brand->getType() == Brand::TYPE_VIRTUAL;
    });
    return $filtered_brands;
  }

  /**
   * {@inheritdoc}
   */
  public function getVirtualAccountBrandLabels() {
    $brands = $this->getVirtualAccountBrands();
    $brand_labels = array_map(function ($brand) {
      /** @var \Drupal\commerce_opp\Brand $brand */
      return $brand->getLabel();
    }, $brands);
    return $brand_labels;
  }

}
