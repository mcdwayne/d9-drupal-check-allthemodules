<?php

/**
 * @file
 */

namespace Drupal\lr_ciam;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\user\Entity\User;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use \LoginRadiusSDK\Utility\Functions;
use \LoginRadiusSDK\LoginRadiusException;
use \LoginRadiusSDK\Clients\IHttpClient;
use \LoginRadiusSDK\Clients\DefaultHttpClient;
use \LoginRadiusSDK\CustomerRegistration\Authentication\UserAPI;
use \LoginRadiusSDK\CustomerRegistration\Management\AccountAPI;

/**
 * Returns responses for Simple FB Connect module routes.
 */
class CiamUserManager {

    public $module_config;
    public $module_auth_config;
    protected $connection;
    protected $apiSecret;
    protected $apiKey;

    public function __construct() {
        $this->connection = \Drupal\Core\Database\Database::getConnection();
        $this->module_config = \Drupal::config('lr_ciam.settings');
        $this->module_auth_config = \Drupal::config('auth.settings');
        $this->apiSecret = trim($this->module_config->get('api_secret'));
        $this->apiKey = trim($this->module_config->get('api_key'));
    }
    

    /**
     * Update uid.
     * @param $ciam_uid
     * @param $user_id
     * @return mixed
     */
    function lr_ciam_update_user_table($ciam_uid, $user_id) {
        try {
            $this->connection->update('users')
                ->fields(array('lr_ciam_uid' => $ciam_uid))
                ->condition('uid', $user_id)
                ->execute();
        }
        catch (Exception $e) {

        }
    }
       
    /**
     * Delete users.
     * @param $user_id
     * @return mixed
     */
    function user_delete($user_id) {
        $accountObj = new AccountAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
        try {
            return $accountObj->delete($user_id);
        }
        catch (LoginRadiusException $e) {
            
        }
    }
    
    /**
     * Get Ciam uid from users table.
     * @param $user_id
     * @return $uid
     */
    function lr_ciam_get_ciam_uid($user_id) {
        $query = \Drupal::database()->select('users', 'u');
        $query->addField('u', 'lr_ciam_uid');
        $query->condition('u.uid', $user_id);
        $uid = $query->execute()->fetchField();
        return $uid;
    }
   
    /**
     * Get uname from users_field_data table.
     * @param $user_id
     * @return $uname
     */
    function lr_ciam_get_ciam_uname($user_id) {
        $query = \Drupal::database()->select('users_field_data', 'u');
        $query->addField('u', 'name');
        $query->condition('u.uid', $user_id);
        $uname = $query->execute()->fetchField();
        return $uname;      
    }

    /**
     * Block user at Ciam
     *
     * @param $uid user id
     * @return mixed
     */
    function lr_ciam_block_user($uid) {
        $accountObj = new AccountAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
        try {
            $data = array(
              "IsActive" => "false"
            );     
           return $accountObj->update($uid, $data);   
           
        }
        catch (LoginRadiusException $e) {   
            \Drupal::logger('ciam')->error($e);   
        }
    }
    
    

    /**
     * Unblock user at ciam.
     *
     * @param $uid user id
     * @return mixed
     */
    function lr_ciam_unblock_user($uid) {
        $accountObj = new AccountAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
        try {
            $data = array(
              "IsActive" => "true"
            );          
            return $accountObj->update($uid, $data);             
        }
        catch (LoginRadiusException $e) {   
               \Drupal::logger('ciam')->error($e);   
        }
    }
    
    /**
     * Delete mapped provider
     *
     * @param $aid
     * @return mixed
     */
    public function deleteMapUser($aid) {
        return $this->connection->delete('loginradius_mapusers')
                ->condition('user_id', $aid)
                ->execute();
    }

    /**
     * Get user profile data
     *
     * @param $userprofile
     * @return mixed
     */
    public function getUserData($userprofile) {
        $userprofile->Email_value = (sizeof($userprofile->Email) > 0 ? $userprofile->Email[0]->Value : '');
        $userprofile->Company_name = (isset($userprofile->Company->Name) ? $userprofile->Company->Name : '');
        $userprofile->Country_name = (isset($userprofile->Country->Name) ? $userprofile->Country->Name : '');
        $userprofile->PhoneNumber = (isset($userprofile->PhoneNumbers) && sizeof($userprofile->PhoneNumbers) > 0 ? $userprofile->PhoneNumbers[0]->PhoneNumber : '');
        return $userprofile;
    }

    /**
     * Insert social provider data
     *
     * @param $user_id
     * @param $provider_id
     * @param $provider
     * @return mixed
     */
    public function insertSocialData($user_id, $provider_id, $provider) {
        $this->connection->insert('loginradius_mapusers')
            ->fields(array(
              'user_id' => $user_id,
              'provider' => $provider,
              'provider_id' => $provider_id,
            ))
            ->execute();
    }

    /**
     * Get user by mail
     *
     * @param $email
     * @return mixed
     */
    public function getUserByEmail($email) {
        return user_load_by_mail($email);
    }

    /**
     * Removed unescaped character
     *
     * @param $str
     * @return mixed
     */
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

     /**
     * Get user name
     *
     * @param $userprofile
     * @return username
     */
    public function getDisplayName($userprofile) {
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
        elseif (!empty($userprofile->PhoneId)) {
           $username = str_replace(array(
          "-",         
          "+",
                ), "", $userprofile->PhoneId);
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
        if (isset($userprofile->Provider) && $userprofile->Provider == 'Email' && isset($userprofile->UserName) && $userprofile->UserName != '') {
            $username = $userprofile->UserName;
        } elseif (!empty($userprofile->FirstName) && !empty($userprofile->LastName)) {
                $username = $userprofile->FirstName . ' ' . $userprofile->LastName;            
        } elseif (!empty($userprofile->Email_value)) {
                $username = $userprofile->Email_value;           
        } else {
            $username = $this->getDisplayName($userprofile);
        }
        return $username;
    }
    
     /**
     * Check exist username
     *
     * @param object $userprofile
     * @return string Username of user
     */
    
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
            $data['fname'] = $this->getDisplayName($userprofile);
        }

        if (empty($data['lname'])) {
            $data['lname'] = $this->getDisplayName($userprofile);
        }

        return $data;
    }

    
    /**
     * Provider login to user
     *
     * @param object $new_user
     * @param object $userprofile
     * @return mixed
     */
    public function provideLogin($new_user, $userprofile, $status = FALSE) {
        if (isset($userprofile) && !empty($userprofile)) {
            if (is_array($userprofile) || is_object($userprofile)) {               
                $query = \Drupal::database()->select('loginradius_mapusers', 'lu');
                $query->addField('lu', 'user_id');
                $query->condition('lu.user_id', $new_user->id());
                $query->condition('lu.provider_id', $userprofile->ID);
                $check_aid = $query->execute()->fetchField();                
                if (isset($check_aid) && !$check_aid) {  
                    $this->insertSocialData($new_user->id(), $userprofile->ID, $userprofile->Provider);
                } 
            }
        }   
                
        if ($new_user->isActive()) {
            $url = '';
            $isNew = FALSE;
           
            if (!$new_user->isNew()) {    
                $this->field_create_user_object($new_user, $userprofile);
                $new_user->save();

                $this->downloadProfilePic($userprofile->ImageUrl, $userprofile->ID, $new_user);
            }

            \Drupal::service('session')->migrate();
            \Drupal::service('session')->set('lrID', $userprofile->ID);
            $_SESSION['emailVerified'] = false;
            if (isset($userprofile->EmailVerified)) {
                $_SESSION['emailVerified'] = $userprofile->EmailVerified;
            }

            if (\Drupal::moduleHandler()->moduleExists('lr_ciam')) {
                if (isset($userprofile->Provider) && $userprofile->Provider == 'Email' && isset($userprofile->UserName) && $userprofile->UserName != '') {
                    $user_name = $userprofile->UserName;
                }

                $user_manager = \Drupal::service('lr_ciam.user_manager');
                $dbuname = $user_manager->lr_ciam_get_ciam_uname($new_user->id());
                          
                if (isset($dbuname) && $dbuname != '') {
                    if (isset($user_name) && $user_name != '' && $dbuname != $user_name) {
                        try {
                            $this->connection->update('users_field_data')
                                ->fields(array('name' => $user_name))
                                ->condition('uid', $new_user->id())
                                ->execute();
                        }
                        catch (Exception $e) {
                               \Drupal::logger('ciam')->error($e);
                                drupal_set_message($e->getMessage(), 'error');
                        }
                    }
                }
            }

            user_login_finalize($new_user);
            if ($status) {
                drupal_set_message(t('You are now logged in as %username.', array('%username' => $new_user->getDisplayName())));
            }
            else {
                drupal_set_message(t('You are now logged in as %username.', array('%username' => $new_user->getDisplayName())));
            }   
            return $this->redirectUser($url);
        }
        else {
            drupal_set_message(t('You are either blocked, or have not activated your account. Please check your email.'), 'error');
            return new RedirectResponse(Url::fromRoute('<front>')->toString());
        }
    }

     /**
     * check provider id exist or not
     *
     * @param string $pid provider id  
     * @return provider id
     */
    public function checkProviderID($pid) {        
        $query = db_select('users', 'u');
        $query->join('loginradius_mapusers', 'lu', 'u.uid = lu.user_id');
        $query->addField('u', 'uid');
        $query->condition('lu.provider_id', $pid);
        $check_aid = $query->execute()->fetchField();
        return $check_aid;
    }
     
    /**
     * redirect user
     *
     * @param string $variable_path   
     * @return mixed
     */
    
    public function redirectUser($variable_path = '') {

        $user = \Drupal::currentUser();
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
        $variable_custom_path = (($variable_path == 'login_redirection') ? 'custom_login_url' : '');
 
        $request_uri = \Drupal::request()->getRequestUri();
        if (strpos($request_uri, 'redirect_to') !== FALSE) {
            // Redirect to front site.                                 
            $redirectUrl = \Drupal::request()->query->get('redirect_to');               
            $response = new TrustedRedirectResponse($redirectUrl);             
            return $response->send();
        } elseif ($this->module_config->get($variable_path) == 1) {
            // Redirect to profile.               
            $response = new RedirectResponse($user->id() . '/edit');
            return $response->send();
        } elseif ($this->module_config->get($variable_path) == 2) {
            // Redirect to custom page.                             
            $custom_url = $this->module_config->get($variable_custom_path);
            if (!empty($custom_url)) {
                $response = new RedirectResponse($custom_url);
                return $response->send();
            }
            else {
                return new RedirectResponse(Url::fromRoute('<front>')->toString());
            }
        } else {
            // Redirect to same page.   
             if(!empty($_SESSION['referer_url'])){                     
                    $refererUrl = $_SESSION['referer_url'];              
                    $response = new RedirectResponse($refererUrl);             
                    return $response->send();
             } else {               
                $destination = (\Drupal::destination()->getAsArray());         
                if(isset($destination['destination']) && $destination['destination'] != ''){                  
                    $response = new RedirectResponse($destination['destination']);
                } else {               
                    $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
                }
                return $response->send();
             }
        }
    }

     /**
     * Get profile pic
     *
     * @param string $picture_url   
     * @param string $id   
     * @param string $user   
     * @return mixed
     */
    
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
                \Drupal::logger('ciam')
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
            \Drupal::logger('ciam')
                ->error('Could not save profile picture. Unhandled error.');
            return FALSE;
        }
    }

     /**
     * Create user
     *
     * @param string $userprofile     
     * @return mixed
     */
    
    public function createUser($userprofile) {
        if (isset($userprofile->ID) && !empty($userprofile->ID)) {
            $user_config = \Drupal::config('user.settings');

            $user_register = $user_config->get('register');
            if ($user_register == 'visitors' || $user_register == 'visitors_admin_approval') {
                $newUserStatus = 0;
                if ($user_register != 'visitors_admin_approval' && ($user_register == 'visitors')) {
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
                    \Drupal::logger('ciam')
                        ->notice('New user created. Username %username, UID: %uid', array(
                          '%username' => $new_user->getDisplayName(),
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
                        \Drupal::moduleHandler()->invokeAll('add_user_data_after_save', [$new_user, $userprofile]);
                    }
                    //Advanced module LR Code Hook End
                    $status = FALSE;
                    if (($user_config->get('verify_mail') == 1) || !$user_config->get('verify_mail')) {
                        $status = TRUE;
                    }

                    if ($new_user->isActive() && $status && $_SESSION['user_verify'] != 1) {
                        $new_user->setLastLoginTime(REQUEST_TIME);
                    }
                }
                else {
                    // Something went wrong                                
                    drupal_set_message(t('Creation of user account failed. Please contact site administrator.'), 'error');
                    \Drupal::logger('ciam')->error('Could not create new user.');
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

    /**
     * Get random email
     *
     * @param string $host     
     * @param string $id     
     * @return mixed
     */
    
    public function getRandomEmail($host, $id) {        
        $email_name = substr(str_replace(array(
          "-",
          "/",
          ".",
          "+",
                ), "", $id), -13);
        $email = $email_name . '@' . $host . '.com';
        $account = user_load_by_mail($email);

        if ($account) {
            $id = $email_name . rand();
            $email = $this->getRandomEmail($id, $host);
        }
        return $email;
    }

    /**
     * Check existing user
     *
     * @param $userprofile           
     * @return mixed
     */
    
    public function checkExistingUser($userprofile) {

        $drupal_user = NULL;
        if (isset($userprofile->ID) && !empty($userprofile->ID)) {

            $uid = $this->checkProviderID($userprofile->ID);
            //Advanced module LR Code Hook End
            if ($uid) {
                $drupal_user = User::load($uid);
            }
        }
        
        if (!$drupal_user) {
            if (empty($userprofile->Email_value)) {
                $phoneid = isset($userprofile->PhoneId) ? $userprofile->PhoneId : $userprofile->ID;                
                $userprofile->Email_value = $this->getRandomEmail($_SERVER['HTTP_HOST'], $phoneid);
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

    /**
     * create user
     *
     * @param $data           
     * @return array
     */
    function lr_ciam_create_user($data) {
        
        try {
            $accountObj = new AccountAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
            return $accountObj->create($data);
        }
        catch (LoginRadiusException $e) {
            if (isset($e->getErrorResponse()->description) && $e->getErrorResponse()->description) {
                return $e->getErrorResponse()->description;
            }
        }
    }
   
    /**
     * set password
     *
     * @param $uid           
     * @param $password           
     * @return mixed
     */
        
    function lr_ciam_set_password($uid, $password) {
        try {
            $accountObj = new AccountAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
            $accountObj->setPassword($uid, $password);
        }
        catch (LoginRadiusException $e) {
           
        }
    }

    /**
     * forgot password
     *
     * @param $email           
     * @param $reset_password_url           
     * @return mixed
     */
    function lr_ciam_forgot_password($email, $reset_password_url) {
        try {
            $userObj = new UserAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
            return $userObj->forgotPassword($email, $reset_password_url);
        }
        catch (LoginRadiusException $e) {
            if (isset($e->getErrorResponse()->description) && $e->getErrorResponse()->description) {
                return $e->getErrorResponse()->description;
            }
        }
    }    

    /**
     * Handle user callback
     *
     * @param string $userprofile           
     * @return mixed
     */
    
    public function handleUserCallback($userprofile) {        
       return new RedirectResponse(Url::fromRoute('user.page')->toString());
    }

    /**
     * Convert field data.
     *
     * @return array
     */
    
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

    /**
     * Convert list data.
     *
     * @param string $property_name User profile property name thorugh which data mapped
     * @param object $userprofile User profile data that you got from social network
     * @param string User field name stored in database
     * @param string $instance Field instance
     * @return array  Contain value of field map data
     */
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

     /**
     * Convert date data.
     *
     * @param string $property_name User profile property name thorugh which data mapped
     * @param object $userprofile User profile data that you got from social network
     * @param string User field name stored in database
     * @param string $instance Field instance
     * @return array  Contain value of field map data
     */
    
    public function field_field_convert_date($property_name, $userprofile, $field, $instance) {
     
        if (!empty($property_name)) {
            if (isset($userprofile->$property_name)) {
                $value = NULL;

                if (strpos($userprofile->$property_name, '/') !== false) {
                    $sldate = explode('/', $userprofile->$property_name);
                    $date = strtotime($userprofile->$property_name);
                    $formatDate = date('Y-m-d\TH:i:s', $date);                   
                }
                else {
                    $sldate = explode('-', $userprofile->$property_name);              
                    $month = isset($sldate[0]) ? trim($sldate[0]) : '';
                    $date = isset($sldate[1]) ? trim($sldate[1]) : '';
                    $year = isset($sldate[2]) ? trim($sldate[2]) : '';
                    $formatDate = trim($year . '-' . $month . '-' . $date, '-');
                    $formatDate = $formatDate . 'T00:00:00';               
                }

                if (count($sldate) == 3) {
                    if (!empty($formatDate)) {
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

    /**
     * Field create user array
     *
     * @param string $drupal_user
     * @param object $userprofile User profile data that you got from social network  
     * @return array
     */
    
    public function field_create_user_array(&$drupal_user, $userprofile) {       
        $this->field_create_user(NULL, $drupal_user, $userprofile, TRUE);
    }

    /**
     * Field create user object
     *
     * @param string $drupal_user
     * @param object $userprofile User profile data that you got from social network  
     * @return object
     */
    
    public function field_create_user_object($drupal_user, $userprofile) {
        $this->field_create_user($drupal_user, $drupal_user, $userprofile, FALSE);
    }

    /**
     * Field create user
     *
     * @param string $drupal_user
     * @param string $drupal_user_ref
     * @param object $userprofile User profile data that you got from social network  
     * @param $register_form
     *
     */
    
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
