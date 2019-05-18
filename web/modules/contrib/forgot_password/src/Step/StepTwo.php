<?php
namespace Drupal\forgot_password\Step;

use Drupal\forgot_password\Button\StepTwoNextButton;
use Drupal\forgot_password\Button\StepTwoPreviousButton;
use Drupal\forgot_password\Validator\ValidatorStepTwo;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class StepTwo.
 *
 * @package Drupal\forgot_password\Step
 */
class StepTwo extends BaseStep {

  /**
   * {@inheritdoc}
   */
  protected function setStep() {
    return StepsEnum::STEP_TWO;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      new StepTwoPreviousButton(),
      new StepTwoNextButton(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildStepFormElements() {
    $config = \Drupal::config('skipta_core.community_variable_settings');
    $info_emailid = $config->get('info_emailid');
    $useremail = $_SESSION['forgot_password']['useremail'];
    $verify_msg = "A verification code has been sent to $useremail";
    $verification = '<div id="step1-identify" class="row resetpass-steps"><div class="col-xs-12 col-sm-4 col-md-4 col-lg-4 steps">IDENTIFICATION</div>
    <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4 active steps">VERIFICATION</div><div class="col-xs-12 col-sm-4 col-md-4 col-lg-4 steps">RESET PASSWORD</div></div>';
    $form['verification'] = [
      '#type' => 'item',
      '#markup' => t($verification),
    ];
	
    $userdata = get_user_details($useremail);
    $form['#prefix'] ='<div id="forgotpass-wrapper" class="user-wrapper">';
    $form['#suffix'] = '</div>';

    $form['help'] = [
      '#type' => 'item',
      '#markup' => '<div id="user_verification"><div id="user_picture" class="row"><div class="col-xs-12 col-sm-2 col-md-2 col-lg-2"><img src="'.$userdata['profile_image'].'" class="avatar" alt="Profile Image"></div><div class="col-xs-12 col-sm-10 col-md-10 col-lg-10"><span class="user_name">'.$userdata['name'].'</span><br/><span class="user_email">'.$useremail.'</span></div></div></div>',
    ];
    
    $form['help1'] = [
      '#type' => 'item',
      '#markup' => t('How would you like to reset your password?'),
    ];
    
    /*if($userdata['security_question'] <> NULL) {
      $form['security_verification'] = [
        '#type' => 'details',
        '#title' => t('Answer Security Question'),
        '#open' => TRUE,
      ];
    
      $form['security_verification']['user_securityquestion'] = [
        '#type' => 'textfield',
        '#title' => $userdata['security_question'],
        '#required' => FALSE,
        '#attributes' => [
          'autocomplete' => 'off'
        ],
        '#default_value' => isset($this->getValues()['user_securityquestion']) ? $this->getValues()['user_securityquestion'] : NULL,
      ];
      
      $form['security_verification']['user_securityanswer'] = [
        '#type' => 'hidden',
        '#default_value' => $userdata['security_answer'],
      ];
    }*/
      
    /*if($userdata['smobile_no'] <> NULL) {
      $form['mobile_verification'] = [
        '#type' => 'details',
        '#title' => t('Receive verification code by text message to (***)***-'.$userdata['smobile_no']),
        '#description' => t('You will receive a 4 digit verification code that can be used to reset your password immediately. Please enter 4 digit verification code.'),
        '#open' => FALSE,
      ];
      
      $form['mobile_verification']['actions']['resend'] = [
        '#type' => 'button',
        '#value' => t('Resend'),
        '#name' => 'resend_otp',
        '#attributes' => [
          'class' => [
            'use-ajax-submit',
            'btn',
            'btn-gray'
          ],
        ],
        '#ajax' => [
          'callback' => '::resendOTPAjaxCallback'
        ]
      ];
      
      $form['mobile_verification']['mverification_code'] = [
        '#type' => 'number',
        'name' => 'mverification_code',
        '#title' => t('PLEASE ENTER YOUR 4-DIGIT VERIFICATION CODE'),
        '#required' => FALSE,
        '#default_value' => isset($this->getValues()['mverification_code']) ? $this->getValues()['mverification_code'] : NULL,
      ];
    }*/
      
    if($useremail <> NULL) {
      $form['email_verification'] = [
        '#type' => 'details',
        '#title' => t('Receive verification code by email'),
        '#description' => t($verify_msg),
        '#open' => FALSE,
      ];
      
      $form['email_verification']['everification_email'] = [
        '#type' => 'radios',
        '#options' => array(
          'pemail' => t($useremail),
        ),
        '#ajax' => [
          'callback' => '::resendEmailOTPAjaxCallback',
          'event' => 'change',
        ],
      ];
    
      $form['email_verification']['everification_code'] = [
        '#type' => 'number',
        'name' => 'everification_code',
        '#title' => t('PLEASE ENTER YOUR 6-DIGIT VERIFICATION CODE'),
        '#required' => FALSE,
        '#default_value' => isset($this->getValues()['everification_code']) ? $this->getValues()['everification_code'] : NULL,
      ];
    }

    return $form;
  }

	/**
     * {@inheritdoc}
    */
	public function getFieldNames() {
		return [
			'everification_code',
		];
	}

	/**
     * {@inheritdoc}
    */
	public function getFieldsValidators() {
		return [
			/*'user_securityquestion' => [
				new ValidatorStepTwo("Incorrect Security Answer."),
			],
			'mverification_code' => [
				new ValidatorStepTwo("Incorrect/Expired OTP."),
			],*/
			'everification_code' => [
				new ValidatorStepTwo("Incorrect OTP"),
			],
		];
	}
}