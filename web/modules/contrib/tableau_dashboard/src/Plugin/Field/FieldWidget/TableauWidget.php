<?php

namespace Drupal\tableau_dashboard\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'access_tableau_widget' widget.
 *
 * @FieldWidget(
 *   id = "tableau_dashboard_widget",
 *   module = "tableau_dashboard",
 *   label = @Translation("Tableau dashboard Widget"),
 *   field_types = {
 *     "tableau_dashboard_field"
 *   }
 * )
 */
class TableauWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $element += [
      '#type' => 'textfield',
      '#default_value' => $value,
      '#size' => 60,
      '#maxlength' => 255,
      '#attached' => ['library' => 'tableau_dashboard/tableau_widget_formatter'],
    ];
    return ['value' => $element];
  }

}
