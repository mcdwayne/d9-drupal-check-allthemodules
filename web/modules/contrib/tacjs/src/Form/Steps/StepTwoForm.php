<?php

namespace Drupal\tacjs\Form\Steps;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class StepOneForm.
 *
 * @package Drupal\tacjs\Form
 */
class StepTwoForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tacjs_configuration_two_step';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable('tacjs.admin_settings_form');
    $data = \Drupal::service('tacjs.settings')->getFieldsSelects();
    foreach ($data as $key => $item) {
      if (!empty($item)) {
        if (!(count($item) == 1 && $item[0]['value'] == 'hidden')) {
          $form[$key] = [
            '#type' => 'details',
            '#title' => 'Configuration Global Tarte au Citron ' . $key,
          ];
        }

        foreach ($item as $k => $v) {
          if ($v['value'] != 'hidden') {
            $form[$key][$v['value']] = [
              '#type' => 'textfield',
              '#title' => $v['name'],
              '#default_value' => $config->get($v['value']),
              '#description' => !empty($v['description']) ? $config->get($v['description']) : "",


            ];

            if (isset($v['value_'])) {
              $form[$key][$v['value_']] = [
                '#type' => 'textfield',
                '#title' => $v['value_'],
                '#default_value' => $config->get($v['value_']),

              ];
            }
          }

        }
      }
    }

    $form['actions']['submit']['#value'] = $this->t('Next');

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable('tacjs.admin_settings_form');

    $data = \Drupal::service('tacjs.settings')
      ->cleanValues($form_state->getValues());
    // Save Values.
    foreach ($data as $k => $v) {
      $config->set($k, $v);
    }
    $config->save();
    // Redirect to step three.
    $form_state->setRedirect('tacjs.step_three');
  }

}
