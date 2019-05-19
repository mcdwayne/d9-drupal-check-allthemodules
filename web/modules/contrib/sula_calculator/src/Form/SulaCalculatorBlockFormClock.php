<?php

namespace Drupal\sula_calculator\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Configure site information settings for this site.
 */
class SulaCalculatorBlockFormClock extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sula_calculator_form_clock';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sula_calculator.settings'];
  }

  /**
   * Creates the form and defines all of the classes for the divs.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $sula_config = $this->config('sula_calculator.settings');
    $acad_year = $sula_config->get('acad_year_length_clock');
    $disclaimer = $sula_config->get('disclaimer');

    $form['#prefix'] = '<div class="container">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'] = 'sula_calculator/sula-calculator';

    $form['disclaimer']['#suffix'] = '<div class="col-md-12 calculator disclaimer-message">' . $disclaimer . '</div>';

    $form['opening_parenthesis'] = [
      '#prefix' => '<div class="calculator col-md-1 hidden-xs hidden-sm"><p>(</p>',
      '#suffix' => '</div>',
    ];

    $form['prog_length'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Program Length'),
      '#description' => $this->t('Enter the length (in weeks) for your program.'),
      '#ajax' => [
        'callback' => [$this, 'calculateAjax'],
        'event' => 'change',
      ],
      '#prefix' => '<div class="col-md-2">',
      '#suffix' => '</div>',
    ];

    $form['division_symbol'] = [
      '#prefix' => '<div class="calculator col-md-1 hidden-xs hidden-sm"><p>÷</p>',
      '#suffix' => '</div>',
    ];

    $form['year_length'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Academic Year Length'),
      '#default_value' => $acad_year,
      '#attributes' => ['disabled' => 'TRUE'],
      '#prefix' => '<div class="col-md-3">',
      '#suffix' => '</div>',
    ];

    $form['closing_parenthesis'] = [
      '#prefix' => '<div class="calculator col-md-1 hidden-xs hidden-sm"><p>)</p>',
      '#suffix' => '</div>',
    ];

    $form['multiplication_symbol'] = [
      '#prefix' => '<div class="calculator col-md-1 hidden-xs hidden-sm"><p>X</p>',
      '#suffix' => '</div>',
    ];

    $form['multiplier'] = [
      '#prefix' => '<div class="calculator col-md-2 hidden-xs hidden-sm"><p>1.5</p>',
      '#suffix' => '</div>',
    ];

    $form['equals_symbol'] = [
      '#prefix' => '<div class="calculator col-md-1 hidden-xs hidden-sm"><p>=</p>',
      '#suffix' => '</div>',

    ];

    $form['calculation-message-clock']['#suffix'] = '<div class="col-md-12 calculator calculate-message sula-clock-message"></div>';

    return $form;
  }

  /**
   * Ajax callback that processes the calculation.
   */
  public function calculateAjax(array &$form, FormStateInterface $form_state) {

    $response = new AjaxResponse();

    if (isset($form['prog_length']['#value']) && isset($form['year_length']['#value'])) {
      // Set the necessary variables.
      $prog_length = (int) $form['prog_length']['#value'];
      $year_length = (int) $form['year_length']['#value'];
      $multiplier = 1.5;

      // Perform the calculation and pass it to the calculate message div.
      $sula = ($prog_length / $year_length) * $multiplier;
      $sula = round($sula, 2);
      if ($prog_length !== 0) {
        $response->addCommand(new HtmlCommand('.sula-clock-message', 'Your maximum SULA Eligibility is ' . $sula . " years."));
      }
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   *
   * This is an empty submit function that can be filled in as needed.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
