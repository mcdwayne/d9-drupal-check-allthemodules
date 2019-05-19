<?php

namespace Drupal\sign_for_acknowledgement\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Database\Database;


/**
 * Default implementation of the base field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("sfa_status")
 */
class SfaStatus extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }
	
  /**
   * c
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $dbman = \Drupal::service('sign_for_acknowledgement.db_manager');
    $fieldman = \Drupal::service('sign_for_acknowledgement.field_manager');
	
    $uid = -1; // invalid user
    if (isset($this->view->exposed_data['user_id'])) { // get user selection
      $uid = ($this->view->exposed_data['user_id']);
      if ($uid == 'All') {
        $uid = -1;
      }
    }
    $node = $this->getEntity($values);
    if ($node->getEntityTypeId() != 'node') {
        return '---';
    }
    $timestamp = $fieldman->expirationDate(TRUE, $node->id(), $node);
    if ($uid == -1) { // get user_id field result
      if (!isset($this->view->field['user_id'])) {
        return '---';
      }
      $username = $this->view->field['user_id']->original_value;
      if ($username == '---') {
        // check if no one can sign...
        $my_users = $node->get('enable_users')->getValue();
        $my_roles = $node->get('enable_roles')->getValue();
        if (empty($my_roles) && 
          empty($my_users)) {
            return '---';
          }
        $status = $dbman->status($timestamp, NULL);

	      return $status? $status : '---';
      }
	  $uid_a = Database::getConnection()->query('SELECT uid FROM {users_field_data} WHERE name = \'' . $username . '\'')->fetchCol();
	  $uid = $uid_a[0];
    }
    if (empty($this->view->field['hid'])) {
      return '---';
    }
    if (empty($this->view->field['hid']->original_value)) {
      $hid = 0;
    } else {
      $hid = $this->view->field['hid']->original_value;
    }
    $st_a = Database::getConnection()->query('SELECT mydate FROM {sign_for_acknowledgement} WHERE hid = ' . $hid)->fetchCol();
    if (empty($st_a)) {
      $signature_timestamp = NULL;
    }
    else {
      $signature_timestamp = $st_a[0];
    }
    $status = $dbman->status($timestamp, $signature_timestamp);
	  return $status? $status : '---'; 
  }

}
