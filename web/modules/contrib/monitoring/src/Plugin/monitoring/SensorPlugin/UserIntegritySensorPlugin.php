<?php
  /**
   * @file
   * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\UserIntegritySensorPlugin.
   */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Drupal\user\Entity\User;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\user\Entity\Role;

/**
 * Monitors user data changes.
 *
 * A custom database query is used here instead of entity manager for
 * performance reasons.
 *
 * @SensorPlugin(
 *   id = "user_integrity",
 *   label = @Translation("Privileged user integrity"),
 *   description = @Translation("Monitors name and e-mail changes of users with access to restricted permissions. Checks if authenticated or anonymous users have privileged access."),
 *   addable = FALSE
 * )
 */
class UserIntegritySensorPlugin extends SensorPluginBase implements ExtendedInfoSensorPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $configurableValueType = FALSE;

  /**
   * The max number of users to list in the verbose output.
   *
   * @var int
   */
  protected $listSize = 500;

  /**
   * The list of restricted permissions.
   *
   * @var array
   */
  protected $restrictedPermissions = array();

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $sensor_result) {

    // Get role IDs with restricted permissions.
    $role_ids = $this->getRestrictedRoles();

    $current_users = $this->processUsers($this->loadCurrentUsers($role_ids));
    // Add sensor message, count of current privileged users.
    $sensor_result->addStatusMessage(count($current_users) . ' privileged user(s)');
    $sensor_result->setStatus(SensorResultInterface::STATUS_OK);
    // Load old user data.
    $old_users = \Drupal::keyValue('monitoring.users')->getAll();
    // If user data is not stored, store them first.
    if (empty($old_users)) {
      foreach ($current_users as $user) {
        \Drupal::keyValue('monitoring.users')->set($user['id'], $user);
      }
    }
    // Check for new users and changes in existing users.
    else {
      $new_users = array_diff_key($current_users, $old_users);
      if (!empty($new_users)) {
        $sensor_result->setStatus(SensorResultInterface::STATUS_WARNING);
        $sensor_result->addStatusMessage(count($new_users) . ' new user(s)');
      }
      // Get the count of privileged users with changes.
      $count = 0;
      $changed_users_ids = array_intersect(array_keys($current_users), array_keys($old_users));
      foreach ($changed_users_ids as $changed_user_id) {
        $changed = $this->getUserChanges($current_users[$changed_user_id], $old_users[$changed_user_id]);
        if (!empty($changed)) {
          $count++;
        }
      }
      if ($count > 0) {
        $sensor_result->addStatusMessage($count . ' changed user(s)');
        $sensor_result->setStatus(SensorResultInterface::STATUS_WARNING);
      }
    }

    // Check anonymous and authenticated users with restricted permissions and
    // show a message.
    $user_register = \Drupal::config('user.settings')->get('register');

    // Check if authenticated or anonymous users have restrict access perms.
    $role_ids_after = array_intersect($role_ids, ['authenticated', 'anonymous']);
    $role_labels = [];
    foreach (Role::loadMultiple($role_ids_after) as $role) {
      $role_labels[$role->id()] = $role->label();
    }

    if (!empty($role_labels)) {
      $sensor_result->addStatusMessage('Privileged access for roles @roles', array('@roles' => implode(', ', $role_labels)));
      $sensor_result->setStatus(SensorResultInterface::STATUS_WARNING);
    }

    // Further escalate if the restricted access is for anonymous.
    if ((in_array('anonymous', $role_ids))) {
      $sensor_result->setStatus(SensorResultInterface::STATUS_CRITICAL);
    }

    // Check if self registration is possible.
    if ((in_array('authenticated', $role_ids)) && $user_register != USER_REGISTER_ADMINISTRATORS_ONLY) {
      $sensor_result->addStatusMessage('Self registration possible.');
      $sensor_result->setStatus(SensorResultInterface::STATUS_CRITICAL);
    }
  }

  /**
   * Gets a list of available users.
   *
   * @param string[] $role_ids
   *   Roles to filter users.
   *
   * @return \Drupal\user\Entity\User[]
   *   Available users.
   */
  protected function loadCurrentUsers(array $role_ids) {
    if (!$role_ids) {
      return [];
    }

    // Loading all users and managing them will kill the system so we limit
    // them.
    $query = \Drupal::entityQuery('user')
      ->sort('access', 'DESC')
      ->range(0, $this->listSize);

    // The authenticated role is not persisted and it could have restrict access
    // so we load every user.
    if (in_array('authenticated', $role_ids)) {
      $uids = $query->condition('uid', '0', '<>')
        ->execute();
    }
    else {
      // Load all users with the roles.
      $uids = $query->condition('roles', $role_ids, 'IN')
        ->execute();
    }

    return User::loadMultiple($uids);
  }

  /**
   * Gets changes made to user data.
   *
   * @param array $current_values
   *   Current user data returned by ::processUsers().
   * @param array $expected_values
   *   Expected user data returned by ::processUsers().
   *
   * @return string[][]
   *   Changes in user.
   */
  protected function getUserChanges(array $current_values, array $expected_values) {

    $changes = array();

    if ($current_values['name'] != $expected_values['name']) {
      $changes['name']['expected_value'] = $expected_values['name'];
      $changes['name']['current_value'] = $current_values['name'];

    }
    if ($current_values['mail'] != $expected_values['mail']) {
      $changes['mail']['expected_value'] = $expected_values['mail'];
      $changes['mail']['current_value'] = $current_values['mail'];
    }

    if ($current_values['password'] != $expected_values['password']) {
      $changes['password']['expected_value'] = '';
      $changes['password']['current_value'] = t('Password changed');
    }
    return $changes;
  }

  /**
   * Gets a list of restricted roles.
   *
   * @return string[]
   *   Restricted roles.
   */
  protected function getRestrictedRoles() {
    /** @var \Drupal\user\PermissionHandlerInterface $permission_handler */
    $permission_handler = \Drupal::service('user.permissions');
    $available_permissions = $permission_handler->getPermissions();;
    $this->restrictedPermissions = array();
    foreach ($available_permissions as $key => $value) {
      if (!empty($value['restrict access'])) {
        $this->restrictedPermissions[] = $key;
      }
    }
    $avaliable_roles = Role::loadMultiple();
    $restricted_roles = array();
    foreach ($avaliable_roles as $role) {
      $permissions = $role->getPermissions();
      if ($role->isAdmin() ||  array_intersect($permissions, $this->restrictedPermissions)) {
        $restricted_roles[] = $role->id();
      }
    }
    return $restricted_roles;
  }

  /**
   * Process user entity into raw value array.
   *
   * @param \Drupal\user\Entity\User[] $users
   *   Users to process.
   *
   * @return array
   *   Processed user data, list of arrays with keys id, name, mail, password
   *   ans changed time.
   */
  protected function processUsers(array $users) {
    $processed_users = array();
    foreach ($users as $user) {
      $id = $user->id();
      $processed_users[$id]['id'] = $id;
      $processed_users[$id]['name'] = $user->getUsername();
      $processed_users[$id]['mail'] = $user->getEmail();
      $processed_users[$id]['password'] = hash('sha256', $user->getPassword());
      $processed_users[$id]['changed'] = $user->getChangedTime();
      $processed_users[$id]['last_accessed'] = $user->getLastAccessedTime();
      $processed_users[$id]['created'] = $user->getCreatedTime();
      $processed_users[$id]['roles'] = implode(", ", $user->getRoles());
    }
    return $processed_users;
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {

    $output = [];
    // Load all the old user data.
    $expected_users = \Drupal::keyValue('monitoring.users')->getAll();
    // Get available roles with restricted permission.
    $role_ids = $this->getRestrictedRoles();
    // Process the current user data.
    $current_users = $this->processUsers($this->loadCurrentUsers($role_ids));

    $new_users_id = array_diff(array_keys($current_users), array_keys($expected_users));

    $deleted_users = array_diff_key($expected_users, $current_users);

    // Verbose output for new users.
    $rows = [];

    foreach ($new_users_id as $id) {
      $time_stamp = $current_users[$id]['created'];
      $last_accessed = $current_users[$id]['last_accessed'];
      // Do this for all, and delete drupal render.
      $user_name = [
        'data' => [
          '#theme' => 'username',
          '#account' => User::load($id),
        ]
      ];
      $rows[] = [
        'user' => $user_name,
        'roles' => ['data' => ['#markup' => $current_users[$id]['roles']]],
        'created' => ['data' => ['#markup' => \Drupal::service('date.formatter')->format($time_stamp, 'short')]],
        'last_accessed' => ['data' => ['#markup' => $last_accessed != 0 ? \Drupal::service('date.formatter')->format($last_accessed, 'short') : t('never')]],
      ];
    }

    if (count($rows) > 0) {
      $header = [
        'user' => t('User'),
        'roles' => t('Roles'),
        'created' => t('Created'),
        'last_accessed' => t('Last accessed'),
      ];

      $output['new_table'] = [
        '#type' => 'verbose_table_result',
        '#title' => t('New users with privileged access'),
        '#header' => $header,
        '#rows' => $rows,
      ];
    }

    // Verbose output for users with changes.
    $rows = [];

    $old_user_ids = array_keys($expected_users);
    foreach ($old_user_ids as $id) {
      $changes = [];
      if (isset($current_users[$id])) {
        $changes = $this->getUserChanges($current_users[$id], $expected_users[$id]);
      }
      foreach ($changes as $key => $value) {
        $time_stamp = $current_users[$id]['changed'];
        $last_accessed = $current_users[$id]['last_accessed'];
        $user_name = [
          'data' => [
            '#theme' => 'username',
            '#account' => User::load($id),
          ]
        ];
        $rows[] = [
          'user' => $user_name,
          'field' => ['data' => ['#markup' => $key]],
          'current_value' => ['data' => ['#markup' => $value['current_value']]],
          'expected_value' => ['data' => ['#markup' => $value['expected_value']]],
          'changed' => ['data' => ['#markup' => \Drupal::service('date.formatter')->format($time_stamp, 'short')]],
          'last_accessed' => ['data' => ['#markup' => $last_accessed != 0 ? \Drupal::service('date.formatter')->format($last_accessed, 'short') : t('never')]],
        ];
      }
    }

    if (count($rows) > 0) {
      $header = [
        'user' => t('User'),
        'Field' => t('Field'),
        'current_value' => t('Current value'),
        'expected_value' => t('Expected value'),
        'changed' => t('Changed'),
        'last_accessed' => t('Last accessed'),
      ];
      $output['changes_table'] = [
        '#type' => 'verbose_table_result',
        '#title' => t('Changed users with privileged access'),
        '#header' => $header,
        '#rows' => $rows,
      ];
    }

    // Verbose output for all privileged users.
    $rows = [];

    foreach ($current_users as $user) {
      $created = $user['created'];
      $user_name = [
        'data' => [
          '#theme' => 'username',
          '#account' => User::load($user['id']),
        ]
      ];
      $rows[] = [
        'user' => $user_name,
        'roles' => ['data' => ['#markup' => $user['roles']]],
        'created' => ['data' => ['#markup' => \Drupal::service('date.formatter')->format($created, 'short')]],
        'last_accessed' => ['data' => ['#markup' => $user['last_accessed'] != 0 ? \Drupal::service('date.formatter')->format($user['last_accessed'], 'short') : t('never')]],
      ];
    }

    if (count($rows) > 0) {
      $header = [
        'user' => t('User'),
        'roles' => t('Roles'),
        'created' => t('Created'),
        'last_accessed' => t('Last accessed')
      ];
      $output['users_privileged'] = [
        '#type' => 'verbose_table_result',
        '#title' => t('All users with privileged access'),
        '#header' => $header,
        '#rows' => $rows,
      ];
    }

    // Verbose output for deleted users.
    $rows = [];

    foreach ($deleted_users as $user) {
      $rows[] = [
        'user' => ['data' => ['#markup' => $user['name']]],
        'roles' => ['data' => ['#markup' => $user['roles']]],
        'created' => ['data' => ['#markup' => \Drupal::service('date.formatter')->format($user['created'], 'short')]],
        'last_accessed' => ['data' => ['#markup' => $user['last_accessed'] != 0 ? \Drupal::service('date.formatter')->format($user['last_accessed'], 'short') : t('never')]],
      ];
    }

    if (count($rows) > 0) {
      $header = [
        'user' => t('User'),
        'roles' => t('Roles'),
        'created' => t('Created'),
        'last_accessed' => t('Last accessed')
      ];
      $output['deleted_users'] = [
        '#type' => 'verbose_table_result',
        '#title' => t('Deleted users with privileged access'),
        '#header' => $header,
        '#rows' => $rows,
      ];
    }

    // Show roles list with the permissions that are restricted for each.
    $roles_list = [];
    foreach (Role::loadMultiple($role_ids) as $role) {
      if (!$role->isAdmin()) {
        $restricted_permissions = array_intersect($this->restrictedPermissions, $role->getPermissions());
        $roles_list[] = $role->label() . ': ' . implode(", ", $restricted_permissions);
      }
    }
    $output['roles_list'] = [
      '#type' => 'fieldset',
      '#title' => t('List of roles with restricted permissions'),
      ['#type' => 'item', '#markup' => !empty($roles_list) ? implode('<br>', $roles_list) : t('None')],
    ];

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

      $form['reset_users'] = array(
          '#type' => 'submit',
          '#value' => t('Reset user data'),
          '#submit' => array(array($this, 'submitConfirmPrivilegedUsers')),
        );
      return $form;
  }

 /**
   * Resets current keyValue storage.
   *
   * @return array
   *   Available users.
   */
  public function submitConfirmPrivilegedUsers(array $form, FormStateInterface $form_state) {
      \Drupal::keyValue('monitoring.users')->deleteAll();
      return $form;
  }

}
