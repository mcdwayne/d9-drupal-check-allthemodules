<?php

namespace Drupal\contacts_events\Plugin\Field\FieldWidget;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'mapped_price_override_data' widget.
 *
 * @FieldWidget(
 *   id = "mapped_price_override_data",
 *   label = @Translation("Mapped price override data"),
 *   field_types = {
 *     "mapped_price_data"
 *   }
 * )
 */
class MappedPriceOverrideDataWidget extends MappedPriceDataWidget implements ContainerFactoryPluginInterface {

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

    // @todo: Allow booking window to be overridden through the UI.
    $element['booking_window'] = [
      '#type' => 'value',
      '#default_value' => isset($items[$delta]->booking_window) ? $items[$delta]->booking_window : NULL,
    ];

    $element['booking_window_overridden'] = [
      '#type' => 'value',
      '#default_value' => isset($items[$delta]->booking_window_overridden) ? $items[$delta]->booking_window_overridden : FALSE,
    ];

    $class_override_id = Html::getUniqueId('mapped-price-data-widget-class-override');
    $element['class'] = [
      '#type' => 'select',
      '#title' => $this->t('Class'),
      '#default_value' => isset($items[$delta]->class) ? $items[$delta]->class : NULL,
      '#options' => [],
      '#states' => [
        'visible' => [
          '#' . $class_override_id => ['checked' => FALSE],
        ],
      ],
    ];

    $element['class_full'] = [
      '#type' => 'select',
      '#title' => $this->t('Class (Overridden)'),
      '#default_value' => isset($items[$delta]->class) ? $items[$delta]->class : NULL,
      '#options' => $this->getOverrideClasses($order_item),
      '#states' => [
        'visible' => [
          '#' . $class_override_id => ['checked' => TRUE],
        ],
      ],
    ];

    $element['class_overridden'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override class?'),
      '#default_value' => isset($items[$delta]->class_overridden) ? $items[$delta]->class_overridden : NULL,
      '#id' => $class_override_id,
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
    $element = parent::processClassOptions($element, $form_state, $complete_form);

    // If there isn't a default already, default to the first class option for
    // the override field.
    $class_options = array_keys($element['class']['#options']);
    $element['class_full']['#default_value'] = $element['class_full']['#default_value'] ?? reset($class_options);

    return $element;
  }

  /**
   * Create an options array of ticket classes for the event on this order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item to create override ticket class options for.
   *
   * @return array
   *   An options array of ticket class IDs to ticket class labels.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getOverrideClasses(OrderItemInterface $order_item) {
    $ticket_classes = $this->priceCalculator->findPriceMap($order_item)->getClasses();

    $class_options = [];
    foreach ($ticket_classes as $ticket_class) {
      $class_options[$ticket_class->id()] = $ticket_class->label();
    }

    return $class_options;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      if ($value['class_overridden']) {
        $value['class'] = $value['class_full'];
      }
      else {
        $value['class_full'] = NULL;
      }
    }
    return $values;
  }

}
