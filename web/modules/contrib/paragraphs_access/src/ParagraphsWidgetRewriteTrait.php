<?php

namespace Drupal\paragraphs_access;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Override the formElement function for the paragraph widgets.
 *
 * Once the widget is built, we want to check the users access to the paragraph
 * if they dont have access, then access to the fields needs to be denied and
 * we want to overwrite the original widget content with a message telling the
 * user why access was denied.
 */
trait ParagraphsWidgetRewriteTrait {

  /**
   * Rewrite paragraph widget content if permission is denied to paragraph.
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

    $entity = $field_state['paragraphs'][$delta]["entity"];
    if (!$entity || !$entity->id() || !$entity->getParentEntity()) {
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
      $element["#paragraphs_access_restricted"] = TRUE;

      // Build blocked content message, and allow other modules to alter
      // textdomain TODO: Add setting to allow you to globaly hide the blocked
      // content message.
      $message = [
        "#prefix" => "<div class='paragraphs_restriction_message'>",
        "#type" => "markup",
        "#markup" => "You do not have access to edit this paragraph.",
        "#suffix" => "</div>",
      ];
      \Drupal::moduleHandler()->alter('paragraphs_access_restriction_message', $entity, $operation, $message);
      $element["restriction_message"] = $message;
    }
    return $element;
  }

}
