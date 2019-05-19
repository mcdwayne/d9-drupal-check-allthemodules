<?php

namespace Drupal\squid_autocomplete\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "squid_entity_reference_autocomplete",
 *   label = @Translation("Autocomplete (Squid)"),
 *   description = @Translation("An autocomplete text field, allowing parentheses"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class SquidEntityReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += array(
      '#type' => 'squid_entity_autocomplete',
    );
    return parent::formElement($items, $delta, $element, $form, $form_state);
  }
}
