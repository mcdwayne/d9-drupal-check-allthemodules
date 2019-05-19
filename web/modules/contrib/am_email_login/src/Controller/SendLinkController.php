<?php

namespace Drupal\am_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SendLinkController extends ControllerBase {

  public function sendMail($user,$link,$user_mail) {

  	// Initialize MailManager
         $mailManager = \Drupal::service('plugin.manager.mail');
         $module = 'am_registration';
         $key = 'send_login_link';
         $to = $user_mail;
         $params = array();
    
     // Initialize token object
         $data = array('user' => $user);
         $token_service = \Drupal::token();

         // Create am_registration config object.
         $config = \Drupal::config('am_registration.settings');

         // Prepare Body and Subject
         $body_message = $token_service->replace($config->get('body'),$data);
         $body_message = str_replace("[user:one-time-login-url]",$link,$body_message);
         $subject = $token_service->replace($config->get('subject'),$data);

         $params['body'] = $body_message;
         $params['subject'] = $subject;
         //echo "<pre>";print_r($params);die("sdf");
         $langcode = \Drupal::currentUser()->getPreferredLangcode();
         $send = true;
         $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
         if ($result['result'] !== true) {
           drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
           return new RedirectResponse('/user/login');
         }

    return $result;
  }
}