<?php

namespace Drupal\commerce_rental\Plugin\Field\FieldFormatter;

use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use CommerceGuys\Intl\Formatter\NumberFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @FieldFormatter(
 *   id = "rental_rate_view",
 *   label = @Translation("Rental Rate Table"),
 *   description = @Translation("Display rental rates in a table format."),
 *   field_types = {
 *     "commerce_rental_rate"
 *   }
 * )
 */
class RentalRateFormatter extends FormatterBase implements ContainerFactoryPluginInterface{

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The number formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface
   */
  protected $numberFormatter;

  /**
   * Constructs a new PriceDefaultFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_price\NumberFormatterFactoryInterface $number_formatter_factory
   *   The number formatter factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, NumberFormatterFactoryInterface $number_formatter_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->numberFormatter = $number_formatter_factory->createInstance();
    $this->numberFormatter->setMaximumFractionDigits(6);
    if ($this->getSetting('strip_trailing_zeroes')) {
      $this->numberFormatter->setMinimumFractionDigits(0);
    }
    if ($this->getSetting('display_currency_code')) {
      $this->numberFormatter->setCurrencyDisplay(NumberFormatterInterface::CURRENCY_DISPLAY_CODE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('commerce_price.number_formatter_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'strip_trailing_zeroes' => FALSE,
        'display_currency_code' => FALSE,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $elements['strip_trailing_zeroes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip trailing zeroes after the decimal point.'),
      '#default_value' => $this->getSetting('strip_trailing_zeroes'),
    ];
    $elements['display_currency_code'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display the currency code instead of the currency symbol.'),
      '#default_value' => $this->getSetting('display_currency_code'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('strip_trailing_zeroes')) {
      $summary[] = $this->t('Strip trailing zeroes after the decimal point.');
    }
    else {
      $summary[] = $this->t('Do not strip trailing zeroes after the decimal point.');
    }
    if ($this->getSetting('display_currency_code')) {
      $summary[] = $this->t('Display the currency code instead of the currency symbol.');
    }
    else {
      $summary[] = $this->t('Display the currency symbol.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $currency_codes = [];
    $currency_storage = $this->entityTypeManager->getStorage('commerce_currency');
    foreach ($items as $delta => $item) {
      $currency_codes[] = $item->currency_code;
    }
    $currencies = $currency_codes ? $currency_storage->loadMultiple($currency_codes) : [];
    $rental_period_storage = $this->entityTypeManager->getStorage('commerce_rental_period');

//    $values = [];
//
//    foreach ($items as $delta => $item) {
//      $rental_period = $rental_period_storage->load($item->period_id);
//      $currency = $currencies[$item->currency_code];
//      $price = $this->numberFormatter->formatCurrency($item->number, $currency);
//      $values[] = [
//        'rental_period' => $rental_period,
//        'price' => $price,
//      ];
//    }
//
//    $elements = [
//      '#theme' => 'commerce_rental_rate',
//      '#values' => $values,
//      '#label' => $this->fieldDefinition->getLabel(),
//    ];

    $elements = [];
    foreach ($items as $delta => $item) {
      $rental_period = $rental_period_storage->load($item->period_id);
      $currency = $currencies[$item->currency_code];
      $elements[$delta] = [
        '#markup' => t('@period: @price', ['@period' => $rental_period->label(), '@price' => $this->numberFormatter->formatCurrency($item->number, $currency)]),
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
            'country',
          ],
        ],
      ];
    }

    return $elements;
  }

}