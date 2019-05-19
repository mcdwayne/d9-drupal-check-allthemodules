<?php


namespace Drupal\smallads\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the wallet selector widget.
 *
 * @FieldWidget(
 *   id = "smallad_scope",
 *   label = @Translation("Smallad scope"),
 *   description = @Translation("How widely this ad can be seen"),
 *   category = @Translation("Smallads"),
 *   field_types = {
 *     "smallad_scope"
 *   }
 * )
 */
class ScopeWidget extends OptionsSelectWidget {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [
      '#title' => t('Visibility'),
      '#type' => 'select',
      '#options' => smallads_scopes(),
      '#default_value' => $items->value
    ];
    return $element;
  }

}
