<?php

/**
 * @file
 * Contains two classes.
 *
 * application_manager: for interaction with applications. Approving them, denying, them, fetching them, etce tc.
 *
 * application: An actual application as loaded or being prepared for saving to the DB.
 */

namespace Drupal\apply_for_role;

use Drupal\Core\Database\Database;
use Drupal\Component\Serialization;

/**
 * Application manager object used for performing any tasks relating to applications.
 */
class application_manager{
  private $apply_for_role_config;

  public function __construct()
  {
    $this->apply_for_role_config = \Drupal::config('apply_for_role.settings');
  }

  /**
   * Helper function that gets all applications for all time.
   */
  public function get_all_applications(){
    $select = Database::getConnection()->select('apply_for_role_applications', 'a')->fields('a');
    // Make this return an array of application objects?
    $db_apps = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $user_applications = array();

    foreach($db_apps as $db_app){
      $user_applications[] = $this->map_application_db_response_to_application($db_app);
    }

    return $user_applications;
  }

  /**
   * Helper function to get a singular application.
   * @param int $aid
   * @return object|bool
   *   Returns an application object or FALSE if no application
   */
  public function get_application($aid){
    $select = Database::getConnection()->select('apply_for_role_applications', 'a')->fields('a');
    $select->condition('a.aid', $aid);
    $db_app_data = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    if(count($db_app_data) == 1){
      return $this->map_application_db_response_to_application($db_app_data[0]);
    }else{
      return FALSE;
    }
  }

  /**
   *
   * Helper function to get all applications for a user
   *
   * @param int $uid
   * @param int $status
   *   0 for never viewed, 1 for accepted, 2 for denied.
   * @return array
   *   An array of applications.
   */
  public function get_applications_for_user($uid, $status = null){
    $select = Database::getConnection()->select('apply_for_role_applications', 'a')->fields('a');
    $select->condition('a.uid', $uid);

    if(isset($status)){
      $select->condition('a.status', $status);
    }

    $db_apps = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $user_applications = array();

    foreach($db_apps as $db_app){
      $user_applications[] = $this->map_application_db_response_to_application($db_app);
    }

    return $user_applications;
  }

  /**
   * Create an application.
   *
   * @param int $uid
   * @param array $rids
   */
  public function create_application($uid, $rids, $message = NULL){
    Database::getConnection()->insert('apply_for_role_applications')->fields(array(
      'uid',
      'rids',
      'created',
      'message',
    ))->values(array(
      $uid,
      Serialization\Json::encode($rids),
      time(),
      $message
    ))->execute();


    // Email admin on approval.
    if($this->apply_for_role_config->get('send_user_approval_email')){
      if($this->apply_for_role_config->get('admin_email_addresses')){
        $to = $this->apply_for_role_config->get('admin_email_addresses');
      }else{
        // No users specificed, send to superadmin.
        // @TODO: Replace deprecated user_load function.
        $to = user_load(1)->getEmail();
      }

      $subject = $this->apply_for_role_config->get('admin_email_subject');
      $body = $this->apply_for_role_config->get('admin_email_body');
      $replacements = array(
        '%URL' => \Drupal::request()->getHost(),
        '%ROLE' => $this->rids_to_text($rids),
      );
      $this->send_email($to, $subject, $body, $replacements);
    }
  }

  /**
   * @param mixed $application
   *   Pass either an AID to load an application, or an actual application object.
   * @return bool
   */
  public function approve_application($application){
    if(!is_object($application)){
      $application = $this->get_application($application);
    }

    // Check if the application has been denied/approved already, and do not proceed if so.
    if($application->get('status') != 0){
      return FALSE;
    }
    else{
      $user = \Drupal::service('entity_type.manager')->getStorage('user')->load($application->get('uid'));

      foreach($application->get('rids') as $role_to_add){
        $user->addRole($role_to_add);
      }

      $user->save();

      if($this->apply_for_role_config->get('send_user_approval_email')){
        // @TODO: Replace deprecated user_load function.
        $to = user_load($application->get('uid'))->getEmail();
        $subject = $this->apply_for_role_config->get('send_user_approval_subject');
        $body = $this->apply_for_role_config->get('send_user_approval_body');
        $replacements = array(
          '%URL' => \Drupal::request()->getHost(),
          '%ROLE' => $this->rids_to_text($application->get('rids')),
        );
        $this->send_email($to, $subject, $body, $replacements);
      }

      // Mark the application as approved.
      Database::getConnection()->update('apply_for_role_applications')
        ->fields(array('status'=> 1))
        ->condition('aid', $application->get('aid'))
        ->execute();
    }
  }

  /**
   * @param mixed $application
   *   Pass either an AID to load an application, or an actual application object.
   * @return bool
   */
  public function deny_application($application){
    if(!is_object($application)){
      $application = $this->get_application($application);
    }

    // Check if the application has been denied/approved already, and do not proceed if so.
    if($application->get('status') != 0){
      return FALSE;
    }
    else{
      if($this->apply_for_role_config->get('send_user_deny_email')){
        // @TODO: Replace deprecated user_load function.
        $to = user_load($application->get('uid'))->getEmail();
        $subject = $this->apply_for_role_config->get('send_user_deny_subject');
        $body = $this->apply_for_role_config->get('send_user_deny_body');
        $replacements = array(
          '%URL' => \Drupal::request()->getHost(),
          '%ROLE' => $this->rids_to_text($application->get('rids')),
        );
        $this->send_email($to, $subject, $body, $replacements);
      }

      // Mark the application as approved.
      Database::getConnection()->update('apply_for_role_applications')
        ->fields(array('status'=> 2))
        ->condition('aid', $application->get('aid'))
        ->execute();
    }
  }

  /**
   * Converts an array of RID's into plain text for insertion into email.
   *
   * @param $rids
   * @return string
   */
  public function rids_to_text($rids){
    $replacement_text = '';
    $first = TRUE;
    $last = count($rids); // Base zero count of last.
    $count = 0;

    // @TODO: Convert role_id to presentable non-machine name. For now this works.

    foreach($rids as $role_id){
      $count++;
      if($first){
        $replacement_text .= $role_id;
        $first = FALSE;
      }
      else if($count == $last){
        $replacement_text .= ' and ' . $role_id;
      }
      else{
        $replacement_text .= ', ' . $role_id;
      }
    }

    return $replacement_text;
  }

  /**
   * Returns a proper username for an application.
   */
  public function display_username_for_application($application){
    $uid = $application->get('uid');
    $account = \Drupal\user\Entity\User::load($uid);
    return $account->getDisplayName() . ' (' . $uid . ')';
  }

  /**
   * Helper function to send a generic email to respective parties during application handling.
   */
  protected function send_email($to, $subject, $body, $replacements = array()){

    $mailManager = \Drupal::service('plugin.manager.mail');
    $langcode = \Drupal::currentUser()->getPreferredLangcode();

    foreach ($replacements as $replacement => $value){
      $body = str_replace($replacement, $value, $body);
    }

    // Send email, capture result.
    $result = $mailManager->mail('apply_for_role', 'apply_for_role', $to, $langcode, array(
      'to' => $to,
      'subject' => $subject,
      'body' => $body,
    ), NULL, TRUE);
  }

  /**
   * Take the db response, map it to an application object.
   *
   * @param array $db_app_array
   *   Array of values as returned from the database for an application.
   *
   * @return object apply_for_role_application
   *   A newly created application object.
   */
  protected function map_application_db_response_to_application($db_app_array){
    return new apply_for_role_application(
      $db_app_array['aid'],
      $db_app_array['uid'],
      Serialization\Json::decode($db_app_array['rids']),
      $db_app_array['status'],
      $db_app_array['created'],
      $db_app_array['message']
    );
  }
}

/**
 * Definition of a singular apply for role application, as used by application manager and potentially beyond.
 */
class apply_for_role_application{
  private $aid;
  private $uid;
  private $rids;
  private $status;
  private $created;
  private $message;

  // Constructor.
  public function __construct($aid, $uid, $rids = NULL, $status = 0, $created = NULL, $message = NULL)
  {
    $this->aid = $aid;
    $this->uid = $uid;
    $this->rids = $rids;
    $this->status = $status;
    $this->created = $created ? $created : time();
    $this->message = $message;
  }

  // Property getter.
  public function get($property){
    return $this->$property;
  }

  // Property getter that returns ALL values at once.
  public function get_all_values(){
    return array(
      'aid' => $this->aid,
      'uid' => $this->uid,
      'rids' => $this->rids,
      'status' => $this->status,
      'created' => $this->created,
      'message' => $this->message,
    );
  }

  // Property setter.
  public function set($property, $value){
    $this->$property = $value;
  }
}