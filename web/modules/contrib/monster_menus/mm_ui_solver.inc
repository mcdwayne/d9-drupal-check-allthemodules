<?php

/**
 * @file
 * Permissions solver
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\monster_menus\Constants;
use Drupal\user\Entity\User;
use Drupal\Core\Link;
use Drupal\Core\Url;

function mm_ui_solver_form($form) {
  _mm_ui_userlist_setup(array(-1 => ''), $form, 'solver', t("User's name:"), TRUE, '');
  $form['solver-choose']['#title'] = '';
  $form['solver']['#mm_list_other_callback'] = 'mmSolverCallback';
  $form['solver']['#description'] = t('Choose a user to see their actual permissions for this page, taking into account the permissions of all parent pages.');

  return $form;
}

function mm_ui_solver_link(&$form, $mmtid) {
  mm_static($form, 'solver_mm_list_callback', $mmtid);
  $form['solver_form'] = mm_ui_solver_form(array());
  $form['solver_form']['#prefix'] = '<div class="hidden" id="solver-form">';
  $form['solver_form']['#suffix'] = '<div id="mm-solver-table"></div></div>';
  $title = t('Solve permissions issues');
  $form['mm-solver-link'] = array(
    '#type' => 'link',
    '#weight' => -10,
    '#prefix' => '<div id="mm-solver-link">',
    '#suffix' => '</div>',
    '#title' => $title,
    '#url' => Url::fromRoute('monster_menus.solver', ['mm_tree' => $mmtid, 'user' => 0]),
    '#options' => array(
      'fragment' => 'solver-form',
      'external' => TRUE,
      'attributes' => array(
        'id' => mm_ui_modal_dialog(array('minWidth' => 500, 'minHeight' => 400), $form),
        'title' => $title,
      ),
    ),
  );
}

/**
 * Show permissions for a tree entry
 *
 * @param $mmtid
 *   MM tree ID of entry to query
 * @param $acct
 *   Account being tested
 * @return
 *   The HTML code for the results
 */
function mm_ui_solver_table($mmtid, User $acct) {
  $list = mm_content_get_parents_with_self($mmtid, FALSE, FALSE);

  $user_access = array();
  $is_admin = $acct->id() == 1;
  foreach (array('administer all menus', 'administer all users', 'administer all groups', 'view all menus') as $access_mode) {
    if ($acct->isAuthenticated() || substr($access_mode, 0, 4) == 'view') {
      $user_access[$access_mode] = $acct->hasPermission($access_mode);
    }
    else {
      $user_access[$access_mode] = FALSE;
    }
  }
  $is_admin |= $user_access['administer all menus'];

  $is_user = $is_group = $is_recycled = FALSE;
  $can_read = $can_recycle = TRUE;

  $header = array(t('Page'), t('Edit'), t('Add subpages'), t('Add content'), t('Read'));
  $rows = array();
  $all_modes = array(
    Constants::MM_PERMS_WRITE => 1,
    Constants::MM_PERMS_SUB   => 1,
    Constants::MM_PERMS_APPLY => 1,
    Constants::MM_PERMS_READ  => 1,
  );
  $everyone = array(
    Constants::MM_PERMS_WRITE => t('everyone can edit'),
    Constants::MM_PERMS_SUB   => t('everyone can add subpages'),
    Constants::MM_PERMS_APPLY => t('everyone can add content'),
    Constants::MM_PERMS_READ  => t('everyone can read')
  );
  $readable = array(
    Constants::MM_PERMS_WRITE => t('can edit this page'),
    Constants::MM_PERMS_SUB   => t('can add subpages to this page'),
    Constants::MM_PERMS_APPLY => t('can add content to this page'),
  );
  $yes = ['#theme' => 'mm_ui_mark_yesno', '#yes' => TRUE];
  $yes = \Drupal::service('renderer')->render($yes);
  $no =  ['#theme' => 'mm_ui_mark_yesno', '#yes' => FALSE];
  $no =  \Drupal::service('renderer')->render($no);
  foreach ($list as $entry) {
    $modes = array();
    $reasons = array(
      Constants::MM_PERMS_WRITE => array(),
      Constants::MM_PERMS_SUB   => array(),
      Constants::MM_PERMS_APPLY => array(),
      Constants::MM_PERMS_READ  => array(),
    );
    $item = mm_content_get($entry);

    if ($entry == mm_content_users_mmtid()) $is_user = TRUE;
    elseif ($entry == mm_content_groups_mmtid()) $is_group = TRUE;

    if ($item->name == Constants::MM_ENTRY_NAME_RECYCLE) $is_recycled = TRUE;

    $name = mm_content_get_name($item);
    if ($entry != 1) {
      $url = mm_content_user_can($entry, Constants::MM_PERMS_WRITE) ? 'monster_menus.handle_page_settings' : 'entity.mm_tree.canonical';
      $name = Link::fromTextAndUrl($name, Url::fromRoute($url, ['mm_tree' => $entry]))->toString();
    }
    $row = array($name);

    if ($is_admin || ($is_user && $user_access['administer all users'] || $is_group && $user_access['administer all groups'])) {
      $modes = $all_modes;
      $reasons[Constants::MM_PERMS_WRITE][] = $reasons[Constants::MM_PERMS_SUB][] = $reasons[Constants::MM_PERMS_APPLY][] = $reasons[Constants::MM_PERMS_READ][] = t('is an administrator');
    }

    if (!$is_admin && $user_access['view all menus']) {
      $modes[Constants::MM_PERMS_READ] = 1;
      $reasons[Constants::MM_PERMS_READ][] = t('can view all pages');
    }

    if (!$can_read) {
      $reasons[Constants::MM_PERMS_WRITE][] = $reasons[Constants::MM_PERMS_SUB][] = $reasons[Constants::MM_PERMS_APPLY][] = $reasons[Constants::MM_PERMS_READ][] = t('cannot read parent');
    }
    else {
      if ($item->uid == $acct->id()) {
        $modes = $all_modes;
        $reasons[Constants::MM_PERMS_WRITE][] = $reasons[Constants::MM_PERMS_SUB][] = $reasons[Constants::MM_PERMS_APPLY][] = $reasons[Constants::MM_PERMS_READ][] = t('is the owner');
      }

      foreach ($everyone as $mode => $reason)
        if (strpos($item->default_mode, $mode) !== FALSE) {
          $modes[$mode] = 1;
          $reasons[$mode][] = $reason;
        }

      $db = Database::getConnection();
      $select = $db->select('mm_tree_access', 'a');
      $select->join('mm_group', 'g', 'g.gid = a.gid');
      $select->leftJoin('mm_tree', 't', 't.mmtid = g.gid');
      $select->leftJoin('mm_virtual_group', 'v', 'v.vgid = g.vgid');
      $and = new Condition('AND');
      $and->condition('g.vgid', 0)
        ->condition('g.uid', $acct->id());
      $or = new Condition('OR');
      $or->condition('v.uid', $acct->id())
        ->condition($and);
      $select->fields('a', array('mode', 'gid'))
        ->condition('a.mmtid', $entry)
        ->condition($or);
      $result = $select->execute();
      foreach ($result as $mode_obj) {
        $modes[$mode_obj->mode] = 1;
        $reasons[$mode_obj->mode][] = $mode_obj->gid < 0 ? t('is a listed user') : t('is in the group %name', array('%name' => mm_content_get_name($mode_obj->gid)));
      }

      if ($item->name == Constants::MM_ENTRY_NAME_RECYCLE) {
        $can_recycle = $can_recycle && mm_content_user_can_recycle($entry, Constants::MM_PERMS_READ, $acct);
        if ($can_recycle) {
          $reasons[Constants::MM_PERMS_READ][] = t('can edit something in bin');
          $modes[Constants::MM_PERMS_READ] = 1;
        }
        else {
          $reasons[Constants::MM_PERMS_READ][] = t('cannot edit anything in bin');
        }
      }
      else {
        if (isset($modes[Constants::MM_PERMS_WRITE])) {
          $modes[Constants::MM_PERMS_SUB] = $modes[Constants::MM_PERMS_APPLY] = $modes[Constants::MM_PERMS_READ] = 1;
          $reasons[Constants::MM_PERMS_SUB][] = $reasons[Constants::MM_PERMS_APPLY][] = $reasons[Constants::MM_PERMS_READ][] = $readable[Constants::MM_PERMS_WRITE];
        }
        else {
          foreach (array(Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY) as $mode) {
            if (isset($modes[$mode])) {
              $modes[Constants::MM_PERMS_READ] = 1;
              $reasons[Constants::MM_PERMS_READ][] = $readable[$mode];
            }
          }
        }

        if ($is_recycled) {
          if (!isset($modes[Constants::MM_PERMS_READ]) || !$can_recycle) {
            $reasons[Constants::MM_PERMS_READ][] = t('cannot edit anything in bin');
          }
        }
      }
    }

    foreach (array_keys($all_modes) as $mode) {
      if (!$reasons[$mode]) $reasons[$mode][] = t('not permitted');
      $tip = [
        '#theme' => 'tooltip',
        '#text' => isset($modes[$mode]) ? $yes : $no,
        '#title' => t('reason(s)'),
        '#tip' => '<ul><li>' . join('; </li><li>', $reasons[$mode]) . '</li></ul>',
        '#html' => TRUE,
      ];
      $row[] = \Drupal::service('renderer')->render($tip);
    }

    $rows[] = $row;
    $can_read = isset($modes[Constants::MM_PERMS_READ]);
  }

  return [
    [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('id' => 'mm-solver'),
    ],
    [
      '#markup' => '<div class="description">' . t('Put the mouse over any @yes or @no symbol above to see the reason(s).<br /><b>Note:</b> The table above does not take into account any unsaved changes to settings.', array('@yes' => $yes, '@no' => $no)) . '</div>'
    ],
  ];
}
