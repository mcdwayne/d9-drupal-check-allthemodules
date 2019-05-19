<?php

namespace Drupal\workflows_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\StateInterface;
use Drupal\workflows_field\Plugin\Field\FieldType\WorkflowsFieldItem;

/**
 * Plugin implementation of the 'workflows_field_state_list' formatter.
 *
 * @FieldFormatter(
 *   id = "workflows_field_state_list",
 *   label = @Translation("States list"),
 *   field_types = {
 *     "workflows_field_item"
 *   }
 * )
 */
class StatesListFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'item_list__states_list',
        '#context' => ['list_style' => 'workflows-states-list'],
        '#attributes' => ['class' => [Html::cleanCssIdentifier($item->value) . '--active']],
        '#items' => $this->buildItems($item),
      ];
    }
    return $elements;
  }

  /**
   * Builds the items array for theme item list.
   *
   * @param \Drupal\workflows_field\Plugin\Field\FieldType\WorkflowsFieldItem $item
   *   The currently active workflow item.
   *
   * @return array
   *   An array of items for theme item_list.
   */
  protected function buildItems(WorkflowsFieldItem $item) {
    $excluded = array_filter($this->getSetting('excluded_states'));
    $items = [];
    $before_current = TRUE;

    foreach ($this->getStatesFromWorkflow() as $key => $state) {
      $is_current = $item->value === $key;

      // Once we've found the current item no longer mark the items as before
      // current. We only apply sibling classes when the item is not the current
      // item.
      if ($is_current) {
        $before_current = FALSE;
        $class = 'is-current';
      }
      else {
        $class = $before_current ? 'before-current' : 'after-current';
      }

      if (!in_array($key, $excluded, TRUE)) {
        $items[] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $state->label(),
          '#wrapper_attributes' => ['class' => [$key, $class]],
        ];
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'excluded_states' => [],
    ];
  }

  /**
   * Gets all available states from the workflow for this field.
   *
   * @return \Drupal\workflows\StateInterface[]
   *   An array of workflow states.
   */
  protected function getStatesFromWorkflow() {
    $workflow = Workflow::load($this->getFieldSetting('workflow'));
    $type = $workflow->getTypePlugin();
    return $type->getStates();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['excluded_states'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Excluded states'),
      '#options' => array_map(function (StateInterface $state) {
        return $state->label();
      }, $this->getStatesFromWorkflow()),
      '#default_value' => $this->getSetting('excluded_states'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($excluded = array_filter($this->getSetting('excluded_states'))) {
      $summary[] = $this->t('Excluded states: @states', ['@states' => implode(', ', $excluded)]);
    }
    else {
      $summary[] = $this->t('Excluded states: n/a');
    }
    return $summary;
  }

}
