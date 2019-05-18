<?php

namespace Drupal\progress_bar\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'progress_bar' field formatter.
 *
 * @FieldFormatter(
 *   id = "progress_bar",
 *   label = @Translation("Progress bar"),
 *   field_types = {
 *     "list_string",
 *     "list_integer",
 *     "list_float",
 *     "state"
 *   }
 * )
 */
class ProgressBarFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'progress_bar_color' => '#337ab7',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Creating color field setting.
    $element['progress_bar_color'] = [
      '#title' => t('Add Color'),
      '#type' => 'textfield',
      '#size' => 20,
      '#description' => 'Add comma(,) seperated color for each state.',
      '#default_value' => $this->getSetting('progress_bar_color'),
      '#required' => TRUE,
      '#element_validate' => array(
        array($this, 'validate'),
      ),
    ];
    return $element;
  }

  /**
   * Validate the color text field.
   */
  public function validate($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    $color = explode(',', $value);
    $count = count($color);
    $field_value = $this->getFieldSetting('allowed_values');
    $field_count = count($field_value);
    if ($field_count == $count) {
      $form_state->setValueForElement($element, $value);
      return;
    }
    else {
      $form_state->setError($element, t("Please enter Color value same as your allowed values in field."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('@setting: @value', ['@setting' => 'progress_bar_color', '@value' => $this->getSetting('progress_bar_color')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $allowed_value = [];
    $list_count = '';
    // If field type is state.
    if ($items->getFieldDefinition()->getType() == 'state') {
      // Get the workflow and state.
      $workflow_manager = \Drupal::service('plugin.manager.workflow');
      /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow */
      $workflow = $workflow_manager->getDefinitions();
      if (!empty($workflow)) {
        $entity_type = $items->getEntity()->getEntityTypeId();
        // Load the entity of commerce and based on that get the state.
        if ($entity_type == 'commerce_order') {
          $allowed_value = $workflow['order_default']['states'];
          $list_count = count($allowed_value);
        }
        elseif ($entity_type == 'commerce_payment') {
          $allowed_value = $workflow['payment_default']['states'];
          $list_count = count($allowed_value);
        }
        elseif ($entity_type == 'commerce_shipping') {
          $allowed_value = $workflow['shipment_default']['states'];
          $list_count = count($allowed_value);
        }
        $elements = $this->getStateDetail($allowed_value, $list_count, $items);
      }
    }
    // Else field type is List.
    else {
      $list = $items->getSettings();
      // If allowed value is present.
      if (in_array('allowed_values', array_keys($list))) {
        $allowed_value = $list['allowed_values'];
        $list_count = count($allowed_value);
        $elements = $this->getStateDetail($allowed_value, $list_count, $items);
      }
    }
    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

  /**
   * Helper function to get the color.
   */
  protected function getColor($allowed_value, $color_value, $list_count) {
    $color = explode(',', $color_value);
    $color_count = count($color);
    $color_data = [];
    if ($color_count < $list_count) {
      foreach ($allowed_value as $value) {
        $color_data[] = $color[0];
      }
    }
    else {
      $color_data = $color;
    }
    return $color_data;
  }

  /**
   * Helper function to get the state data.
   */
  protected function getStateData($allowed_value, $list_count, $search_value, $color) {
    // Array Loop Counter.
    $loop_count = 0;
    $state_data = array();
    $lowest_percent = (1 / $list_count) * 100;
    // Go through all allowed values.
    foreach ($allowed_value as $key => $value) {
      // If loop count is less than search position.
      if ($loop_count < $search_value + 1) {
        // State.
        $state = (($loop_count + 1) / $list_count) * 100;
        // Add items.
        $state_data[] = array(
          'state' => $state,
          'name' => $key,
          'color' => $color[$loop_count],
          'lowest_percent' => $lowest_percent,
        );
      }
      ++$loop_count;
    }
    return $state_data;
  }

  /**
   * Helper function to get the element data for state.
   */
  protected function getStateDetail($allowed_value, $list_count, $items) {
    $elements = [];
    $color_value = $this->getSetting('progress_bar_color');
    $color = $this->getColor($allowed_value, $color_value, $list_count);
    // Get the state value for each row.
    foreach ($items as $delta => $item) {
      $search_value = array_search($this->viewValue($item), array_keys($allowed_value));
      $state = $this->getStateData($allowed_value, $list_count, $search_value, $color);
      $elements[$delta] = [
        '#theme' => 'progress_bar_format',
        '#state' => $state,
        '#attached' => array('library' => array('progress_bar/progress-bar')),
      ];
    }
    return $elements;
  }

}
