<?php

/**
 * @file
 * Custom node types for Monster Menus
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;
use Drupal\monster_menus\Constants;
use Drupal\node\Controller\NodeViewController;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

function mm_node_types() {
  return array(
    'redirect' => array(
      'base' => 'mm_node_redirect',
    ),
    'subpglist' => array(
      'base' => 'mm_node_subpglist',
    ),
  );
}

/**
 * Generate a page displaying a single node, along with its comments. This
 * modified version of node_show() is used by MM to allow comments to only be
 * visible to certain roles, when specified by the node's editor. In order for
 * this to work, you must enable the "access comments" permission for all roles.
 *
 * @param NodeInterface $node
 *   The node object to display
 * @return array
 *   The HTML of the resulting page
 */
function mm_node_show(NodeInterface $node) {
  $view_mode = 'full';
  \Drupal::moduleHandler()->alter('mm_node_show', $node, $view_mode);
  $entity_manager = \Drupal::service('entity.manager');
  /** @var NodeInterface $node */
  $node = $entity_manager->getTranslationFromContext($node);
  $node_view_controller = new NodeViewController($entity_manager, \Drupal::service('renderer'), \Drupal::currentUser());
  $page = $node_view_controller->view($node);
  if (!$node->isDefaultRevision()) {
    unset($page['nodes'][$node->id()]['#cache']);
  }
  return $page;
}

function mm_node_all_nodes_hook($op, $args) {
  $return = array();
  // By default, assume all args are passed in an array, to preserve references.
  // If not, use func_get_args().
  $all_args = func_get_args();
  if (count($all_args) != 2 || !is_array($args)) {
    $args = $all_args;
    array_shift($args);
  }
  $node = $args[0];
  if (is_object($node)) {
    if (is_subclass_of($node, '\Drupal\node\NodeInterface')) {
      /** @var NodeInterface $node */
      $type = $node->getType();
    }
    elseif (is_subclass_of($node, '\Drupal\node\NodeTypeInterface')) {
      /** @var NodeType $node */
      $type = $node->id();
    }
  }
  if (isset($type)) {
    $list = mm_node_types();
    if (isset($list[$type])) {
      $mm_nodefunc = $list[$type]['base'] . '_all_nodes_' . $op;
      if (function_exists($mm_nodefunc)) {
        $result = call_user_func_array($mm_nodefunc, $args);
        if (isset($result) && is_array($result)) {
          $return = NestedArray::mergeDeepArray([$return, $result]);
        }
        elseif (isset($result)) {
          $return[] = $result;
        }
      }
    }
  }
  return $return;
}

/******************** Redirector functions start here *********************/

/**
 * Implements hook_form_FORMID_alter().
 */
function monster_menus_form_node_redirect_form_alter(&$form, FormState $form_state) {
  /** @var NodeInterface $node */
  $node = $form_state->getFormObject()->getEntity();
  $form['#title'] = $node->isNew() ? t('Create a redirector') : t('Edit a redirector');

  $form['title']['#access'] = FALSE;
  $form['title']['widget'][0]['value']['#default_value'] = t('Redirector');

  $form['field_redirect_url']['widget'][0]['value']['#type'] = 'url';
  $form['field_redirect_url']['widget'][0]['value']['#attributes']['class'][] = 'node-redir-url';

  $mmlist = array();
  if ($node) {
    $redir_mmtid = $node->field_redirect_mmtid->getValue();
    if ($redir_mmtid && isset($redir_mmtid[0]['value']) && ($tree = mm_content_get($redir_mmtid[0]['value'])) && mm_content_user_can($redir_mmtid[0]['value'], Constants::MM_PERMS_READ)) {
      $mmlist = array($redir_mmtid[0]['value'] => mm_content_get_name($tree));
      $pop_mmtid = $redir_mmtid[0]['value'];
    }
    else {
      $pop_mmtid = mm_home_mmtid();
      if ($node->id()) {
        $select = Database::getConnection()->select('mm_node2tree', 'n');
        $select->join('mm_tree', 't', 'n.mmtid = t.mmtid');
        $select->fields('t', array('mmtid', 'name'));
        $select->condition('n.nid', $node->id());
        $r = $select->execute()->fetchObject();
        if ($r) {
          $pop_mmtid = $r->mmtid;
        }
      }
    }

    $parents = mm_content_get_parents($pop_mmtid);
    array_shift($parents);  // skip root
    $pop_start = implode('/', $parents) . "/$pop_mmtid";
  }
  else {
    mm_parse_args($mmtids);
    $pop_start = implode('/', $mmtids);
  }

  $mmtid_widget = &$form['field_redirect_mmtid']['widget'][0]['value'];
  $mmtid_widget = [
    '#type' => 'mm_catlist',
    '#default_value' => $mmlist,
    '#mm_list_popup_start' => $pop_start,
    '#mm_list_max' => 1,
    '#mm_list_selectable' => Constants::MM_PERMS_READ,
    '#mm_list_other_name' => 'field_redirect_url[0][value]',
    '#value_callback' => '_mm_node_redirect_mmtid_value_callback',
  ] + $mmtid_widget;

  $form['#validate'][] = function(&$form, FormState $form_state) {
    if (!empty($form_state->getValue('field_redirect_mmtid')[0]['value'])) {
      $mmtid = $form_state->getValue('field_redirect_mmtid')[0]['value'];
      if (empty($mmtid) || !mm_content_user_can($mmtid, Constants::MM_PERMS_READ)) {
        $form_state->setErrorByName('field_redirect_mmtid',
          t('You are not allowed to redirect to page %cat.', array('%cat' => mm_content_get_name($mmtid))));
      }
    }
    elseif (empty($form_state->getValue('field_redirect_url')[0]['value'])) {
      $form_state->setErrorByName('field_redirect_url', t('You must either enter a URL or choose a page to redirect to.'));
    }
  };
  mm_static($form, 'node_redir');
}

function _mm_node_redirect_mmtid_value_callback(&$element, $input, FormState &$form_state) {
  if ($input === FALSE) {
    // Return default value.
    return NULL;
  }
  if (preg_match_all('#(\d+(?:/\d+)*)\{([^}]*)\}#', $input, $matches, PREG_SET_ORDER)) {
    return (int) $matches[0][1];
  }
  return 0;
}

/**
 * Implements hook_node_presave(), actually called by
 * monster_menus_node_presave()
 */
function mm_node_redirect_all_nodes_presave(NodeInterface $node) {
  // Make sure it's at the top of the page, so pagination can't prevent the
  // redirection from occurring.
  $node->setSticky(TRUE);
}

/**
 * Implements hook_view().
 */
function mm_node_redirect_all_nodes_view(NodeInterface $node, &$build, EntityViewDisplayInterface $display, $view_mode) {
  $redir_mmtid = $node->field_redirect_mmtid->getValue();
  unset($build['field_redirect_mmtid'], $build['field_redirect_url']);
  if ($node->access('update')) {
    if ($redir_mmtid && $redir_mmtid[0]['value']) {
      if (mm_content_get($redir_mmtid[0]['value'])) {
        $dest = mm_content_get_mmtid_url($redir_mmtid[0]['value']);
      }
    }
    elseif (!empty($node->field_redirect_url->getValue())) {
      $dest = Url::fromUri($node->field_redirect_url->getValue()[0]['value'], ['absolute' => TRUE]);
    }

    if (isset($dest)) {
      $output = t('This page would normally redirect the user to <a href=":dest">this location</a>. You are seeing this page because you are allowed to change the redirection.', array(':dest' => $dest->toString()));
    }
    else {
      \Drupal::messenger()->addError(t('A redirector on this page seems to be broken. This can happen when it refers to another page in the CMS which has been deleted. Please click on the Edit link to correct the problem.'));
      $output = t('This is a broken redirector.');
    }
    if ($view_mode == 'teaser') {
      $build['content']['teaser']['#markup'] = $output;
      $build['content']['body']['#markup'] = '';
    }
    else {
      $build['content']['teaser']['#markup'] = '';
      $build['content']['body']['#markup'] = $output;
    }
  }
  elseif ($view_mode == 'teaser' || $view_mode == 'full') {
    if ($redir_mmtid && $redir_mmtid[0]['value']) {
      if (mm_content_get($redir_mmtid[0]['value'])) {
        $resp = new RedirectResponse(mm_content_get_mmtid_url($redir_mmtid[0]['value'])->toString(), 303);
        $resp->send();
        return;
      }
    }
    elseif (!empty($node->field_redirect_url->getValue())) {
      $resp = new RedirectResponse(Url::fromUri($node->field_redirect_url->getValue()[0]['value'], ['absolute' => TRUE])->toString(), 303);
      $resp->send();
      return;
    }
    else {
      $node->no_attribution = TRUE;
      $node->setTitle('');
    }
  }
}

/**
 * Implements hook_mm_fix_node_urls_info().
 */
function mm_node_redirect_mm_fix_node_urls_info() {
  return array(
    'field_redirect_url' => array(
      'table' => 'node__field_redirect_url',
      'join on' => '%alias.revision_id = node.vid',
      'table field' => 'field_redirect_url_value',
      'get' => function (NodeInterface $node) {
        return isset($node->field_redirect_url->getValue()[0]['value']) ? $node->field_redirect_url->getValue()[0]['value'] : NULL;
      },
      'set' => function ($value, NodeInterface $node) {
        $node->field_redirect_url->setValue([['value' => $value]]);
      },
    ),
  );
}

/******************** Subpage List functions start here *********************/


/**
 * Implements hook_form_FORMID_alter().
 */
function monster_menus_form_node_subpglist_form_alter(&$form, FormState $form_state) {
  /** @var NodeInterface $node */
  $node = $form_state->getFormObject()->getEntity();
  $form['#title'] = $node->isNew() ? t('Create a subpage list') : t('Edit a subpage list');
}

function monster_menus_preprocess_node__subpglist(&$variables) {
  $teaser = $variables['view_mode'] == 'teaser';
  /** @var NodeInterface $node */
  $node = $variables['node'];

  // We don't use any of the default formatting, so remove everything but body
  // and links. But save a list of visible fields first, so that we can be sure
  // only use those in the final output.
  $visible_fields = array_keys($variables['content']);
  $variables['content'] = array_intersect_key($variables['content'], ['body' => 0, 'links' => 0]);

  if (!$teaser) {
    mm_parse_args($mmtids, $oarg_list, $this_mmtid);
    if (!isset($this_mmtid)) {
      $with_node = mm_content_get_by_nid($node->id());
      if (!count($with_node)) {
        return;
      }
      $this_mmtid = $with_node[0];
    }

    if ($temp = $node->get('field_subpglist_filter')[0]) {
      $text_filter = FilterFormat::load($temp->getValue()['target_id']);
    }
    else {
      $text_filter = FilterFormat::load(filter_fallback_format());
    }
    $values = [];
    foreach ($node->toArray() as $name => $value) {
      if ($name != 'field_subpglist_filter' && !strncmp('field_subpglist_', $name, 16)) {
        $values[$name] = in_array($name, $visible_fields) && $value ? $value[0]['value'] : '';
        if (!isset($node->subpglist_no_filter) && $values[$name] && preg_match('{(_prefix|_suffix)$}', $name)) {
          if (($filtered = function_exists('editor_filter_xss') ? editor_filter_xss($values[$name], $text_filter) : check_markup($values[$name], $text_filter ? $text_filter->id() : NULL)) !== FALSE) {
            $values[$name] = $filtered;
          }
        }
      }
    }

    $tree_unfiltered = mm_content_get_tree($this_mmtid,
      array(
        Constants::MM_GET_TREE_RETURN_PERMS => TRUE,
        Constants::MM_GET_TREE_RETURN_BLOCK => TRUE,
        Constants::MM_GET_TREE_DEPTH => $values['field_subpglist_depth'],
        Constants::MM_GET_TREE_ADD_TO_CACHE => TRUE,
      )
    );
    array_shift($tree_unfiltered);

    // There are two types of filtering that need to happen. One: Completely
    // remove (prune) all items (and their kids) that the user can't possibly see.
    // Two: Separate out the items that most users can't see, but the current
    // user can. Keep the topmost parent only.
    $shown = $hidden = array();
    $hide_skip_level = $prune_skip_level = 0;
    foreach ($tree_unfiltered as $item) {
      if ($item->level <= $prune_skip_level) {
        $prune_skip_level = 0;
      }
      if ($item->level <= $hide_skip_level) {
        $hide_skip_level = 0;
      }
      if (!$prune_skip_level) {
        if ((empty($item->bid) || $item->bid == Constants::MM_MENU_BID || $item->bid == Constants::MM_MENU_UNSET ||
            $item->hidden && ($item->perms[Constants::MM_PERMS_WRITE] || $item->perms[Constants::MM_PERMS_SUB] || $item->perms[Constants::MM_PERMS_APPLY])) && ($item->name != Constants::MM_ENTRY_NAME_RECYCLE || mm_content_user_can_recycle($item->mmtid, Constants::MM_PERMS_READ))
        ) {
          if (!$hide_skip_level) {
            if (!$item->perms[Constants::MM_PERMS_IS_RECYCLED] && !$item->hidden) {
              $shown[] = $item;
            }
            else {
              $hide_skip_level = $item->level;
              $hidden[] = $item;
            }
          }
        }
        else {
          $prune_skip_level = $item->level;
        }
      }
    }
    unset($tree_unfiltered);

    $out = array('subpglist' => array(), 'subpglist-hidden' => array());
    foreach (array('subpglist' => $shown, 'subpglist-hidden' => $hidden) as $section => $tree) {
      if (count($tree)) {
        $cols = min($values['field_subpglist_columns'], count($tree));
        $tree = array_values($tree);
        $branches = array();
        $cur_branch = $this_mmtid;
        foreach ($tree as $leaf) {
          if ($leaf->parent == $this_mmtid) {
            $cur_branch = $leaf->mmtid;
          }
          $branches[$cur_branch][] = $leaf;
        }

        $per_col = floor(count($tree) / $cols);
        $remainder = count($tree) - $per_col * $cols;
        $col = 0;
        $cur_in_col = 0;
        $rem_in_col = $remainder > 0;
        $cur_branch = 0;
        $out[$section][$col] = array(
          '#prefix' => $values['field_subpglist_column_prefix'],
          '#suffix' => $values['field_subpglist_column_suffix'],
        );
        foreach ($branches as $branch) {
          if (($cur_in_col >= $per_col + $rem_in_col && $col != $cols - 1) ||
            (count($branches) - $cur_branch == $cols - $col - 1)
          ) {
            $cur_in_col = 0;
            $rem_in_col = --$remainder > 0;
            $col++;
            $out[$section][$col] = array(
              '#prefix' => $values['field_subpglist_column_prefix'],
              '#suffix' => $values['field_subpglist_column_suffix'],
            );
          }
          $index = 0;
          $out[$section][$col][] = mm_node_subpglist_render_branch($branch, $index, $values);
          $cur_in_col += count($branch);
          $cur_branch++;
        }
      }
    }

    if (count($out['subpglist'])) {
      $out['subpglist']['#prefix'] = $values['field_subpglist_outer_prefix'];
      $out['subpglist']['#suffix'] = $values['field_subpglist_outer_suffix'];
      $out['subpglist']['#weight'] = 1;
    }

    if (count($out['subpglist-hidden'])) {
      $out['subpglist-hidden']['#weight'] = 2;
      $out['subpglist-hidden']['#prefix'] = (isset($node->subpglist_hidden_title) ? $node->subpglist_hidden_title : t('<p>The following item(s) are normally hidden:</p>')) . $values['field_subpglist_outer_prefix'];
      $out['subpglist-hidden']['#suffix'] = $values['field_subpglist_outer_suffix'];
    }

    $variables['content']['subpglist'] = array(
      '#weight' => 102,
      $out,
    );

    $postamble = $values['field_subpglist_postamble'];
    if (!empty($postamble)) {
      if (substr($postamble, 0, 3) != '<p>') {
        $postamble = "<p>$postamble</p>";
      }
      $variables['content']['postamble'] = array(
        '#markup' => $postamble,
        '#weight' => 103,
      );
    }
  }
}

function mm_node_subpglist_render_branch($branch, &$index, $values) {
  $out = array();
  $start_level = $branch[$index]->level;
  $max = count($branch);

  while ($index < $max && $branch[$index]->level == $start_level) {
    $leaf = $branch[$index++];

    $sub = array();
    while ($index < $max && $branch[$index]->level > $start_level) {
      $sub[] = array(
        '#prefix' => $values['field_subpglist_section_prefix'],
        'list' => mm_node_subpglist_render_branch($branch, $index, $values),
        '#suffix' => $values['field_subpglist_section_suffix'],
      );
    }

    $out[] = array(
      '#cache' => ['tags' => ['mm_tree:' . $leaf->mmtid]],
      '#prefix' => $values['field_subpglist_row_prefix'],
      Link::fromTextAndUrl(mm_content_get_name($leaf), mm_content_get_mmtid_url($leaf->mmtid))->toRenderable(),
      '#suffix' => $values['field_subpglist_row_suffix'],
      $sub,
    );
  }

  return $out;
}
