<?php

namespace Drupal\contacts_events\Plugin\Field\FieldWidget;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Utility\Html;
use Drupal\contacts_events\Entity\SingleUsePurchasableEntityInterface;
use Drupal\contacts_events\PriceCalculator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'mapped_price_data' widget.
 *
 * @FieldWidget(
 *   id = "mapped_price_data",
 *   label = @Translation("Mapped price data"),
 *   field_types = {
 *     "mapped_price_data"
 *   }
 * )
 */
class MappedPriceDataWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The events price calculator service.
   *
   * @var \Drupal\contacts_events\PriceCalculator
   */
  protected $priceCalculator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * Constructs a MappedPriceDataWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\contacts_events\PriceCalculator $price_calculator
   *   The price calculator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, PriceCalculator $price_calculator, EntityTypeManagerInterface $entity_type_manager, CurrencyFormatterInterface $currency_formatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->priceCalculator = $price_calculator;
    $this->entityTypeManager = $entity_type_manager;
    $this->currencyFormatter = $currency_formatter;
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
      $configuration['third_party_settings'],
      $container->get('contacts_events.price_calculator'),
      $container->get('entity_type.manager'),
      $container->get('commerce_price.currency_formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'price_shown' => 'final',
      'override_indicator' => FALSE,
      'strip_trailing_zeroes' => FALSE,
      'currency_display' => 'symbol',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $form_state->getFormObject()->getEntity()->getTargetEntityTypeId();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $element['price_shown'] = [
      '#type' => 'value',
      '#value' => 'final',
    ];

    $element['override_indicator'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Indicate the that price was overridden'),
      '#default_value' => $this->getSetting('override_indicator'),
    ];

    if ($entity_type->entityClassImplements(SingleUsePurchasableEntityInterface::class)) {
      $element['price_shown'] = [
        '#type' => 'select',
        '#title' => $this->t('Price shown'),
        '#default_value' => $this->getSetting('price_shown'),
        '#options' => [
          'calculated' => $this->t('Only show the calculated price'),
          'both' => $this->t('Show the calculated and final price (if different)'),
          'final' => $this->t('Only show the final price'),
        ],
        '#description' => $this->t('The calculated price may be adjusted, e.g. an override.'),
        '#id' => Html::getUniqueId('mapped-price-data-widget-price-shown'),
      ];

      $element['override_indicator']['#states'] = [
        'invisible' => [
          ['#' . $element['price_shown']['#id'] => ['value' => 'both']],
        ],
      ];
    }

    $elements['strip_trailing_zeroes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip trailing zeroes after the decimal point.'),
      '#default_value' => $this->getSetting('strip_trailing_zeroes'),
    ];
    $elements['currency_display'] = [
      '#type' => 'radios',
      '#title' => $this->t('Currency display'),
      '#options' => [
        'symbol' => $this->t('Symbol (e.g. "$")'),
        'code' => $this->t('Currency code (e.g. "USD")'),
        'none' => $this->t('None'),
      ],
      '#default_value' => $this->getSetting('currency_display'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    switch ($this->getSetting('price_shown')) {
      case 'calculated':
        $summary['price_shown'] = $this->t('Show calculated price');
        break;

      case 'final':
        $summary['price_shown'] = $this->t('Show final price');
        break;

      case 'both':
        $summary['price_shown'] = $this->t('Show both prices');
        break;
    }

    if ($this->getSetting('price_shown') != 'both' && $this->getSetting('override_indicator')) {
      $summary['override_indicator'] = $this->t('Indicate overridden prices');
    }

    if ($this->getSetting('strip_trailing_zeroes')) {
      $summary['strip_trailing_zeroes'] = $this->t('Strip trailing zeroes after the decimal point.');
    }
    else {
      $summary['strip_trailing_zeroes'] = $this->t('Do not strip trailing zeroes after the decimal point.');
    }

    $currency_display = $this->getSetting('currency_display');
    $currency_display_options = [
      'symbol' => $this->t('Symbol (e.g. "$")'),
      'code' => $this->t('Currency code (e.g. "USD")'),
      'none' => $this->t('None'),
    ];
    $summary['currency_display'] = $this->t('Currency display: @currency_display.', [
      '@currency_display' => $currency_display_options[$currency_display],
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Get hold of the order item.
    $order_item = $this->getOrderItem($items);
    if (!$order_item) {
      return $element;
    }

    // Add a processor to fill out values which depend on submitted values.
    $element['#items'] = $items;
    $element['#process'][] = [$this, 'processClassOptions'];

    $element['booking_window'] = [
      '#type' => 'value',
      '#default_value' => isset($items[$delta]->booking_window) ? $items[$delta]->booking_window : NULL,
    ];

    $element['booking_window_overridden'] = [
      '#type' => 'value',
      '#default_value' => isset($items[$delta]->booking_window_overridden) ? $items[$delta]->booking_window_overridden : FALSE,
    ];

    /* @var \Drupal\Core\Render\ElementInfoManagerInterface $element_info */
    $element['class'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Class'),
      '#default_value' => isset($items[$delta]->class) ? $items[$delta]->class : NULL,
      '#options' => [],
    ];

    $element['class_overridden'] = [
      '#type' => 'value',
      '#default_value' => isset($items[$delta]->class_overridden) ? $items[$delta]->class_overridden : FALSE,
    ];

    return $element;
  }

  /**
   * Process callback to build the class options outside of the cache.
   *
   * @param array $element
   *   The class element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form.
   *
   * @return array
   *   The class element with the options set.
   */
  public function processClassOptions(array $element, FormStateInterface $form_state, array $complete_form) {
    $order_item = $this->getOrderItem($element['#items']);

    // Add our class options.
    foreach ($this->priceCalculator->findClasses($order_item) as $class) {
      $element['class']['#options'][$class->id()] = $class->label();
    }

    // Calculate the price.
    $this->priceCalculator->calculatePrice($order_item);

    // See which prices we should show. If the price is not overridden, we can
    // simply show the final price.
    $show_indicator = $this->getSetting('override_indicator');
    if (!($price_overridden = $order_item->isUnitPriceOverridden())) {
      $price_shown = 'final';
      $show_indicator = FALSE;
    }
    else {
      $price_shown = $this->getSetting('price_shown');
    }

    $options = [
      'currency_display' => $this->getSetting('currency_display'),
    ];
    if ($this->getSetting('strip_trailing_zeroes')) {
      $options['minimum_fraction_digits'] = 0;
    }

    // We want the calculated price if we are showing calculated or both.
    if (in_array($price_shown, ['calculated', 'both'])) {
      /* @var SingleUsePurchasableEntityInterface $entity */
      $entity = $element['#items']->getEntity();
      $price = $entity->getCalculatedPrice();
      if ($price_shown == 'both') {
        $element['price_calculated'] = $this->buildPriceElement($this->t('Calculated price'), $price, $options);
      }
      else {
        $element['price_calculated'] = $this->buildPriceElement($this->t('Price'), $price, $options, $show_indicator);
      }
    }

    // We want the final price if we are showing final or both.
    if (in_array($price_shown, ['final', 'both'])) {
      $price = $order_item->getUnitPrice();
      if ($price_shown == 'both') {
        $element['price_final'] = $this->buildPriceElement($this->t('Final price'), $price, $options);
      }
      else {
        $element['price_final'] = $this->buildPriceElement($this->t('Price'), $price, $options, $show_indicator);
      }
    }

    return $element;
  }

  /**
   * Build the render array for a formatted price.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $title
   *   The title for the element.
   * @param \Drupal\commerce_price\Price $price
   *   The price to show.
   * @param array $options
   *   An array of price formatting options.
   * @param bool $show_override_indicator
   *   Whether to show the override indicator.
   *
   * @return array
   *   The price element render array.
   */
  protected function buildPriceElement($title, Price $price, array $options, $show_override_indicator = FALSE) {
    $element = [
      '#type' => 'item',
      '#title' => $title,
      '#markup' => $this->currencyFormatter
        ->format($price->getNumber(), $price->getCurrencyCode(), $options),
    ];
    if ($show_override_indicator) {
      $element['#markup'] .= '*';
    }

    return $element;
  }

  /**
   * Get the order item from the field items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items we're working with.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface|null
   *   The order item, or NULL if we can't find it.
   */
  protected function getOrderItem(FieldItemListInterface $items) {
    $entity = $items->getEntity();

    // See if we already have it.
    if ($entity instanceof OrderItemInterface) {
      return $entity;
    }

    // Otherwise see if we can get it.
    if ($entity instanceof SingleUsePurchasableEntityInterface) {
      $order_item = $entity->getOrderItem();
      // Ensure the order item entity is up to date.
      $order_item->set('purchased_entity', $entity);
      return $order_item;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Only applicable on order items and purchasable entities.
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    if ($entity_type_id == 'commerce_order_item') {
      return TRUE;
    }

    $entity_definition = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    return $entity_definition->entityClassImplements(PurchasableEntityInterface::class);
  }

}
