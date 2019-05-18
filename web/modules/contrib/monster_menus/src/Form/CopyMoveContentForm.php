<?php

namespace Drupal\monster_menus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\monster_menus\Constants;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CopyMoveContentForm extends FormBase {
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
    return 'mm_ui_content_copymove';
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

  public function buildForm(array $form, FormStateInterface $form_state) {
    $item = $form_state->getBuildInfo()['args'][0];
    $x = mm_ui_strings($is_group = $item->is_group);

    if ($item->parent > 0) {
      $pitem = mm_content_get_tree($item->parent, array(
          Constants::MM_GET_TREE_DEPTH        => 0,
          Constants::MM_GET_TREE_RETURN_PERMS => TRUE,
        )
      );
    }

    $form['#attributes'] = array('class' => array('mm-ui-copymove'));
    $form['mmtid'] = array(
      '#type' => 'value',
      '#value' => $item->mmtid
    );

    $form['mode'] = array(
      '#type' => 'details',
      '#title' => $this->t('Mode'),
      '#open' => TRUE,
    );
    $form['mode']['mode'] = array(
      '#type' => 'radios',
      '#default_value' => 'copy',
      '#options' => array('copy' => $this->t('Copy'), 'move' => $this->t('Move')));

    $form['copy'] = array(
      '#type' => 'details',
      '#attributes' => array('id' => 'copydiv'),
      '#title' => $this->t('What to copy'),
      '#open' => TRUE,
    );
    $mustcheck = $is_group ? '' : $this->t('You must choose "This @thing", "Contents", or both.', $x);
    $form['copy']['copy_page'] = array(
      '#type' => 'checkbox',
      '#default_value' => TRUE,
      '#title' => $this->t('This @thing', $x));
    $form['copy']['copy_subpage'] = array(
      '#type' => 'checkbox',
      '#default_value' => FALSE,
      '#attributes' => array('style' => 'margin-left: 20px'),
      '#title' => $this->t('and any @subthings', $x));
    if (!$is_group) {
      $form['copy']['copy_nodes'] = array(
        '#type' => 'checkbox',
        '#default_value' => FALSE,
        '#title' => $this->t('Contents'));
      $form['copy']['desc'] = array(
        '#type' => 'item',
        '#input' => FALSE,
        '#description' => $this->t('When contents are copied without their current @thing, the new copies take on the permissions of the destination @thing.', $x));
    }

    $form['move'] = array(
      '#type' => 'details',
      '#attributes' => array('id' => 'movediv'),
      '#title' => $this->t('What to move'),
      '#open' => TRUE,
    );
    $form['move']['move_mode'] = array(
      '#type' => 'radios',
      '#default_value' => 'page',
      '#description' => $is_group ? NULL : $this->t('Moved contents keep their original permissions.'),
      '#options' => array(
        'page' => $this->t($is_group ? 'This @thing and any @subthings' : 'This @thing, any @subthings, and all their contents', $x),
        'nodes' => $this->t('Just the contents appearing on this @thing', $x)));

    $limit_move = isset($item->flags['limit_move']) && !$this->currentUser()->hasPermission('administer all menus');
    if ($is_group) {
      unset($form['move']['move_mode']['#options']['nodes']);
      if ($limit_move) {
        unset($form['mode']['mode']['#options']['move']);
        $form['mode']['mode']['#description'] = $this->t('You are not allowed to move this group.') . ' ' . $form['mode']['mode']['#description'];
      }
      elseif (mm_content_is_vgroup($item->mmtid)) {
        $form['mode']['mode']['#default_value'] = 'move';
        unset($form['mode']['mode']['#options']['copy']);
      }
    }
    elseif ($item->perms[Constants::MM_PERMS_IS_RECYCLED]) {
      unset($form['mode']['mode']['#options']['move']);
      $form['mode']['mode']['#description'] = $this->t('This @thing is in the recycle bin. You can copy it, but it cannot be moved.', $x);
    }
    elseif ($limit_move) {
      $form['move']['move_mode']['#default_value'] = 'nodes';
      unset($form['move']['move_mode']['#options']['page']);
      $form['move']['move_mode']['#description'] = $this->t('You are not allowed to move this page, only its contents.') . ' ' . $form['move']['move_mode']['#description'];
    }
    else {
      $prevent_mode = mm_get_setting('prevent_showpage_removal');
      if ($prevent_mode != Constants::MM_PREVENT_SHOWPAGE_REMOVAL_NONE && ($showpage_mmtid = mm_content_test_showpage_mmtid($item->mmtid))) {
        if ($prevent_mode == Constants::MM_PREVENT_SHOWPAGE_REMOVAL_WARN || $this->currentUser()->hasPermission('administer all menus')) {
          $form['move']['move_mode']['#description'] = $showpage_mmtid == $item->mmtid ?
            $this->t('<strong>Warning:</strong> This page contains dynamic content which may no longer appear if it is moved to another location.') :
            $this->t('<strong>Warning:</strong> The sub-page <a href=":link">@title</a> contains dynamic content which may no longer appear if this page is moved to another location.', array('@title' => mm_content_get_name($showpage_mmtid), ':link' => mm_content_get_mmtid_url($showpage_mmtid)->toString()));
        }
        else {
          $form['move']['move_mode']['#default_value'] = 'nodes';
          unset($form['move']['move_mode']['#options']['page']);
          $form['move']['move_mode']['#description'] = $showpage_mmtid == $item->mmtid ?
            $this->t('This page contains dynamic content which will no longer appear if it is moved to another location. You may only move the contents.') :
            $this->t('The sub-page <a href=":link">@title</a> contains dynamic content which will no longer appear if this page is moved to another location. You may only move the contents.', array('@title' => mm_content_get_name($showpage_mmtid), ':link' => mm_content_get_mmtid_url($showpage_mmtid)->toString()));
        }
      }
    }

    $form['dest'] = array(
      '#type' => 'details',
      '#title' => $this->t('Destination'),
      '#open' => TRUE,
    );

    $parents = mm_content_get_parents($item->mmtid);
    if (!$this->currentUser()->hasPermission('administer all menus')) array_shift($parents);  // skip root
    $pop_start = implode('/', $parents) . "/$item->mmtid";

    $form['dest']['dest'] = array(
      '#title' => $this->t('Destination'),
      '#type' => $is_group ? 'mm_grouplist' : 'mm_catlist',
      '#required' => $item->parent <= 0,
      '#default_value' => isset($pitem) ?
        array($pitem[0]->mmtid => mm_content_get_name($pitem[0])) :
        array('' => $this->t('(choose the destination)')),
      '#mm_list_popup_start' => $pop_start,
      '#mm_list_min' => 1,
      '#mm_list_max' => 1,
      '#mm_list_selectable' => Constants::MM_PERMS_SUB . Constants::MM_PERMS_APPLY,
      '#description' => $this->t('Choose where to copy/move the @thing. The default value is the @thingpos current parent.', $x)
    );

    $form['name_alias'] = array(
      '#type' => 'details',
      '#attributes' => array('id' => 'namediv'),
      '#title' => $is_group ? $this->t('Name') : $this->t('Name and URL name'),
      '#open' => TRUE,
    );
    $form['name_alias']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('New @thing name', $x),
      '#default_value' => $item->name,
      '#size' => 40,
      '#maxlength' => 128,
      '#description' => $this->t('If you do not change the Destination above, you must modify the page name and URL name here.'),
      '#states' => [
        // Add a state which is triggered from copymove.js.
        'required' => ["#namediv" => ['required' => TRUE]],
      ],
    );

    if (!$is_group) {
      $form['name_alias']['alias'] = array(
        '#type' => 'machine_name',
        '#machine_name' => array(
          'exists' => 'mm_ui_machine_name_exists',
          'source' => array('name_alias', 'name'),
          'label' => $this->t('New URL name'),
          'replace_pattern' => '[^-.\w]+',
          'replace' => '-',
          'error' => $this->t('The URL name must contain only letters, numerals, hyphens, periods and underscores.'),
          'standalone' => TRUE,
        ),
        '#title' => $this->t('New URL name'),
        '#default_value' => $item->alias,
        '#size' => 20, '#maxlength' => 128,
        '#description' => $this->t('The name that will be used in the Web address of the page. ' .
          'Make this a shortened version of the Page name, using only lowercase letters, ' .
          'numerals, hyphens, periods and underscores.')
      );
      if (empty($item->is_user_home) && !$this->currentUser()->hasPermission('administer all menus')) {
        // Add a state which is triggered from copymove.js.
        $form['name_alias']['alias']['#states'] = [
          'required' => ["#namediv" => ['required' => TRUE]],
        ];
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Go!'),
      '#button_type' => 'primary',
    );
    mm_static($form, 'copymove', $mustcheck);

    return $form;
  }

  public static function validate(array &$form, FormStateInterface $form_state, $restore) {
    $form_values =& $form_state->getValues();
    self::getValues($form_values, $src_mmtid, $modes, $src_parent_mmtid, $dest_mmtid);
    $nodes_only = $modes['move_nodes'] || $modes['copy_nodes'] && !$modes['copy_page'];

    if (!$restore && ($modes['copy_page'] || $modes['move_page'])) {
      mm_ui_validate_sometimes_required($form['name_alias']['name'], trim($form_values['name']), $form_state);
      if (isset($form['name_alias']['alias'])) {
        mm_ui_validate_sometimes_required($form['name_alias']['alias'], trim($form_values['alias']), $form_state);
      }
    }

    if (!$src_mmtid || !is_numeric($src_mmtid)) {
      $form_state->setErrorByName('', t('Missing one or more required fields'));
      return;
    }

    $x = mm_ui_strings(mm_content_is_group($src_mmtid));

    if ($modes['move_page'] && !mm_content_user_can($src_mmtid, Constants::MM_PERMS_WRITE))
      $message = t('You do not have permission to modify this @thing.', $x);

    if ($modes['move_page'] && !$restore && !mm_content_user_can($src_parent_mmtid, Constants::MM_PERMS_SUB))
      $message = t('You do not have permission to modify the source @thingpos parent.', $x);

    if (!is_numeric($dest_mmtid) || !mm_content_user_can($dest_mmtid, $nodes_only ? Constants::MM_PERMS_APPLY : Constants::MM_PERMS_SUB))
      $message = t('You do not have permission to modify the destination @thing.', $x);

    if (mm_content_is_normal($src_mmtid) != ($dest_mmtid == 1 || mm_content_is_normal($dest_mmtid)) || mm_content_is_group($src_mmtid) != mm_content_is_group($dest_mmtid))
      $message = t('Source and destination are not of the same type.');

    if (mm_content_user_can($dest_mmtid, Constants::MM_PERMS_IS_RECYCLED))
      if ($modes['copy_nodes'] || $modes['copy_page']) {
        $message = 'You cannot copy to a recycle bin.';
      }
      else {
        $message = 'You cannot move to a recycle bin using this option. Please use the Delete option, instead.';
      }

    if (!empty($message)) {
      \Drupal::logger('mm')->warning($message, array());
      $form_state->setErrorByName('', t($message));
      return;
    }

    if (mm_content_is_vgroup($src_mmtid) != mm_content_is_vgroup($dest_mmtid)) {
      $form_state->setErrorByName('dest', mm_content_is_vgroup($src_mmtid) ? t('Pre-defined groups can only be moved to create sub-groups of other pre-defined groups.') : t('You cannot copy/move a regular group inside the pre-defined groups list.'));
    }

    if (!$nodes_only && !_mm_ui_validate_entry($src_mmtid, $dest_mmtid, $form_state, $form_values, TRUE)) {
      return;
    }

    if ($dest_mmtid == $src_parent_mmtid && $modes['move_page']) {
      $form_state->setErrorByName('', t('Instead of moving a @thing within the same parent @thing, rename it using Settings-&gt;Edit.', $x));
    }
    elseif ($dest_mmtid == $src_mmtid && $nodes_only && $modes['move_nodes']) {
      $form_state->setErrorByName('', t('Moving just a @thingpos contents to itself results in no change.', $x));
    }
    elseif ($dest_mmtid == $src_mmtid && !$modes['move_nodes'] && ($modes['move_page'] || $modes['copy_recur'])) {
      $form_state->setErrorByName('', t('You cannot copy or move a @thing within itself.', $x));
    }
    elseif (mm_content_is_child($dest_mmtid, $src_mmtid)) {
      if ($modes['move_page']) {
        $form_state->setErrorByName('dest', t('You cannot move a @thing into a @subthing of itself. Please choose another destination.', $x));
      }
      elseif ($modes['copy_recur']) {
        $form_state->setErrorByName('dest', t('You cannot copy a @thing and any @subthings into a child of itself. Please choose another destination.', $x));
      }
    }
    elseif ($modes['move_nodes'] && mm_content_is_archive($dest_mmtid)) {
      $form_state->setErrorByName('dest', t('You cannot move content into an archive. Move the content into the main @thing, instead, and the archive will be updated automatically.', $x));
    }
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    self::validate($form, $form_state, FALSE);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values =& $form_state->getValues();
    $this->getValues($form_values, $src_mmtid, $modes, $src_parent_mmtid, $dest_mmtid);

    $name = trim($form_values['name']);
    $alias = isset($form_values['alias']) ? trim($form_values['alias']) : NULL;
    $x = mm_ui_strings(mm_content_is_group($src_mmtid));

    if ($modes['copy_page'] || $modes['copy_nodes']) {
      if ($modes['copy_page'] && $modes['copy_nodes']) {
        $msg = $modes['copy_recur'] ?
          $this->t('The @thing, any @subthings, and their contents were successfully copied.', $x) :
          $this->t('The @thing and its contents were successfully copied.', $x);
      }
      elseif ($modes['copy_nodes']) {
        $msg = $this->t('The @thingpos contents were successfully copied.', $x);
      }
      else {
        $msg = $modes['copy_recur'] ?
          $this->t('The @thing and any @subthings were successfully copied.', $x) :
          $this->t('The @thing was successfully copied.', $x);
      }

      $copy_params = array(
        Constants::MM_COPY_ALIAS =>    $alias,
        Constants::MM_COPY_CONTENTS => $modes['copy_nodes'],
        Constants::MM_COPY_NAME =>     $name,
        Constants::MM_COPY_OWNER =>    $this->currentUser()->id(),
        Constants::MM_COPY_READABLE => TRUE,
        Constants::MM_COPY_RECUR =>    $modes['copy_recur'],
        Constants::MM_COPY_TREE =>     $modes['copy_page'],
      );
      $new_mmtid = mm_content_copy($src_mmtid, $dest_mmtid, $copy_params);

      if (is_numeric($new_mmtid)) {
        if ($new_mmtid != $src_mmtid) $msg .= ' ' . $this->t('You are now viewing the destination.');
        \Drupal::messenger()->addStatus($msg);
        mm_set_form_redirect_to_mmtid($form_state, $new_mmtid);
      }
      else {
        \Drupal::messenger()->addError($this->t($new_mmtid));
      }
    }
    elseif ($modes['move_page']) {
      $old_tree = mm_content_get($src_mmtid);
      $error = mm_content_move($src_mmtid, $dest_mmtid, '');

      if (!$error) {
        if ($name != $old_tree->name || $alias != $old_tree->alias) {
          mm_content_update_quick(array('name' => $name, 'alias' => $alias), array('mmtid' => $src_mmtid), $dest_mmtid);
        }
        \Drupal::messenger()->addStatus($this->t('The @thing was successfully moved.', $x));
        mm_set_form_redirect_to_mmtid($form_state, $src_mmtid);
      }
      else {
        \Drupal::messenger()->addError($this->t($error));
      }
    }
    elseif ($modes['move_nodes']) {
      $total = 0;
      $ok = array();
      foreach (mm_content_get_nids_by_mmtid($src_mmtid) as $nid) {
        $total++;
        $uid = $this->database->select('node_field_data', 'n')
          ->fields('n', array('uid'))
          ->condition('n.nid', $nid)
          ->execute()->fetchField();
        if (!empty($uid) || $uid === 0) {
          // FIXME: verify that this works as intended, without actually affecting
          // the node's owner unexpectedly.
          $node = Node::load($nid);
          $node->setOwnerId($uid);
          if (mm_content_node_access($node, 'update')) {
            // If node already appears on both $src_mmtid and $dest_mmtid, just
            // remove entry from mm_node2tree. Unfortunately, merge() and
            // upsert() do not handle this case.
            if ($this->database->select('mm_node2tree')
              ->condition('mmtid', $dest_mmtid)
              ->condition('nid', $nid)
              ->countQuery()
              ->execute()->fetchField()) {
              $this->database->delete('mm_node2tree')
                ->condition('mmtid', $src_mmtid)
                ->condition('nid', $nid)
                ->execute();
            }
            else {
              $this->database->update('mm_node2tree')
                ->fields(array('mmtid' => $dest_mmtid))
                ->condition('mmtid', $src_mmtid)
                ->condition('nid', $nid)
                ->execute();
            }
            mm_content_reset_custom_node_order($dest_mmtid, $nid);
            $ok[] = $nid;
          }
        }
        mm_content_clear_node_cache($ok);
        mm_content_notify_change('move_node', NULL, $ok, array('old_mmtid' => $src_mmtid, 'new_mmtid' => $dest_mmtid));
      }

      $failed = $total - count($ok);
      $x += array('@total' => $total, '@ok' => count($ok), '@failed' => $failed);
      if ($failed) {
        if ($failed == $total) {
          \Drupal::messenger()->addStatus($this->t('You do not have permission to move any of the @total piece(s) of content on the @thing.', $x));
          mm_set_form_redirect_to_mmtid($form_state, $src_mmtid);
        }
        else {
          \Drupal::messenger()->addStatus($this->t('Only @ok of the @total piece(s) of content on the @thing could be moved, due to lack of permission.', $x));
          mm_set_form_redirect_to_mmtid($form_state, $dest_mmtid);
        }
      }
      elseif (!$total) {
        \Drupal::messenger()->addStatus($this->t('There are no contents on this @thing to move.', $x));
        mm_set_form_redirect_to_mmtid($form_state, $src_mmtid);
      }
      else {
        \Drupal::messenger()->addStatus($this->t('@total piece(s) of content were successfully moved.', $x));
        mm_set_form_redirect_to_mmtid($form_state, $dest_mmtid);
      }
    }
  }

  private static function getValues($form_values, &$src_mmtid, &$modes, &$src_parent_mmtid, &$dest_mmtid) {
    $src_mmtid =      $form_values['mmtid'];
    $copy_page =      $form_values['mode'] == 'copy' && $form_values['copy_page'];
    $modes = array(
      'copy_page' =>  $copy_page,
      'copy_recur' => $copy_page && $form_values['copy_subpage'],
      'copy_nodes' => $form_values['mode'] == 'copy' && $form_values['copy_nodes'],
      'move_page' =>  $form_values['mode'] == 'move' && $form_values['move_mode'] == 'page',
      'move_nodes' => $form_values['mode'] == 'move' && $form_values['move_mode'] == 'nodes'
    );
    $src_parent_mmtid = mm_content_get_parent($src_mmtid);
    $dest_mmtid = mm_ui_mmlist_key0($form_values['dest']);
  }

}
