<?php

namespace Drupal\user_expire\Form;


use Drupal\user\Entity\Role;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\RoleInterface;

/**
 * User expire admin settings form.
 */
class UserExpireSettingsForm extends FormBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a \Drupal\user_expire\Form\UserExpireSettingsForm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *    The database service.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_expire_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the rules and the roles.
    $rules = user_expire_get_role_rules();
    $user_roles = Role::loadMultiple();
    $roles = array();

    foreach ($user_roles as $rid => $role) {
      $roles[$role->id()] = $role->get('label');
    }

    // Save the current roles for use in submit handler.
    $form['current_roles'] = array(
      '#type' => 'value',
      '#value' => $roles,
    );

    // Now show boxes for each role.
    $form['user_expire_roles'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('User inactivity expire by role settings'),
      '#description' => $this->t('Configure expiration of users by roles. Enter 0 to disable for the role. Enter 7776000 for 90 days.'),
    );

    foreach ($roles as $rid => $role) {
      if ($rid === RoleInterface::ANONYMOUS_ID) {
        continue;
      }

      $form['user_expire_roles']['user_expire_' . $rid] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Seconds of inactivity before expiring %role users', array('%role' => $role)),
        '#default_value' => isset($rules[$rid]->inactivity_period) ? $rules[$rid]->inactivity_period : 0,
      );
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('current_roles') as $rid => $role) {
      if ($rid === RoleInterface::ANONYMOUS_ID) {
        continue;
      }

      if (!ctype_digit($form_state->getValue('user_expire_' . $rid))) {
        $form_state->setErrorByName('user_expire_' . $rid, $this->t('Inactivity period must be an integer.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Start with a beginner's mind.
    $this->database->truncate(('user_expire_roles'))->execute();

    // Insert the rows that were inserted.
    foreach ($form_state->getValue('current_roles') as $rid => $role) {
      // Only save non-zero values.
      if (!empty($form_state->getValue('user_expire_' . $rid))) {

        $this->database->insert('user_expire_roles')
          ->fields(array('rid', 'inactivity_period'))
          ->values(array(
            'rid' => $rid,
            'inactivity_period' => (int) $form_state->getValue('user_expire_' . $rid),
          ))
          ->execute();
      }
    }
  }
}
