<?php

namespace Drupal\hp\Plugin\hp;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Fail validation Human Presence form protection plugin.
 *
 * @HpFormStrategy(
 *   id = "fail_validation",
 *   label = "Fail validation",
 * )
 */
class FailValidation extends FormStrategyBase implements FormStrategyInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'minimal_confidence' => '100',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['minimal_confidence'] = [
      '#type' => 'number',
      '#title' => $this->t('The minimal confidence needed to pass form validation.'),
      '#description' => $this->t('Should be a number between 0 and 100. 0 means always pass validation.'),
      '#default_value' => $this->configuration['minimal_confidence'],
      '#min' => 0,
      '#max' => 100,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['minimal_confidence'] = $values['minimal_confidence'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hpFormValidation(array &$element, FormStateInterface $form_state) {
    // Fetch the Human Presence check response.
    $response = $this->checkSession();

    // If the response is 100% sure the user is a human, do not prevent submission.
    if (!empty($response) && $response->signal == 'HUMAN' && $response->confidence >= $element['#hp_config']['minimal_confidence']) {
      return;
    }

    // Otherwise fail the detection check and prevent form submission.
    $form_state->setErrorByName('', t('Sorry, we could not process your submission at this time. Please try again later.'));
    $message = t('Suspicious form submission blocked: <pre>@response</pre>', ['@response' => print_r($response, TRUE)]);
    \Drupal::logger('hp')->notice($message);
  }

}
