<?php

namespace Drupal\contextual_filter_range_validator\Plugin\views\argument_validator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;

/**
 * Validate whether an argument falls within a specified range.
 *
 * @ingroup views_argument_validate_plugins
 *
 * @ViewsArgumentValidator(
 *   id = "range",
 *   title = @Translation("Range")
 * )
 */
class RangeArgumentValidator extends ArgumentValidatorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['range_min'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum value'),
      '#description' => $this->t('Inclusive. Leave blank for no minimum.'),
      '#default_value' => (isset($this->options['range_min'])
        ? $this->options['range_min'] : ''),
    ];
    $form['range_max'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum value'),
      '#description' => $this->t('Inclusive. Leave blank for no maximum.'),
      '#default_value' => (isset($this->options['range_max'])
        ? $this->options['range_max'] : ''),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $parents = ['options', 'validate', 'options', 'range'];
    $parents_name = implode($parents, '][');
    $values = $form_state->getValue($parents);

    if (!empty($values['range_min']) && !is_numeric($values['range_min'])) {
      $form_state->setErrorByName(
        $parents_name . '][range_min',
        t('Minimum value must be a number.')
      );
    }
    elseif (!empty($values['range_max']) && !is_numeric($values['range_max'])) {
      $form_state->setErrorByName(
        $parents_name . '][range_max',
        t('Maximum value must be a number.')
      );
    }
    elseif (is_numeric($values['range_min']) && is_numeric($values['range_max'])
      && $values['range_min'] > $values['range_max']) {
      $form_state->setErrorByName(
        $parents_name . '][range_min',
        t('Minimum value cannot be greater than Maximum value.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    $options = $this->options;
    if (!empty($options['range_min']) || $options['range_min'] == '0') {
      $min = (float) $options['range_min'];
    }
    if (!empty($options['range_max']) || $options['range_max'] == '0') {
      $max = (float) $options['range_max'];
    }

    if (is_numeric($argument)) {
      $val = (float) $argument;
      if (isset($min) && isset($max) && $val >= $min && $val <= $max) {
        return TRUE;
      }
      elseif (isset($min) && !isset($max) && $val >= $min) {
        return TRUE;
      }
      elseif (isset($max) && !isset($min) && $val <= $max) {
        return TRUE;
      }
      elseif (!isset($max) && !isset($min)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
