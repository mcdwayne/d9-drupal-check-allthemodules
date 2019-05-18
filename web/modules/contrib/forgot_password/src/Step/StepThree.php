<?php
namespace Drupal\forgot_password\Step;

use Drupal\forgot_password\Button\StepThreeFinishButton;
use Drupal\forgot_password\Button\StepThreePreviousButton;
use Drupal\forgot_password\Validator\ValidatorRequired;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class StepThree.
 *
 * @package Drupal\forgot_password\Step
 */
class StepThree extends BaseStep {

  /**
   * {@inheritdoc}
   */
  protected function setStep() {
    return StepsEnum::STEP_THREE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      new StepThreePreviousButton(),
      new StepThreeFinishButton(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildStepFormElements() {
	  $useremail = $_SESSION['forgot_password']['useremail'];
	  $resetpass = '<div id="step1-identify" class="row resetpass-steps"><div class="col-xs-12 col-sm-4 col-md-4 col-lg-4 steps">IDENTIFICATION</div>
    <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4 steps">VERIFICATION</div><div class="col-xs-12 col-sm-4 col-md-4 col-lg-4 active steps">RESET PASSWORD</div></div>';
    
    $form['resetpass'] = [
      '#type' => 'item',
      '#markup' => t($resetpass),
    ];

    if(!empty($useremail)) {
      $userdata = get_user_details($useremail);
      $form['#prefix'] ='<div id="forgotpass-wrapper" class="user-wrapper">';
      $form['#suffix'] = '</div>';

      $form['help'] = [
        '#type' => 'item',
        '#markup' => '<div id="user_verification"><div id="user_picture" class="row"><div class="col-xs-12 col-sm-2 col-md-2 col-lg-2"><img src="'.$userdata['profile_image'].'" class="avatar" alt="Profile Image"></div><div class="col-xs-12 col-sm-10 col-md-10 col-lg-10"><span class="user_name">'.$userdata['name'].'</span><br/><span class="user_email">'.$useremail.'</span></div></div></div>',
      ];
      
      $form['user_id'] = [
        '#type' => 'hidden',
        '#default_value' => $userdata['uid'],
      ];
      
      $form['pass_fields'] =[
        '#type' => 'password_confirm',
        '#size' => 25,
        '#description' => t('Your password must adhere to the following rules:<br>
        <ul>
          <li>8 characters</li>
          <li>Must contain each of the following:
            <ul><li>1 Upper Case Letter</li>
              <li>1 Lower Case Letter</li>
              <li>One Number</li>
              <li>1 Special Character (!@#$%^&*)</li>
            </ul>
          </li>
        </ul>'),
        '#size' => 32,
        '#attributes' => [
          'class' => ['SK-field__input SK-field__input--text']
        ],
        '#required' => FALSE,
      ];
      
      $form['agree'] = [
        '#type' => 'checkbox',
        '#title' => t('I Agree'),
        '#required' => FALSE,
      ];
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames() {
    return [
      'pass_fields', 'agree',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsValidators() {
    return [
      'pass_fields' => [
        new ValidatorRequired("Tell me where I can find your LinkedIn please."),
      ],
      
      'agree' => [
        new ValidatorRequired("Tell me where I can find your LinkedIn please."),
      ],
    ];
  }

}
