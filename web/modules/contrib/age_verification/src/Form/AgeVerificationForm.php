<?php

/**
 * @file
 * Contains \Drupal\age_verification\Form\AgeVerificationForm.
 */

namespace Drupal\age_verification\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class AgeVerificationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'age_verification_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('age_verification.settings');

    $form['dob'] = [
      '#title' => $this->t('Please enter your date of birth'),
      '#type' => 'date',
      '#default_value' => !empty($form_state->getValue(['dob'])) ? $form_state->getValue(['dob']) : [],
      '#required' => TRUE,
    ];
    $form['confirmation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I confirm that this is my age'),
      '#required' => TRUE,
    ];
    // Output the admin description text in the form if it was set.
    if (!empty($config->get('age_verification_description'))) {
      $form['custom_age_verification_description'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $config->get('age_verification_description') . '</p>',
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('age_verification.settings');
    $form_values = $form_state->getValues();

    $dob = array();
    list($dob['year'], $dob['month'], $dob['day']) = explode('-', $form_values['dob']);

    if (!empty($dob['month'])) {
      // Get rid of future dates.
      if ((int) $dob['year'] > date('Y')) {
        $dob['year'] = date('Y');
      }
      // We are going to run off midnight for these calculations.
      // Set $date_now to the unix time of today at midnight. This depends on
      // your server settings.
      $date_now = strtotime('today midnight');
      // Form values of day month year are converted to unix time.
      $date_posted = strtotime($form_values['dob']);
      // Simple math calculationt to determine difference.
      $difference = $date_now - $date_posted;
      // Add the Age to $accepted_age with a default of 21.
      $accepted_age = (int) $config->get('age_verification_age_limit') * 31556926;
      // Compare the accepted_age with years of difference.
      if ($difference <= $accepted_age) {
        // Throw an error if user age is less than the age selected.
        // !variable: Inserted as is, with no sanitization or formatting.
        $form_state->setErrorByName('dob', t('You need to be !age or over to access the site.', [
          '!age' => (int) $config->get('age_verification_age_limit'),
        ]));
      }
    }

    // Throw an error if user has not confirmed his age.
    if (!empty($form_values['confirmation']) && $form_values['confirmation'] != 1) {
      $form_state->setErrorByName('confirmation', t('You need to confirm your age.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Add TRUE to session age_verified.
    $session = \Drupal::request()->getSession();
    $session->set('age_verified', 1);
    // Add a redirect to requested page. Using $form_state built in redirects.
    $redirect = $session->get('age_verification_path');
    if (!empty($redirect)) {
      $form_state->setRedirect($redirect);
    }

    // For everything else, redirect to homepage.
    $form_state->setRedirect('<front>');
  }

}
