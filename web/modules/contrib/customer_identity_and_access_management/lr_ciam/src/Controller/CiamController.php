<?php

/**
 * @file
 * Contains \Drupal\lr_ciam\Controller\CiamController.
 */

namespace Drupal\lr_ciam\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Access\AccessResult;
use \LoginRadiusSDK\Utility\Functions;
use \LoginRadiusSDK\LoginRadiusException;
use \LoginRadiusSDK\Clients\IHttpClient;
use \LoginRadiusSDK\Clients\DefaultHttpClient;
use \LoginRadiusSDK\CustomerRegistration\Authentication\UserAPI;
use LoginRadiusSDK\CustomerRegistration\Management\AccountAPI;
use LoginRadiusSDK\Advance\CloudAPI;


/**
 * Returns responses for Social Login module routes.
 */
class CiamController extends ControllerBase {

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
            $container->get('lr_ciam.user_manager')     
        );
    }  

    /**
     * Return change password form
     *
     * Handle token and validate the user.
     *
     */
    public function userChangePassword($user) {
        $post_value = $_POST;
        $config = \Drupal::config('lr_ciam.settings');
        $apiKey = $config->get('api_key');
        $apiSecret = $config->get('api_secret');
     
        if (isset($post_value['setpasswordsubmit']) && $post_value['setpasswordsubmit'] == 'submit') {
            
            if (isset($post_value['setnewpassword']) && !empty($post_value['setnewpassword']) && isset($post_value['setconfirmpassword']) && !empty($post_value['setconfirmpassword'])) {
              
                if ($post_value['setnewpassword'] == $post_value['setconfirmpassword']) {
                    
                    try {
                        $accountObject = new AccountAPI($apiKey, $apiSecret, array('output_format' => 'json'));
                        $result = $accountObject->setPassword($_SESSION['_sf2_attributes']['user_profile_uid'], $post_value['setnewpassword']);
                        if (isset($result) && $result) {
                            drupal_set_message(t('Password set successfully.'));
                        }
                    }
                    catch (LoginRadiusException $e) {
                        \Drupal::logger('ciam')->error($e);
                        drupal_set_message($e->getMessage(), 'error');                        
                    }
                }
                else {                    
                    drupal_set_message('The Confirm Password field does not match the Password field.', 'error');
                }
            }
            else {                
                drupal_set_message('The password and confirm password fields are required.', 'error');
            }
        }
        
          try {
              $userObject = new UserAPI($apiKey, $apiSecret, array('output_format' => 'json')); 
              $userprofile = $userObject->getProfile($_SESSION['_sf2_attributes']['access_token'], 'Password');                 
          }
          catch (LoginRadiusException $e) {                
              \Drupal::logger('ciam')->error($e);                 
          }
          
          
        if (isset($userprofile->Password) && $userprofile->Password != '') {
            $output = array(
              '#title' => t('Change Password'),
              '#theme' => 'change_password',
              '#attributes' => array('class' => array('change-password'))
            );            
        } else {
            $output = array(
              '#title' => t('Set Password'),
              '#theme' => 'set_password',
              '#attributes' => array('class' => array('set-password'))
            );          
        }
        return $output;
    }
    
    /**
     * Show change password form
     *        
     */
   public function changePasswordAccess() {
        $config = \Drupal::config('lr_ciam.settings');
        $user = \Drupal::currentUser()->getRoles();
        $access_granted = in_array("administrator", $user);
        $apiKey = $config->get('api_key');
        $apiSecret = $config->get('api_secret');
        try {
            $cloudObject = new CloudAPI($apiKey, $apiSecret, array('output_format' => 'json'));
            $configData = $cloudObject->getConfigurationList();               
        }
        catch (LoginRadiusException $e) {
            \Drupal::logger('ciam')->error($e);
        }
        $optionVal = isset($configData->EmailVerificationFlow)? $configData->EmailVerificationFlow : '';  
       
        if ($access_granted) {
            return AccessResult::forbidden();
        }
        
        else if ($optionVal === 'required' || $optionVal === 'disabled') {
            if (isset($_SESSION['_sf2_attributes']['provider']) && $_SESSION['_sf2_attributes']['provider'] == 'Email') {
                return AccessResult::allowed();
            }
            else {
                return AccessResult::forbidden();
            }
        }
        elseif ($optionVal === 'optional') {
            if (isset($_SESSION['_sf2_attributes']['provider']) && $_SESSION['_sf2_attributes']['provider'] == 'Email' || isset($_SESSION['emailVerified']) && $_SESSION['emailVerified']) {
                return AccessResult::allowed();
            }
            else {
                return AccessResult::forbidden();
            }
        } 
        return AccessResult::forbidden();
    }
    
    /**
     * Response for path 'user/login'
     *
     * Handle token and validate the user.
     *
     */
    public function userRegisterValidate() {
        $config = \Drupal::config('lr_ciam.settings');
        if (isset($_GET['action_completed']) && $_GET['action_completed'] == 'register') {
            drupal_set_message('Email for verification has been sent to your provided email id, check email for further instructions');
            return $this->redirect("<front>");
        }

        if (isset($_GET['action_completed']) && $_GET['action_completed'] == 'forgotpassword') {
            drupal_set_message('Password reset information sent to your provided email id, check email for further instructions');
            return $this->redirect("<front>");
        }  

        $request_token = isset($_REQUEST['token']) ? trim($_REQUEST['token']) : '';        
        if (isset($_REQUEST['token'])) {     
            $apiKey = trim($config->get('api_key'));
            $apiSecret = trim($config->get('api_secret'));
            $userObject = new UserAPI($apiKey, $apiSecret, array('output_format' => 'json')); 
            \Drupal::service('session')->set('access_token', $request_token);     
            
              //Get Userprofile form Access Token.
            try {
                $userprofile = $userObject->getProfile($request_token);
                $userprofile->widget_token = $request_token;
                \Drupal::service('session')->set('user_profile_uid', $userprofile->Uid);     
                \Drupal::service('session')->set('user_profile_data', $userprofile);     
            }
            catch (LoginRadiusException $e) {                
                \Drupal::logger('ciam')->error($e); 
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
             \Drupal::service('session')->set('phoneId', isset($userprofile->PhoneId) ? $userprofile->PhoneId : '');  
            // Advanced module LR Code Hook End.
            if (\Drupal::currentUser()->isAnonymous()) {           
                if (isset($userprofile) && isset($userprofile->ID) && $userprofile->ID != '') {
                    $userprofile = $this->user_manager->getUserData($userprofile);
                    $_SESSION['user_verify'] = 0;

                    if (empty($userprofile->Email_value)) {

                        $uid = $this->user_manager->checkProviderID($userprofile->ID);

                        if ($uid) {
                            $drupal_user = User::load($uid);
                        }
                        
                        if (isset($drupal_user) && $drupal_user->id()) {
                            return $this->user_manager->provideLogin($drupal_user, $userprofile);
                        }
                    }
                    return $this->user_manager->checkExistingUser($userprofile);
                }
            } else {
               return $this->user_manager->handleUserCallback($userprofile);
            }
        }
        else {  
            return $this->redirect('user.login');
        }
    }
}
