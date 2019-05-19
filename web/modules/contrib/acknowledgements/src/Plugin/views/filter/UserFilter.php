<?php

namespace Drupal\sign_for_acknowledgement\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Cache\Cache;

/**
 * Simple filter to handle greater than/less than filters
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("sfa_user")
 */
class UserFilter extends \Drupal\views\Plugin\views\filter\FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function exposedTranslate(&$form, $type) {
	parent::exposedTranslate($form, $type);
	if (!\Drupal::currentUser()->hasPermission('view acknowledgements table')) {
      $form['#default_value'] = '0';
	  if (isset($form['#options']['All'])) {
        array_shift($form['#options']);
	  }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
	parent::buildExposeForm($form, $form_state);
	if (isset($form['expose']['multiple'])) {
      unset($form['expose']['multiple']);
    }
  }
  
  /**
   * {@inheritdoc}
   */
  protected function getValueOptions() {
	  
    $users = [];
	if (\Drupal::currentUser()->hasPermission('view acknowledgements table')) {
      $users = Database::getConnection()->query('SELECT uid, name FROM {users_field_data} WHERE uid > 0 AND status = 1 ORDER BY name ASC')->fetchAllKeyed(0, 1);    
    }
    $this->valueOptions = [ '0' => t('Current user')] + $users;

    return $users;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    //$form['value']['#tree'] = TRUE;

    if (empty($this->valueOptions)) {
      // Initialize the array of possible values for this filter.
      $this->getValueOptions();
    }

	$exposed = $form_state->get('exposed');

	$form['value'] = [
        '#type' => 'select',
        '#title' => t('Select user'),
        '#size' => 1,
	    '#options' => $this->valueOptions,
        '#default_value' => empty($this->value[0])? '0' : $this->value[0],
      ];
	
    if (!empty($this->options['exposed'])) {
      $identifier = $this->options['expose']['identifier'];
      $user_input = $form_state->getUserInput();
      if ($exposed && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value;
        $form_state->setUserInput($user_input);
      }
    } 
	if (!$exposed) {
      $form['value']['#options'] = [ 0 => t('Current user')];
	}
  }

  /**
   * {@inheritdoc}
   */
  function query() {
    $this->ensureMyTable();
	
    @$info = &$this->query->getTableInfo('sfa');
    $val = $this->value[0];
    if ($val == 0) {
        $val = '***CURRENT_USER***';
    }	  
    $info['join']->extra[0]['value'] = $val;
    $info['join']->extra[0]['numeric'] = TRUE;
    $info['join']->extra[0]['field'] = 'user_id';

    // check if user applies to node
    if ($val == '***CURRENT_USER***') {
        $val = \Drupal::currentUser()->id();
    }
    $usr = \Drupal\user\Entity\User::load($val);
    if (empty($usr)) {
      $this->query->addWhereExpression(0, '1 = 0'); // remove rows
        return;
    }
    $roles = [];
    $roles = Database::getConnection()->query('SELECT roles_target_id FROM {user__roles} WHERE entity_id = ' . $val)->fetchCol();
    $roles[] = 'authenticated';
    $this->query->addTable('node__enable_roles');
    $this->query->addTable('node__enable_users');
    $db_or = new Condition('OR');
    foreach ($roles as $role) {
      $db_or->condition('node__enable_roles.enable_roles_value', $role, '=');
    }
    $db_or->condition('node__enable_users.enable_users_value', $val, '=');
    $this->query->addWhere(0, $db_or);
    $this->query->distinct = TRUE;
  }
}
