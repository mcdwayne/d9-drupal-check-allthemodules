<?php

/**
 * @file
 * Render the Form Field Type.
 */

namespace Drupal\drupalcreate_form_field_type\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Render the Form Field.
 *
 * @FieldFormatter(
 *   id = "formfield_default",
 *   module = "form",
 *   label = @Translation("Form"),
 *   field_types = {
 *     "form"
 *   }
 * )
 *
 * @package Drupal\drupalcreate_form_field_type\Plugin\Field\FieldFormatter
 */
class FormfieldDefaultFormatter extends FormatterBase {

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements        = array();
    $available_forms = \Drupal::entityManager()
                              ->getStorage('contact_form')
                              ->loadMultiple();
    foreach ($items as $delta => $item) {
      if (isset($available_forms[$item->value])) {
        // Load the form with a specific $form_name.
        // Create the view for the form.
        $entity  = \Drupal::entityManager()
                          ->getStorage('contact_form')
                          ->load($item->value);
        $message = \Drupal::entityManager()
                          ->getStorage('contact_message')
                          ->create(array('contact_form' => $entity->id()));
        // Get the form based on the view defined above.
        $form               = \Drupal::service('entity.form_builder')
                                     ->getForm($message);
        $elements[$delta] = $form;
      }
    }

    return $elements;
  }

}
