<?php

/**
 * @file
 *
 * This is a temporary wrapper around the FileWidget class to fix a minor bug with #ajax element
 * (see todo below in ::process()) until it is fixed in the Drupal core.
 *
 * And another bug with unsetting userInput() in ::submit() callback (see todo below in ::submit()).
 */

namespace Drupal\visualn_file_field\Plugin;

use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\NestedArray;

class FileWidgetWrapper extends FileWidget {

  public static function process($element, FormStateInterface $form_state, $form) {
    $item = $element['#value'];
    $item['fids'] = $element['fids']['#value'];

    // Add the display field if enabled.
    if ($element['#display_field']) {
      $element['display'] = [
        '#type' => empty($item['fids']) ? 'hidden' : 'checkbox',
        '#title' => t('Include file in display'),
        '#attributes' => ['class' => ['file-display']],
      ];
      if (isset($item['display'])) {
        $element['display']['#value'] = $item['display'] ? '1' : '';
      }
      else {
        $element['display']['#value'] = $element['#display_default'];
      }
    }
    else {
      $element['display'] = [
        '#type' => 'hidden',
        '#value' => '1',
      ];
    }

    // Add the description field if enabled.
    if ($element['#description_field'] && $item['fids']) {
      $config = \Drupal::config('file.settings');
      $element['description'] = [
        '#type' => $config->get('description.type'),
        '#title' => t('Description'),
        '#value' => isset($item['description']) ? $item['description'] : '',
        '#maxlength' => $config->get('description.length'),
        '#description' => t('The description may be used as the label of the link to the file.'),
      ];
    }

    // Adjust the Ajax settings so that on upload and remove of any individual
    // file, the entire group of file fields is updated together.
    if ($element['#cardinality'] != 1) {
      $parents = array_slice($element['#array_parents'], 0, -1);
      $new_options = [
        'query' => [
          'element_parents' => implode('/', $parents),
        ],
      ];
      $field_element = NestedArray::getValue($form, $parents);
      $new_wrapper = $field_element['#id'] . '-ajax-wrapper';
      foreach (Element::children($element) as $key) {
        if (isset($element[$key]['#ajax'])) {
          // @todo: do not change other ajaxified elements that may be possibly added by extending classes
          if (!in_array($key, array('upload_button', 'remove_button'))) {
            continue;
          }
          $element[$key]['#ajax']['options'] = $new_options;
          $element[$key]['#ajax']['wrapper'] = $new_wrapper;
        }
      }
      unset($element['#prefix'], $element['#suffix']);
    }

    // Add another submit handler to the upload and remove buttons, to implement
    // functionality needed by the field widget. This submit handler, along with
    // the rebuild logic in file_field_widget_form() requires the entire field,
    // not just the individual item, to be valid.
    foreach (['upload_button', 'remove_button'] as $key) {
      $element[$key]['#submit'][] = [get_called_class(), 'submit'];
      $element[$key]['#limit_validation_errors'] = [array_slice($element['#parents'], 0, -1)];
    }

    return $element;
  }


  public static function submit($form, FormStateInterface $form_state) {
    // During the form rebuild, formElement() will create field item widget
    // elements using re-indexed deltas, so clear out FormState::$input to
    // avoid a mismatch between old and new deltas. The rebuilt elements will
    // have #default_value set appropriately for the current state of the field,
    // so nothing is lost in doing this.
    $button = $form_state->getTriggeringElement();
    $parents = array_slice($button['#parents'], 0, -2);
    // @todo: using input shouldn't be cleared, otherwise it seems to reset initial field
    //    configuration values that may be needed by extending classes,
    //    see visualn_file widget formElement() for details.
    //NestedArray::setValue($form_state->getUserInput(), $parents, NULL);

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    $submitted_values = NestedArray::getValue($form_state->getValues(), array_slice($button['#parents'], 0, -2));
    foreach ($submitted_values as $delta => $submitted_value) {
      if (empty($submitted_value['fids'])) {
        unset($submitted_values[$delta]);
      }
    }

    // If there are more files uploaded via the same widget, we have to separate
    // them, as we display each file in its own widget.
    $new_values = [];
    foreach ($submitted_values as $delta => $submitted_value) {
      if (is_array($submitted_value['fids'])) {
        foreach ($submitted_value['fids'] as $fid) {
          $new_value = $submitted_value;
          $new_value['fids'] = [$fid];
          $new_values[] = $new_value;
        }
      }
      else {
        $new_value = $submitted_value;
      }
    }

    // Re-index deltas after removing empty items.
    $submitted_values = array_values($new_values);

    // Update form_state values.
    NestedArray::setValue($form_state->getValues(), array_slice($button['#parents'], 0, -2), $submitted_values);

    // Update items.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['items'] = $submitted_values;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);
  }

}
