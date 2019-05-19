<?php

namespace Drupal\sign_for_acknowledgement\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;
use Drupal\Core\Database\Database;


/**
 * Default implementation of the base field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("sfa_user")
 */
class User extends NumericField {
	
  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
	if (empty($value)) {
      if (isset($this->view->exposed_data['user_id'])) { // get user selection
        $value = ($this->view->exposed_data['user_id']);
        if ($value == 'All') {
          $value = $this->getValue($values);
        }
      }
      else {
        $value = '0';
      }
  }
  if (empty($value) && isset($this->view->argument) && isset($this->view->argument['user_id']->value[0])) {
    $value = $this->view->argument['user_id']->value[0];
  }
  if ($value === '0') {
    $value = \Drupal::currentUser()->id();
  }
	if (!empty($value))  {
	  $u =  Database::getConnection()->query('SELECT name FROM {users_field_data} WHERE uid = ' . $value)->fetchCol();
	  if (!empty($u)) {
        return $u[0];
	  }
	}
    return '---';
  }

}
