<?php

/**
 * @file
 * Contains \Drupal\sociallogin\Controller\SocialLoginController.
 */
namespace Drupal\sociallogin\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use LoginRadiusSDK\LoginRadius;
use LoginRadiusSDK\LoginRadiusException;
use LoginRadiusSDK\SocialLogin\SocialLoginAPI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Url;


module_load_include('php', 'sociallogin', 'customhttpclient');
global $apiClient_class;
$apiClient_class = 'CustomHttpClient';
/**
 * Returns responses for Social Login module routes.
 */
class SocialLoginController extends ControllerBase {

  protected $user_manager;
  protected $connection;

  public function __construct($user_manager) {
    $this->user_manager = $user_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sociallogin.user_manager')
    );
  }

  /**
   * Response for path 'user/sociallogin'
   *
   * Handle token and validate the user.
   *
   */
  public function userRegisterValidate() {    
    $config = \Drupal::config('sociallogin.settings');
    
  if(isset($_GET['action_completed']) && $_GET['action_completed'] == 'register'){    
    drupal_set_message('Email for verification has been sent to your provided email id, check email for further instructions');
    return $this->redirect("<front>");         
  }
  
  if(isset($_GET['action_completed']) && $_GET['action_completed'] == 'forgotpassword') {
    drupal_set_message('Password reset information sent to your provided email id, check email for further instructions');
    return $this->redirect("<front>");
  }
  
    // handle email popup.
    if (isset($_POST['lr_emailclick'])) {
      return $this->user_manager->emailPopupSubmit();
    }
    //clear session of loginradius data when email popup cancel.
    elseif (isset($_POST['lr_emailclick_cancel'])) {
      unset($_SESSION['lrdata']);
      return $this->redirect('<current>');
    }
    elseif (isset($_REQUEST['token'])) {
      $apiSecret = trim($config->get('api_secret'));
      $apiKey = trim($config->get('api_key'));

      try {
        $socialLoginObj = new SocialLoginAPI($apiKey, $apiSecret, array(
          'output_format' => TRUE,
          'authentication' => FALSE
        ));
      }
      catch (LoginRadiusException $e) {
        \Drupal::logger('sociallogin')->error($e);
        drupal_set_message($e->getMessage(), 'error');
        return $this->redirect('user.login');
      }
    
      //Get Access token.
      try {      
        $result_accesstoken = $socialLoginObj->exchangeAccessToken(trim($_REQUEST['token']));
      }
      catch (LoginRadiusException $e) {
        \Drupal::logger('sociallogin')->error($e);
        drupal_set_message($e->getMessage(), 'error');
        return $this->redirect('user.login');
      }

      //Get Userprofile form Access Token.
      try {
        $userprofile = $socialLoginObj->getUserProfiledata($result_accesstoken->access_token); 
        $userprofile->widget_token = $result_accesstoken->access_token;
      }
      catch (LoginRadiusException $e) {
        \Drupal::logger('sociallogin')->error($e);
        drupal_set_message($e->getMessage(), 'error');
        return $this->redirect('user.login');
      }
  // Advanced module LR Code Hook Start.
  // Make sure at least one module implements our hook.
  if (count(\Drupal::moduleHandler()->getImplementations('add_loginradius_userdata')) > 0) {
    // Call all modules that implement the hook, and let them.
    // Make changes to $variables.
    $result = \Drupal::moduleHandler()->invokeAll('add_loginradius_userdata', [$userprofile, $userprofile->widget_token]);
    $value = end($result);
    if (!empty($value)) {
      $userprofile = $value;
    }
  }

  // Advanced module LR Code Hook End.
      if (\Drupal::currentUser()->isAnonymous()) {

        if (isset($userprofile) && isset($userprofile->ID) && $userprofile->ID != '') {
          $userprofile = $this->user_manager->getUserData($userprofile);
          $_SESSION['user_verify'] = 0;

          if ($config->get('email_required') == 1 && empty($userprofile->Email_value)) {   
           
            $uid = $this->user_manager->checkProviderID($userprofile->ID);

            if ($uid) {
              $drupal_user = User::load($uid);
            }

            if (isset($drupal_user) && $drupal_user->id()) {              
              return $this->user_manager->provideLogin($drupal_user, $userprofile);
            }
            else {               
              $_SESSION['lrdata'] = $userprofile;
              $text_email_popup = $config->get('popup_status');             
       
              $popup_params = array(
                'msg' => $this->t($text_email_popup, array('@provider' => t($userprofile->Provider))),
                'provider' => $userprofile->Provider,
                'msgtype' => 'status',
              );
              $popup_params['message_title'] = $config->get('popup_title');
              return $form['email_popup'] = $this->user_manager->getPopupForm($popup_params);
            }
          }   
          return $this->user_manager->checkExistingUser($userprofile);
        }
      }  
      else {          
        return $this->user_manager->handleAccountLinking($userprofile);
      }
    }
    else {    
      return $this->redirect('user.login');
    }
  }

  /**
   * Delete Social account of user.
   *
   * @param int $uid
   * @param int $pid
   * @param string $provider
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function userSocialAccountDelete($uid = 0, $pid = 0, $provider = '') {  
    global $base_url;
    $user = \Drupal::currentUser();
        //Advanced module LR Code Hook Start
  if (count(\Drupal::moduleHandler()->getImplementations('add_user_delete_form_submit')) > 0) {
    // Call all modules that implement the hook, and let them make changes to $variables.
    \Drupal::moduleHandler()->invokeAll('add_user_delete_form_submit', array($uid, $provider, $pid));
  }
  //Advanced module LR Code Hook End
    $query = $this->user_manager->deleteSocialAccount($pid);

    if ($query) {
      drupal_set_message(t('Your social login identity for %provider successfully deleted.', array('%provider' => $provider)));
    }
    else {
      drupal_set_message(t('We were unable to delete the linked account.'), 'error');
    }

     $response = new RedirectResponse($base_url . '/user/'.$user->id() . '/edit');
      return $response->send();

  }
  
/**
 * Show User Profile at Dialog box
 *
 * @param type $js JS enabled
 * @param type $user_id User ID
 * @return string Conatin user profile data in renderable html form
 */
public function lrDebugLog() {  
    $lrLog = db_query("SELECT * FROM {loginradius_log} ORDER BY log_id DESC LIMIT 20");
    $lrLog = $lrLog->fetchAll(); 

    if(isset($lrLog) && $lrLog){
    $output['logs_key'] = $lrLog[0];
    }
    $output['loginradius_logs'] = $lrLog;    
    $output = json_decode(json_encode($output), true);
    return [
      '#theme' => 'lr_debug_log',
      '#output' => $output,        
    ];
}

public function lrClearLog(){  
     db_delete('loginradius_log')      
        ->execute();      
     $response = new RedirectResponse('logs');
     return $response->send();       
  }
}
