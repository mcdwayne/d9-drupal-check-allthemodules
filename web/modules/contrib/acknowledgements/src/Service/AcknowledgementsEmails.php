<?php

namespace Drupal\sign_for_acknowledgement\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Utility\Token;

/**
 * Service to interact with the node fields.
 */
class AcknowledgementsEmails {

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;
  /**
   * A database object.
   *
   */
  protected $database;

  /**
   * {@inheritdoc}
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param Connection $database
   *   The drupal connection
   */
  public function __construct(ConfigFactoryInterface $config_factory = NULL, Connection $database = NULL) {
    $this->config = $config_factory->get('sign_for_acknowledgement.settings');
	  $this->database =  $database;
  }

  /**
   * keep track of sent emails
   */
  public function saveEmailSent($uids, $nid) {
    foreach ($uids as $uid) {
      $this->database->insert('sign_for_acknowledgement_email')
      ->fields(array(
        'node_id' => $nid,
        'user_id' => $uid,
        'mydate' => time()
        ))
      ->execute();
    }
  }
  
  /**
   * check whether mail is already sent
   * return integer count of sent messages
   */
  public function emailAlreadySent($userid, $nodeid) {
    $result = $this->database->query('SELECT COUNT(*) FROM {sign_for_acknowledgement_email} WHERE node_id = :nid AND user_id = :uid',
    array(':nid' => $nodeid, ':uid' => $userid));
	  $result = $result->fetchCol();
    return $result[0];
  }

  /**
   * build to string with users emails
   */
  public function buildReceivers($uids) {
    $users = \Drupal\user\Entity\User::loadMultiple($uids);
    $result = '';
    foreach ($users as $user) {
      if ($user->get('status')->value == 0) {
        continue;
      }
      if (!empty($result)) {
        $result .= ', ';
      }
      $result .= $user->getEmail();
    }
    return $result;
  }
  
  /**
   * get uid list ($emails) from roles ($roles) to which send email related to specified node ($nid)
   */
  public function getFromRoles(&$emails, $roles, $nid) {
    foreach($roles as $key => $value) {
      if ($key !== $value) {
        continue;
      }
      $rolename = $value; // == t('authenticated user') ? 'authenticated user' : $value;
      $query = "SELECT ur.entity_id FROM {user__roles} AS ur WHERE ur.roles_target_id = '$rolename'";
      $result = $this->database->query($query);
      $uids = $result->fetchCol();

      foreach ($uids as $uid) {
        if (in_array($uid, $emails)) {
          continue;
        }
        if ($this->emailAlreadySent($uid, $nid)) {
          continue;
        }
        $emails[] = $uid;
      }
    }	
  }
  
  /**
   * get uid list ($emails) from uid list ($uids) to which send email related to specified node ($nid)
   */
  public function getFromUsers(&$emails, $uids, $nid) {
      foreach ($uids as $uid) {
		    if ($uid == '_none') {
		      continue;
		    }
        if (in_array($uid, $emails)) {
          continue;
        }
        if ($this->emailAlreadySent($uid, $nid)) {
          continue;
        }
        $emails[] = $uid;
      }
  }
  
  /**
   * Send email
   */
  public function nodeEmail(\Drupal\node\Entity\Node $node, $roles, $users, $nosign) {
    $no_signature = FALSE;
	  $emails = [];
	  if ($node->email_roles->value && is_array($roles)) {
  	  $this->getFromRoles($emails, $roles, $node->id());
	  }
    if ($node->email_users->value && is_array($users)) {
	    $this->getFromUsers($emails, $users, $node->id());
	  }
    if (empty($roles) && empty($users)) {
      if (is_array($nosign)) {
	      $this->getFromRoles($emails, $nosign, $node->id());
	    }
	    $no_signature = TRUE;
    }
    if (empty($emails)) {
      return; // no user found, return
    }

	  $mailManager = \Drupal::service('plugin.manager.mail');
	  $tokenManager = \Drupal::token();
    $params = array();
    $params['subject'] = $tokenManager->replace(\Drupal\Component\Utility\Html::escape($this->config->get($no_signature? 'email_subject_nosign' : 'email_subject')), array('node' => $node));
    $params['body'] = $tokenManager->replace(\Drupal\Component\Utility\Html::escape($this->config->get($no_signature? 'email_body_nosign' : 'email_body')), array('node' => $node));
    $params['headers'] = array('Bcc' => $this->buildReceivers($emails));
    $site_mail = \Drupal::config('system.site')->get('mail');
    $to = $this->config->get('email_to') == 1 ? $site_mail : 'undisclosed-recipients:;';
    $mailManager->mail('sign_for_acknowledgement', 'notify', $to, \Drupal::languageManager()->getDefaultLanguage()->getId(), $params);
    $this->saveEmailSent($emails, $node->id());
    drupal_set_message(t('Selected users have been notified via e-mail'));
  }
}
