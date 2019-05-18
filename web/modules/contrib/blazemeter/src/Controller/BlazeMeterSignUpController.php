<?php


namespace Drupal\blazemeter\Controller;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp;

class BlazeMeterSignUpController extends ControllerBase {
	
  private $blazemeter_url = 'https://a.blazemeter.com';

  public function signUp(){
    $config = \Drupal::configFactory()->getEditable('blazemeter.settings');
    $first_name = \Drupal::request()->request->get('first_name');
    $last_name = \Drupal::request()->request->get('last_name');
    $email = \Drupal::request()->request->get('email');
    $password = \Drupal::request()->request->get('password');
    if($first_name == ''){
      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new InvokeCommand(
        '.reg-error-message',
        'show'
      ));
      $ajax_response->addCommand(new ReplaceCommand(
        '.reg-error-message',
        '<div class="reg-error-message"><p>Enter first name</p></div>'
      ));
      return $ajax_response;
    }
    if($last_name == ''){
      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new InvokeCommand(
        '.reg-error-message',
        'show'
      ));
      $ajax_response->addCommand(new ReplaceCommand(
        '.reg-error-message',
        '<div class="reg-error-message"><p>Enter last name</p></div>'
      ));
      return $ajax_response;
    }
    if($email == ''){
      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new InvokeCommand(
        '.reg-error-message',
        'show'
      ));
      $ajax_response->addCommand(new ReplaceCommand(
        '.reg-error-message',
        '<div class="reg-error-message"><p>Enter email</p></div>'
      ));
      return $ajax_response;
    }
    if($password == ''){
      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new InvokeCommand(
        '.reg-error-message',
        'show'
      ));
      $ajax_response->addCommand(new ReplaceCommand(
        '.reg-error-message',
        '<div class="reg-error-message"><p>Enter password</p></div>'
      ));
      return $ajax_response;
    }
    $client = new GuzzleHttp\Client();
    try{
      $response = $client->post($this->blazemeter_url . '/api/latest/user/register',
        array(
          "json" => [
            'email' => $email,
            'password' => $password,
            'firstName' => $first_name,
            'lastName' => $last_name,
          ]));
    }

    catch(GuzzleHttp\Exception\BadResponseException $e){
      $response_body = json_decode($e->getResponse()->getBody());
      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new InvokeCommand(
        '.reg-error-message',
        'show'
      ));
      $ajax_response->addCommand(new ReplaceCommand(
        '.reg-error-message',
        '<div class="reg-error-message"><p>' . $response_body->error->message . '</p></div>'
      ));
      return $ajax_response;
    }
    $response_body = json_decode($response->getBody(), TRUE);
    $api_key = $response_body['result']['user']['apiKey'];
    $config->set('user_key', $api_key)->save();
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new CloseDialogCommand());
    $ajax_response->addCommand(new InvokeCommand(
      '#edit-userkey',
      'val',
      array($api_key)
    ));
    $ajax_response->addCommand(new InvokeCommand(
      '#edit-signup',
      'hide'
    ));
    $ajax_response->addCommand(new InvokeCommand(
      '#edit-login',
      'hide'
    ));
    return $ajax_response;
  }

  public function authAutocomplete($string){
  }
}