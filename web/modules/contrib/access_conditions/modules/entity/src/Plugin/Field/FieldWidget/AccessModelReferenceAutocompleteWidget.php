<?php

namespace Drupal\access_conditions_entity\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;

/**
 * Implementation of the 'access_model_reference_autocomplete_widget' widget.
 *
 * @FieldWidget(
 *   id = "access_model_reference_autocomplete_widget",
 *   label = @Translation("Access conditions"),
 *   field_types = {
 *     "access_model_reference"
 *   }
 * )
 */
class AccessModelReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['operation'] = [
      '#type' => 'radios',
      '#options' => [
        0 => $this->t('View'),
        1 => $this->t('Update'),
        2 => $this->t('Delete'),
      ],
      '#default_value' => isset($items[$delta]->operation) ? $items[$delta]->operation : 0,
      '#required' => TRUE,
    ];

    $element['#attached']['library'][] = 'access_conditions_entity/access_model.fieldtype.widget';

    return $element;
  }

}
