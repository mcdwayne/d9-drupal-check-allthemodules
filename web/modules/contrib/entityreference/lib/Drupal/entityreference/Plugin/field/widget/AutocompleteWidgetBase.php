<?php

/**
 * @file
 * Definition of Drupal\entityreference\Plugin\field\widget\AutocompleteWidgetBase.
 */

namespace Drupal\entityreference\Plugin\field\widget;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Parent plugin for entity-reference autocomplete widgets.
 */
abstract class AutocompleteWidgetBase extends WidgetBase {

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {

    $form['match_operator'] = array(
      '#type' => 'select',
      '#title' => t('Autocomplete matching'),
      '#default_value' => $this->getSetting('match_operator'),
      '#options' => array(
        'STARTS_WITH' => t('Starts with'),
        'CONTAINS' => t('Contains'),
      ),
      '#description' => t('Select the method used to collect autocomplete suggestions. Note that <em>Contains</em> can cause performance issues on sites with thousands of nodes.'),
    );
    $form['size'] = array(
      '#type' => 'textfield',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#element_validate' => array('form_validate_number'),
      // Minimum value for form_validate_number().
      '#min' => 1,
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $element = $this->prepareElement($items, $delta, $element, $langcode, $form, $form_state, 'entityreference/autocomplete/single');
    return array('target_id' => $element);
  }

  /**
   * Prepapre the element.
   *
   * @default_path
   *   The menu item to be used in the autocomplete path.
   */
  protected function prepareElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state, $default_path) {
    $instance = $this->instance;
    $field = $this->field;
    $entity = isset($element['#entity']) ? $element['#entity'] : NULL;

    // Prepare the autocomplete path.
    $path = $this->getSetting('path');
    $autocomplete_path = !empty($path) ? $path : $default_path;
    $autocomplete_path .= '/' . $field['field_name'] . '/' . $instance['entity_type'] . '/' . $instance['bundle'] . '/';

    // Use <NULL> as a placeholder in the URL when we don't have an entity.
    // Most webservers collapse two consecutive slashes.
    $id = 'NULL';
    if ($entity) {
      if ($eid = $entity->id()) {
        $id = $eid;
      }
    }
    $autocomplete_path .= $id;

    $element += array(
      '#type' => 'textfield',
      '#maxlength' => 1024,
      '#default_value' => implode(', ', $this->getLabels($items)),
      '#autocomplete_path' => $autocomplete_path,
      '#size' => $this->getSetting('size'),
      '#element_validate' => array(array($this, 'elementValidate')),
    );
    return $element;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::errorElement().
   */
  public function errorElement(array $element, array $error, array $form, array &$form_state) {
    return $element['target_id'];
  }

  /**
   * Element validate.
   */
  public function elementValidate($element, &$form_state) {
  }

  /**
   * Get the entity labels.
   */
  protected function getLabels(array $items) {
    $instance = $this->instance;
    $field = $this->field;

    $entity = isset($element['#entity']) ? $element['#entity'] : NULL;
    $handler = entityreference_get_selection_handler($field, $instance, $entity);

    $entity_ids = array();
    $entity_labels = array();

    // Build an array of entities ID.
    foreach ($items as $item) {
      $entity_ids[] = $item['target_id'];
    }

    // Load those entities and loop through them to extract their labels.
    $entities = entity_load_multiple($field['settings']['target_type'], $entity_ids);

    foreach ($entities as $entity_id => $entity_item) {
      $label = $entity_item->label();
      $key = "$label ($entity_id)";
      // Labels containing commas or quotes must be wrapped in quotes.
      if (strpos($key, ',') !== FALSE || strpos($key, '"') !== FALSE) {
        $key = '"' . str_replace('"', '""', $key) . '"';
      }
      $entity_labels[] = $key;
    }
    return $entity_labels;
  }
}
