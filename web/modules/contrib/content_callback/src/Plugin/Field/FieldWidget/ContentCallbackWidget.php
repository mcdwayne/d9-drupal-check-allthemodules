<?php

/**
 * @file
 * Contains \Drupal\content_callback\Plugin\field\widget\ContentCallbackWidget.
 */

namespace Drupal\content_callback\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'content_callback_select' widget.
 *
 * @FieldWidget(
 *   id = "content_callback_select",
 *   label = @Translation("Select list"),
 *   field_types = {
 *     "content_callback"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ContentCallbackWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $selected_items = $this->getSelectedOptions($items, $delta);

    $element['select'] = array(
      '#type' => 'select',
      '#title' => $element['#title'],
      '#options' => $this->getOptions($items->getEntity()),
      '#default_value' => $selected_items,
      // Do not display a 'multiple' select box if there is only one option.
      '#multiple' => $this->multiple && count($this->options) > 1,
      '#ajax' => array(
        'callback' => array(get_class($this), 'refreshContentCallbackOptions'),
        'wrapper' => 'refresh_options',
      ),
    );

    $element['options_container'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' =>'refresh_options',
      ),
      '#tree' => TRUE,
    );

    // In some cases we want to expand the field widget with some extra fields
    // if we selected a callback, check that plugin instance to see if it needs
    // to add some additional fields.
    if ($form_state->getTriggeringElement()) {
      $triggering_element = $form_state->getTriggeringElement();
      $field_name = $items[$delta]->getFieldDefinition()->getFieldStorageDefinition()->get('field_name');

      if (isset($triggering_element['#array_parents']) && (strpos($triggering_element['#name'], $field_name) > -1)) {
        $callback = $triggering_element['#value'];
        if ($triggering_element['#multiple']) {
          $callback = reset($callback);
        }
      }
    }
    elseif (!empty($selected_items[0])) {
      $callback = reset($selected_items);
    }
    else {
      // We didn't select any callback, continue
      return $element;
    }

    // Get the options form
    if(!empty($callback) && $callback != '_none') {
      $manager = \Drupal::service('plugin.manager.content_callback');
      $definition = $manager->getDefinition($callback);

      if (!empty($definition['has_options'])) {
        $element['options_container']['#type'] = 'details';
        $element['options_container']['#title'] = $element['#title'] . ' options';

        if (!empty($definition) && class_exists($definition['class'])) {
          $content_callback = $manager->createInstance($callback);
          $saved_options = $items[$delta]->options;

          $content_callback->optionsForm($element['options_container'], $saved_options);
        }
      }
    }

    return $element;
  }

  /**
   * Refreshes the content callback options.
   */
  public function refreshContentCallbackOptions($form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    return $element['options_container'];
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param array $form_state
   *   The form state.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#required'] && $element['select']['#value'] == '_none') {
      $form_state->setError($element, t('!name field is required.', array('!name' => $element['#title'])));
    }

    // Massage submitted form values.
    // Drupal\field\Plugin\Type\Widget\WidgetBase::submit() expects values as
    // an array of values keyed by delta first, then by column, while our
    // widgets return the opposite.

    // If there isn't a value selected save nothing
    $value = $element['select']['#value'];
    if ($value == '_none') {
      $form_state->setValueForElement($element, array());
      return;
    }

    // Massages the options
    $massaged_values = array();
    $options = Element::children($element['options_container']);
    foreach ($options as $option) {
      $massaged_values[$option] = $element['options_container'][$option]['#value'];
    }

    // Transpose selections from field => delta to delta => field.
    if (is_array($value) && !empty($value)) {
      // Support multiple values
      foreach ($value as $value_item) {
        $items[] = array(
          'value' => $value_item,
          'options' => $massaged_values,
        );
      }
    }
    else {
      $items[] = array(
        'value' => $value,
        'options' => $massaged_values,
      );
    }

    // Set the changed form
    $form_state->setValueForElement($element, $items);
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    if (!isset($this->options)) {
      $options = [];

      $manager = \Drupal::service('plugin.manager.content_callback');
      foreach ($manager->getDefinitions() as $id => $definition) {
        if (!isset($definition['entity_types']) || !is_array($definition['entity_types']) || in_array($entity->getEntityTypeId(), $definition['entity_types'])) {
          $options[$id] = $definition['title'];
        }
      }

      // Add an empty option if the widget needs one.
      if ($empty_label = $this->getEmptyLabel()) {
        $options = ['_none' => $empty_label] + $options;
      }

      $this->options = $options;
    }

    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if ($this->multiple) {
      // Multiple select: add a 'none' option for non-required fields.
      if (!$this->required) {
        return t('- None -');
      }
    }
    else {
      // Single select: add a 'none' option for non-required fields,
      // and a 'select a value' option for required fields that do not come
      // with a value selected.
      if (!$this->required) {
        return t('- None -');
      }
      if (!$this->has_value) {
        return t('- Select a value -');
      }
    }
  }
}
