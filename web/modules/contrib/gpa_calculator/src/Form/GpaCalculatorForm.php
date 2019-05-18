<?php

/**
 * @file
 * Contains \Drupal\gpa_calculator\Form\GpaCalculatorForm.
 */

namespace Drupal\gpa_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements an example form.
 */
class GpaCalculatorForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gpa_calculator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gpa_calculator.gpa');

    $gpa_instructions = $config->get('instructions') != '' ? $config->get('instructions') : '';
    $gpa_calculator_grades = $config->get('grades');
    $gpa_calculator_grades_array = array();
    // Turn grades string into array.
    foreach (explode("\n", $gpa_calculator_grades) as $grade) {
      if ($gpa_calculator_grades != '') {
        list ($key, $value) = explode('|', $grade, 2);
        $gpa_calculator_grades_array[$key] = $value;
      }
    }

    $form['gpa_instructions'] = array(
      '#markup' => '<div class="gpa-calculator-instructions-wrapper">' . $gpa_instructions . '</div>',
    );

    $add_row_link = \Drupal::l(t('Add Row'), Url::fromRoute('<front>', $route_parameters = array(), array('attributes' => array('id' => 'gpa-add-row'))));

    $form['add_row'] = array(
      '#markup' => '<div id="gpa-add-row">' . $add_row_link . '</div>'
    );

    $gpa_table_head = '<div id="grades_table">';
    $gpa_table_head .= '<div class="gpa-table-thead">';
    $gpa_table_head .= '<div class="gpa-table-cell gpa-th">#</div>';
    $gpa_table_head .= '<div class="gpa-table-cell gpa-th">' . t('Class/Course Name') . '</div>';
    $gpa_table_head .= '<div class="gpa-table-cell gpa-th">' . t('Grade') . '</div>';
    $gpa_table_head .= '<div class="gpa-table-cell gpa-th">' . t('Credits Earned') . '</div>';
    $gpa_table_head .= '</div>';
    $gpa_table_head .= '<div class="gpa-table-body"></div>';

    $form['gpa_table_head'] = array(
      '#markup' => $gpa_table_head,
    );

    $gpa_table_end = '</div>';

    $form['gpa_table_end'] = array(
      '#markup' => $gpa_table_end,
    );

    $form['prev_gpa'] = array(
      '#type' => 'textfield',
      '#title' => t('Cumulative GPA'),
      '#attributes' => array('id' => 'prev-gpa'),
      '#size' => 3,
    );

    $form['prev_hours'] = array(
      '#type' => 'textfield',
      '#title' => t('Cumulative Credits Earned'),
      '#attributes' => array('id' => 'prev-hours'),
      '#size' => 3,
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Calculate'),
      '#button_type' => 'primary',
    );

    $form['gpa_results_wrapper'] = array(
      '#markup' => '<div class="gpa-results-wrapper">',
    );

    $form['gpa_current_output'] = array(
      '#markup' => '<div id="gpa-current-output"></div>',
    );

    $form['gpa_cumulative_output'] = array(
      '#markup' => '<div id="gpa-cumulative-output"></div>',
    );

    $form['gpa_results_wrapper_end'] = array(
      '#markup' => '</div>',
    );

    $form['#attached']['library'][] = 'gpa_calculator/gpa.calculator.form';
    $form['#attached']['library'][] = 'gpa_calculator/gpa.calculator.admin';

    $form['#attached']['drupalSettings']['gpa_calculator']['gpaCalculator']['grades'] = $gpa_calculator_grades_array;

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}
