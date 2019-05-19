<?php

namespace Drupal\sign_for_acknowledgement\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Cache\Cache;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Service to interact with the database.
 */
class AcknowledgementsDatabase {

  // STRING CONSTANTS
  const SIGNED_OK = 'signed ok...';
  const TO_BE_SIGNED = 'still to be signed...';
  const OUT_OF_TERMS = 'signed out of terms...';
  const TERMS_EXPIRED = 'terms have expired...';

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
   */
  public function __construct(ConfigFactoryInterface $config_factory = NULL, Connection $database = NULL) {
    $this->config = $config_factory->get('sign_for_acknowledgement.settings');
    $this->database =  $database; //Database::getConnection();
  }

  /**
   * Test if document is already signed.
   *
   * @param int $userid
   *   id of the current user
   * @param int $nodeid
   *   id of the current node
   * @param int $signature timestamp
   *   timestamp of the signature
   *
   * @return bool
   *   TRUE if current document is signed by the current user      
   */
  public function alreadySigned($userid, $nodeid, &$signature_timestamp, &$alternate, &$annotation) {
    $result = $this->database->query('SELECT * FROM {sign_for_acknowledgement} WHERE node_id = :nid AND user_id = :uid',
    array(':nid' => $nodeid, ':uid' => $userid));
    $result->allowRowCount = TRUE;
    $rc = ($result->rowCount() > 0);
    if ($rc == TRUE && $signature_timestamp == -1) {
      foreach($result as $item) {
        $signature_timestamp = $item->mydate;
        $alternate = $item->alternate;
        $annotation = $item->annotation;
        break;
      }
    }
    return $rc;
  }
  /**
   * Create an 'having read' record.
   *
   * @param int $userid
   *   id of the current user
   * @param int $nodeid
   *   id of the current node
   */
  public function signDocument($userid, $nodeid, $annotation = '') {
    $timestamp = 0;
    $alternate = '';
    $note = '';
    if ($this->alreadySigned($userid, $nodeid, $timestamp, $alternate, $note)) {
      return FALSE;
    }
    $sign_id = $this->database->insert('sign_for_acknowledgement')
    ->fields(array(
      'node_id' => $nodeid,
      'user_id' => $userid,
      'mydate' => time(),
      'annotation' => $annotation,
      ))
    ->execute();
    Cache::invalidateTags(['node:' . $nodeid]);
    return TRUE;
  }
  /**
   * Create an alternate 'having read' record.
   *
   * @param int $userid
   *   id of the current user
   * @param int $nodeid
   *   id of the current node
   * @param string agreement
   *   user agreement text  
   */
  public function alternateSignDocument($userid, $nodeid, $agreement, $annotation) {
    $timestamp = 0;
    $alternate = '';
    $note = '';
    if ($this->alreadySigned($userid, $nodeid, $timestamp, $alternate, $note)) {
      return;
    }
    $sign_id = $this->database->insert('sign_for_acknowledgement')
    ->fields(array(
      'node_id' => $nodeid,
      'user_id' => $userid,
      'mydate' => time(),
      'alternate' => $agreement,
      'annotation' => $annotation,
      ))
    ->execute();
    Cache::invalidateTags(['node:' . $nodeid]);
    return TRUE;
  }
  /**
   * Delete an 'having read' record.
   *
   * @param int $userid
   *   id of the current user
   * @param int $nodeid
   *   id of the current node
   */
  public function unsignDocument($userid, $nodeid) {
    // Delete all acknowledgements to this node/user
    $this->database->delete('sign_for_acknowledgement')
      ->condition('node_id', $nodeid)
      ->condition('user_id', $userid)
      ->execute();
    Cache::invalidateTags(['node:' . $nodeid]);
  }

  public function tableName($key) {
    return 'user__' . $key;
  }

  /**
   * get customized status message
   * @param constant $msg status message unfiltetered untranslated
   * return custimized status message
   */
  public function getCustomMessage($msg) {
    $msg2 = $msg3 = NULL;
    switch ($msg) {
      case self::SIGNED_OK:
        $msg2 = 'signed_ok';
        $msg3 = t('signed ok...');
        break;
      case self::TO_BE_SIGNED:
        $msg2 = 'to_be_signed';
        $msg3 = t('still to be signed...');
        break;
      case self::OUT_OF_TERMS:
        $msg2 = 'out_of_terms';
        $msg3 = t('signed out of terms...');
        break;
      case self::TERMS_EXPIRED:
        $msg2 = 'terms_expired';
        $msg3 = t('terms have expired...');
        break;
    }
    if ($msg2 && $this->config->get($msg2)) {
      return $this->config->get($msg2);
    }
    return $msg3;
  }
  
  /**
   * @param timestamp $expire (the expiration date)
   * @param timestamp $signed (the signature date)
   * @return string describing the status of the signature.  
   */    
  public function status($expire, $signed)
  {
    $now = time();
    if ($signed) {
      if (empty($expire) || $signed < $expire) {
        return $this->getCustomMessage(self::SIGNED_OK);
      }
      else {
        return $this->getCustomMessage(self::OUT_OF_TERMS);
      }
    }
    else {
      if (empty($expire) || $now < $expire) {
        return $this->getCustomMessage(self::TO_BE_SIGNED);
      }
      else {
        return $this->getCustomMessage(self::TERMS_EXPIRED);
      }
    }
  }

  /**
   * get statuses array
   *
   */
  public function statuses($full = FALSE) {
    $return = [
      self::SIGNED_OK => $this->getCustomMessage(self::SIGNED_OK),
      self::TO_BE_SIGNED => $this->getCustomMessage(self::TO_BE_SIGNED),
    ];
    if ($full) {
      $return[self::OUT_OF_TERMS] = $this->getCustomMessage(self::OUT_OF_TERMS);
      $return[self::TERMS_EXPIRED] = $this->getCustomMessage(self::TERMS_EXPIRED);
    }
    return $return;
  }
  
  
  public function clearRenderCache() {
    Cache::invalidateTags(['rendered']);
  }

  /**
   * Prepares user data for output
   * @param object $node the current node
   * @param array $header_cells the header cells
   * @param array $rows the rows to display
   */    
  public function outdata($node, $timestamp, $session_name, &$header_cells, &$rows, $csv = FALSE)
  {
    $nodeid = $node->id();
    $rows_limit = $this->config->get('limit');
    $session = isset($_SESSION[$session_name]) ? $_SESSION[$session_name] : array();
    $custom_fields = $this->config->get('fields');
    $do_annotation = $node->annotation_field->value;
    
    // Username cell...
    $header_cells['username'] = $csv? t('Username') : array('data' => t('Username'), 'field' => 'u.name', 'sort' => 'asc');
    // Role cell...
    if ($this->config->get('show_roles')) {
      $header_cells['roles'] = t('Roles');
    }
    if ($this->config->get('show_email')) {
      $header_cells['email'] = t('Email');
    }
    // $fields cells
    foreach ($custom_fields as $key => $value) {
      if ($value !== $key) {
        continue;
      }
      $account = User::load(\Drupal::currentUser()->id());
      $label = $account->$value->getFieldDefinition()->getLabel();
      $header_cells[$key] = $csv? $label : array('data' => $label);
    }
    // $header_cells['read'] = array('data' => t('Signed'), 'field' => 'f.hid');
    if ($node->alternate_form->value || $node->alternate_form_multiselect->value) {
      $header_cells['agreement'] = $csv? t('Agreement') : array('data' => t('Agreement'), 'field' => 'f.alternate');
    }
    if ($do_annotation) {
      $header_cells['annotation'] = $csv? t('Annotation') : array('data' => t('Annotation'), 'field' => 'f.annotation');
    }
    // Datetime cell...
    $header_cells['date'] = $csv? t('Date') : array('data' => t('Date'), 'field' => 'f.mydate');
    // Expiration
    if ($csv && !(empty($timestamp))) {
      $header_cells['expiration'] = t('Expiration');
    }
    // Status cell...
    $header_cells['status'] = t('Status');

    // Building query with abstraction layer.
    $query = $this->database->select('users_field_data', 'u');
    $query = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
                          ->orderByHeader($header_cells);
    if ($rows_limit > 0 && $csv == FALSE) {
    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
          ->limit($rows_limit);
    }

    $query->condition('u.uid', 0, '<>')
      ->condition('u.status', 1)
      ->fields('u', array('uid', 'name'))
      ->fields('u', array('uid', 'mail'))
      ->fields('f', array('hid', 'alternate'))
      ->fields('f', array('hid', 'annotation'))
      ->fields('f', array('hid', 'mydate'));
    if (count($session) && isset($session['agreement']) && $session['agreement'] != 'any') {
      $query->condition('f.alternate', $session['agreement']);
    }

    $count = 0;
    foreach ($custom_fields as $key => $value) {
    if ($value !== $key) {
      continue;
    }
      $field_table = $this->tableName($key);
      $t = 't' . $count;
      $x = 'x' . ($count++);
      $referenced = false;
      $query->leftjoin($field_table, $x, 'u.uid = ' . $x . '.entity_id');
      if ($this->database->schema()->fieldExists($field_table, $key . '_target_id')) {
        $referenced = true;
        $query->addField($t, 'name', $key);
        $query->leftjoin('taxonomy_term_field_data', $t, $t . '.tid = ' . $x . '.' . $key . '_target_id');
      } else {
        $query->addField($x, $key . '_value', $key);
      }
      if (count($session) && isset($session[$key]) && $session[$key] != 'any') {
        if ($referenced) {
          $query->condition($t . '.name', $session[$key]);
        } else {
          $query->condition($x . '.' . $key . '_value', $session[$key]);
        }
      }
    }

    $query->leftjoin('sign_for_acknowledgement',
      'f',
      'f.user_id = u.uid AND f.node_id = :nid',
      array(':nid' => $nodeid)
    );

    $query->addField('r1', 'roles_target_id');
    $query->leftjoin('user__roles',
      'r1',
      'r1.entity_id = u.uid'
    );

    $query->addField('r3', 'entity_id');
    $query->addField('r3', 'enable_roles_value');
    $query->leftjoin('node__enable_roles',
      'r3',
      '(r3.enable_roles_value = \'authenticated\' OR r1.roles_target_id = r3.enable_roles_value) AND r3.entity_id = :nid',
      array(':nid' => $nodeid)
    );

    $query->addField('u1', 'entity_id');
    $query->addField('u1', 'enable_users_value');
    $query->leftjoin('node__enable_users',
      'u1',
      'u1.enable_users_value = u.uid AND u1.entity_id = :nid',
      array(':nid' => $nodeid)
    );

    $db_or = new Condition('OR'); //db_or();
    $db_or->condition('r3.enable_roles_value', NULL, 'IS NOT');
    $db_or->condition('u1.enable_users_value', NULL, 'IS NOT');
    $query->condition($db_or);

    if (count($session) && $session['status'] && $session['status'] != 'any') {
      switch($session['status']) {
        case self::SIGNED_OK:
          $query->condition('mydate', NULL, 'IS NOT');
          if ($timestamp) {
            $query->condition('mydate', $timestamp, '<');
          }
          break;
        case self::OUT_OF_TERMS:
          $query->condition('mydate', NULL, 'IS NOT');
          $query->condition('mydate', $timestamp, '>=');
          break;
        case self::TO_BE_SIGNED:
          $query->condition('mydate', NULL, 'IS');
          if ($timestamp) {
            $query->condition($timestamp, time(), '>');
          }
          break;
        case self::TERMS_EXPIRED:        
          $query->condition('mydate', NULL, 'IS');
          $query->condition($timestamp, time(), '<=');
          break;
      }
    }

    $records = $query->execute()->fetchAll();

    foreach ($records as $record) {
      if (isset($rows[$record->uid])) {
        if (empty($rows[$record->uid]['roles'])) {
          $rows[$record->uid]['roles'] = $record->roles_target_id;
          continue;
        }
        $a = explode(', ', $rows[$record->uid]['roles']);
        if (in_array($record->roles_target_id, $a)) {
          continue;
        }
        if (!empty($record->roles_target_id)) {
          $rows[$record->uid]['roles'] .= ', '.$record->roles_target_id; // TODO enhance
        }
        continue;
      }
      $rows[$record->uid]['username'] = $record->name;
      if ($this->config->get('show_roles')) {    
        $rows[$record->uid]['roles'] = isset($record->roles_target_id)? $record->roles_target_id : '---';
      }
      if ($this->config->get('show_email')) {
        if ($csv) {
          $rows[$record->uid]['email'] = isset($record->mail)? $record->mail : '---';
        }
        else {
          $rows[$record->uid]['email'] = isset($record->mail)? Link::fromTextAndUrl($record->mail, Url::fromUri("mailto:$record->mail"))->toString() : '---';
        }
      }
      foreach ($custom_fields as $key => $value) {
        if ($value !== $key) {
          continue;
        }
        $rows[$record->uid][$key] = isset($record->$key)? $record->$key : '---';
      }
      if ($node->alternate_form->value || $node->alternate_form_multiselect->value) {
        $rows[$record->uid]['agreement'] = $record->hid ? Xss::filter($record->alternate) : t('---');
      }
      if ($do_annotation) {
        $rows[$record->uid]['annotation'] = $record->hid ? Xss::filter($record->annotation) : t('---');
      }
      $rows[$record->uid]['date'] = $record->hid && $record->mydate ? \Drupal::service('date.formatter')->format($record->mydate, 'short') : t('---');
      if ($csv && !(empty($timestamp))) {
        $rows[$record->uid]['expiration'] = \Drupal::service('date.formatter')->format($timestamp, 'short');
      }
      $rows[$record->uid]['status'] = $this->status($timestamp, $record->mydate);
    }
  }
}
