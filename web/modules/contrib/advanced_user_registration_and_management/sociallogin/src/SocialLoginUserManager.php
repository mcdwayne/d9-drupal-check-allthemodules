<?php

/**
 * @file
 */
namespace Drupal\sociallogin;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use \LoginRadiusSDK\LoginRadiusException;
use \LoginRadiusSDK\CustomerRegistration\AccountAPI;


/**
 * Returns responses for Simple FB Connect module routes.
 */
class SocialLoginUserManager {

  public $module_config;
  protected $connection;

  public function __construct() {
    $this->connection = \Drupal\Core\Database\Database::getConnection();
    $this->module_config = \Drupal::config('sociallogin.settings');
    $this->apiSecret = trim($this->module_config->get('api_secret'));
    $this->apiKey = trim($this->module_config->get('api_key'));
  }

  public function getUserData($userprofile) {
    $userprofile->Email_value = (sizeof($userprofile->Email) > 0 ? $userprofile->Email[0]->Value : '');
    $userprofile->Company_name = (isset($userprofile->Company->Name) ? $userprofile->Company->Name : '');
    $userprofile->Country_name = (isset($userprofile->Country->Name) ? $userprofile->Country->Name : '');
    $userprofile->PhoneNumber = (isset($userprofile->PhoneNumbers) && sizeof($userprofile->PhoneNumbers) > 0 ? $userprofile->PhoneNumbers[0]->PhoneNumber : '');
    return $userprofile;
  }
  
  public function insertSocialData($user_id, $provider_id, $provider) {  
    $this->connection->insert('loginradius_mapusers')
      ->fields(array(
        'user_id' => $user_id,
        'provider' => $provider,
        'provider_id' => $provider_id,
      ))
      ->execute();
  }

  public function getUserByEmail($email) {
    return user_load_by_mail($email);
  }

  public function removeUnescapedChar($str) {
    $in_str = str_replace(array(
      '<',
      '>',
      '&',
      '{',
      '}',
      '*',
      '/',
      '(',
      '[',
      ']',
      '!',
      ')',
      '&',
      '*',
      '#',
      '$',
      '%',
      '^',
      '|',
      '?',
      '+',
      '=',
      '"',
      ','
    ), array(''), $str);
    $cur_encoding = mb_detect_encoding($in_str);

    if ($cur_encoding == "UTF-8" && mb_check_encoding($in_str, "UTF-8")) {
      return $in_str;
    }
    else {
      return utf8_encode($in_str);
    }
  }

  public function getUsername($userprofile) {
    if (!empty($userprofile->FullName)) {
      $username = $userprofile->FullName;
    }
    elseif (!empty($userprofile->ProfileName)) {
      $username = $userprofile->ProfileName;
    }
    elseif (!empty($userprofile->NickName)) {
      $username = $userprofile->NickName;
    }
    elseif (!empty($userprofile->Email_value)) {
      $user_name = explode('@', $userprofile->Email_value);
      $username = $user_name[0];
    }
    else {
      $username = $userprofile->ID;
    }
    return $username;
  }

  /**
   * Get username from user profile data
   *
   * @param object $userprofile User profile information
   * @return string Username of user
   */
  public function usernameOption($userprofile) {
    $option = $this->module_config->get('username_option');
    $enableUname = $this->module_config->get('raas_enable_user_name');
    $emailVerifyOption = $this->module_config->get('raas_email_verification_condition');
    if(isset($emailVerifyOption) && $emailVerifyOption == '1') {
        $enableUname = 'false';
    }
    if(isset($enableUname) && $enableUname == 'true') {    
        try {            
        $apiKey = $this->module_config->get('api_key');    
        $apiSecret = $this->module_config->get('api_secret');         
        
        $accountObj = new AccountAPI($apiKey, $apiSecret, array('output_format' => 'json'));           
        
                $returndata = $accountObj->getAccounts($userprofile->Uid);          
                $username = '';
                foreach($returndata as $key=>$value){
                if($value->Provider == 'RAAS' && $value->UserName != ''){
                   $username = $value->UserName;
                  }                
                } 
                
                } catch (LoginRadiusException $e) {              
                    $msg = isset($e->getErrorResponse()->description) ? $e->getErrorResponse()->description : 'Password is not set';
                    drupal_set_message(t($msg), 'error');
                 }
    } else if (!empty($userprofile->FirstName) && !empty($userprofile->LastName) && $option != 2) {
      if ($option == 1) {
        $username = $userprofile->FirstName . '-' . $userprofile->LastName;
      }
      else {
        $username = $userprofile->FirstName . ' ' . $userprofile->LastName;
      }
    }
    elseif ($option == 2 && !empty($userprofile->Email_value)) {
      $username = $userprofile->Email_value;
    }
    else {
      $username = $this->getUsername($userprofile);
    }
    return $username;
  }

  public function checkExistUsername($userprofile) {
    $user_name = $this->usernameOption($userprofile);    
    $index = 0;

    while (TRUE) {
      if (user_load_by_name($user_name)) {
        $index++;
        $user_name = $user_name . $index;
      }
      else {
        break;
      }
    }

    $data['username'] = $this->removeUnescapedChar($user_name);
    $data['fname'] = (!empty($userprofile->FirstName) ? $userprofile->FirstName : '');
    $data['lname'] = (!empty($userprofile->LastName) ? $userprofile->LastName : '');

    if (empty($data['fname'])) {
      $data['fname'] = $this->getUsername($userprofile);
    }

    if (empty($data['lname'])) {
      $data['lname'] = $this->getUsername($userprofile);
    }

    return $data;
  }

  public function provideLogin($new_user, $userprofile, $status = FALSE) { 
           
      $config = \Drupal::config('sociallogin.settings');
      $apiSecret = trim($config->get('api_secret'));
      $apiKey = trim($config->get('api_key'));
      $accountObj = new AccountAPI($apiKey, $apiSecret, array('output_format' => 'json'));
      try{
          $result = $accountObj->getAccounts($userprofile->Uid);
        }
        catch (LoginRadiusException $e){
        watchdog_exception('type', $e);
        }
     
        if(isset($result) && !empty($result)){  
    
          foreach($result as $value){            
           if(is_array($value) || is_object($value)){   
              $check_aid = db_query("SELECT user_id FROM {loginradius_mapusers} WHERE user_id = :uid AND provider_id = :providerid", array(
      ':uid' => $new_user->id(),
      ':providerid' => $value->ID,
    ))
      ->fetchField(); 
          
             if(isset($check_aid) && !$check_aid){ 
              $this->insertSocialData($new_user->id(), $value->ID, $value->Provider);             
             }
           }
          }
        }     

    $_SESSION['spd_userprofile'] = $userprofile;
 
    if ($new_user->isActive() && $new_user->getLastLoginTime() != 0) {      
      $url = '';
      $isNew = FALSE;
      
      if ($userprofile->FirstLogin) {         
        $url = 'register_redirection';
      }

      if ($this->module_config->get('update_user_profile') == 1 && !$new_user->isNew()) {
        $this->field_create_user_object($new_user, $userprofile);
        $new_user->save();
  
        $this->downloadProfilePic($userprofile->ImageUrl, $userprofile->ID, $new_user);
      }
      
      \Drupal::service('session')->migrate();
      \Drupal::service('session')->set('lrID', $userprofile->ID);
      \Drupal::service('session')->set('provide_name', $userprofile->Provider);
      $_SESSION['emailVerified'] = false;
      if (isset($userprofile->EmailVerified)) {
        $_SESSION['emailVerified'] = $userprofile->EmailVerified;
      }
      
        if (\Drupal::moduleHandler()->moduleExists('userregistration')) {
                $user_name = $this->usernameOption($userprofile);
                $user_manager = \Drupal::service('userregistration.user_manager'); 
                $dbuname = $user_manager->userregistration_get_raas_uname($new_user->id());
                if(isset($dbuname) && $dbuname != ''){
                    
                if (isset($user_name) && $user_name != '' && $dbuname != $user_name) {                      
                    try {
                          $this->connection->update('users_field_data')
                            ->fields(array('name' => $dbuname))
                            ->condition('uid', $new_user->id())
                            ->execute();     
                    } catch (Exception $e) {
                        
                      }
                    }               
              }
            }

        user_login_finalize($new_user);
      if ($status) {
        drupal_set_message(t('You are now logged in as %username.', array('%username' => $new_user->getUsername())));
      }
      else {
        drupal_set_message(t('You are now logged in as %username.', array('%username' => $new_user->getUsername())));
      }
      
      return $this->redirectUser($url);
    }
    else {
      drupal_set_message(t('You are either blocked, or have not activated your account. Please check your email.'), 'error');
      return new RedirectResponse(Url::fromRoute('<front>')->toString());
    }
  }
  
  public function checkProviderID($pid) {
    return $this->connection->query("SELECT am.uid FROM {users} am INNER JOIN {loginradius_mapusers} sm ON am.uid = sm.user_id WHERE  sm.provider_id = :provider_id", array(
      ':provider_id' => $pid,
    ))
      ->fetchField();
  }

  public function getMappedAccounts($uid) {
    return $this->connection->query("SELECT am.uid, sm.provider, sm.provider_id FROM {users} am INNER JOIN {loginradius_mapusers} sm ON am.uid = sm.user_id WHERE am.uid = :uid", array(
      ':uid' => $uid
    ))
       ->fetchAll();
  }

  public function deleteSocialAccount($pid) {
    return $this->connection->delete('loginradius_mapusers')
      ->condition('provider_id', $pid)
      ->execute();
  }
  public function deleteMapUser($uid) {
    return $this->connection->delete('loginradius_mapusers')
      ->condition('user_id', $uid)
      ->execute();
  }

  public function redirectUser($variable_path = '') {

    $user = \Drupal::currentUser();
    $config = \Drupal::config('hostedpage.settings');
      //Advanced module LR Code Hook Start
  // Make sure at least one module implements our hook.
  if (count(\Drupal::moduleHandler()->getImplementations('before_user_redirect')) > 0) {
    // Call all modules that implement the hook, and let them make changes to $variables.
    $use_data = array('userprofile' => $userprofile, 'form' => $form, 'account' => $account);

    $data = \Drupal::moduleHandler()->invokeAll('before_user_redirect', $use_data);
    if (!empty($data) && $data != 'true') {
      return $data;
    }
  }
  //Advanced module LR Code Hook End
    $variable_path = (!empty($variable_path) ? $variable_path : 'login_redirection');
    $variable_custom_path = (($variable_path == 'login_redirection') ? 'custom_login_url' : 'custom_register_url');
    $request_uri = \Drupal::request()->getRequestUri();
    
    if ($this->module_config->get($variable_path) == 1) {  
      // Redirect to profile.
      $response = new RedirectResponse($user->id() . '/edit');
      return $response->send();
    }
    elseif ($this->module_config->get($variable_path) == 2) {      
      // Redirect to custom page.
      $custom_url = $this->module_config->get($variable_custom_path);

      if (!empty($custom_url)) {
        $response = new RedirectResponse($custom_url);
        return $response->send();
      }
      else {

        return new RedirectResponse(Url::fromRoute('<front>')->toString());
      }
    }
    else {     
      // Redirect to same page.
       $enablehosted = $config->get('lr_hosted_page_enable');  
       if ((isset($enablehosted) && $enablehosted == '1')) {
       return new RedirectResponse(Url::fromRoute('<front>')->toString());
      } else {
      $destination = (\Drupal::destination()->getAsArray());;
      $response = new RedirectResponse($destination['destination']);
      return $response->send();
    }}
  }

  public function downloadProfilePic($picture_url, $id, $user) {
    if (user_picture_enabled()) {
      // Make sure that we have everything we need
      if (!$picture_url || !$id) {
        return FALSE;
      }
      $picture_config = \Drupal::config('field.field.user.user.user_picture');
      $pictureDirectory = $picture_config->get('settings.file_directory');
      $data = array('user' => $user);
      $pictureDirectory = \Drupal::token()->replace($pictureDirectory, $data);
      // Check target directory from account settings and make sure it's writeable
      $directory = file_default_scheme() . '://' . $pictureDirectory;
      if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
        \Drupal::logger('sociallogin')
          ->error('Could not save profile picture. Directory is not writeable: @directory', array('@dir' => $directory));
      }
      // Download the picture. Facebook API always serves the images in jpg format.
      $destination = $directory . '/' . SafeMarkup::checkPlain($id) . '.jpg';
      $request = @file_get_contents($picture_url);
      if ($request) {
        $picture_file_data = file_save_data($request, $destination, FILE_EXISTS_REPLACE);
        $maxResolution = $picture_config->get('settings.max_resolution');
        $minResolution = $picture_config->get('settings.min_resolution');
        file_validate_image_resolution($picture_file_data, $maxResolution, $minResolution);
        $user->set('user_picture', $picture_file_data->id());
        $user->save();
        unset($_SESSION['messages']['status']);
        return TRUE;
      }

      // Something went wrong
      \Drupal::logger('sociallogin')
        ->error('Could not save profile picture. Unhandled error.');
      return FALSE;
    }
  }

  public function createUser($userprofile) {
    if (isset($userprofile->ID) && !empty($userprofile->ID)) {
      $user_config = \Drupal::config('user.settings');
      
      $user_register = $user_config->get('register');
      if ($user_register == 'visitors' || $user_register == 'visitors_admin_approval' || $this->module_config->get('admin_login') == 1) {
        $newUserStatus = 0;
        if ($user_register != 'visitors_admin_approval' && ($user_register == 'visitors' || $this->module_config->get('admin_login') == 1)) {
          $newUserStatus = 1;
        }
        $data = $this->checkExistUsername($userprofile);
        //set up the user fields
        $password = user_password(32);
        $fields = array(
          'name' => $data['username'],
          'mail' => $userprofile->Email_value,
          'init' => $userprofile->Email_value,
          'pass' => $password,
          'status' => $newUserStatus,
        );

        $new_user = User::create($fields);
    
        $this->field_create_user_object($new_user, $userprofile);
        $new_user->save();
        // Log notice and invoke Rules event if new user was succesfully created
        if ($new_user->id()) {
           
          \Drupal::logger('sociallogin')
            ->notice('New user created. Username %username, UID: %uid', array(
              '%username' => $new_user->getUsername(),
              '%uid' => $new_user->id(),
            ));
          //  return $new_user;
          $this->connection->insert('loginradius_mapusers')
            ->fields(array(
              'user_id' => $new_user->id(),
              'provider' => $userprofile->Provider,
              'provider_id' => $userprofile->ID,
            ))
            ->execute();
          $this->downloadProfilePic($userprofile->ImageUrl, $userprofile->ID, $new_user);
      
          //Advanced module LR Code Hook Start
    if (count(\Drupal::moduleHandler()->getImplementations('add_user_data_after_save')) > 0) {
      // Call all modules that implement the hook, and let them make changes to $variables.
     
    
      \Drupal::moduleHandler()->invokeAll('add_user_data_after_save',  [$new_user, $userprofile]);
    }
    //Advanced module LR Code Hook End
          $status = FALSE;
          if (($user_config->get('verify_mail') == 1 && $this->module_config->get('skip_email_verification') == 1) || !$user_config->get('verify_mail')) {
            $status = TRUE;
          }

          if ($new_user->isActive() && $status && $_SESSION['user_verify'] != 1) {
            $new_user->setLastLoginTime(REQUEST_TIME);
          }
        }
        else {
          // Something went wrong
          drupal_set_message(t('Creation of user account failed. Please contact site administrator.'), 'error');
          \Drupal::logger('sociallogin')->error('Could not create new user.');
          return FALSE;
        }
            //Advanced module LR Code Hook Start
    // Make sure at least one module implements our hook.
    if (count(\Drupal::moduleHandler()->getImplementations('check_send_verification_email')) > 0) {
      // Call all modules that implement the hook, and let them make changes to $variables.
      $userprofile->Password = $form_state['values']['pass'];
      $result = \Drupal::moduleHandler()->invokeAll('check_send_verification_email', $account, $userprofile);
      if (isset($result['lr_social_invite_message_popup'])) {
        return array('lr_social_invite_message_popup' => $result['lr_social_invite_message_popup']);
      }
      $status = end($result);

    }
    //Advanced module LR Code Hook End
        if ($new_user->isActive() && $status && $_SESSION['user_verify'] != 1) {
          if ($this->module_config->get('welcome_email') == 1) {
            $params = array(
              'account' => $new_user,
              'pass' => $password,
            );
            \Drupal::service('plugin.manager.mail')
              ->mail('sociallogin', 'welcome_email', $new_user->getEmail(), $new_user->getPreferredLangcode(), $params);

          }
      
          return $this->provideLogin($new_user, $userprofile);

        }
        elseif ($user_register != 'visitors_admin_approval' && ($new_user->isActive() || ($_SESSION['user_verify'] == 1 && $status))) {
          // Require email confirmation
          _user_mail_notify('status_activated', $new_user);
          $_SESSION['user_verify'] = 0;
          drupal_set_message(t('Once you have verified your e-mail address, you may log in via Social Login.'));
          return new RedirectResponse(Url::fromRoute('user.login')->toString());
        }
        else {
          _user_mail_notify('register_pending_approval', $new_user);
          drupal_set_message(t('Thank you for applying for an account. Your account is currently pending approval by the site administrator.<br />In the meantime, a welcome message with further instructions has been sent to your e-mail address.'));
          return new RedirectResponse(Url::fromRoute('user.login')->toString());
        }
      }
      else {
        drupal_set_message(t('Only site administrators can create new user accounts.'), 'error');

        return new RedirectResponse(Url::fromRoute('user.login')->toString());
      }
    }
  }

  public function  getRandomEmail($provider, $id) {
    $email_name = substr(str_replace(array(
      "-",
      "/",
      ".",
    ), "_", $id), -10);
    $email = $email_name . '@' . $provider . '.com';
    $account = user_load_by_mail($email);

    if ($account) {
      $id = $email_name . rand();
      $email = $this->getRandomEmail($id, $provider);
    }
    return $email;
  }

  public function checkExistingUser($userprofile) {
      
    $drupal_user = NULL;
    if (isset($userprofile->ID) && !empty($userprofile->ID)) {

      $uid = $this->connection->query("SELECT am.uid FROM {users} am INNER JOIN {loginradius_mapusers} sm ON am.uid = sm.user_id WHERE  sm.provider_id = :provider_id", array(
        ':provider_id' => $userprofile->ID,
      ))
        ->fetchField();
            //Advanced module LR Code Hook Start
    // Make sure at least one module implements our hook.
    if (count(\Drupal::moduleHandler()->getImplementations('check_raas_uid')) > 0) {
       
      // Call all modules that implement the hook, and let them make changes to $variables.
      $result = \Drupal::moduleHandler()->invokeAll('check_raas_uid', $userprofile);
      $account = end($result);
    }
    //Advanced module LR Code Hook End
      if ($uid) {
     
        $drupal_user = User::load($uid);
      }
    }
    if (!$drupal_user) {     
      if (empty($userprofile->Email_value) && $this->module_config->get('email_required') == 0) {

        $userprofile->Email_value = $this->getRandomEmail($userprofile->Provider, $userprofile->ID);
      }
      if (!empty($userprofile->Email_value)) {   
        $drupal_user = $this->getUserByEmail($userprofile->Email_value);
        if ($drupal_user) {                
          $this->insertSocialData($drupal_user->id(), $userprofile->ID, $userprofile->Provider);
        }
      }
    }

    if ($drupal_user) {            
      return $this->provideLogin($drupal_user, $userprofile, TRUE);
    }
    else {                  
      return $this->createUser($userprofile);
    }
  }

  public function handleAccountLinking($userprofile) {
    if ($userprofile->ID != '' && !\Drupal::currentUser()->isAnonymous()) {

      $user = \Drupal::currentUser();
         //Advanced module LR Code Hook Start
      if (count(\Drupal::moduleHandler()->getImplementations('add_user_identities_submit')) > 0) {
        // Call all modules that implement the hook, and let them make changes to $variables.
        \Drupal::moduleHandler()->invokeAll('add_user_identities_submit', array($userprofile));
      }
      //Advanced module LR Code Hook End
    if (!\Drupal::moduleHandler()->moduleExists('userregistration')) {
     
      $provider_id_exist = $this->connection->query("SELECT am.uid FROM {users} am INNER JOIN {loginradius_mapusers} sm ON am.uid = sm.user_id WHERE  sm.provider_id = :provider_id", array(
        ':provider_id' => $userprofile->ID,
      ))
        ->fetchField();

      if (empty($provider_id_exist) && !$provider_id_exist) {         
         $this->insertSocialData($user->id(), $userprofile->ID, $userprofile->Provider);
         drupal_set_message(t("Your account successfully mapped with this account."));         
      }
      else {
        drupal_set_message(t("This account is already linked with an account. try to choose another account."), 'warning');
      }
    }
    }
    return new RedirectResponse(Url::fromRoute('user.page')->toString());
  }

  public function getPopupForm($params) {
    return array(
      '#type' => 'item',
      '#title' => '',
      '#theme' => 'lr_popup',
      '#popup_params' => $params,
      '#attached' => array(
        'library' => array('sociallogin/drupal.sociallogin_email_popup'),
      ),
    );
  }

  public function emailPopupSubmit() {

    if (isset($_SESSION['lrdata']) && !empty($_SESSION['lrdata'])) {
      $userprofile = $_SESSION['lrdata'];
      $userprofile->Email_value = trim($_POST['email']);

      if (!\Drupal::service('email.validator')
        ->isValid($userprofile->Email_value)
      ) {

        $popup_params = array(
          'msg' => t('This email is invalid. Please choose another one.'),
          'provider' => $userprofile->Provider,
          'msgtype' => 'warning'
        );
        $popup_params['message_title'] = $this->module_config->get('popup_title');
        return $form['email_popup'] = $this->getPopupForm($popup_params);
      }
      else {
        $check_mail = user_load_by_mail($userprofile->Email_value);

        if (!empty($check_mail)) {
          $email_wrong = $this->module_config->get('popup_error');
          $popup_params = array(
            'msg' => t($email_wrong),
            'provider' => $userprofile->Provider,
            'msgtype' => 'warning',
          );
          $popup_params['message_title'] = $this->module_config->get('popup_title');
          return $form['email_popup'] = $this->getPopupForm($popup_params);
        }
        else {
          unset($_SESSION['lrdata']);
          $_SESSION['user_verify'] = 1;
          return $this->createUser($userprofile);
        }
      }
    }
    return new RedirectResponse(Url::fromRoute('<current>')->toString());
  }

  public function field_field_convert_info() {
    $convert_info = array(
      'text' => array(
        'label' => t('Text'),
        'callback' => 'field_field_convert_text',
      ),
      'email' => array(
        'label' => t('Text'),
        'callback' => 'field_field_convert_text',
      ),
      'string' => array(
        'label' => t('String'),
        'callback' => 'field_field_convert_text',
      ),
      'string_long' => array(
        'label' => t('Long String'),
        'callback' => 'field_field_convert_text',
      ),
      'text_long' => array(
        'label' => t('Long text'),
        'callback' => 'field_field_convert_text',
      ),
      'list_text' => array(
        'label' => t('List (\'text\')'),
        'callback' => 'field_field_convert_list',
      ),
      'datetime' => array(
        'label' => t('Date'),
        'callback' => 'field_field_convert_date',
      ),
      'date' => array(
        'label' => t('Date'),
        'callback' => 'field_field_convert_date',
      ),
      'datestamp' => array(
        'label' => t('Date'),
        'callback' => 'field_field_convert_date',
      ),
    );

    \Drupal::moduleHandler()->alter('field_field_convert_info', $convert_info);
    return $convert_info;
  }

  /**
   * Convert text and text_long data.
   *
   * @param string $property_name User profile property name thorugh which data mapped
   * @param object $userprofile User profile data that you got from social network
   * @param string User field name stored in database
   * @param string $instance Field instance
   * @return array  Contain value of field map data
   */
  public function field_field_convert_text($property_name, $userprofile, $field, $instance) {
    $value = NULL;
    if (!empty($property_name)) {
      if (isset($userprofile->$property_name)) {
        if (is_string($userprofile->$property_name)) {
          $value = $userprofile->$property_name;
        }
        elseif (is_object($userprofile->$property_name)) {
          $object = $userprofile->$property_name;

          if (isset($object->name)) {
            $value = $object->name;
          }
        }
      }
      return $value ? array('value' => $value) : NULL;
    }
  }

  public function field_field_convert_list($property_name, $userprofile, $field, $instance) {
    if (!empty($property_name)) {
      if (!isset($userprofile->$property_name) && !is_string($userprofile->$property_name)) {
        return;
      }

      $options = list_allowed_values($field);
      $best_match = 0.0;
      $match_sl = strtolower($userprofile->$property_name);

      foreach ($options as $key => $option) {
        $option = trim($option);
        $match_option = strtolower($option);
        $this_match = 0;
        similar_text($match_option, $match_sl, $this_match);

        if ($this_match > $best_match) {
          $best_match = $this_match;
          $best_key = $key;
        }
      }
      return isset($best_key) ? array('value' => $best_key) : NULL;
    }
  }

  public function field_field_convert_date($property_name, $userprofile, $field, $instance) {
        if (!empty($property_name)) {
            if (isset($userprofile->$property_name)) {
                $value = NULL;
                
                if(strpos($userprofile->$property_name, '/') !== false) {
                    $sldate = explode('/', $userprofile->$property_name);                  
                    $date = strtotime($userprofile->$property_name);
                    $formatDate = date('Y-m-d\TH:i:s', $date);
                } else {                  
                     $sldate = explode('-', $userprofile->$property_name);
                     $month = isset($sldate[0])?trim($sldate[0]):'';
                     $date = isset($sldate[1])?trim($sldate[1]):'';
                     $year = isset($sldate[2])?trim($sldate[2]):'';
                     $formatDate = trim($year.'-'.$month.'-'.$date,'-');
                     $formatDate = $formatDate.'T00:00:00'; 
                }
       
                if (count($sldate) == 3) {                    
                  if(!empty($formatDate)){                       
                        $value = array(
                          'value' => $formatDate,
                          'date_type' => $instance['type'],
                        );
                    }
                }
                return $value;
            }
        }
    }

  public function field_create_user_array(&$drupal_user, $userprofile) {
    $this->field_create_user(NULL, $drupal_user, $userprofile, TRUE);
  }
  
  public function field_create_user_object($drupal_user, $userprofile) {
    $this->field_create_user($drupal_user, $drupal_user, $userprofile, FALSE);
  }

  public function field_create_user($drupal_user, &$drupal_user_ref, $userprofile, $register_form = FALSE) {
    $field_map = $this->module_config->get('user_fields');  
    $field_convert_info = $this->field_field_convert_info();
    $entity_type = 'user';
    $instances = array();
    foreach (\Drupal::entityManager()
               ->getFieldDefinitions($entity_type, 'user') as $field_name => $field_definition) {
      $user_bundle = $field_definition->getTargetBundle();

      if (!empty($user_bundle)) {
        $instances[$field_name]['type'] = $field_definition->getType();
        $instances[$field_name]['label'] = $field_definition->getLabel();
      }
    }
    foreach ($instances as $field_name => $instance) {

      $field = FieldStorageConfig::loadByName($entity_type, $field_name);
     
      if (isset($field_map[$field_name]) && isset($field_convert_info[$field->getType()]['callback'])) {
        $callback = $field_convert_info[$field->getType()]['callback'];
        $field_property_name = $field_map[$field_name];

        if ($value = $this->$callback($field_property_name, $userprofile, $field, $instance)) {
          if ($register_form) {
            $drupal_user_ref[$field_name]['widget']['0']['value']['#default_value'] = isset($value['value']) ? $value['value'] : $value;
          }
          else {         
            $drupal_user->set($field_name, $value);
          }
        }
      }
    }
  }
}
