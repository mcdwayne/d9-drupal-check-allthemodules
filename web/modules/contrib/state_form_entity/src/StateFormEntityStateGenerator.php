<?php

namespace Drupal\state_form_entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StateFormEntityStateGenerator.
 *   This class is the generator of states.
 *
 * @package Drupal\state_form_entity
 */
class StateFormEntityStateGenerator {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entity_type_manager;

  /**
   * StateFormEntityStateGenerator constructor.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entity_type_manager = $entityTypeManager;
  }

  /**
   * Get and create element behaviors need in form.
   *
   * @param array $form
   *   The form parent element.
   *
   * @return mixed
   *   The form.
   */
  public function handlerStatesElements(array &$form, FormStateInterface $form_state, $form_id) {
    // Handle show hide effect.
    $states = $this->entity_type_manager
      ->getStorage('state_form_entity')
      ->loadMultiple();

    $fieldPrefix = $suffix = '';

    if ($form['#type'] == "inline_entity_form") {
      $fieldPrefix = self::generateParentsField($form);
      $suffix = ']';
    }

    foreach ($states as $state) {
      /** @var \Drupal\state_form_entity\Entity\StateFormEntity $state */
      if (!self::stateFieldIsInForm($form, $state)) {
        continue;
      }

      $fieldSuffix = '';

      if (isset($form[$state->getFieldToggle()]['widget']['value'])) {
        $fieldSuffix = '[value]';
      }
      if (isset($form[$state->getFieldToggle()]['widget'][0]['value'])) {
        $fieldSuffix = '[0][value]';
      }

      $form = self::generateStatesElements($form, $state->getFieldTarget(), $state->getStateFormEntityType(), $fieldPrefix, $state->getFieldToggle(), $fieldSuffix, $suffix, $state->getValueNested());
    }

    return $form;
  }

  /**
   * Handle element state.
   *
   * @param array $form
   *   The form wrapper elements.
   * @param string $fieldTarget
   *   The field name.
   * @param string $stateType
   *   The behavior required.
   * @param string $fieldPrefix
   *   The potential prefix for field.
   * @param string $fieldToggle
   *   The field name.
   * @param string $fieldSuffix
   *   The potential suffix for field.
   * @param string $suffix
   *   The second potential suffix for field.
   * @param string $value
   *   The behavior value.
   *
   * @return mixed
   *   The state created and returned.
   */
  protected static function generateStatesElements(array $form, $fieldTarget, $stateType, $fieldPrefix, $fieldToggle, $fieldSuffix, $suffix, $value) {

    $fieldsTypes = ['checkbox', 'checkboxes'];
    $neested = 'value';
    if (isset($form[$fieldToggle]['widget']['#type']) && in_array($form[$fieldToggle]['widget']['#type'], $fieldsTypes) ||
      isset($form[$fieldToggle]['widget']['value']['#type']) && in_array($form[$fieldToggle]['widget']['value']['#type'], $fieldsTypes)) {
      $neested = 'checked';
      $value = (boolean) $value;
    }

    if (is_array($fieldTarget)) {
      foreach ($fieldTarget as $field) {
        $form = self::buildStatesElement($form, $field, $stateType, $fieldPrefix, $fieldToggle, $fieldSuffix, $suffix, $neested, $value);
      }
    }
    if (!is_array($fieldTarget)) {
      $form = self::buildStatesElement($form, $fieldTarget, $stateType, $fieldPrefix, $fieldToggle, $fieldSuffix, $suffix, $neested, $value);
    }

    return $form;
  }

  /**
   * Factorise the difference between multiples field target or not.
   *
   * @param array $form
   *   The form.
   * @param string $fieldTarget
   *   The field targeting by state.
   * @param string $stateType
   *   The state type.
   * @param string $fieldPrefix
   *   The field prefix if necessary.
   * @param string $fieldToggle
   *   The field event state.
   * @param string $fieldSuffix
   *   The field suffix if necessary.
   * @param string $suffix
   *   The suffix to detected value.
   * @param string $neested
   *   The type of value eg: checked, value.
   * @param mixed $value
   *   The value of field toggle.
   *
   * @return array
   *   Return the form with state.
   */
  public static function buildStatesElement(array $form, $fieldTarget, $stateType, $fieldPrefix, $fieldToggle, $fieldSuffix, $suffix, $neested, $value) {
    if (!isset($form[$fieldTarget])) {
      return $form;
    }
    $form[$fieldTarget]['#states'][$stateType] = [
      ':input[name="' . $fieldPrefix . '' . $fieldToggle . '' . $suffix . '' . $fieldSuffix . '"]' => [$neested => $value],
    ];

    return $form;
  }

  /**
   * Handle ajax element.
   *
   * @param array $form
   *   The form wrapper elements.
   * @param string $field
   *   The field name.
   * @param string $state
   *   The behavior required.
   * @param array $callback
   *   The callback need to get value, and event js.
   *
   * @return mixed
   *   The ajax event created and returned.
   */
  public static function generateStatesAjaxElements(array $form, $field, $state, array $callback) {
    $form[$field]['widget']['#' . $state] = $callback;

    return $form;
  }

  /**
   * Method handle recusrivity.
   *
   * @param array $form
   *   The form.
   *
   * @return mixed
   *   The field target.
   */
  protected static function generateParentsField(array $form) {
    $fieldPrefix = $form['#parents'][0];

    foreach ($form['#parents'] as $key => $parent) {
      if ($key != 0) {
        $fieldPrefix .= '[' . $parent . ']';
      }
    }
    $fieldPrefix .= '[';

    return $fieldPrefix;
  }

  /**
   * Check if field toggle and the fields target are in form.
   *
   * @param array $form
   *   The form.
   * @param object $state
   *   The state to generate.
   *
   * @return bool
   *   Return boolean TRUE if fields are in form.
   */
  protected function stateFieldIsInForm(array $form, $state) {
    $bool = FALSE;
    $targets = $state->getFieldTarget();

    if (!is_array($targets)) {
      if (isset($form[$targets]) && isset($form[$state->getFieldToggle()])) {
        $bool = TRUE;
      }
    }

    if (is_array($targets)) {
      foreach ($targets as $target) {
        if (isset($form[$target]) && isset($form[$state->getFieldToggle()])) {
          $bool = TRUE;
        }
      }
    }

    return $bool;
  }

}
