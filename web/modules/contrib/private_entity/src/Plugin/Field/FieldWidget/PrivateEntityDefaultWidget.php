<?php

namespace Drupal\private_entity\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'private_entity_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "private_entity_default_widget",
 *   label = @Translation("Private Entity"),
 *   field_types = {
 *     "private_entity"
 *   }
 * )
 */
class PrivateEntityDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'radios',
      '#options' => [1 => t('Private'), 0 => t('Public')],
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : 0,
    ];
    return $element;
  }

}
