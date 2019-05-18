<?php
namespace Drupal\forgot_password\Step;

use Drupal\forgot_password\Button\StepOneNextButton;
use Drupal\forgot_password\Validator\ValidatorEmail;

/**
 * Class StepOne.
 *
 * @package Drupal\forgot_password\Step
 */
class StepOne extends BaseStep {

  /**
   * {@inheritdoc}
   */
  protected function setStep() {
    return StepsEnum::STEP_ONE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      new StepOneNextButton(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildStepFormElements() {
    if(!empty($this->getValues()['user_email'])) {
      $user_email = $this->getValues()['user_email'];
    }
    elseif (!empty($_SESSION['forgot_password']['useremail'])) {
      $user_email = $_SESSION['forgot_password']['useremail'];
    }
    else {
      $user_email = '';
    }

    $identification = '<div id="step1-identify" class="row resetpass-steps"><div class="col-xs-12 col-sm-4 col-md-4 col-lg-4 active steps">IDENTIFICATION</div>
    <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4 steps">VERIFICATION</div><div class="col-xs-12 col-sm-4 col-md-4 col-lg-4 steps">RESET PASSWORD</div></div>';
    $form['identification'] = [
      '#type' => 'item',
      '#markup' => t($identification),
    ];
	
    $form['user_email'] = [
      '#type' => 'email',
      '#title' => t("Enter Email Address"),
      '#required' => FALSE,
      '#attributes' => [
        'autocomplete' => 'off'
      ],
      '#default_value' => $user_email,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames() {
    return [
      'user_email',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsValidators() {
    return [
      'user_email' => [
        new ValidatorEmail("Empty/Invalid Email Address"),
      ],
    ];
  }
}