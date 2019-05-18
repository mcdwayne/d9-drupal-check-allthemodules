<?php

namespace Drupal\presshub\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\presshub\PresshubHelper;

/**
 * Plugin implementation of the 'field_presshub_services' widget.
 *
 * @FieldWidget(
 *   id = "field_presshub_services",
 *   module = "presshub",
 *   label = @Translation("Services"),
 *   field_types = {
 *     "field_presshub_services"
 *   }
 * )
 */
class Services extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $service_name = isset($items[$delta]->service_name) ? $items[$delta]->service_name : '';
    $presshub = new PresshubHelper();
    if ($options = $presshub->getServices()) {
      $element += [
        '#type'          => 'checkboxes',
        '#title'         => $this->t('Service'),
        '#options'       => $options,
        '#default_value' => $service_name,
        '#required'      => TRUE,
      ];
    }
    else {
      $element += [
        '#plain_text' => $this->t('Please login to Presshub and enable at least one integration.'),
      ];
    }
    return [
      'service_name' => $element
    ];
  }

}
