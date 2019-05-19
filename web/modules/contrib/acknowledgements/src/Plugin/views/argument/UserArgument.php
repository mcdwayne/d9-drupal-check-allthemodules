<?php

namespace Drupal\sign_for_acknowledgement\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\NumericArgument;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;

/**
 * Basic argument handler for arguments that are numeric. Incorporates
 * break_phrase.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("sfa_user")
 */
class UserArgument extends NumericArgument {

  /**
   * The operator used for the query: or|and.
   * @var string
   */
  public $operator;

  /**
   * The actual value which is used for querying.
   * @var array
   */
  public $value;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    unset($options['break_phrase']);
    unset($options['not']);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    unset($form['break_phrase']);
    unset($form['not']);
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    $this->value = [$this->argument];
	@$info = &$this->query->getTableInfo('sfa');
	$val = $this->value[0];
    $info['join']->extra[0]['value'] = $val;
    $info['join']->extra[0]['numeric'] = TRUE;
    $info['join']->extra[0]['field'] = 'user_id';

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

  }
}
