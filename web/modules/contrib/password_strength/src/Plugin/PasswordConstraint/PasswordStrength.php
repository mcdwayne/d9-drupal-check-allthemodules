<?php

//TODO - Add in "tokens" into annotations (see: error message, which should show #chars from config)

namespace Drupal\password_strength\Plugin\PasswordConstraint;

use Drupal\Core\Form\FormStateInterface;
use Drupal\password_policy\PasswordConstraintBase;
use Drupal\password_policy\PasswordPolicyValidation;

/**
 * Enforces a specific character length for passwords.
 *
 * @PasswordConstraint(
 *   id = "password_strength_constraint",
 *   title = @Translation("Password Strength"),
 *   description = @Translation("PasswordStrength is a password strength estimator using pattern matching and minimum entropy calculation. Scores range from 0 to 4, 4 being the strongest password."),
 *   error_message = @Translation("Your password lacks strength and has too many common patterns."),
 * )
 */
class PasswordStrength extends PasswordConstraintBase {

  public $strength_scores = [
    '0' => 'Very weak (0)',
    '1' => 'Weak (1)',
    '2' => 'Average (2)',
    '3' => 'Strong (3)',
    '4' => 'Very strong (4)',
  ];

  /**
   * {@inheritdoc}
   */
  function validate($password, $user_context) {
    unset($user_context['uid']);

    $userData = array_values($user_context);

    $configuration = $this->getConfiguration();
    $validation = new PasswordPolicyValidation();

    $password_strength = new \Drupal\password_strength\PasswordStrength();
    $strength = $password_strength->passwordStrength($password, $userData);

    if ($strength['score'] < $configuration['strength_score']) {
      $validation->setErrorMessage($this->t('The password has a score of @password-score but the policy requires a score of at least @policy-score', array('@password-score'=>$strength['score'], '@policy-score'=>$configuration['strength_score'])));
    }
    return $validation;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'strength_score' => 3,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['strength_score'] = array(
      '#type' => 'select',
      '#title' => t('Password Strength Minimum Score'),
      '#options' => $this->strength_scores,
      '#default_value' => $this->getConfiguration()['strength_score'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['strength_score'] = $form_state->getValue('strength_score');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return $this->t('Password Strength minimum score of @score', array('@score' => $this->configuration['strength_score']));
  }

}