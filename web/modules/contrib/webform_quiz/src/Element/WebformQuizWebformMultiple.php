<?php

namespace Drupal\webform_quiz\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformMultiple;

/**
 * Provides a webform element to assist in creation of multiple elements.
 *
 * @FormElement("webform_quiz_webform_multiple")
 */
class WebformQuizWebformMultiple extends WebformMultiple {

  public static function initializeElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    parent::initializeElement($element, $form_state, $complete_form);

    $form_state_storage = $form_state->getStorage();
    $key = $form_state_storage['machine_name.initial_values']['key'];
    $element['#parent_webform_element_key'] = $key;
  }

  /**
   * {@inheritdoc}
   */
  protected static function buildElementRow($table_id, $row_index, array $element, $default_value, $weight, array $ajax_settings) {
    // Check the correct answers in the configuration form.

    $row = parent::buildElementRow($table_id, $row_index, $element, $default_value, $weight, $ajax_settings);

    /** @var \Drupal\webform\Plugin\WebformSourceEntityManager $element_manager */
    $entity_manager = \Drupal::service('plugin.manager.webform.source_entity');

    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = $entity_manager->getSourceEntity();
    $element_config = $webform->getElement($element['#parent_webform_element_key']);


    if (isset($row["value"]["#default_value"]) && isset($element_config['#correct_answer'])) {
      $row['is_correct_answer']['#default_value'] = in_array($row["value"]["#default_value"], $element_config['#correct_answer']);
    }

    return $row;
  }

}
