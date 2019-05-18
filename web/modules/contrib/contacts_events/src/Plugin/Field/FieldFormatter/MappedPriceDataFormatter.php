<?php

namespace Drupal\contacts_events\Plugin\Field\FieldFormatter;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\contacts_events\PriceCalculator;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'mapped_price_data' formatter.
 *
 * @FieldFormatter(
 *   id = "mapped_price_data",
 *   label = @Translation("Mapped price data"),
 *   field_types = {
 *     "mapped_price_data"
 *   }
 * )
 */
class MappedPriceDataFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The events price calculator service.
   *
   * @var \Drupal\contacts_events\PriceCalculator
   */
  protected $priceCalculator;

  /**
   * Constructs a MappedPriceDataFormatter object.
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
   * @param \Drupal\contacts_events\PriceCalculator $price_calculator
   *   The price calculator service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, PriceCalculator $price_calculator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->priceCalculator = $price_calculator;
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
      $container->get('contacts_events.price_calculator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $order_item = $this->getOrderItem($items);
    $price_items = $this->priceCalculator->findPriceMap($order_item);
    $windows = $price_items->getBookingWindows();
    $classes = $price_items->getClasses();

    foreach ($items as $delta => $item) {
      // See if we can show the booking window.
      if ($windows && $item->booking_window) {
        foreach ($windows as $window) {
          if ($window->id == $item->booking_window) {
            $elements[$delta]['booking_window'] = [
              '#type' => 'item',
              '#title' => $this->t('Booking window'),
              '#markup' => $window->label,
            ];

            if ($item->booking_window_overridden) {
              $elements[$delta]['booking_window']['#description'] = $this->t('Overridden');
            }

            break;
          }
        }
      }

      // See if we can show the class.
      if ($item->class) {
        foreach ($classes as $class) {
          if ($class->id() == $item->class) {
            $elements[$delta]['class'] = [
              '#type' => 'item',
              '#title' => $this->t('Class'),
              '#markup' => $class->label(),
            ];

            if ($item->class_overridden) {
              $elements[$delta]['class']['#description'] = $this->t('Overridden');
            }

            break;
          }
        }
      }
    }

    return $elements;
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
    if (method_exists($entity, 'getOrderItem')) {
      /* @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $entity->getOrderItem();
      return $order_item;
    }

    return NULL;
  }

}
