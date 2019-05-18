<?php

/**
 * @file
 * Contains integrations with forms.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Create moderators list of users.
 */
function _forum_access_forum_moderators_form($form_state, $tid) {
  // Find our moderator ACL. If tid exists it means that term isn't new
  // and is edited now.
  if (isset($tid)) {
    $acl_id = forum_access_get_acl($tid, 'moderate');
    $form = acl_edit_form($form_state, $acl_id, t('Moderators'));
    $form['warning'] = [
      '#type' => 'item',
      '#markup' => t('Moderators receive all grants above.'),
    ];
    $form['note'] = [
      '#type' => 'item',
      '#markup' => t('Note: Changes to moderators are not saved until you click Save below.'),
    ];
    return $form;
  }
}

/**
 * Save ACL configuration.
 */
function _forum_access_form_moderators_save(FormStateInterface $form_state) {
  $data = $form_state->getValue(['forum_access', 'moderators']);
  if (!empty($data)) {
    acl_save_form($data, 0);
  }
}

/**
 * Save roles permissions.
 */
function _forum_access_form_roles_permissions_save(FormStateInterface $form_state) {
  $access = $form_state->getValue(['forum_access', 'grants', 'checkboxes']);
  $tid = $form_state->getValue('tid');
  // Remove and re-create records.
  forum_access_set_settings($tid, $access);
}

/**
 * Update access records.
 */
function _forum_access_update_access_records($form_state) {
  $tid = $form_state->getValue('tid');
  $forum_access = $form_state->getValue('forum_access');
  switch ($forum_access['update_choice']) {
    case 'all':
      node_access_rebuild(TRUE);
      break;

    case 'batch':
      // Mass update in batch mode, modeled after node.module.
      $limit = $forum_access['update_limit'];
      $count = Drupal::database()
        ->select('node__taxonomy_forums')
        ->condition('taxonomy_forums_target_id', $tid)
        ->countQuery()
        ->distinct()
        ->execute()
        ->fetchField();
      $batch = [
        'title' => t('Updating content access permissions'),
        'file' => drupal_get_path('module', 'forum_access') . '/includes/forum_access.admin.inc',
        'operations' => [
          ['_forum_access_update_batch_operation', [$tid, $limit, $count]],
        ],
        'finished' => '_forum_access_update_batch_finished',
      ];
      batch_set($batch);
      break;

    case 'later':
      node_access_needs_rebuild(TRUE);
      break;
  }
}

/**
 * Ooperations for batch.
 */
function _forum_access_update_batch_operation($tid, $limit, $count, &$context) {
  $node_storage = \Drupal::entityManager()->getStorage('node');
  if (empty($context['sandbox'])) {
    // Initiate multistep processing.
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_node'] = 0;
    $context['sandbox']['max'] = $count;
  }
  $nids = \Drupal::entityQuery('node')
    ->condition('nid', $context['sandbox']['current_node'], '>')
    ->sort('nid', 'ASC')
    // Disable access checking since all nodes must be processed even if the
    // user does not have access. And unless the current user has the bypass
    // node access permission, no nodes are accessible since the grants have
    // just been deleted.
    ->accessCheck(FALSE)
    ->condition("taxonomy_forums.target_id", $tid, '=')
    ->execute();
  $node_storage->resetCache($nids);
  $nodes = Node::loadMultiple($nids);
  foreach ($nodes as $nid => $node) {
    // To preserve database integrity, only write grants if the node
    // loads successfully.
    if (!empty($node)) {
      /** @var \Drupal\node\NodeAccessControlHandlerInterface $access_control_handler */
      $access_control_handler = \Drupal::entityManager()
        ->getAccessControlHandler('node');
      $grants = $access_control_handler->acquireGrants($node);
      \Drupal::service('node.grant_storage')->write($node, $grants);
    }
    $context['sandbox']['progress']++;
    $context['sandbox']['current_node'] = $nid;
  }
  // Multistep processing : report progress.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Post-processing for forum_access_form_submit().
 */
function _forum_access_update_batch_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('The content access permissions have been updated.'));
    node_access_needs_rebuild(FALSE);
  }
  else {
    drupal_set_message(t('The content access permissions have not been properly updated.'), 'error');
  }
}

/**
 * List of permissions.
 */
function _forum_access_forum_permissions_form() {
  $variables = [];
  $permissions = [
    'access content' => 'system',
    'access comments' => 'comment',
    'create forum content' => 'node',
    'post comments' => 'comment',
    'skip comment approval' => 'comment',
    'edit own forum content' => 'node',
    'edit any forum content' => 'node',
    'delete own forum content' => 'node',
    'delete any forum content' => 'node',
    'administer comments' => 'comment',
    'administer forums' => 'forum',
    'administer nodes' => 'node',
    'access content overview' => 'node',
    'view own unpublished content' => 'node',
    'edit own comments' => 'comment',
  ];
  foreach ($permissions as $perm => $module) {
    $key = '@' . str_replace(' ', '_', $perm);
    $variables[$key] = _forum_access_permission_link($module, $perm);
  }
  $form = [
    '#type' => 'details',
    '#title' => t('Permissions information'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['list'] = [
    '#theme' => 'item_list',
    '#title' => t('Note that users need'),
    '#items' => [
      t('the <strong>@access_content</strong> and <strong>@access_comments</strong> permissions <strong>AND <em>View</em></strong> to be able to see this forum and its content at all,', $variables),
      t('the <strong>@create_forum_content</strong> (and similar) permissions <strong>AND <em>Post</em></strong> to be able to create forum content, and', $variables),
      t('the <strong>@post_comments</strong> (and optionally <strong>@skip_comment_approval</strong>) permission AND <em>Post</em> to be able to post comments/replies;', $variables),
      t('the <strong>@edit_own_forum_content</strong> or <strong>@edit_any_forum_content</strong> (and similar) permissions (<strong>OR <em>Edit</em></strong>) can be added if desired, <strong>plus</strong>', $variables),
      t('the <strong>@delete_own_forum_content</strong> or <strong>@delete_any_forum_content</strong> (and similar) permissions (<strong>OR <em>Delete</em></strong>) if desired;', $variables),
      t('the <strong>@administer_comments</strong> (global!) permission <strong>OR <em>Edit</em>/<em>Delete</em></strong> to be able to edit/delete comments;', $variables),
      t('the <strong>@administer_forums</strong> permission <strong>AND <em>View</em></strong> to be able to administer forums (and change access!).', $variables),
      t('Furthermore note that content which is not published is treated in a different way by Drupal: it can be viewed only by its author
        (with the <strong>@view_own_unpublished_content</strong> permission) or by users with the <strong>@administer_nodes</strong> permission. Unpublished comments and
        replies are accessible to users with <strong><em>Edit</em> OR <em>Delete</em></strong>, <strong>OR</strong> with the <strong>@administer_comments</strong> permission,
        but they are never counted on the forum page.', $variables),
      t('The global <strong>@edit_own_comments</strong> permission is ignored, but the edit/delete forum content permissions are extended to comments;
        the per-forum <strong><em>Edit</em></strong> and <strong><em>Delete</em></strong> apply to both nodes and comments, too.', $variables),
    ],
  ];
  return $form;
}

/**
 * Generate permissions link.
 */
function _forum_access_permission_link($module, $permission) {
  $permissions = &drupal_static(__FUNCTION__, []);
  if (empty($permissions)) {
    $permission_handler = \Drupal::service('user.permissions');
    $permissions_data = $permission_handler->getPermissions();
    foreach ($permissions_data as $perm => $perm_item) {
      $provider = $perm_item['provider'];
      $permissions[$provider][$perm] = strip_tags($perm_item['title']);
    }
  }
  return $permissions[$module][$permission];
}

/**
 * Get form table with roles grants.
 */
function _forum_access_forum_grants_form($form_state, $settings) {
  $roles = user_roles();
  $administer_forums_roles = user_roles(FALSE, 'administer forums');
  $bypass_node_access_roles = user_roles(FALSE, 'bypass node access');
  $vid = \Drupal::config('forum.settings')->get('vocabulary');
  $cols = [
    'view' => t('View this forum'),
    'create' => t('Post in this forum'),
    'update' => t('Edit posts'),
    'delete' => t('Delete posts'),
  ];
  _forum_access_grants_form_elements($form, $roles, $administer_forums_roles, $bypass_node_access_roles, $cols, $settings);
  $form['info'] = [
    '#type' => 'item',
    '#description' => t('For explanations of <em class="placeholder">special cases</em>, hover your mouse over role names.'),
  ];
  return $form;
}

/**
 * Get roles permissions form for container.
 */
function _forum_access_container_grants_form($form_state, $settings) {
  $roles = user_roles();
  $administer_forums_roles = user_roles(FALSE, 'administer forums');
  $bypass_node_access_roles = user_roles(FALSE, 'bypass node access');
  $vid = \Drupal::config('forum.settings')->get('vocabulary');
  $vocabulary = Vocabulary::load($vid);
  $cols = [
    'view' => t('View this container'),
    'create' => t('See this container in the forums selection list'),
  ];
  _forum_access_grants_form_elements($form, $roles, $administer_forums_roles, $bypass_node_access_roles, $cols, $settings);
  $form['info'] = [
    '#type' => 'item',
    '#description' => t('Users who can see any forum or container within this one should get the <strong><em>View</em></strong> grant. <br />
      Users who can post to a forum within this container should get the <strong><em>See</em></strong> grant, so that the forum appears in the proper context in the %Forums selection list.',
      ['%Forums' => $vocabulary->label()]),
  ];
  return $form;
}

/**
 * Common grants form elements for forum and container.
 */
function _forum_access_grants_form_elements(&$form, $roles, $administer_forums_roles, $bypass_node_access_roles, $cols, $settings) {
  $form = [
    '#theme' => 'forum_access_table',
  ];
  // Build table rows.
  foreach ($roles as $rid => $role) {
    $form['rows'][$rid]['label'] = [
      '#type' => 'item',
      // @todo Add check_plain.
      '#markup' => $role->label(),
    ];
    $special = NULL;
    if (isset($administer_forums_roles[$rid])) {
      $special = t("This role has the '@administer_forums' permission, and granting 'View' enables the role holders to change the settings on this page, including Access Control!",
        [
          '@administer_forums' => t('administer forums'),
        ]);
      if (isset($bypass_node_access_roles[$rid])) {
        $special .= ' ' . t("Because the role also has the '@bypass_node_access' permission, it has full access to all nodes either way.",
            [
              '@bypass_node_access' => t('bypass node access'),
            ]);
      }
    }
    elseif (isset($bypass_node_access_roles[$rid])) {
      $special = t("This role has the '@bypass_node_access' permission and thus full access to all nodes.",
        [
          '@bypass_node_access' => t('bypass node access'),
        ]);
    }
    if (isset($special)) {
      $form['rows'][$rid] += [
        '#prefix' => '<em><span title="' . $special . '" class="forum-access-special-role">',
        '#suffix' => '</span></em>',
        '#disabled' => TRUE,
      ];
    }
    $roles[$rid] = '';
  }
  $form['header']['roles'] = [
    '#markup' => t('Roles'),
    '#tree' => TRUE,
  ];
  // Add checkboxes.
  foreach ($cols as $cid => $ctitle) {
    $form['checkboxes'][$cid] = [
      '#type' => 'checkboxes',
      '#options' => $roles,
      '#default_value' => $settings[$cid],
    ];
    $form['header'][$cid] = [
      '#markup' => $ctitle,
      '#tree' => TRUE,
    ];
  }
}
