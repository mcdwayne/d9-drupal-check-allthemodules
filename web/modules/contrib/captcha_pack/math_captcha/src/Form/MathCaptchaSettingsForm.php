<?php

namespace Drupal\math_captcha\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Math CAPTCHA settings form.
 */
class MathCaptchaSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'math_captcha_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['math_captcha.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('math_captcha.settings');

    $form = [];
    $enabled_challenges = _math_captcha_enabled_challenges();
    $form['math_captcha_enabled_challenges'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled math challenges'),
      '#options' => [
        'addition' => $this->t('Addition: x + y = z'),
        'subtraction' => $this->t('Subtraction: x - y = z'),
        'multiplication' => $this->t('Multiplication: x * y = z'),
      ],
      '#default_value' => $enabled_challenges,
      '#description' => $this->t('Select the math challenges you want to enable.'),
    ];

    $form['math_captcha_textual_numbers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Textual representation of numbers'),
      '#default_value' => $config->get('math_captcha_textual_numbers'),
      '#description' => $this->t('When enabled, the numbers in the challenge will get a textual representation if available. E.g. "four" instead of "4".'),
    ];

    $form['math_captcha_textual_operators'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Textual representation of operators'),
      '#default_value' => $config->get('math_captcha_textual_operators'),
      '#description' => $this->t('When enabled, the operators in the challenge will get a textual representation if available. E.g. "plus" instead of "+".'),
    ];

    // Addition challenge.
    $form['math_captcha_addition'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Addition challenge: x + y = z'),
      '#states' => [
        'invisible' => [
          ':input[name="math_captcha_enabled_challenges[addition]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['math_captcha_addition']['math_captcha_addition_argmax'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum value for x and y'),
      '#default_value' => $config->get('math_captcha_addition_argmax'),
      '#maxlength' => 3,
      '#size' => 3,
      '#states' => [
        'required' => [
          ':input[name="math_captcha_enabled_challenges[addition]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['math_captcha_addition']['math_captcha_addition_allow_negative'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow negative values.'),
      '#default_value' => $config->get('math_captcha_addition_allow_negative'),
    ];

    // Subtraction challenge.
    $form['math_captcha_subtraction'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Subtraction challenge: x - y = z'),
      '#states' => [
        'invisible' => [
          ':input[name="math_captcha_enabled_challenges[subtraction]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['math_captcha_subtraction']['math_captcha_subtraction_argmax'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum value for x and y'),
      '#default_value' => $config->get('math_captcha_subtraction_argmax'),
      '#maxlength' => 3,
      '#size' => 3,
      '#states' => [
        'required' => [
          ':input[name="math_captcha_enabled_challenges[subtraction]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['math_captcha_subtraction']['math_captcha_subtraction_allow_negative'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow negative values.'),
      '#default_value' => $config->get('math_captcha_subtraction_allow_negative'),
    ];

    // Multiplication challenge.
    $form['math_captcha_multiplication'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Multiplication challenge: x * y = z'),
      '#states' => [
        'invisible' => [
          ':input[name="math_captcha_enabled_challenges[multiplication]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['math_captcha_multiplication']['math_captcha_multiplication_argmax'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum value for x and y'),
      '#default_value' => $config->get('math_captcha_multiplication_argmax'),
      '#maxlength' => 3,
      '#size' => 3,
      '#states' => [
        'required' => [
          ':input[name="math_captcha_enabled_challenges[multiplication]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['math_captcha_multiplication']['math_captcha_multiplication_allow_negative'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow negative values.'),
      '#default_value' => $config->get('math_captcha_multiplication_allow_negative'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $enabled_challenges = $form_state->getValue('math_captcha_enabled_challenges');
    if (count(array_filter($enabled_challenges)) < 1) {
      $form_state->setErrorByName('math_captcha_enabled_challenges', $this->t('You should select at least one type of math challenges.'));
    }

    $challenges = array_keys($enabled_challenges);

    foreach ($challenges as $challenge) {
      if (empty($enabled_challenges[$challenge])) {
        continue;
      }

      $argmax = "math_captcha_{$challenge}_argmax";
      if (!ctype_digit($form_state->getValue($argmax))) {
        $form_state->setErrorByName($argmax, $this->t('Maximum value should be an integer.'));
      }
      else {
        $form_state->setValue($argmax, intval($form_state->getValue($argmax)));
        if ($form_state->getValue($argmax) < 2) {
          $form_state->setErrorByName($argmax, $this->t('Maximum value should be an integer and at least 2'));
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('math_captcha.settings');
    $config->set('math_captcha_enabled_challenges', $form_state->getValue('math_captcha_enabled_challenges'));
    $config->set('math_captcha_textual_numbers', $form_state->getValue('math_captcha_textual_numbers'));
    $config->set('math_captcha_textual_operators', $form_state->getValue('math_captcha_textual_operators'));
    $config->set('math_captcha_addition_argmax', $form_state->getValue('math_captcha_addition_argmax'));
    $config->set('math_captcha_addition_allow_negative', $form_state->getValue('math_captcha_addition_allow_negative'));
    $config->set('math_captcha_subtraction_argmax', $form_state->getValue('math_captcha_subtraction_argmax'));
    $config->set('math_captcha_subtraction_allow_negative', $form_state->getValue('math_captcha_subtraction_allow_negative'));
    $config->set('math_captcha_multiplication_argmax', $form_state->getValue('math_captcha_multiplication_argmax'));
    $config->set('math_captcha_multiplication_allow_negative', $form_state->getValue('math_captcha_multiplication_allow_negative'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
