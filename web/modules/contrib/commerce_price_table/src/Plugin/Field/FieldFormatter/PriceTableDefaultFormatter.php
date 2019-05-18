<?php

namespace Drupal\commerce_price_table\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_price\Plugin\Field\FieldFormatter\PriceDefaultFormatter;

/**
 * Plugin implementation of the 'commerce_price_table' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_multiprice_default",
 *   label = @Translation("Price chart"),
 *   field_types = {
 *     "commerce_price_table"
 *   }
 * )
 */
class PriceTableDefaultFormatter extends PriceDefaultFormatter {

  /**
   * Price table display orientation constants.
   */
  const HORIZONTAL_MODE = 0;
  const VERTICAL_MODE = 1;

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * Constructs a new PriceTableDefaultFormatter object.
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
   *   Any third party settings.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, CurrencyFormatterInterface $currency_formatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $currency_formatter);

    $this->currencyFormatter = $currency_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'table_orientation' => PriceTableDefaultFormatter::HORIZONTAL_MODE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = [
      'price_label' => [
        '#type' => 'textfield',
        '#title' => $this->t('Price label for the price table'),
        '#default_value' => $this->getSetting('price_label'),
      ],
      'quantity_label' => [
        '#type' => 'textfield',
        '#title' => $this->t('Quantity label for the price table'),
        '#default_value' => $this->getSetting('quantity_label'),
      ],
      'table_orientation' => [
        '#type' => 'radios',
        '#options' => $this->getOrientationOptionsList(),
        '#title' => $this->t('Orientation of the price table'),
        '#default_value' => $this->getSetting('table_orientation'),
      ],

    ] + parent::settingsForm($form, $form_state);

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $quantity_label = $this->getSetting('quantity_label');
    $price_label = $this->getSetting('price_label');

    $summary = [
        $this->t('Quantity label: @label', [
          '@label' => (!empty($quantity_label) ? $quantity_label : $this->t('Quantity')),
        ]),
        $this->t('Price label: @label', [
          '@label' => (!empty($price_label) ? $price_label : $this->t('Price')),
        ]),
        $this->t('Orientation: @label', [
          '@label' => $this->getOrientationLabel($this->getSetting('table_orientation')),
        ]),
    ]  + parent::settingsSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $header = [];
    $elements = [];
    $options = $this->getFormattingOptions();

    foreach ($items as $delta => $item) {
      if (isset($item->min_qty) && $item->max_qty && $item->amount) {
        $header[] = $this->getQuantityHeaders($item);
        $row[] = [
          'data' => $this->currencyFormatter->format($item->amount, $item->currency_code, $options),
        ];
      }
    }

    if ($this->getSetting('table_orientation') == PriceTableDefaultFormatter::VERTICAL_MODE) {
      $rows = [];
      $header_old = $header;
      $header = [$header_old[0], $row[0]];
      for ($index = 1; $index < count($row); $index++) {
        $rows[] = ['data' => [$header_old[$index], $row[$index]['data']]];
      }
    }
    else {
      $rows = isset($row) ?[$row] : [];
    }

    $elements[] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];

    return $elements;
  }

  /**
   * Gets the formatting options for the currency formatter.
   *
   * @return array
   *   The formatting options.
   */
  protected function getFormattingOptions() {
    $options = [
      'currency_display' => $this->getSetting('currency_display'),
    ];
    if ($this->getSetting('strip_trailing_zeroes')) {
      $options['minimum_fraction_digits'] = 0;
    }

    return $options;
  }

  /**
   * Helper method that takes care of the quantity displayed in the headers of
   * the price table.
   */
  protected function getQuantityHeaders($item) {
    // Set the quantity text to unlimited if it's -1. $item->min_qty
    $max_qty = $item->max_qty == -1 ? $this->t('Unlimited') : $item->max_qty;
    // If max and min qtys are the same, only show one.
    if ($item->min_qty == $max_qty) {
      $quantity_text = $item->min_qty ;
    }
    else {
      $quantity_text = $item->min_qty  . ' - ' . $max_qty;
    }
    return $quantity_text;
  }

  /**
   * Return list with available orientation options.
   *
   * @return array
   */
  protected function getOrientationOptionsList() {
    return [
      PriceTableDefaultFormatter::HORIZONTAL_MODE => $this->t('Horizontal'),
      PriceTableDefaultFormatter::VERTICAL_MODE => $this->t('Vertical'),
    ];
  }

  /**
   * Return orientation mode label.
   *
   * @param int $orientation_id
   *   ID of orientation mode.
   *
   * @return string
   */
  protected function getOrientationLabel($orientation_id) {
    $orientation_options = $this->getOrientationOptionsList();
    if (array_key_exists($orientation_id, $orientation_options)) {
      return $orientation_options[$orientation_id];
    }

    // If no match found, then return default orientation label.
    return $orientation_options[PriceTableDefaultFormatter::HORIZONTAL_MODE];
  }
}
