<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\EditContentForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Controller\DefaultController;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EditContentForm extends FormBase {
  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_ui_content_edit';
  }

  /**
   * Constructs an object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  public function buildForm(array $form, FormStateInterface $form_state, $item = NULL, $mmtid = NULL, $is_group = FALSE, $is_new = FALSE, $is_search = FALSE) {
    $x = mm_ui_strings($is_group);
    $all_menus = $this->currentUser()->hasPermission('administer all menus');
    $ilist = array_combine(array(1, 2, 3, 4, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 75, 100), array(1, 2, 3, 4, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 75, 100));

    $form['path'] = array(
      '#type' => 'value',
      '#value' => $mmtid,
    );

    $owner = $form_state->isSubmitted() ? $form_state->getValue('owner') : (isset($item->uid) ? $item->uid : '');
    $is_site_root = $mmtid == 1 || !$is_new && (!empty($item->parent) && $item->parent == 1 && !$item->perms[Constants::MM_PERMS_IS_USER]);

    if (!$is_new && $this->currentUser()->hasPermission('see create/modify times')) {
      // this code correctly handles legacy tree nodes without creation dates/users
      if (!empty($item->ctime) && !is_null($item->cuid)) {
        $x['@ctime'] = mm_format_date($item->ctime, 'medium');
        $x['@cuser'] = mm_ui_uid2name($item->cuid, TRUE);
      }

      if (!empty($item->mtime) && !is_null($item->muid)) {
        $x['@mtime'] = mm_format_date($item->mtime, 'medium');
        $x['@muser'] = mm_ui_uid2name($item->muid, TRUE);
      }

      if (isset($x['@ctime'])) $msg = $this->t('This @thing was created by @cuser on @ctime.', $x);

      if (isset($x['@mtime']) && (!isset($x['@ctime']) || $x['@mtime'] != $x['@ctime']))
        if (!empty($msg)) $msg .= ' ' . $this->t('It was last modified by @muser on @mtime.', $x);
        else $msg = $this->t('This @thing was last modified by @muser on @mtime.', $x);

      if (!empty($msg)) {
        $form['moddate'] = array(
          '#markup' => $msg,
        );
      }
    }

    if ($is_group) {
      $form['is_group'] = array(
        '#type' => 'value',
        '#value' => TRUE,
      );
    }

    if ($is_new) {
      $form['is_new'] = array(
        '#type' => 'value',
        '#value' => TRUE,
      );
    }
    else {
      $form['weight'] = array(
        '#type' => 'value',
        '#value' => $item->weight,
      );
    }

    $flags_not_admin = [];
    foreach (monster_menus_mm_tree_flags() as $flag => $val) {
      $flags_not_admin[$flag] = isset($item->flags[$flag]) && !$all_menus;
    }

    _mm_ui_form_array_merge($form, 'settings_perms', array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Permissions'),
      '#collapsible' => TRUE, '#collapsed' => FALSE,
    ));

    if ($flags_not_admin['limit_write']) {
      $form['settings_perms']['message'] = array('#type' => 'item', '#input' => FALSE, '#markup' => $this->t('<p>You are not allowed to modify the first column of the permissions.</p>'));
    }

    if (DefaultController::menuAccessSolverByMMTID($mmtid)) {
      module_load_include('inc', 'monster_menus', 'mm_ui_solver');
      mm_ui_solver_link($form['settings_perms'], $mmtid);
    }

    $types = static::getPermsLabels(isset($item->flags['limit_write']), $is_group, $x);
    if ($is_group) unset($types[Constants::MM_PERMS_APPLY]);

    $default_modes = array();
    if (!$form_state->hasValue('group_r_everyone') && isset($item->default_mode)) {
      $default_modes = explode(',', $item->default_mode);
    }

    $users = $groups = array();

    if (!$is_search) {
      // individual users
      if ($form_state->hasValue('all_values_user')) {
        $form_state['post']['path'] = $mmtid;
        list($groups, $users,) = _mm_ui_form_parse_perms($form_state, NULL, FALSE);
      }
      else {
        $gids = array();
        $select = $this->database->select('mm_tree', 't');
        $select->join('mm_tree_access', 'a', 'a.mmtid = t.mmtid');
        $select->fields('a', array('gid'))
          ->condition('a.gid', 0, '<')
          ->condition('a.mmtid', $item->mmtid);
        $result = $select->execute();
        foreach ($result as $r) {
          $gids[] = $r->gid;
        }

        if ($gids) {
          $users_in_groups = mm_content_get_users_in_group($gids, NULL, FALSE, 0);
          if (!is_null($users_in_groups)) {
            foreach ($users_in_groups as $uid => $usr) {
              if (is_numeric($uid) && $uid >= 0) {
                $select = $this->database->select('mm_group', 'g');
                $select->join('mm_tree_access', 'a', 'a.gid = g.gid');
                $select->addExpression('GROUP_CONCAT(a.mode)', 'modes');
                $select->condition('a.gid', 0, '<')
                  ->condition('g.gid', $gids, 'IN')
                  ->condition('g.uid', $uid, 'IN');
                $r = $select->execute()->fetchObject();
                if ($r) {
                  $users[$uid]['modes'] = explode(',', $r->modes);
                  $users[$uid]['name'] = $usr;
                }
              }
            }
          }
        }

        $temp_perms = mm_content_get_perms($item->mmtid, FALSE, TRUE, TRUE);
        $allowed = array();
        foreach (array(Constants::MM_PERMS_WRITE, Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY, Constants::MM_PERMS_READ) as $mode) {
          foreach (array_keys($temp_perms[$mode]['groups']) as $gid) {
            if (!isset($allowed[$gid])) {
              $allowed[$gid] = !$is_new || mm_content_user_can($gid, Constants::MM_PERMS_APPLY);
              if ($allowed) {
                $members = mm_content_get_users_in_group($gid, '<br />', FALSE, 20, TRUE, $form);
                if ($members == '') $members = $this->t('(none)');
                $groups[$gid]['name'] = mm_content_get_name($gid);
                $groups[$gid]['members'] = $members;
              }
            }
            $groups[$gid]['modes'][] = $mode;
          }
        }
      }
    }

    $this->permissionsForm($form['settings_perms'], $types, $default_modes, $groups, $users, $owner, $is_search, $flags_not_admin['limit_write'], $x);

    if (!$is_search) {
      $node_prop_desc = '';
      if ($this->currentUser()->hasPermission('propagate page perms')) {
        $form['settings_perms']['propagate'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Copy these permissions to all @subthings of this @thing', $x),
          '#default_value' => FALSE,
          '#description' => $this->t('If this option is checked, the permissions will be copied to all @subthings of this one that you have permission to change.', $x),
        );
        $node_prop_desc = ' ' . $this->t('If the option above is also checked, permissions will be copied to the content on all @subthings.', $x);
      }

      $form['settings_perms']['node_propagate'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Copy these permissions to all content on this @thing', $x),
        '#access' => !$is_group && $this->currentUser()->hasPermission('propagate node perms'),
        '#default_value' => FALSE,
        '#description' => $this->t('If this option is checked, the permissions will be copied to all pieces of content on this @thing that you have permission to change.', $x) . $node_prop_desc,
      );
    }   // !$is_search

    $form['settings_perms']['hover'] = array(
      '#type' => 'value',
      '#value' => $is_new ? '' : $item->hover,
    );

    $form['additional_settings'] = array(
      '#type' => 'vertical_tabs',
    );

    _mm_ui_add_summary_js($form);   // Initialize summaries.
    if (isset($item->flags['limit_name']) && !$all_menus) {
      $form['settings_general']['name'] = array(
        '#type' => 'value',
        '#value' => $item->name,
        '#group' => 'additional_settings',
      );
    }
    else {
      $form['settings_general'] = array(
        '#type' => 'details',
        '#title' => $this->t('General settings'),
        '#description' => $this->t('General settings for this @thing', $x),
        '#group' => 'additional_settings',
      );
      _mm_ui_add_summary_js($form['settings_general'], 'settings_general');
      $form['settings_general']['name'] = array(
        '#type' => 'textfield',
        '#title' => $is_group ? $this->t('Group name') : ($is_site_root ? $this->t('Site name') : $this->t('Page name')),
        '#default_value' => isset($item->name) ? $item->name : '',
        '#required' => TRUE,
        '#size' => 40,
        '#maxlength' => 128,
        '#description' => $is_group ? '' : $this->t('The name that appears in menus.'),
      );
    }

    if ($is_group) {
      if ($is_new ? $mmtid == 1 : $item->parent == 1) {
        $form['settings_general']['alias'] = array(
          '#type' => 'value',
          '#value' => $item->alias,
          '#group' => 'additional_settings',
        );
      }

      $form['members'] = array(
        '#type' => 'details',
        '#title' => $this->t('Group members'),
        '#open' => TRUE,
      );

      if (!$is_new && $this->currentUser()->hasPermission('administer permissions')) {
        $roles = [];
        foreach (Role::loadMultiple() as $rid => $role) {
          if ($role->mm_gid == $item->mmtid) {
            $roles[] = [
              'r_name' => $role->label(),
              'r_link' => Url::fromRoute('entity.user_role.edit_form', ['user_role' => $rid])->toString(),
              'p_link' => Url::fromRoute('entity.user_role.edit_permissions_form', ['user_role' => $rid])->toString(),
            ];
          }
        }

        if ($roles) {
          $strings = array('@plur' => (count($roles) == 1 ? $this->t('the role') : $this->t('these roles:') . ' '));
          $message = 'This group\'s members will be added to @plur ';
          foreach ($roles as $index => $role) {
            if ($index > 0) {
              $message .= ', ';
            }
            $message .= '<a href=":r_link' . $index . '">:r_name' . $index . '</a> (<a href=":p_link' . $index . '">permissions</a>)';
            $strings[':r_link' . $index] = $role['r_link'];
            $strings[':r_name' . $index] = $role['r_name'];
            $strings[':p_link' . $index] = $role['p_link'];
          }
          $form['members']['warning'] = array(
            '#type' => 'item',
            '#input' => FALSE,
            '#markup' => $this->t($message, $strings),
          );
        }
      }

      if (mm_content_is_vgroup($item->mmtid)) {
        $form['members']['#description'] = $this->t('Enter two portions of a SQL statement that returns the user IDs (uids) of the users in the group:<br />' .
          '<code>SELECT <span style="color:green;text-decoration:underline">ColumnName</span> AS uid <span style="color:green;text-decoration:underline">FROM TableName WHERE Condition</span></code>');
        if ($is_new) {
          $data = array(
            'field' => $this->t('ColumnName'),
            'qfrom' => $this->t('FROM TableName WHERE Condition'),
          );
        }
        else {
          $select = $this->database->select('mm_group', 'g');
          $select->join('mm_vgroup_query', 'v', 'g.vgid = v.vgid');
          $select->fields('v')
            ->condition('g.gid', $item->mmtid);
          $data = $select->execute()->fetchAssoc();
          if ($data) {
            $form['vgid'] = array(
              '#type' => 'value',
              '#value' => $data['vgid'],
            );

            $msgs = array(
              Constants::MM_VGROUP_DIRTY_NEXT_CRON => $this->t('This group will be regenerated during the next cron run.'),
              Constants::MM_VGROUP_DIRTY_FAILED => $this->t('This group has been marked as potentially corrupt, and must be examined before it will be regenerated.'),
              Constants::MM_VGROUP_DIRTY_REDO => $this->t('This group was previously marked as potentially corrupt, but will be regenerated during the next cron run.'),
            );
            if (isset($msgs[$data['dirty']])) {
              if ($data['dirty'] == Constants::MM_VGROUP_DIRTY_FAILED) {
                \Drupal::messenger()->addError($msgs[$data['dirty']]);
              }
              else {
                \Drupal::messenger()->addWarning($msgs[$data['dirty']]);
              }
            }
          }
        }

        $form['members']['qfield'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Column to select'),
          '#default_value' => isset($data['field']) ? $data['field'] : '',
          '#size' => 40,
          '#maxlength' => 40,
          '#description' => $this->t('The name of the database column (or a constant value) to SELECT; if blank, the group will not contain any users'),
        );
        $form['members']['qfrom'] = array(
          '#type' => 'textarea',
          '#title' => $this->t('FROM clause'),
          '#default_value' => $data['qfrom'],
          '#rows' => 4,
          '#wysiwyg' => FALSE,
          '#description' => $this->t('The FROM portion of the SELECT statement; can be blank'),
        );
        $form['members']['tokens'] = array(
          '#type' => 'details',
          '#title' => $this->t('Tokens'),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
          array (
            // FIXME: this probably doesn't work. Figure out the new method.
            '#theme' => 'token_tree',
            '#token_types' => array('mm_tree'),
            '#global_types' => FALSE,
          ),
        );
      }
      else {    // normal group
        $token = \Drupal::csrfToken()->get(Constants::MM_LARGE_GROUP_TOKEN . $mmtid . ($is_new ? 'new' : ''));
        $form['mm_form_token'] = array('#value' => $token);
        mm_static($form, 'group_table', $mmtid, $token, 'members');
        _mm_ui_userlist_setup($is_search ? array() : NULL, $form['members'], 'members', $this->t('Members:'), FALSE, $this->t('Choose the members of this group.'), '', !$is_search);
        if (!$is_search && isset($form['members']['members-add'])) {
          $form['members']['members-add'][] = ['#markup' => '&nbsp;&nbsp;&nbsp;'];
          $form['members']['members-add'][] = Link::createFromRoute($this->t('Download as CSV'), 'monster_menus.export', ['mm_tree' => $mmtid], ['attributes' => ['title' => $this->t('Download a CSV file containing the members of this group')]])->toRenderable();
        }
        $form['members']['upload_group'] = array(
          '#type' => 'details',
          '#prefix' => '<div class="clearfix"></div>',
          '#title' => $this->t('Upload group members'),
          '#open' => FALSE,
          '#weight' => 999,
        );
        $form['members']['upload_group']['upload_file'] = array(
          '#name' => 'files[upload_file]',
          '#type' => 'file',
          '#title' => $this->t('CSV file of usernames'),
          '#description' => $this->t('Upload a file containing one username per line. The contents of this file will replace the entire user list, regardless of any changes made above.'),
        );
        $form['members']['upload_group']['actions'] = array(
          '#type' => 'actions',
          'upload_action' => array(
            '#type' => 'submit',
            '#value' => $this->t('Upload'),
          ),
        );
      }
    }
    elseif (isset($item->flags['limit_alias']) && !$all_menus || ($is_new ? $mmtid == 1 : $item->parent == 1)) {      // !$is_group
      $form['settings_general']['alias'] = array(
        '#type' => 'value',
        '#value' => $item->alias,
        '#group' => 'additional_settings',
      );
    }
    else {
      $form['settings_general']['alias'] = array(
        '#type' => 'machine_name',
        '#title' => $this->t('URL name'),
        '#machine_name' => array(
          'exists' => 'mm_ui_machine_name_exists',
          'source' => array('settings_general', 'name'),
          'label' => $this->t('URL name'),
          'replace_pattern' => '[^-.\w]+',
          'replace' => '-',
          'error' => $this->t('The URL name must contain only letters, numerals, hyphens, periods and underscores.'),
          'standalone' => TRUE,
        ),
        '#default_value' => isset($item->alias) ? $item->alias : '',
        '#required' => (!isset($item->is_user_home) || !$item->is_user_home) && !$all_menus,
        '#size' => 20,
        '#maxlength' => 128,
        '#description' => $this->t('The name that will be used in the Web address of the page. ' .
          'Make this a shortened version of the Page name, using only lowercase letters, ' .
          'numerals, hyphens, periods and underscores.'),
        '#group' => 'additional_settings',
      );
      if (!$is_search && !$is_new) {
        $prevent_mode = mm_get_setting('prevent_showpage_removal');
        if ($prevent_mode != Constants::MM_PREVENT_SHOWPAGE_REMOVAL_NONE && ($showpage_mmtid = mm_content_test_showpage_mmtid($item->mmtid))) {
          if ($prevent_mode == Constants::MM_PREVENT_SHOWPAGE_REMOVAL_WARN || $this->currentUser()->hasPermission('administer all menus')) {
            $form['settings_general']['alias']['#description'] = $showpage_mmtid == $item->mmtid ?
              $this->t('<strong>Warning:</strong> This page contains dynamic content which may no longer appear if its URL name is changed.') :
              $this->t('<strong>Warning:</strong> The sub-page <a href=":link">@title</a> contains dynamic content which may no longer appear if this page\'s URL name is changed.', array('@title' => mm_content_get_name($showpage_mmtid), ':link' => mm_content_get_mmtid_url($showpage_mmtid)->toString()));
          }
          else {
            $form['settings_general']['alias'] = array(
              '#type' => 'value',
              '#value' => $item->alias,
              '#group' => 'additional_settings',
            );
            $form['settings_general']['message'] = array(
              '#type' => 'item',
              '#input' => FALSE,
              '#description' => $showpage_mmtid == $item->mmtid ?
                $this->t('This page\'s URL name cannot be changed because it contains dynamic content that depends on the name.') :
                $this->t('This page\'s URL name cannot be changed because the sub-page <a href=":link">@title</a> contains dynamic content that depends on the name.', array('@title' => mm_content_get_name($showpage_mmtid), ':link' => mm_content_get_mmtid_url($showpage_mmtid)->toString())),
              '#group' => 'additional_settings',
            );
          }
        }
      }
    }

    if (isset($form['settings_general']['alias'])) {
      $form['settings_general']['alias_name'] = array(
        '#type' => 'hidden',
        '#attributes' => array('class' => array('mm-alias-name')),
        '#value' => isset($item->alias) ? $item->alias : '',
        '#group' => 'additional_settings',
      );
    }

    if ($all_menus) {
      $form['flags'] = array(
        '#type' => 'details',
        '#title' => $this->t('Flags'),
        '#description' => $this->t('Attributes used in special queries; only administrators can edit this list'),
        '#group' => 'additional_settings',
      );
      _mm_ui_add_summary_js($form['flags'], 'flags');

      $predefined = mm_ui_flags_info();
      foreach ($predefined as $module => $list) {
        if (count($predefined) > 1 || $is_search) {
          $form['flags'][$module] = array(
            '#type' => 'details',
            '#title' => $module,
            '#open' => FALSE,
          );
          $form['flags'][$module]['title'] = array(
            '#markup' => "<h2>{$module}</h2>",
          );
        }
        ksort($list);
        $weight = 1;
        foreach ($list as $flag => $elem) {
          if (!isset($elem['#title'])) {
            $elem['#title'] = $flag;
          }
          $elem['#weight'] = $elem['#type'] == 'checkbox' ? $weight : $weight + 100;
          if ($elem['#type'] == 'textfield') {
            $elem['#maxlength'] = 255;
          }
          $elem['#default_value'] = $elem['#type'] == 'checkbox' ? isset($item->flags[$flag]) : (isset($item->flags[$flag]) ? $item->flags[$flag] : '');
          $elem['#prefix'] = "<div class='container-inline'>";
          $elem['#suffix'] = '&nbsp;&nbsp;&nbsp;' .
            $this->renderTooltip([
              '#text' => $this->t('help'),
              '#title' => $elem['#title'],
              '#tip' => $elem['#description']
            ]) . '</div>';

          $elem['#attributes'] = array('class' => array('flag-checkbox'));
          unset($elem['#description']);
          $form['flags'][$module]["flag_$flag"] = $elem;

          unset($item->flags[$flag]);
          $weight++;
        }
      }

      $free_flags = array();
      foreach ($item->flags as $flag => $data)
        if (!empty($data)) $free_flags[] = "$flag=$data";
        else $free_flags[] = $flag;

      $form['flags']['free_flags'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Others'),
        '#default_value' => join("\n", $free_flags),
        '#wysiwyg' => FALSE,
        '#weight' => 200,
        '#rows' => max(count($free_flags) + 1, 2),
        '#description' => $this->t('A free-form list of attributes, one per line. Can either be <code>name</code> or <code>name=value</code>.'),
      );
    }

    if (!$is_group) {
      $form['menu'] = array(
        '#type' => 'details',
        '#title' => $this->t('Menu and layout'),
        '#group' => 'additional_settings',
      );
      _mm_ui_add_summary_js($form['menu'], 'menu');

      $menu_vals = array(
        'bid' => Constants::MM_MENU_UNSET,
        'max_depth' => -1,
        'max_parents' => -1,
        'allow_reorder' => -1,
      );
      if (!$is_new) {
        $result = $this->database->select('mm_tree_block', 'b')
          ->fields('b', array('bid', 'max_depth', 'max_parents'))
          ->condition('b.mmtid', $item->mmtid)
          ->range(0, 1)
          ->execute();
        if ($r = $result->fetchAssoc()) $menu_vals = $r;
        $allow_reorder = mm_content_resolve_cascaded_setting('allow_reorder', $item->mmtid, $reorder_at, $reorder_parent);
        if ($reorder_at == $item->mmtid) {
          $menu_vals['allow_reorder'] = $allow_reorder;
        }
        elseif (!isset($reorder_at) && ($item->mmtid == 1 || $item->mmtid == mm_home_mmtid())) {
          $menu_vals['allow_reorder'] = 0;
        }
        else {
          $menu_vals['allow_reorder'] = -1;
        }
      }

      if (!$flags_not_admin['limit_hidden']) {
        $form['menu']['hide_menu'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Don\'t show this @thing in the menu', $x),
          '#description' => $this->t('If checked, this page will not be listed in the navigation menu but can still be accessed directly using its URL address.'),
          '#default_value' => isset($item->hidden) ? $item->hidden : FALSE,
        );
      }
      else {
        $form['menu']['hide_menu'] = array('#type' => 'value', '#value' => $item->hidden);
      }

      if ($flags_not_admin['limit_location'] || $is_site_root) {
        $form['menu']['menu_start'] = array(
          '#type' => 'value',
          '#value' => $is_site_root ? Constants::MM_MENU_BID : $menu_vals['bid'],
        );
      }
      else {
        $blocks = array(Constants::MM_MENU_UNSET => $this->t('Standard page'));
        $help =   array(Constants::MM_MENU_UNSET => $this->t('Creates a new page and the menu item that points to the page. The menu item will be indented and listed alphabetically under the parent menu item in the left navigation menu.'));
        foreach (mm_content_get_blocks(TRUE) as $bid => $b) {
          $blocks[$bid] = $b->toArray()['settings']['label'];
          $help[$bid] =   $b->toArray()['settings']['help'];
        }

        $form['menu']['menu_start'] = array(
          '#type' => $is_search ? 'select' : 'mm_help_radios',
          '#title' => $this->t('Location on screen'),
          '#default_value' => isset($blocks[$menu_vals['bid']]) ? $menu_vals['bid'] : mm_ui_mmlist_key0($blocks),
          '#attributes' => array('class' => array('settings-menu-start')),
          '#options' => $blocks,
          '#help' => $help,
        );
      }

      $form['menu']['max_depth'] = array(
        '#type' => 'select',
        '#title' => $this->t('Max. number of child levels to display'),
        '#default_value' => $menu_vals['max_depth'],
        '#attributes' => array('class' => array('settings-max-depth')),
        '#options' => _mm_ui_levels(),
      );
      if ($all_menus) {
        $form['menu']['max_parents'] = array(
          '#type' => 'select',
          '#title' => $this->t('Max. number of parent levels to display'),
          '#default_value' => $menu_vals['max_parents'],
          '#attributes' => array('class' => array('settings-max-parents')),
          '#options' => _mm_ui_levels(),
          '#description' => $this->t('As the user gets deeper down in the menu tree, higher-level entries will be removed. This keeps a deeply-nested menu from getting too indented. This setting is inherited by any @subthings.', $x),
        );
        $form['menu']['allow_reorder'] = array(
          '#type' => 'select',
          '#title' => $this->t('Allow the menu and its children to be reordered'),
          '#default_value' => $menu_vals['allow_reorder'],
          '#options' => array(-1 => $this->t('(inherit from parent page)'), 1 => $this->t('Yes'), 0 => $this->t('No')),
          '#description' => $this->t('Administrators always have the ability to reorder menus.'),
        );
      }
      else {
        $form['menu']['max_parents'] = array(
          '#type' => 'value',
          '#value' => $menu_vals['max_parents'],
        );
      }

      if ($this->currentUser()->hasPermission('show/hide post information')) {
        $form['defaults']['node_info'] = array(
          '#type' => 'select',
          '#title' => $this->t('Default attribution style'),
          '#default_value' => isset($item->node_info) ? $item->node_info : '',
          '#options' => _mm_ui_node_info_values($form),
          '#description' => $this->t('Unless disabled by an administrator, this value will be the default for all new content added to this page. You can change this behavior in the <em>Appearance</em> settings of the content.'),
        );
      }
      elseif (!$is_search) {
        $form['defaults']['node_info'] = array(
          '#type' => 'value',
          '#value' => $item->node_info,
        );
      }

      mm_add_js_setting($form, 'comment_enabled', mm_module_exists('comment'));

      if (!$is_group && mm_module_exists('comment')) {
        if (mm_get_setting('comments.finegrain_readability')) {
          $comments_readable = $is_new ? '' : mm_content_get_cascaded_settings($item->mmtid, 'comments_readable');
          $form['defaults']['comments_readable'] = array(
            '#type' => 'select',
            '#title' => $this->t('Who can read comments by default'),
            '#default_value' => $comments_readable,
            '#options' => _mm_ui_comment_read_setting_values($this->t('(inherit from parent page)')),
            '#description' => $this->t('This value will be the default for all new content added to this page. You can change this behavior in the <em>Comment settings</em> of the content.'),
          );
        }
        $form['defaults']['comment'] = array(
          '#type' => 'select',
          '#title' => $this->t('Who can add comments by default'),
          '#access' => $this->currentUser()->hasPermission('enable/disable comments') || $this->currentUser()->hasPermission('administer comments'),
          '#default_value' => isset($item->comment) ? $item->comment : '',
          '#options' => _mm_ui_comment_write_setting_values(),
          '#description' => $this->t('Unless disabled by an administrator, this value will be the default for all new content added to this page. You can change this behavior in the <em>Comment settings</em> of the content.'),
        );
      }

      if (Element::getVisibleChildren($form['defaults'])) {
        $form['defaults'] = array_merge($form['defaults'], array(
          '#type' => 'details',
          '#title' => $this->t('Defaults'),
          '#group' => 'additional_settings',
        ));
        _mm_ui_add_summary_js($form['defaults'], 'defaults');
      }

      $form['appearance'] = array(
        '#type' => 'details',
        '#title' => $this->t('Appearance'),
        '#group' => 'additional_settings',
      );
      $theme[''] = $this->t('(use parent\'s theme)');
      $all_themes = array();
      $allowed_themes = mm_content_resolve_cascaded_setting('allowed_themes', $item->mmtid, $theme_at, $theme_parent, $is_new);
      $desc_add = '';
      foreach (\Drupal::service('theme_handler')->listInfo() as $id => $t) {
        if ($t->status) {
          $name = $t->info['name'];
          if ($is_search || !count($allowed_themes) || in_array($name, $allowed_themes)) {
            $theme[$id] = $name;
          }
          elseif ($all_menus) {
            $theme[$id] = $name . ' *';
            $desc_add = ' ' . $this->t('Themes in the list ending with * are only available to administrators, based on the current settings.');
          }

          $all_themes[$id] = $name;
        }
      }
      natcasesort($theme);

      if (count($theme) > 2) {
        $parent_mmtids = $is_new ? mm_content_get_parents_with_self($item->mmtid) : mm_content_get_parents($item->mmtid);
        $parents = mm_content_get($parent_mmtids, array(), 0, TRUE);
        foreach (array_reverse($parents) as $parent) {
          if (!empty($parent->theme) && isset($all_themes[$parent->theme])) {
            $desc_add = ' ' . $this->t('If no theme is chosen here, the theme %themename from <a href=":link">@title</a> will be used.',
                array('%themename' => $parent->theme, '@title' => mm_content_get_name($parent), ':link' => mm_content_get_mmtid_url($parent->mmtid)->toString())) . $desc_add;
            break;
          }
        }

        if (!isset($parent_link)) {
          $desc_add = ' ' . $this->t('If no theme is chosen here, the default theme %themename will be used.',
              array('%themename' => \Drupal::config('system.theme')->get('default'))) . $desc_add;
        }

        $form['appearance']['theme'] = array(
          '#type' => 'select',
          '#title' => $this->t('Theme for this @thing and its children', $x),
          '#default_value' => isset($item->theme) ? $item->theme : '',
          '#options' => $theme,
          '#description' => $this->t('If chosen, the theme will be applied to this @thing and its @subthings, unless overridden at a lower level.', $x) . $desc_add,
        );
      }
      else {
        $form['appearance']['theme'] = array(
          '#type' => 'value',
          '#value' => $item->theme,
        );
      }

      if ($all_menus) {
        $desc = $this->t('This option is only available to administrators.');
        if (!$theme_parent) {
          $desc .= ' ' . $this->t('No parents of this @thing have theme limits, so if you deselect all themes here, non-admin users will be able to choose any theme in the list.', $x);
        }
        else {
          $desc .= ' ' . $this->t('To inherit the settings of the parent, <a href=":link">@title</a>, deselect all themes here.',
              array('@title' => mm_content_get_name($theme_parent), ':link' => mm_content_get_mmtid_url($theme_parent)->toString()));
        }
        natcasesort($all_themes);
        $form['appearance']['allowed_themes'] = array(
          '#type' => 'select',
          '#title' => $this->t('Allowed themes for this @thing and its children', $x),
          '#multiple' => TRUE,
          '#size' => 5,
          '#default_value' => !$is_new && $theme_at == $item->mmtid ? $allowed_themes : array(),
          '#options' => $all_themes,
          '#description' => $desc,
        );
      }

      _mm_ui_add_summary_js($form['appearance'], 'appearance');
      $form['appearance']['previews'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Show only the summaries ("teasers") of all contents'),
        '#default_value' => isset($item->previews) ? $item->previews : '',
      );

      if (mm_get_setting('pages.enable_rss')) {
        $form['appearance']['rss'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Enable the RSS feed for this page'),
          '#default_value' => !empty($item->rss) ? $item->rss : '',
        );
      }
      else {
        $form['appearance']['rss'] = array(
          '#type' => 'value',
          '#value' => isset($item->rss) ? $item->rss : '',
        );
      }

      $nodes_per_page = $is_new ? '' : mm_content_get_cascaded_settings($item->mmtid, 'nodes_per_page');
      if (is_null($nodes_per_page) && !$is_new && $item->mmtid == mm_home_mmtid()) {
        $nodes_per_page = Constants::MM_DEFAULT_NODES_PER_PAGE;
      }

      $form['appearance']['nodes_per_page'] = array(
        '#type' => 'select',
        '#title' => $this->t('Pieces of content to display at one time'),
        '#default_value' => $nodes_per_page,
        '#options' => array('' => $this->t('(inherit from parent page)')) + $ilist + array(
          0 => $this->t('(display all content immediately)'),
          -2 => $this->t('(display all content as needed)'),
        ),
        '#description' => $this->t('If more than this number of pieces of content is present, pagination controls will be displayed.'),
      );
      $form['appearance']['hide_menu_tabs'] = array(
        '#type' => 'select',
        '#title' => t('Hide the Contents/Settings/etc. tabs from non-admin users'),
        '#default_value' => $is_new ? -1 : mm_content_get_cascaded_settings($item->mmtid, 'hide_menu_tabs'),
        '#options' => array(-1 => t('(inherit from parent page)'), 1 => t('Hide'), 0 => t('Show')),
        '#access' => $all_menus,
      );

      if ($all_menus) {
        $form['settings_node_types'] = array(
          '#type' => 'details',
          '#title' => $this->t('Allowed content types'),
          '#group' => 'additional_settings',
        );
        $form['settings_node_types']['title'] = array(
          '#markup' => '<h3>' . $this->t('Allowed content types for this @thing and its children', $x) . '</h3>',
        );
        _mm_ui_add_summary_js($form['settings_node_types'], 'settings_node_types');

        $all_types = array();
        $allowed_node_types = mm_content_resolve_cascaded_setting('allowed_node_types', $item->mmtid, $types_at, $types_parent, $is_new);
        $types_inherit = $is_new || $types_at != $item->mmtid || count($allowed_node_types) == 0;
        /** @var NodeType $t */
        foreach (NodeType::loadMultiple() as $t)
          $all_types[$t->id()] = $t->label();
        natcasesort($all_types);

        $desc = $this->t('This option is only available to administrators.');
        if (!$types_parent) {
          $cb_desc = $this->t('<span style="color:red">No parents of this @thing have node types defined. Unless you uncheck this option and select something in the Node Types list, only administrators will be able to add content.</span>', $x);
          $form['settings_node_types']['#collapsed'] = FALSE;
        }
        else {
          $cb_desc = $this->t('If checked, the settings of the parent, @link, will be inherited',
            array('@link' => Link::fromTextAndUrl(mm_content_get_name($types_parent), mm_content_get_mmtid_url($types_parent))->toString()));
          $desc .= ' ' . $this->t('If you deselect all node types here and do not check the Inherit option, only admin users will be able to add new content.');
        }

        $form['settings_node_types']['allowed_node_types_inherit'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Inherit from parent @thing', $x),
          '#default_value' => $types_inherit,
          '#description' => $cb_desc,
          '#attributes' => array('class' => array('settings-node-types')),
        );
        $form['settings_node_types']['allowed_node_types'] = array(
          '#type' => 'select',
          '#title' => $this->t('Node types'),
          '#multiple' => TRUE,
          '#size' => 10,
          '#default_value' => $types_at == $item->mmtid ? $allowed_node_types : array(),
          '#options' => $all_types,
          '#states' => [
            'invisible' => ["#edit-allowed-node-types-inherit" => ['checked' => TRUE]],
          ],
        );
        $form['settings_node_types']['help'] = array(
          '#markup' => '<div class="description">' . $desc . '</div>',
        );
      }

      $form['settings_archive'] = array(
        '#type' => 'details',
        '#title' => $this->t('Archive'),
        '#group' => 'additional_settings',
        '#access' => $this->currentUser()->hasPermission('create archives'),
      );
      _mm_ui_add_summary_js($form['settings_archive'], 'settings_archive');
      $item->frequency = 'month';
      $item->main_nodes = 10;
      $item->archive_mmtid = array();
      if (!$is_new) {
        $tree = mm_content_get($mmtid, Constants::MM_GET_ARCHIVE);
        if (isset($tree->archive_mmtid)) {
          $item = (object)array_merge((array)$item, (array)$tree);
          $item->archive_mmtid = array($item->archive_mmtid => mm_content_get_name($item->archive_mmtid));
        }
      }

      if (isset($tree) && isset($tree->archive_mmtid) && $tree->archive_mmtid == $mmtid) {
        $x['@title'] = mm_content_get_name($tree->main_mmtid);
        $x['@link'] = Link::createFromRoute(mm_content_get_name($tree->main_mmtid), 'monster_menus.handle_page_settings', ['mm_tree' => $tree->main_mmtid])->toString();
        $form['settings_archive']['#description'] = $this->t('This @thing is an archive for @link. To change the archival settings, visit that @thing.', $x);
      }
      else {
        $form['settings_archive']['#description'] = $this->t('Contents past a certain age will be automatically moved to a secondary page, where they are organized by date.');
        $have_archive = count($item->archive_mmtid);
        $form['settings_archive']['archive'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Use an archive'),
          '#default_value' => $have_archive,
        );
        $form['settings_archive']['inner'] = array(
          '#type' => 'item',
          '#input' => FALSE,
          '#prefix' => '<div class="settings-archive">',
          '#suffix' => '</div>',
          '#states' => [
            'visible' => ["#edit-archive" => ['checked' => TRUE]],
          ],
        );
        $form['settings_archive']['inner']['frequency'] = array(
          '#type' => 'select',
          '#title' => $this->t('Frequency'),
          '#options' => array(
            'day' => $this->t('daily'),
            'week' => $this->t('weekly'),
            'month' => $this->t('monthly'),
            'year' => $this->t('yearly')
          ),
          '#default_value' => $item->frequency,
        );
        $form['settings_archive']['inner']['main_nodes'] = array(
          '#type' => 'select',
          '#title' => $this->t('Pieces of content to show on the main page'),
          '#default_value' => $item->main_nodes,
          '#description' => $this->t('At least this many posts will be shown, even if they have been archived.'),
          '#options' => $ilist,
        );
        $path_mmtids = mm_content_get_parents_with_self($item->mmtid);
        $form['settings_archive']['inner']['archive_mmtid'] = array(
          '#type' => 'mm_catlist',
          '#mm_list_selectable' => Constants::MM_PERMS_WRITE,
          '#mm_list_popup_start' => implode('/', $path_mmtids),
          '#mm_list_route' => 'monster_menus.browser_load',
          '#title' => $this->t('Location of archive:'),
          '#description' => $this->t('Choose the page to contain the archived contents. It must already exist.'),
          '#mm_list_min' => 1,
          '#mm_list_max' => 1,
          '#default_value' => $item->archive_mmtid,
          '#states' => [
            'required' => ["#edit-archive" => ['checked' => TRUE]],
          ],
        );
      }   // not archive destination
    }   // !$is_group

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $is_new ? ($mmtid == 1 ? $this->t('Create site', $x) : $this->t('Create @subthing', $x)) : $this->t('Save settings'),
      '#button_type' => 'primary',
    );
    mm_add_library($form, 'modal_dialog');

    if (\Drupal::request()->request->all() && $form_state->getErrors()) {
      \Drupal::messenger()->addError($this->t('The settings have not been saved because of the errors.'));
    }

    return $form;
  }

  public static function getPermsLabels($limited, $is_group, $x) {
    return array(
      Constants::MM_PERMS_WRITE => $limited ?
        array(
          t('Change @thing settings', $x),
          'If checked, @class can change this @thingpos settings.',
        ) :
        array(
          t('Delete/&#8203;change settings', $x),
          'If checked, @class can delete this @thing or change its settings.',
        ),
      Constants::MM_PERMS_SUB => array(
        t('Append @subthings', $x),
        'If checked, @class can append @subthings to this @thing.',
      ),
      Constants::MM_PERMS_APPLY => array(
        t('Add content', $x),
        'If checked, @class can add content to this @thing.',
      ),
      Constants::MM_PERMS_READ => $is_group ?
        array(
          t('See group members'),
          'If checked, @class can see the members of this group.',
        ) :
        array(
          t('Read', $x),
          'If checked, @class can read this @thing.',
        ),
    );
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_vals =& $form_state->getValues();
    if ($form_state->getTriggeringElement()['#id'] == 'edit-upload-action') {
      $validators = array('file_validate_extensions' => array('csv'));
      $file = file_save_upload('upload_file', $validators);
      if (!$file) {
        $form_state->setErrorByName('upload_file', $this->t('A file must be uploaded if you want to replace group membership using a file.'));
      }
      else {
        $form_vals['upload_file'] = $file;
      }
    }
    $is_new = isset($form_vals['is_new']);

    $mmtid = $form_vals['path'];
    if (!is_numeric($mmtid)) {
      $form_state->setErrorByName('', $this->t('Bad data'));
      return;
    }

    $x = mm_ui_strings($is_group = mm_content_is_group($mmtid));

    if (!mm_content_user_can($mmtid, $is_new ? Constants::MM_PERMS_SUB : Constants::MM_PERMS_WRITE)) {
      $message = $is_new ?
        'You do not have permission to add @subthings to this @thing' :
        'You do not have permission to modify this @thing';
      \Drupal::logger('mm')->warning($message, $x);
      $form_state->setErrorByName('', $this->t($message, $x));
      return;
    }

    $test_mmtid = !$is_new ? mm_content_get_parent($mmtid) : $mmtid;

    if (!_mm_ui_validate_entry($mmtid, $test_mmtid, $form_state, $form_vals, $is_new)) {
      return;
    }

    if ($is_group) {
      if (mm_content_is_vgroup($mmtid)) {
        if (trim($form_vals['qfield']) != '') {
          $q = 'SELECT ' . trim($form_vals['qfield']) . ' AS uid';
          if (trim($form_vals['qfrom']) != '') {
            $q .= ' ' . trim($form_vals['qfrom']);
          }
          try {
            $this->database->query($q);
          }
          catch (\Exception $e) {
            $form_state->setErrorByName('members', $this->t('There was an error testing the query.<br /><strong>Query:</strong> @query<br /><strong>Error:</strong> @error',
              array('@query' => $q, '@error' => $e->getMessage())));
          }
        }
      }
      elseif (isset($form_vals['members'])) {
        _mm_ui_verify_userlist($form_state, $form_vals['members'], 'members');
      }
    }

    if ($this->currentUser()->hasPermission('administer all menus')) {
      _mm_ui_verify_userlist($form_state, $form_vals['owner'], 'owner');
    }

    list($form_vals['all_values_group'], $form_vals['all_values_user'], $form_vals['default_modes']) = _mm_ui_form_parse_perms($form_state, NULL, TRUE);

    if (!empty($form_vals['archive']) && $this->currentUser()->hasPermission('create archives')) {
      $archive_mmtid = mm_ui_mmlist_key0($form_vals['archive_mmtid']);
      // We're using a "sometimes_required", so this test is necessary
      if (!isset($archive_mmtid)) {
        $form_state->setErrorByName('archive_mmtid', $this->t('The archive location is required.'));
      }
      elseif (!mm_content_user_can($archive_mmtid, Constants::MM_PERMS_WRITE)) {
        $form_state->setErrorByName('archive_mmtid', $this->t('You do not have permission to modify the @thing you chose for the archive.', $x));
      }
      else {
        $tree = mm_content_get($archive_mmtid, Constants::MM_GET_ARCHIVE);
        if (isset($tree->archive_mmtid)) {
          if ($tree->archive_mmtid == $archive_mmtid && ($is_new || $tree->main_mmtid != $mmtid)) {
            $form_state->setErrorByName('archive_mmtid', $this->t('The @thing you chose for the archive is already the archive for another @thing.', $x));
          }
          elseif ($tree->main_mmtid == $archive_mmtid) {
            $form_state->setErrorByName('archive_mmtid', $this->t('The @thing you chose for the archive already contains an archive. Please choose a different @thing.', $x));
          }
        }
        $content = mm_content_get_nids_by_mmtid($archive_mmtid, 1);
        if (count($content)) {
          $form_state->setErrorByName('archive_mmtid', $this->t('The @thing you chose for the archive already has contents. Before it can become an archive, you must remove the contents.', $x));
        }
      }
    }

    if (isset($form_vals['menu_start']) && $form_vals['menu_start'] != Constants::MM_MENU_UNSET) {
      $blocks = mm_content_get_blocks(TRUE);
      $bid = $form_vals['menu_start'];
      if (is_null($test_mmtid) || $bid != Constants::MM_MENU_UNSET && !isset($blocks[$bid])) {
        $form_state->setErrorByName('menu_start', $this->t('The <em>Location on screen</em> you chose is not valid.', $x));
      }
      elseif ($blocks[$bid]->toArray()['settings']['show_node_contents'] && $form['menu']['menu_start']['#default_value'] != $bid && !mm_content_user_can($test_mmtid, Constants::MM_PERMS_WRITE)) {
        $form_state->setErrorByName('menu_start', $this->t('The <em>Location on screen</em> you chose would change the appearance of this @thing\'s parent. You do not have edit/delete permission for the parent @thing.', $x));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_vals =& $form_state->getValues();
    $mmtid = $form_vals['path'];
    $x = mm_ui_strings($is_group = mm_content_is_group($mmtid));
    $is_new = isset($form_vals['is_new']);
    $alias = empty($form_vals['alias']) || $mmtid == mm_home_mmtid() && !$is_new ? '' : trim($form_vals['alias']);
    $name = trim($form_vals['name']);

    if ($is_group && ($is_new || $mmtid != mm_content_groups_mmtid())) {
      // Currently, groups always have MM_PERMS_APPLY for everyone. Change this if
      // you want to only allow some users to apply a group to something's
      // permissions.
      $form_vals['all_values_group'][Constants::MM_PERMS_APPLY] = $form_vals['all_values_user'][Constants::MM_PERMS_APPLY] = array();
      if (!in_array(Constants::MM_PERMS_APPLY, $form_vals['default_modes'])) $form_vals['default_modes'][] = Constants::MM_PERMS_APPLY;
    }
    $perms = array();
    foreach (array(Constants::MM_PERMS_WRITE, Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY, Constants::MM_PERMS_READ) as $m) {
      $perms[$m]['groups'] = isset($form_vals['all_values_group'][$m]) ? $form_vals['all_values_group'][$m] : array();
      $perms[$m]['users']  = isset($form_vals['all_values_user'][$m]) ? $form_vals['all_values_user'][$m] : array();
    }

    $flags = array();
    // Get the current cascaded settings.
    $cascaded = $is_new ? array() : mm_content_get_cascaded_settings($mmtid);
    $owner = $this->currentUser()->id();
    if ($this->currentUser()->hasPermission('administer all menus')) {
      $owner = $form_vals['owner'];

      $predefined = \Drupal::moduleHandler()->invokeAll('mm_tree_flags');
      foreach ($predefined as $flag => $elem) {
        $flag_name = "flag_$flag";
        if (!empty($form_vals[$flag_name])) {
          $flags[$flag] = $elem['#type'] == 'checkbox' ? '' : trim($form_vals[$flag_name]);
        }
      }

      if (!empty($form_vals['free_flags'])) {
        foreach (explode("\n", $form_vals['free_flags']) as $f) {
          $f = trim($f);
          if (!empty($f)) {
            if (preg_match('/^\s*(.*?)\s*=\s*(.*?)\s*$/', $f, $data))
              $flags[$data[1]] = $data[2];
            else $flags[trim($f)] = '';
          }
        }
      }

      $cascaded['allowed_node_types'] = array();
      if (empty($form_vals['allowed_node_types_inherit'])) {
        $cascaded['allowed_node_types'] = isset($form_vals['allowed_node_types']) && count($form_vals['allowed_node_types']) ? $form_vals['allowed_node_types'] : array('');  // intentionally empty
      }
    }
    elseif (!$is_new) {
      if ($tree = mm_content_get($mmtid, Constants::MM_GET_FLAGS)) {  // read old flags for item
        _mm_ui_is_user_home($tree);
        $flags = $tree->flags;
      }
    }

    // Merge cascaded settings not already handled above
    foreach (mm_content_get_cascaded_settings() as $setting_name => $desc)
      if ($setting_name != 'allowed_node_types')
        if (!isset($desc['user_access']) || $this->currentUser()->hasPermission($desc['user_access']))
          if (isset($form_vals[$setting_name]))
            $cascaded[$setting_name] = $form_vals[$setting_name];

    $md = isset($form_vals['max_depth']) ? $form_vals['max_depth'] : -1;
    $mp = isset($form_vals['max_parents']) ? $form_vals['max_parents'] : -1;
    if (isset($form_vals['menu_start'])) {
      $ms = $form_vals['menu_start'];
      if (empty($ms) && ($md != -1 || $mp != -1)) $ms = Constants::MM_MENU_UNSET;
    }
    else {
      $ms = '';
      $md = $mp = -1;
    }

    $params = array(
      'alias'                => $alias,
      'archive_mmtid'        => 0,
      'cascaded'             => $cascaded,
      'comment'              => isset($form_vals['comment']) ? $form_vals['comment'] : '',
      'default_mode'         => implode(',', $form_vals['default_modes']),
      'flags'                => $flags,
      'hidden'               => !empty($form_vals['hide_menu']),
      'hover'                => $form_vals['hover'],
      'max_depth'            => $md,
      'max_parents'          => $mp,
      'menu_start'           => $ms,
      'name'                 => $name,
      'node_info'            => isset($form_vals['node_info']) ? $form_vals['node_info'] : NULL,
      'perms'                => $perms,
      'previews'             => !empty($form_vals['previews']),
      'propagate_node_perms' => $form_vals['node_propagate'],
      'recurs_perms'         => !empty($form_vals['propagate']),
      'rss'                  => isset($form_vals['rss']) ? $form_vals['rss'] : '',
      'theme'                => isset($form_vals['theme']) ? $form_vals['theme'] : '',
      'uid'                  => $owner,
      'weight'               => isset($form_vals['weight']) && empty($form_vals['hide_menu']) ? $form_vals['weight'] : 0,
    );
    if ($is_group) {
      if ($form_state->getTriggeringElement()['#id'] == 'edit-upload-action') {
        $filepath = $form_vals['upload_file'][0]->getFileUri();
        $handle = @fopen($filepath, 'r');
        $params['members'] = array();
        $success = 0;
        $fail = 0;
        if ($handle) {
          while ($row = fgetcsv($handle, 1000, ',')) {
            $username = trim($row[0]);
            if ($username !== '') {
              $uid = $this->database->query('SELECT uid FROM {users_field_data} WHERE name = :username', array(':username' => $username))->fetchField();
              if (!empty($uid)) {
                $params['members'][] = $uid;
                $success++;
              }
              else {
                $fail++;
              }
            }
          }
          if ($success) {
            \Drupal::messenger()->addStatus($this->t('Successfully imported @success user(s).', array('@success' => $success)));
          }
          if ($fail) {
            \Drupal::messenger()->addStatus($this->t('Failed to import @fail user(s).', array('@fail' => $fail)));
          }
        }
        else {
          \Drupal::messenger()->addStatus($this->t('There was an internal problem uploading users. Please try again.'));
        }
      }
      else {
        if (isset($form_vals['members'])) {
          $params['members'] = array_keys($form_vals['members']);
        }
        $params['large_group_form_token'] = isset($form_vals['members-use-large-group']) && $form_vals['members-use-large-group'] == 'yes' ? $form['mm_form_token']['#value'] : '';
        $params['qfield'] = $qfield = isset($form_vals['qfield']) ? trim($form_vals['qfield']) : '';
        $params['qfrom'] = isset($form_vals['qfrom']) ? trim($form_vals['qfrom']) : '';
      }
    }
    // !$is_group
    else if (!empty($form_vals['archive'])) {
      $archive_mmtid = mm_ui_mmlist_key0($form_vals['archive_mmtid']);
      if ($archive_mmtid) {
        $params['archive_mmtid'] = $archive_mmtid;
        $params['frequency'] = $form_vals['frequency'];
        $params['main_nodes'] = $form_vals['main_nodes'];
      }
    }

    mm_module_invoke_all_array('mm_content_edit_submit_alter', array($is_new, $mmtid, &$params));

    $mmtid = mm_content_insert_or_update($is_new, $mmtid, $params);
    if (empty($mmtid)) return;

    if ($is_group && !empty($qfield) && mm_content_is_vgroup($mmtid)) {
      \Drupal::messenger()->addStatus($this->t('The members of this virtual group will be updated during the next cron run.'));
    }

    if ($is_new) {
      if (!$is_group && in_array(Constants::MM_PERMS_READ, $form_vals['default_modes'])) {
        $x[':link'] = Url::fromRoute('monster_menus.handle_page_settings', ['mm_tree' => $mmtid])->toString();
        \Drupal::messenger()->addStatus($this->t('<div id="public-warning">The @subthing was successfully created. Note that it is publicly viewable. To make adjustments to who can read it, <a href=":link">click here to change the settings</a>.</div>', $x));
      }
      else {
        \Drupal::messenger()->addStatus($this->t('The @subthing was successfully created.', $x));
      }

      if ($form_vals['path'] == 1 && DefaultController::accessAllAdmin()) {
        $form_state->setRedirect('monster_menus.mm_admin_list_sites');
      }
      else {
        mm_set_form_redirect_to_mmtid($form_state, $mmtid);
      }
    }
    else {
      if (!$is_group && in_array(Constants::MM_PERMS_READ, $form_vals['default_modes'])) {
        $x[':link'] = Url::fromRoute('monster_menus.handle_page_settings', ['mm_tree' => $mmtid])->toString();
        \Drupal::messenger()->addStatus($this->t('<div id="public-warning">The settings for this @thing have been saved. Note that it is publicly viewable. To make adjustments to who can read it, <a href=":link">click here to change the settings</a>.</div>', $x));
      }
      else {
        \Drupal::messenger()->addStatus($this->t('The settings for this @thing have been saved.', $x));
      }
    }
  }

  public static function permissionsForm(&$form, $types, $default_modes, $groups, $users, $owner = NULL, $is_search = FALSE, $limit_write = FALSE, $x = array(), $instance_suffix = '') {
    $x['@class'] = t('everyone');
    $all_menus = \Drupal::currentUser()->hasPermission('administer all menus');
    $checks = array();
    foreach (array_keys($types) as $type) {
      $checks[] = $type == Constants::MM_PERMS_WRITE && !$all_menus ? NULL : count($default_modes) && in_array($type, $default_modes);
      $checks[] = FALSE;
    }
    $form['table']['everyone'] = array(
      '#type' => 'value',
      '#value' => array(
        'title' => t('Everyone'),
        'types' => $types,
        'headings' => TRUE,
      ),
    );
    $form['table']['everyone'][] = _mm_ui_perms_table_row('group', "everyone$instance_suffix", t('All users'), '', NULL, $types, $x, $checks);

    if (!$is_search) {
      $form['table']['indiv'] = array(
        '#type' => 'value',
        '#value' => array(
          'title' => t('Individuals'),
          'types' => $types,
          'action' => mm_ui_add_user_subform($form, 'settings-perms-indiv-add', t('add'), t('User(s) to add to permissions:'), t('Add users to permissions'), 'Drupal.MMSettingsPermsAddUsers'),
        ),
      );
      $x['@class'] = t('this user');
      if (is_numeric($owner)) {
        list($name, $msg, $owner_readonly) = mm_ui_owner_desc($form, $x, $owner, $is_search);
        $form['table']['indiv'][] = _mm_ui_perms_table_row(
          'user',
          'owner',
          t('<span class="settings-perms-owner-prefix">Owner: </span><span class="settings-perms-owner-name">@name</span>', array('@name' => $name)),
          $msg,
          $owner_readonly ? NULL : mm_ui_add_user_subform($form, 'settings-perms-indiv-owner', t('change'), t('Owner:'), t('Change the owner'), 'Drupal.MMSettingsPermsOwner', $owner, $name),
          $types,
          $x,
          array(TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE)
        );
      }

      $checks = array(TRUE, FALSE, TRUE, FALSE, TRUE, FALSE, TRUE, FALSE); // defaults for new row when no users are listed

      $delete_link = mm_ui_js_link_no_href(['title' => t('Remove this user'), 'onclick' => 'return Drupal.MMSettingsPermsDelete(this)'], t('delete'));
      $all_values_user = '';
      foreach ($users as $uid => $data) {
        $checks = array();
        foreach (array_keys($types) as $type) {
          if ($checked = in_array($type, $data['modes'])) {
            $all_values_user .= $type . $uid;
          }
          $checks[] = $checked;
          $checks[] = $type == Constants::MM_PERMS_WRITE && $limit_write;
        }
        $name = array(
          array('#type' => 'item', '#input' => FALSE, '#markup' => $data['name']),
        );
        $form['table']['indiv'][] = _mm_ui_perms_table_row('user', $uid, $name, '', $checks[0] && $limit_write ? '' : $delete_link, $types, $x, $checks);
      }

      // Empty row to be used when adding new users
      if ($limit_write) {
        $checks[0] = FALSE;
        $checks[1] = TRUE;
      }
      $form['table']['indiv'][] = _mm_ui_perms_table_row('user', 'new', '', '', $delete_link, $types, $x, $checks);
      if (!empty($owner) && empty($owner_readonly)) {
        $form['owner'] = array('#type' => 'hidden', '#default_value' => $owner);
      }

      $form["all_values_user$instance_suffix"] = array(
        '#type' => 'hidden',
        '#default_value' => $all_values_user,   // default value, in case JS is disabled
        '#attributes' => array('class' => array('mm-permissions-all-values-user')),
      );
      if ($limit_write) {
        // Tell the JS code that it needs to act differently
        $form['limit_write_not_admin'] = array('#type' => 'hidden');
      }

      $form['table']['groups'] = array(
        '#type' => 'value',
        '#value' => array(
          'title' => t('Groups'),
          'types' => $types,
          'action' => mm_ui_settings_perms_add_group_link($form),
        ),
      );

      $delete_link = mm_ui_js_link_no_href(['title' => t('Remove this group'), 'onclick' => 'return Drupal.MMSettingsPermsDelete(this)'], t('delete'));
      $x['@class'] = t('the users in this group');
      $elem = array(
        array(
          '#type' => 'details',
          '#open' => FALSE,
          array(
            '#type' => 'item',
            '#input' => FALSE,
          ),
        ),
      );
      $all_values_group = '';
      foreach ($groups as $gid => $data) {
        if (empty($gid)) {
          continue;
        }
        $checks = array();
        foreach (array_keys($types) as $type) {
          if ($checked = in_array($type, $data['modes'])) {
            $all_values_group .= $type . $gid;
          }
          $checks[] = $checked;
          $checks[] = $type == Constants::MM_PERMS_WRITE && $limit_write;
        }
        $elem[0]['#title'] = $data['name'];
        $group_details = mm_content_get($gid);
        if ($group_details && !empty($group_details->uid) && ($group_user = User::load($group_details->uid))) {
          $owner_display = ['#type' => 'username', '#account' => $group_user];
          $owner_display = \Drupal::service('renderer')->render($owner_display);
        }
        else {
          $owner_display = t('not available');
        }
        $info = mm_get_setting(mm_content_is_vgroup($gid) ? 'vgroup.group_info_message' : 'group.group_info_message');
        $info = t($info, array('@gid' => $gid, '@owner' => $owner_display));
        $group_info_message = '<div id="mmgroupinfo-' . $gid . '" class="hidden"><p>' . $info . '</p></div>';
        $group_info_link = Link::fromTextAndUrl(t('Group information'), Url::fromRoute('<current>', [], array(
          'fragment' => "mmgroupinfo-" . $gid,
          'external' => TRUE,
          'attributes' => array(
            'id' => mm_ui_modal_dialog([], $elem[0][0]),
            'title' => t('Information about this group'),
          ),
        )))->toString();
        $edit_link = mm_content_user_can($gid, Constants::MM_PERMS_WRITE) ? Link::fromTextAndUrl(t('Edit this group'), Url::fromRoute('monster_menus.handle_page_settings', ['mm_tree' => $gid]))->toString() . ' | ' : '';
        $elem[0][0]['#markup'] = $group_info_message . '<div class="form-item">' . $edit_link . $group_info_link . '<br />' . $data['members'] . '</div>';
        $form['table']['groups'][] = _mm_ui_perms_table_row('group', $gid, $elem, '', $checks[0] && $limit_write ? '' : $delete_link, $types, $x, $checks);
      }

      // Empty row to be used when adding new groups
      if ($limit_write) {
        $checks[0] = FALSE;
        $checks[1] = TRUE;
      }
      else {
        $checks[0] = $checks[1] = FALSE;
      }
      $elem[0]['#title'] = ' ';
      $elem[0][0] = ['#markup' => '<div class="mm-permissions-group-new form-item"></div>'];
      $form['table']['groups'][] = _mm_ui_perms_table_row('group', 'new', $elem, '', $delete_link, $types, $x, $checks);

      $form["all_values_group$instance_suffix"] = array(
        '#type' => 'hidden',
        '#default_value' => $all_values_group,   // default value, in case JS is disabled
        '#attributes' => array('class' => array('mm-permissions-all-values-group')),
      );
    }
    mm_ui_permissions($form['table']);
  }

  private function renderTooltip($desc) {
    $desc['#theme'] = 'tooltip';
    return \Drupal::service('renderer')->render($desc);
  }

}
