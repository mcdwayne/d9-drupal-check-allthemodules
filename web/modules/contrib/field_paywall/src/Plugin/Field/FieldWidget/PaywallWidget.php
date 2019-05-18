<?php
/**
 * @file
 * Contains \Drupal\field_paywall\Plugin\Field\FieldWidget\PaywallWidget.
 */
namespace Drupal\field_paywall\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'paywall_widget' widget.
 *
 * @FieldWidget(
 *   id = "paywall_widget",
 *   label = @Translation("Paywall"),
 *   field_types = {
 *     "paywall"
 *   }
 * )
 */
class PaywallWidget extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['enabled'] = array(
      '#title' => t('Enabled'),
      '#type' => 'checkbox',
      '#default_value' => isset($items[$delta]->enabled) ? $items[$delta]->enabled : NULL,
    );

    return $element;
  }
}
