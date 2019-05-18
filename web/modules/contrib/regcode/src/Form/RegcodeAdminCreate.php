<?php

/**
 * @file
 * Contains \Drupal\regcode\Form\RegcodeAdminCreate.
 */

namespace Drupal\regcode\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class RegcodeAdminCreate extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'regcode_admin_create';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $form = [];

    // Basics.
    $form['regcode_create'] = [
      '#type' => 'fieldset',
      '#title' => t('General settings'),
    ];

    $form['regcode_create']['regcode_create_code'] = [
      '#type' => 'textfield',
      '#title' => t("Registration code"),
      '#description' => t('Leave blank to have code generated. Used as prefix when <em>Number of codes</em> is greater than 1.'),
    ];

    $form['regcode_create']['regcode_create_maxuses'] = [
      '#type' => 'textfield',
      '#title' => t("Maximum uses"),
      '#default_value' => 1,
      '#size' => 10,
      '#required' => TRUE,
      '#description' => t('How many times this code can be used to register (enter 0 for unlimited).'),
    ];

    $form['regcode_create']['regcode_create_length'] = [
      '#type' => 'textfield',
      '#title' => t("Code size"),
      '#size' => 10,
      '#default_value' => 12,
    ];

    $form['regcode_create']['regcode_create_format'] = [
      '#type' => 'select',
      '#default_value' => \Drupal::config('regcode.settings')->get('regcode_generate_format'),
      '#title' => t('Format of the generated codes'),
      '#options' => [
        'alpha' => t('Letters'),
        'numeric' => t('Numbers'),
        'alphanum' => t('Numbers & Letters'),
        'hexadec' => t('Hexadecimal'),
      ],
    ];

    $form['regcode_create']['regcode_create_case'] = [
      '#type' => 'checkbox',
      '#title' => t('Uppercase generated codes'),
      '#default_value' => \Drupal::config('regcode.settings')->get('regcode_generate_case'),
    ];    

    $form['regcode_create']['regcode_create_begins'] = [
      '#type' => 'date',
      '#title' => t("Active from"),
      '#description' => t('When this code should activate (leave blank to activate immediately). Accepts any date format that strtotime can handle.'),
      '#default_value' => [
        'day' => 0,
        'month' => 0,
        'year' => 0,
      ],
      '#element_validate' => [
        '_regcode_date_validate'
      ],
    ];

    $form['regcode_create']['regcode_create_expires'] = [
      '#type' => 'date',
      '#title' => t("Expires on"),
      '#description' => t('When this code should expire (leave blank for no expiry). Accepts any date format that strtotime can handle.'),
      '#default_value' => [
        'day' => 0,
        'month' => 0,
        'year' => 0,
      ],
      '#element_validate' => [
        '_regcode_date_validate'
      ],
    ];

    // Bulk.
    $form['regcode_create_bulk'] = [
      '#type' => 'fieldset',
      '#title' => t('Bulk settings'),
      '#description' => t('Multiple codes can be created at once, use these settings to configure the code generation.'),
    ];

    $form['regcode_create_bulk']['regcode_create_number'] = [
      '#type' => 'textfield',
      '#title' => t("Number of codes to generate"),
      '#size' => 10,
      '#default_value' => 1,
    ];

    $form['regcode_create_bulk_submit'] = [
      '#type' => 'submit',
      '#value' => t("Create codes"),
    ];

    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if (!is_numeric($form_state->getValue(['regcode_create_maxuses'])) || $form_state->getValue(['regcode_create_maxuses']) < 0) {
      $form_state->setErrorByName('regcode_create_maxuses', t('Invalid maxuses, specify a positive integer or enter "0" for unlimited'));
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $code = new \stdClass();

    // Convert dates into timestamps.
    foreach (['begins', 'expires'] as $field) {
      $value = $form_state->getValue(['regcode_create_' . $field]);
      if (!empty($value)) {
        $code->$field = NULL;
        $date  = strtotime($value);
        $day   = date('d', $date);
        $month = date('m', $date);
        $year  = date('Y', $date);
        if (isset($year) && $year != 0) {
          $code->$field = mktime(0, 0, 0, $month, $day, $year);
        }
      }
    }

    // Grab form values.
    $code->is_active = 1;
    $code->maxuses = $form_state->getValue(['regcode_create_maxuses']);

    // Start creating codes.
    for ($i = 0; $i < (int) $form_state->getValue(['regcode_create_number']); $i++) {
      $code->code = $form_state->getValue(['regcode_create_code']);

      // Generate a code.
      if (empty($code->code) || $form_state->getValue(['regcode_create_number']) > 1) {
        $gen = regcode_generate($form_state->getValue(['regcode_create_length']), $form_state->getValue(['regcode_create_format']), $form_state->getValue(['regcode_create_case']));
        $code->code .= $gen;
      }

      // Save code.
      if (regcode_save($code, REGCODE_MODE_SKIP)) {
        drupal_set_message(t('Created registration code (%code)', [
          '%code' => $code->code
          ]));
      }
      else {
        drupal_set_message(t('Unable to create code (%code) as code already exists', [
          '%code' => $code->code
          ]), 'warning');
      }
    }
  }

}
