<?php
/**
 * @file
 * Contains \Drupal\forgot_password\Controller\ForgotPasswordFormController
 */

namespace Drupal\forgot_password\Controller;

use Drupal\Core\Controller\ControllerBase;

class ForgotPasswordFormController extends ControllerBase {
	/*public static function forgot_password_otp($mobile, $otp) {
    $config = \Drupal::config('twilio_settings');
    $sid = $config->get('Twilio_Account_SID');
    $token = $config->get('Twilio_Auth_Token');
    $twilio_number = $config->get('Phone_Number');
    $from = $twilio_number;
    
		$to = '+1'.$mobile;
    $body = 'Your verification code is '.$otp;
		$uri = 'https://api.twilio.com/2010-04-01/Accounts/'.$sid.'/Messages.json';
    $client = \Drupal::httpClient();
		$request = $client->request('POST', $uri, ['auth' => [$sid, $token], 'form_params' => ['To' => $to, 'From' => $from, 'Body' => $body]]);
		$response = $request->getBody();
		return $response;
	}*/
	
	public static function forgot_password_emailotp($email, $otp) {
		$objMarketo = new MarketoAPIController();
		$config = \Drupal::config('skipta_core.marketo_templete_variables');
		$programName = $config->get('programName');
		$campaignName = $config->get('password_reset_campaignName');
		$objMarketo->programName = $programName;
		$objMarketo->campaignName = $campaignName;
		$objMarketo->email = $email;
		$objMarketo->customToken = $otp;

		$res = $objMarketo->sendEmail();
		
		if ($res !== true) {
			drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
		}
		else {
			drupal_set_message(t('Your message has been sent.'));
		}
	}
}