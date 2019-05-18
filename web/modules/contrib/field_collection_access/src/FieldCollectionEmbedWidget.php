<?php

namespace Drupal\field_collection_access;

use Drupal\field_collection\Plugin\Field\FieldWidget\FieldCollectionEmbedWidget as fcFieldCollectionEmbedWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Modifes the default field collection widget to include the access control.
 */
class FieldCollectionEmbedWidget extends fcFieldCollectionEmbedWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $field_name = $this->fieldDefinition->getName();

    // Nest the field collection item entity form in a dedicated parent space,
    // by appending [field_name, delta] to the current parent space.
    // That way the form values of the field collection item are separated.
    $parents = array_merge($element['#field_parents'], [$field_name, $delta]);

    $element += [
      '#element_validate' => [[static::class, 'validate']],
      '#parents' => $parents,
      '#field_name' => $field_name,
    ];

    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element['#type'] = 'fieldset';
    }

    $field_state = static::getWidgetState($element['#field_parents'], $field_name, $form_state);

    $entity = $field_state['field_collection_item'][$delta];
    if (!$entity || !$entity->id() || !$entity->getHost()) {
      return $element;
    }

    $operation = 'update';
    if (!$entity->access($operation)) {
      // If user does not have access, hide hide all children, and sets the
      // blocked property.
      $element_children = Element::children($element);
      foreach ($element_children as $key) {
        $element[$key]["#access"] = FALSE;
      }
      $element["#field_collection_access_restricted"] = TRUE;

      // Build blocked content message, and allow other modules to alter
      // textdomain TODO: Add setting to allow you to globaly hide the blocked
      // content message.
      $message = [
        "#prefix" => "<div class='fci_restriction_message'>",
        "#type" => "markup",
        "#markup" => "Additional Blocked Content exists on this node.",
        "#suffix" => "<div class='fci_restriction_message'>",
      ];
      \Drupal::moduleHandler()->alter('field_collection_access_restriction_message', $entity, $operation, $message);
      $element["restriction_message"] = $message;
    }

    return $element;
  }

}
