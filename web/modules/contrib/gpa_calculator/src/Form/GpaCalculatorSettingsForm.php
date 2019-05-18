<?php

/**
 * @file
 * Contains \Drupal\gpa_calculator\Form\GpaCalculatorSettingsForm.
 */

namespace Drupal\gpa_calculator\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure maintenance settings for this site.
 */
class GpaCalculatorSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'gpa_calculator_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gpa_calculator.gpa'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gpa_calculator.gpa');
    $form['gpa_calculator_school_name'] = array(
      '#type' => 'textfield',
      '#title' => t('School'),
      '#description' => t('Enter you school\'s name here. If blank, block subject will read as "GPA Calculator."'),
      '#default_value' => $config->get('school_name'),
    );

    $form['gpa_calculator_instructions'] = array(
      '#type' => 'textarea',
      '#title' => t('Instructions'),
      '#description' => t('Provide instructions or a description for your GPA calculator.'),
      '#default_value' => $config->get('instructions'),
    );

    $grades_options_example = ' 4.0|A ';
    $grades_options_example .= '3.67|A- ';
    $grades_options_example .= '3.33|B+ ';
    $grades_options_example .= '3.0|B ';
    $grades_options_example .= '2.67|C+ ';
    $grades_options_example .= '2.33|C ';
    $grades_options_example .= '2.0|C- ';
    $grades_options_example .= '1.67|D+ ';
    $grades_options_example .= '1.33|D ';
    $grades_options_example .= '1.0|D- ';
    $grades_options_example .= '0.0|F';

    $grades_description = t('Enter grade options for the select box values on separate lines.  Key-value pairs must be entered separated by pipes. i.e. safe_key|Some readable option.  If blank, default vales will be:') . $grades_options_example;

    $form['gpa_calculator_grades'] = array(
      '#type' => 'textarea',
      '#title' => t('Grades'),
      '#description' => $grades_description,
      '#default_value' => $config->get('grades'),
    );

    $form['#attached']['library'][] = 'gpa_calculator/gpa.calculator.admin';

    return parent::buildForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('gpa_calculator.gpa')
      ->set('school_name', Html::escape($form_state->getValue('gpa_calculator_school_name')))
      ->set('instructions', Html::escape($form_state->getValue('gpa_calculator_instructions')))
      ->set('grades', Html::escape($form_state->getValue('gpa_calculator_grades')))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
