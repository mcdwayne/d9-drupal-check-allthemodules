<?php

namespace Drupal\mailman_integration\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Drupal\user\UserInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Default controller for the mailman_integration module.
 */
class MailmanIntegrationController extends ControllerBase {

  /**
   * The database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Constructs a new MailmanIntegrationController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Mailman list view.
   */
  public function mailmanIntegrationViewList() {
    $build = [];
    $build['mm_head']['#markup'] = mailman_integration_list_header();
    $build['search_form'] = \Drupal::formBuilder()->getForm('Drupal\mailman_integration\Form\MailmanIntegrationSearchListForm');
    return $build;
  }

  /**
   * Add user callback.
   */
  public function mailmanIntegrationAddUserCallback($list_name) {
    $build = [];
    $build['list_form'] = \Drupal::formBuilder()->getForm('Drupal\mailman_integration\Form\MailmanIntegrationSubcribeUserForm', $list_name);
    $build['divider1']['#markup'] = $this->mailmanIntegrationContainerDivider();
    $build['list_user'] = \Drupal::formBuilder()->getForm('\Drupal\mailman_integration\Form\MailmanIntegrationListUserForm', $list_name);
    return $build;
  }

  /**
   * User serach callback.
   */
  public function mailmanIntegrationUserAcCallback(Request $request) {
    $string = $request->query->get('q');
    $matches = [];
    if ($string) {
      $result = $this->database->select('users_field_data', 'users')
        ->fields('users', ['name', 'uid'])
        ->condition('name', db_like($string) . '%', 'LIKE')
        ->range(0, 10)
        ->execute();
      foreach ($result as $user) {
        $name = SafeMarkup::checkPlain($user->name);
        $matches[] = array('value' => $name, 'label' => $name);
      }
    }
    return new JsonResponse($matches);
  }

  /**
   * Add role user callback.
   */
  public function mailmanIntegrationAddRoleUserCallback($list_name) {
    $build = [];
    $build['list_form'] = \Drupal::formBuilder()->getForm('\Drupal\mailman_integration\Form\MailmanIntegrationAddRolelistForm', $list_name);
    $build['divider1']['#markup'] = $this->mailmanIntegrationContainerDivider();
    $build['list_user'] = \Drupal::formBuilder()->getForm('\Drupal\mailman_integration\Form\MailmanIntegrationListUserForm', $list_name);
    return $build;
  }

  /**
   * Simple diver css class.
   */
  public function mailmanIntegrationContainerDivider() {
    $build = '<div class="mailman-divider"></div>';
    return $build;
  }

  /**
   * Mailman search list results.
   */
  public function getMailmanSearchReasults($type, $name, $header) {
    $select_stmt = $this->database->select('mailman_integration_list', 'ma');
    $select_stmt->fields('ma', [
      'bundle',
      'list_name',
      'list_owners',
      'description',
    ]
    );
    if ($type) {
      if ($type != 'all') {
        $select_stmt->condition('ma.bundle', $type);
      }
      if ($name) {
        $select_stmt->condition(db_or()
          ->condition('ma.list_name', '%' . db_like($name) . '%', 'LIKE')
          ->condition('ma.list_owners', '%' . db_like($name) . '%', 'LIKE')
        );
      }
    }
    $select_stmt->groupBy('list_name');
    $select_stmt->groupBy('bundle');
    $select_stmt->groupBy('list_owners');
    $select_stmt->groupBy('description');
    $select_stmt = $select_stmt->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header)->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(\Drupal::config('mailman_integration.settings')->get('mailman_integration_list_pagination'));
    $lists = $select_stmt->execute();
    return $lists;
  }

  /**
   * User can access the mailman integration List.
   */
  public function mailmanIntegrationUserAccess(UserInterface $user = NULL) {
    $account = \Drupal::currentUser();
    if ($account->hasPermission('administer mailman_integration')) {
      // Administrators can switch anyone's mailman List set.
      return AccessResult::allowed()->cachePerPermissions();
    }
    elseif ($user->id() == $account->id()) {
      return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
    }
    // No opinion.
    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * Insert the mailman list into  Mailman Integration custom table.
   */
  public function insertListData($params) {
    $entity_id     = ($params['entity_id']) ? $params['entity_id'] : NULL;
    $entity_type   = $params['entity_type'];
    $bundle        = $params['bundle'];
    $list_name     = $params['listname'];
    $list_owners   = isset($params['list_owner']) ? $params['list_owner'] : '';
    $list_desc     = isset($params['list_desc']) ? $params['list_desc'] : '';
    $list_visible = isset($params['visible_list']) ? $params['visible_list'] : 0;
    $insert_stmt = db_insert('mailman_integration_list');
    $insert_stmt->fields(array(
      'entity_id' => $entity_id,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'list_name' => $list_name,
      'list_owners' => $list_owners,
      'description' => $list_desc,
      'visible_to_user' => $list_visible,
      'created' => REQUEST_TIME,
    ));
    $mailman_id = $insert_stmt->execute();
    return $mailman_id;
  }

  /**
   * Insert mailman users into mailman custom table.
   */
  public function insertUsers($list_name, $mail, $list_id = 0, $uid = 0, $created_by = 0) {
    $user = \Drupal::currentUser();
    if (!$created_by) {
      $created_by = ($user->id()) ? $user->id() : 0;
    }
    if (!$list_id) {
      $list_id = mailman_integration_get_list_id($list_name);
    }
    if (!$list_id || !$uid) {
      return;
    }
    $insert_stmt = db_insert('mailman_list_users');
    $insert_stmt->fields([
      'uid'    => $uid,
      'list_id' => $list_id,
      'mail'    => $mail,
      'created_by' => $created_by,
      'created'    => REQUEST_TIME,
    ]);
    $insert_stmt->execute();
  }

  /**
   * Get the mailman list if user can subscribe.
   *
   * @param int $uid
   *   The user id.
   */
  public function getUserlist($uid) {
    if (!$uid) {
      return array();
    }
    $select_stmt = $this->database->select('mailman_integration_list', 'list');
    $select_stmt->leftJoin('mailman_list_users', 'u', 'u.list_id = list.list_id');
    $select_stmt->fields('list', array('list_id', 'list_name'));
    $select_stmt->condition('u.uid', $uid);
    $select_stmt->groupBy('list.list_name');
    $select_stmt->groupBy('list.list_id');
    $result = $select_stmt->execute()->fetchAll();
    return $result;
  }

  /**
   * Select the mailman list from Mailman Integration custom table.
   */
  public function selectListData($params) {
    $entity_id    = ($params['entity_id']) ? $params['entity_id'] : NULL;
    $entity_type  = $params['entity_type'];
    $bundle       = $params['bundle'];
    $list_name     = $params['listname'];
    $select_stmt = $this->database->select('mailman_integration_list', 'ma');
    $select_stmt->fields('ma', [
      'list_id',
      'entity_id',
      'entity_type',
      'bundle',
      'list_name',
      'list_owners',
      'description',
      'visible_to_user',
    ]
    );
    if ($entity_id) {
      $select_stmt->condition('ma.entity_id', $entity_id);
    }
    if ($entity_type) {
      $select_stmt->condition('ma.entity_type', $entity_type);
    }
    if ($bundle) {
      $select_stmt->condition('ma.bundle', $bundle);
    }
    if ($list_name) {
      $select_stmt->condition('ma.list_name', $list_name);
    }
    $result = $select_stmt->execute()->fetchAll();
    if (isset($params['role_list']) && $params['role_list']) {
      $role_list = array();
      foreach ($result as $role) {
        $role_list[] = trim(SafeMarkup::checkPlain($role->entity_id));
      }
      return $role_list;
    }
    elseif (isset($params['list_type']) && $params['list_type']) {
      $list_type = array();
      foreach ($result as $res) {
        $list_type[$res->list_name] = $res->bundle;
      }
      return $list_type;
    }
    return $result;
  }

  /**
   * Update the mailman list into Mailman Integration custom table.
   */
  public function updateListData($params) {
    $list_name     = $params['list_name'];
    $list_owners   = $params['list_owner'] ? $params['list_owner'] : '';
    $list_desc     = $params['list_desc'] ? $params['list_desc'] : '';
    $list_visible = isset($params['visible_list']) ? $params['visible_list'] : 0;
    $upd_stmt = db_update('mailman_integration_list');
    $upd_stmt->fields(array(
      'list_owners'    => $list_owners,
      'description'    => $list_desc,
      'visible_to_user' => $list_visible,
      'changed'        => REQUEST_TIME,
    ));
    $upd_stmt->condition('list_name', $list_name);
    $upd_stmt->execute();
  }

  /**
   * List the mailman users from mailman custom table.
   */
  public function selectListUsers($list_id = 0, $search_val = '', $execute = 0) {
    if (!$list_id) {
      return array();
    }
    $select_stmt = $this->database->select('mailman_list_users', 'list_user');
    $select_stmt->leftJoin('users_field_data', 'u', 'u.uid = list_user.uid');
    $select_stmt->fields('list_user', array('id',
      'uid',
      'list_id',
      'mail',
      'created_by',
      'created',
    )
    );
    $select_stmt->fields('u', array('name'));
    if ($list_id) {
      $select_stmt->condition('list_user.list_id', $list_id);
    }
    if ($search_val) {
      $select_stmt->condition(db_or()
        ->condition('u.name', '%' . db_like($search_val) . '%', 'LIKE')
        ->condition('list_user.mail', '%' . db_like($search_val) . '%', 'LIKE')
      );
    }
    if ($execute) {
      $result = $select_stmt->execute()->fetchAll();
      return $result;
    }
    else {
      return $select_stmt;
    }
  }

  /**
   * Delete the mailman users from mailman custom table.
   */
  public function removeListUsers($list_name, $mail = 0, $list_id = 0, $force_delete_by_id = 0) {
    if (!$list_id) {
      $list_id = mailman_integration_get_list_id($list_name);
    }
    $delete_stmt = db_delete('mailman_list_users');
    if ($list_id && $mail) {
      $delete_stmt->condition('list_id', $list_id);
      $delete_stmt->condition('mail', $mail);
    }
    elseif ($force_delete_by_id) {
      $delete_stmt->condition('list_id', $list_id);
    }
    $delete_stmt->execute();
  }

  /**
   * Select the mailman list roles from Mailman Integration roles table.
   */
  public function selectListRoles($params) {
    $role_id    = ($params['role_id']) ? $params['role_id'] : NULL;
    $list_id    = $params['list_id'];
    $list_name   = $params['listname'];
    $select_stmt = $this->database->select('mailman_list_roles', 'ma');
    $select_stmt->fields('ma', array('id',
      'list_id',
      'role_id',
      'list_name',
      'created',
    )
    );
    if ($role_id) {
      $select_stmt->condition('ma.role_id', $role_id);
    }
    if ($list_id) {
      $select_stmt->condition('ma.list_id', $list_id);
    }
    if ($list_name) {
      $select_stmt->condition('ma.list_name', $list_name);
    }
    $result = $select_stmt->execute()->fetchAll();
    if (isset($params['role_list']) && $params['role_list']) {
      $role_list = array();
      foreach ($result as $role) {
        $role_id = SafeMarkup::checkPlain($role->role_id);
        $role_list[] = trim($role_id);
      }
      return $role_list;
    }
    return $result;
  }

  /**
   * Get the drupal roles.
   */
  public function getRoleList($roles, $return_uid = 0) {
    $user_list = array();
    if (!count($roles)) {
      return $user_list;
    }
    $query = $this->database->select('user__roles', 'ur');
    $query->leftJoin('users_field_data', 'u', 'u.uid = ur.entity_id AND bundle = :bundle', array(':bundle' => 'user'));
    $query->fields('u', array('uid', 'mail', 'name'));
    $query->fields('ur', array('roles_target_id'));
    $query->condition('ur.roles_target_id', $roles, 'IN');
    $result = $query->execute();
    if ($result) {
      if ($return_uid) {
        foreach ($result as $row) {
          $user_list[$row->mail] = array('name' => $row->name, 'uid' => $row->uid);
        }
      }
      else {
        foreach ($result as $row) {
          $user_list[$row->mail] = $row->name;
        }
      }
    }
    return $user_list;
  }

  /**
   * Insert the mailman list roles into  Mailman Integration roles table.
   */
  public function insertListRoles($params) {
    $role_id    = ($params['role_id']) ? $params['role_id'] : NULL;
    $list_id    = $params['list_id'];
    $list_name   = $params['listname'];
    $insert_stmt = db_insert('mailman_list_roles');
    $insert_stmt->fields(array(
      'list_id'   => $list_id,
      'role_id'   => $role_id,
      'list_name' => $list_name,
      'created'   => REQUEST_TIME,
    )
    );
    $list_id = $insert_stmt->execute();
    return $list_id;
  }

  /**
   * Remove the mailman list role into  Mailman Integration roles table.
   */
  public function deleteListRole($params) {
    $role_id    = ($params['role_id']) ? $params['role_id'] : NULL;
    $list_id    = $params['list_id'];
    $list_name   = $params['listname'];
    $delete_stmt = db_delete('mailman_list_roles');
    if ($list_id) {
      $delete_stmt->condition('list_id', $list_id);
    }
    if ($role_id) {
      $delete_stmt->condition('role_id', $role_id);
    }
    if ($list_name) {
      $delete_stmt->condition('list_name', $list_name);
    }
    $delete_stmt->execute();
  }

  /**
   * Delete the mailman list into Mailman Integration custom table.
   */
  public function deleteListData($params) {
    $entity_id    = ($params['entity_id']) ? $params['entity_id'] : NULL;
    $entity_type  = $params['entity_type'];
    $bundle       = $params['bundle'];
    $list_name     = $params['listname'];
    $delete_stmt = db_delete('mailman_integration_list');
    if ($entity_id) {
      $delete_stmt->condition('entity_id', $entity_id);
    }
    if ($entity_type) {
      $delete_stmt->condition('entity_type', $entity_type);
    }
    if ($bundle) {
      $delete_stmt->condition('bundle', $bundle);
    }
    if ($list_name) {
      $delete_stmt->condition('list_name', $list_name);
    }
    $is_del = $delete_stmt->execute();
    return $is_del;
  }

  /**
   * Get the mailman list if user can subscribe.
   *
   * @param int $uid
   *   The user id.
   */
  public function userSubscribeList($uid) {
    if (!$uid) {
      return [];
    }
    $select_stmt = $this->database->select('mailman_integration_list', 'list');
    $select_stmt->leftJoin('mailman_list_users', 'u', 'u.list_id = list.list_id and u.uid =' . $uid);
    $select_stmt->fields('list', ['list_id',
      'list_name',
      'description',
    ]
    );
    $select_stmt->fields('u', ['uid']);
    $select_stmt->condition('list.visible_to_user', 1);
    $select_stmt->groupBy('list.list_id');
    $select_stmt->groupBy('list.list_name');
    $select_stmt->groupBy('list.description');
    $select_stmt->groupBy('u.uid');
    $result = $select_stmt->execute()->fetchAll();
    return $result;
  }

}
