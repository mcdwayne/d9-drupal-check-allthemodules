<?php

/**
 * @file
 * Contains \Drupal\webtrees\Helper\WebtreesUser.
 */

namespace Drupal\webtrees\Helper;

use \mysqli;
use Drupal\webtrees\Helper\User;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Password\PasswordInterface;

/**
 * Manage interaction with Webtrees user database
 */
class WebtreesUser {
  /**
   * Webtrees database
   *
   * @var \mysqli
   */
  public $database;

  /**
   * Webtrees module settings.
   *
   * @var \Drupal\Core\Config\ConfigBase
   */
  public $my_config;

  /**
   * Webtrees user ID
   *
   * @var int
   */
  public $user_id;

  /**
   * Webtrees user name
   *
   * @var string
   */
  public $user_name;

  /**
   * Webtrees user's real name
   *
   * @var string
   */
  public $real_name;

  /**
   * Webtrees user password
   *
   * @var string
   */
  public $password;

  /**
   * Webtrees user password hash
   *
   * @var string
   */
  public $hash;

  /**
   * Webtrees user email
   *
   * @var string
   */
  public $email;

  /**
   * Webtrees role
   *
   * @var string
   */
  public $role;

  /**
   * Validate error text. Used by validateLoging.
   *
   * @var string
   */
  protected $error_message;

  /**
   * Login name. Used by validateLoging.
   *
   * @var string
   */
  protected $login_name;

  /**
   * Login password. Used by validateLoging.
   *
   * @var string
   */
  protected $login_password;

  /**
   * Login valid flag.
   *
   * @var boolean
   */
  protected $login_valid;

  /**
   * Webtrees settings
   *
   * @var array
   */
  public $settings;


  /**
   * Constructs a WebtreesUser object.
   */
  public function __construct() {
    $my_config = \Drupal::config('webtrees.settings');
    if ($my_config->get('database.use_drupal')){
      $databases=Database::getConnectionInfo('default');
      $host=$databases['default']['host'];
      $user=$databases['default']['username'];
      $password=$databases['default']['password'];
      $port=$databases['default']['port'];
    } else {
      $host=$my_config->get('database.host');
      $user=$my_config->get('database.user');
      $password=$my_config->get('database.password');
      $port=$my_config->get('database.port');
    }

    $database=$my_config->get('database.database'); // always use our database and prefix

    $this->database=new mysqli($host,$user,$password,$database,$port);

    $this->my_config=$my_config;
    $this->user_id=0; // initially invalid user
  }

  /**
   * Check for database error 
   *
   * @return string
   */
  public function connectError() {
    return $this->database->connect_error;
  }

  /**
   * Check if current user information is valid, $user_id==0 indicates invalid information
   *
   * @return boolean
   */
  public function isLoaded() {
    return $this->user_id ;
  }

  /**
   * Load Webtrees user by id, name, email or name/email
   *
   * @param string $string
   *    SELECT argument
   *
   * @param string $type
   *    should be: id | name | email | name_or_email
   *
   * @return boolean
   */
  public function load($string,$type='id') {
    $this->user_id=0; // in case fetch fails
    $prefix=$this->my_config->get('database.prefix');

    switch ($type) {
      case 'name_or_email':
        $stmt=$this->database->prepare('SELECT * FROM '.$prefix.'user WHERE user_name=? OR email=?');
        $stmt->bind_param('ss',$string,$string);
        break;
      case 'name':
        $stmt=$this->database->prepare('SELECT * FROM '.$prefix.'user WHERE user_name=?');
        $stmt->bind_param('s',$string);
        break;
      case 'email':
        $stmt=$this->database->prepare('SELECT * FROM '.$prefix.'user WHERE email=?');
        $stmt->bind_param('s',$string);
        break;
      case 'id':
        $stmt=$this->database->prepare('SELECT * FROM '.$prefix.'user WHERE user_id=?');
        $stmt->bind_param('i',$string);
        break;
      default;
        // fail silently
        $this->log(t('Unknown type for function load: ').$type,'error');
        return;
    }

    $stmt->execute();
    $stmt->bind_result($this->user_id,$this->user_name,$this->real_name,$this->email,$this->hash);
    $result=$stmt->fetch();
    $stmt->close();

    if ($this->user_id) {
      // load user settings
      $stmt=$this->database->prepare('SELECT setting_name,setting_value FROM '.$prefix.'user_setting WHERE user_id=?');
      $stmt->bind_param('i',$this->user_id);
      $stmt->execute();
      $stmt->bind_result($setting_name,$setting_value);

      $this->settings=array();
      while($stmt->fetch()){
        $this->settings[$setting_name]=$setting_value;
      }
      $stmt->close();

      $stmt=$this->database->prepare('SELECT setting_value FROM '.$prefix.'user_gedcom_setting WHERE user_id=? and setting_name="canedit"');
      $stmt->bind_param('i',$this->user_id);
      $stmt->execute();
      $stmt->bind_result($this->role);
      if(!$stmt->fetch()) {
        $this->role='access'; // member
      }

      $stmt->close();
    }

    return $this->isLoaded();
  }


  /**
   * Update or create webtrees user
   *   Update only modifies the main user record that includes the password
   *   Create will use default values
   *
   * @return boolean
   *    true if save works
   */
  function save () {
    $prefix=$this->my_config->get('database.prefix');
   
    if ($this->isLoaded()) {
      // update user record

      $stmt=$this->database->prepare('UPDATE '.$prefix.'user SET user_name=?,real_name=?,email=?,password=? WHERE user_id=?');
      $stmt->bind_param('ssssi',$this->user_name,$this->real_name,$this->hash,$this->user_id);
      $stmt->execute();
      $stmt->close();      
    } else {
      // create new user record

      $stmt=$this->database->prepare('INSERT INTO '.$prefix.'user (user_name,real_name,email,password) VALUES (?,?,?,?)');
      $stmt->bind_param('ssss',$this->user_name,$this->real_name,$this->email,$this->hash);
      $stmt->execute();
      $stmt->close();

      // get the user_id
      $stmt=$this->database->prepare('SELECT user_id FROM '.$prefix.'user WHERE user_name=?');
      $stmt->bind_param('s',$this->user_name);
      $stmt->execute();
      $stmt->bind_result($this->user_id);
      $result=$stmt->fetch();
      $stmt->close();

      // create user_gedcom_setting entries
      $gedcom=$this->my_config->get('webtrees_user.gedcom');

      $stmt=$this->database->prepare(
            'INSERT INTO '.$prefix.'user_gedcom_setting (user_id,gedcom_id,setting_name,setting_value) VALUES '
           .'(?,?,"canedit",?),'
           .'(?,?,"gedcom_id","0"),'
           .'(?,?,"RELATIONSHIP_PATH_LENGTH","0")'
           );
      $stmt->bind_param('issisis',$this->user_id,$gedcom,$this->role,$this->user_id,$gedcom,$this->user_id,$gedcom);
      $stmt->execute();
      $stmt->close();

      // create user_setting entries
      $this->saveSettings();
    }
  }


  /**
   * Verify $password_hash matches password
   *
   * @param string $password
   *
   * @return boolean
   */
  public function passwordVerify($password) {
    $this->password = $password;

    // Webtrees uses different encryption depending upon the version
    return   (PHP_VERSION_ID < 50307 && password_hash('foo', PASSWORD_DEFAULT) === false)
           ? crypt($password, $this->hash) === $this->hash
           : password_verify($password, $this->hash);
  }

  /**
   * Set Webtrees user password
   *
   * @param string $password
   */
  public function setPassword($password) {
    // Webtrees uses different encryption depending upon the version

    $this->password = $password;
    $this->hash = (PHP_VERSION_ID < 50307 && password_hash('foo', PASSWORD_DEFAULT) === false) 
                ? crypt($password)
                : password_hash($password,PASSWORD_DEFAULT);
  }

  /**
   * Check if Webtrees user is enabled
   *
   * @return boolean
   */
  public function isBlocked() {
    return !($this->settings['verified'] || $this->settings['verified_by_admin']) ;
  }

  /**
   * Get setting value
   *
   * @param string $setting_name
   *
   * @return string
   */
  public function get($setting_name) {
    return isset($this->settings[$setting_name])?$this->settings[$setting_name]:'';
  }

  /**
   * Set setting value
   *
   * @param string $setting_name
   * @param string $setting_value
   */
  public function set($setting_name,$setting_value) {
    $this->settings[$setting_name]=$setting_value;
  }

  /**
   * Save setting values
   * Assumes valid $user_id
   * Updates or adds settings. It will not remove settings.
   */
  public function saveSettings() {
   $table_name=$this->my_config->get('database.prefix').'user_setting';
   $get=$this->database->prepare('SELECT setting_value FROM '.$table_name.' WHERE user_id=? AND setting_name=?');
   $get->bind_param('is',$this->user_id,$setting_name);
   $get->bind_result($setting_value);
   
   $add=$this->database->prepare('INSERT INTO '.$table_name.' VALUES (?,?,?)');
   $add->bind_param('iss',$this->user_id,$setting_name,$setting_value);
   
   foreach ($this->settings as $setting_name => $setting_value) {
    $get->execute();
    if ($get->fetch()) {
     $put=$this->database->prepare("UPDATE $table_name SET $setting_name=? WHERE user_id=?");
     $put->bind_param('si',$setting_value,$this->user_id);
     $put->execute();
     $put->close();
    } else {
     $add->execute();
    }
   }
   $get->close();
   $add->close();
  }

  /**
   * Close database
   */
  public function close() {
    $this->database->close();
  }

  /**
   * Log message
   *
   * @param string $message
   *    translated message
   *
   * @param string $info 
   *    one of: alert, critical, debug, emergency, error, info, notice, warning
   */
  public function log($message,$type='info') {
    switch ($type) {
      case 'info':
        if ($this->my_config->get('configuration.logging')) {
          \Drupal::logger('webtrees')->log($type,$message);
        }
        break;

//    case 'debug': // need to add a debug configuration option and debug hooks sometime

      default:
        \Drupal::logger('webtrees')->log($type,$message);
        break;
    }
  }


  /**
   * Flag error. Used by validateLoging.
   *
   * @param string
   *   untranslated error message
   */
  function setError(string $message) {
    $this->error_message=$message;
  }

  /**
   * Get error message
   *
   * @return string
   *   untranslated error message
   */
  function getError() {
    return $this->error_message;
  }


  /**
   * See if we are done validating
   *
   * @return boolean
   */
  protected function doneValidate(){
    return $this->error_message || $this->login_valid;
  }

  /**
   * Validate log in form
   *
   * @param FormStateInterface $form_state
   */
  public function validateLogin(FormStateInterface $form_state){
    $this->login_valid=false;

    if ($this->my_config->get('configuration.enable')) {
      if ($this->connectError()) {
        $this->error('Webtrees login enabled but database is inaccessible');
      } else {
        // Load users from both lists
        $this->login_name = trim($form_state->getValue('name'));
        $this->login_password = trim($form_state->getValue('pass'));

        // See what user info to use for logging in
        if ($this->my_config->get('configuration.use_webtrees')) {
          $primary='webtrees';
          $secondary='drupal';
        } else {
          $primary='drupal';
          $secondary='webtrees';
        }
        $this->doValidate($primary);
        if (!$this->doneValidate()) {
          if ($this->my_config->get('configuration.allow_reverse')) {
            $this->doValidate($secondary);
          } else {
            $this->setError('Unrecognized username or password.');
          }
        }
      }
      // Check for errors
      if ($this->getError()) {
        $this->form_state->setErrorByName('name',t($this->getError()));
      }
    }
  }

  /**
   * Create user
   *   Assumes valid Webtrees user is loaded
   *
   * @return \Drupal\user\UserInterface
   *   initialized but not saved
   */
  function createDrupalUser() {
    // Get matching Drupal role
    if ($this->get('canadmin')) {
      $role = $this->my_config->get('role.webtrees.administrator');
    } else {
      switch ($this->role) {
        case 'admin':
          $role = $this->my_config->get('role.webtrees.manager');
          break;
        case 'accept':
          $role = $this->my_config->get('role.webtrees.moderator');
          break;
        case 'edit':
          $role = $this->my_config->get('role.webtrees.editor');
          break;
        case 'access':
          $role = $this->my_config->get('role.webtrees.member');
          break;
        default:
          $role = 'authenticated';
          break;
      }
    }
    $values = array (
      'name' => $this->user_name,
      'mail' => $this->email,
      'status' => 1,
    );
    $user = entity_create('user',$values);
    if ($role!='authenticated') {
      // only add non-authenticated roles
      $user->addRole($role);
    }
    $this->log('Created Drupal user','notice');
    return $user;
  }


  /**
   * Validate log in
   *
   * @param string $type
   *   webtrees | drupal
   */
  protected function doValidate(string $type) {
    if ($type=='webtrees') {
      $this->load($this->login_name,'name_or_email');
      if ($this->isLoaded()) {
        // check password
        if ($this->passwordVerify($this->login_password)) {
          // Check for email conflicts
          $user  = user_load_by_name($this->user_name) ;
          $user2 = user_load_by_mail($this->email);

          if ($user) {
            if ($user2 && ($user->get('uid')->value!=$user2->get('uid')->value)) {
              // Error: We have two different Drupal users. One matches the username. The other matches the email.
              $this->setError('Webtrees and Drupal user email mismatch. Please notify administraor');
              return;
            }
          } else {
            $user=$user2; // $user2 may be null but there cannot be an email conflict
          }

          // Check if Webtrees user or matching Drupal user is blocked
          if (!($this->isBlocked() || ($user && $user->isBlocked()))) {
            // We can log in. Create or update Drupal user

            if (!$user) { 
              // Create Drupal user since there are no conflicts
              // Get matching Drupal role
              $user=$this->createDrupalUser();
            }
            $user->setPassword($this->login_password);
            $user->save();
            $this->login_valid=true;
            return;
          }
        // Present generic error since user is blocked or password does not match
        $this->setError('Unrecognized username or password.');
        }
      }
    } else {
      // Check Drupal login

      $user  = user_load_by_name($this->login_name) ;
      $user2 = user_load_by_mail($this->login_name);
      if ($user) {
        if ($user2 && $user->get('uid')->value!=$user2->get('uid')->value) {
          $this->setError('Webtrees Drupal error: login name matches two users via email and user name.');
          return;
        }
        // $user and $user2 are the same or no match via email
      } else {
        $user=$user2;
      }
      if ($user) {
        if ($user->isBlocked) {
          // done, let Drupal catch block error
          $this->login_valid=true;
          return;
        }
        $username=$user->get('name')->value;
        if (\Drupal::service('user.auth')->authenticate($username,$this->login_password)) {
          // Create or update matching webtrees user
          $prefix=$this->my_config->get('database.prefix');

          $this->load($username,'name');

          if ($this->isLoaded()) {
            if ($this->isBlocked()) {
              // Present generic error since user is blocked
              $this->setError('Unrecognized username or password.');
              return;
            }
            // just update user password
          } else {
            // Create user
            $this->user_name=$username;
            $this->real_name=$username;
            $this->email=$user->get('mail')->value;
            $this->log(t('Created webtrees user: ').$username);

            // check if administator
            $admin=$this->my_config->get('role.drupal.administrator');
            if ($admin && $user->hasPermission($admin)) {
              $canadmin='1';
              $this->role='admin';
            } else {
              $canadmin='0';
              $this->role='none';
              $role=$this->my_config->get('role.drupal.moderator');
              if ($role &&  $user->hasPermission($role)) {
                $this->role='accept';
              } else {
                $role=$this->my_config->get('role.drupal.editor');
                if ($role &&  $user->hasPermission($role)) {
                  $this->role='edit';
                }
              }
            }

            // may get some settings from configuration form in the future
            $this->settings=array (
              'admin' => '0',
              'canadmin' => $canadmin,
              'autoaccept' => $this->get('autoaccept')?'1':'0',
              'comment' => $this->get('comment'),
              'comment_exp' => $this->get('comment_exp'),
              'defaulttab' => '0',
              'language' => $this->get('language'),
              'contactmethod' => $this->get('contactmethod'), //messaging1, messaging2, mailto, none
              'max_relation_path' => '1',
              'pwrequested' => '',
              'reghascode' => '',
              'reg_timestamp' => time(),
              'session_time' => time(),
              'sync_gedcom' => '0',
              'TIMEZONE' => $this->get('timezone'),
              // at least one of the verified options should be set. Set if both defaults are not set.
              'verified' => $this->get('verified')?'1':($this->get('verified_by_admin')?'0':'1'),
              'verified_by_admin' => $this->get('verified_by_admin')?'1':'0',
              'visiblonline' => $this->get('visibleonline')?'1':'0',
            );
          }
          $this->setPassword($this->login_password);
          $this->save();

          $this->login_valid=true;
        }
      }
    }
  }
}


