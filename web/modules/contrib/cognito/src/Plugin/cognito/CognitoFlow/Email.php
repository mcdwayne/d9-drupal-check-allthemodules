<?php

namespace Drupal\cognito\Plugin\cognito\CognitoFlow;

/**
 * The email registration cognito flow.
 *
 * @CognitoFlow(
 *   id = "cognitoflow_email",
 *   label = @Translation("Email"),
 *   forms = {
 *     "profile" = "\Drupal\cognito\Form\Email\ProfileForm",
 *     "signup" = "\Drupal\cognito\Form\Email\RegisterForm",
 *     "admin_signup" = "\Drupal\cognito\Form\Email\AdminRegisterForm",
 *     "login" = "\Drupal\cognito\Form\Email\UserLoginForm",
 *     "password_reset" = "\Drupal\cognito\Form\Email\PassResetForm",
 *   },
 *   challenges = {
 *     "NEW_PASSWORD_REQUIRED" = {
 *       "route" = "cognito.challenge.new_password"
 *     }
 *   }
 * )
 */
class Email extends CognitoFlowBase {

  /**
   * {@inheritdoc}
   */
  public function getSetupInstructions() {
    return $this->t('Ensure you select email and not username when creating your user pool. This cannot be changed later.');
  }

}
