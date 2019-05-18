<?php

/**
 * @file
 * User interface routines for monster_menus
 */

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Database;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\filter\Render\FilteredMarkup;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Controller\DefaultController;
use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Groups;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

function mm_ui_comment_redirect($default) {
  if (mm_parse_args($mmtids, $oarg_list, $this_mmtid) == 'mm')
    return "mm/$this_mmtid";
  return $default;
}

function mm_ui_machine_name_exists() {
  // This is a dummy function. The real validation is handled elsewhere.
  return FALSE;
}

/**
 * Implements hook_form_BASE_FORM_ID_alter(). (admin/structure/types/manage)
 */
function monster_menus_form_node_type_form_alter(&$form) {
  if (isset($form['comment']['comment'])) {
    $form['comment']['comment']['#description'] = t('Users with the <em>administer comments</em> or <em>enable/disable comments</em> permission will be able to override this setting.');
  }

  if (isset($form['display']['node_submitted'])) {
    $form['display']['node_submitted']['#description'] = t('Because you are using Monster Menus, the setting for <em>Submitted by [username] on [date]</em> text is controlled independently, within each piece of content. The setting here acts as a global override for all content of the given type. If not checked, the text is never displayed for this type of content.');
  }

  $form['mm_default_region'] = array(
    '#type' => 'details',
    '#title' => t('Default region'),
    '#description' => t('This setting controls where new content of this type will appear on the page, by default. After the node has been saved, its location can be changed with the <em>Reorder</em> tab.'),
    '#group' => 'additional_settings',
  );
  _mm_ui_get_regions($regions, $select);
  foreach ($regions as $region => $data) {
    $form['mm_default_region'][] = array(
      '#type' => 'item',
      '#input' => FALSE,
      '#markup' => $data['message'],
      '#states' => array('visible' => array(':input[name="mm_default_region"]' => array('value' => $region))),
    );
  }
  $defaults = mm_get_setting('nodes.default_region');
  $form['mm_default_region']['mm_default_region'] = array(
    '#type' => 'select',
    '#title' => t('Default region for newly created nodes'),
    '#options' => $select,
    '#default_value' => isset($defaults[$form['type']['#default_value']]) ? $defaults[$form['type']['#default_value']] : Constants::MM_UI_REGION_CONTENT,
    '#weight' => -1,
  );
  $form['#submit'][] = function($form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = \Drupal::service('config.factory')->getEditable('monster_menus.settings');
    $defaults = $config->get('nodes.default_region');
    $defaults[$form_state->getValue('type')] = $form_state->getValue('mm_default_region');
    $config
      ->set('nodes.default_region', $defaults)
      ->save();
  };
}

function _mm_ui_get_regions(&$regions, &$select, $li = TRUE) {
  $regions = array();
  $num_themes = 0;
  foreach (\Drupal::service('theme_handler')->listInfo() as $theme => $data) {
    if ($data->status) {
      $num_themes++;
      foreach ($data->info['regions'] as $region => $long_name) {
        if (!isset($data->info['regions_hidden']) || !in_array($region, $data->info['regions_hidden'])) {
          $regions[$region]['long_name'] = $long_name;
          $regions[$region]['themes'][] = $theme;
        }
      }
    }
  }

  uasort($regions, function($a, $b) {
    return strcasecmp($a['long_name'], $b['long_name']);
  });

  $select = array();
  foreach ($regions as $region => $data) {
    natcasesort($data['themes']);
    $list = count($data['themes']) == $num_themes ? t('all themes') : implode(', ', $data['themes']);
    if (mb_strlen($list) > 50) {
      $list = Unicode::truncateBytes($list, 50) . '...';
    }
    $select[$region] = $data['long_name'] . ' (' . $list . ')';
    if (count($data['themes']) == $num_themes) {
      $regions[$region]['message'] = t('The selected region is used in all themes.');
    }
    else {
      $msg = t('The selected region is used in the following theme(s):');
      if ($li) {
        $regions[$region]['message'] = $msg . '<ul><li>' . implode('</li><li>', $data['themes']) . '</li>';
      }
      else {
        $regions[$region]['message'] = $msg . '&nbsp; ' . implode(', ', $data['themes']);
      }
    }
  }
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 *
 * Adjust a couple of form elements on the block admin page.
 */
function monster_menus_form_block_form_alter(&$form, FormState &$form_state, $form_id) {
  if ($form_state->getTemporaryValue('is_mm_block')) {
    $form['settings']['label']['#title'] = t('Internal title');
    $form['settings']['label']['#description'] = t('The name visible to users in the Appearance settings for a @thing', mm_ui_strings(FALSE));
    // Visibility settings are not meaningful for MM blocks
    $form['visibility']['#access'] = FALSE;
  }
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 *
 * Remove some unneeded node creation options, and add some of our own. This
 * only works if MM happens to load after the modules that create these form
 * elements.
 */
function monster_menus_form_node_form_alter(&$form, FormState &$form_state, $form_id) {
  /** @var NodeInterface $node */
  $node = $form_state->getFormObject()->getEntity();

  // Note: this cannot be turned into a lambda/closure because the form array
  // needs to be serializable for the Preview function to work.
  $form['#entity_builders'][] = '_monster_menus_node_builder';
  mm_parse_args($mmtids, $oarg_list, $this_mmtid);
  $mmlist = $mmlist_restricted = $mmlist_restricted_link = array();
  $db = Database::getConnection();

  if (isset($form['title'])) {
    if (isset($form['title']['widget'][0]['value'])) {
      if (!isset($form['title']['widget'][0]['value']['#description'])) {
        $form['title']['widget'][0]['value']['#description'] = '';
      }
      $desc = &$form['title']['widget'][0]['value']['#description'];
    }
    else {
      if (!isset($form['title']['#description'])) {
        $form['title']['#description'] = '';
      }
      $desc = &$form['title']['#description'];
    }
    $desc = t('@previous To prevent the title from displaying when viewing a page, surround it with square brackets. Example: [My Title]', ['@previous' => $desc]);
  }

  if (!$node->isNew()) {   // existing node
    if ($mmtids) {
      $form['#mm_redirect'] = mm_content_get_mmtid_url($this_mmtid);
    }

    if (mm_content_node_is_recycled($node, $this_mmtid)) {
      _mm_ui_form_array_merge($form, 'mm_categories', array(
        '#type' => 'details',
        '#title' => t('Pages'),
        '#group' => 'advanced',
        '#weight' => 120,
      ));

      _mm_ui_recycle_page_list(array(mm_content_get_parent($this_mmtid)), $names, $msg);
      if (!$names) {
        $msg = t('This content is not associated with a page. If restored, it will be visible only by its direct web address.');
      }
      elseif (count($names) == 1) {
        $msg = t('If you restore this content, it will return to the page @link.', array('@link' => $names[0]));
      }
      else {
        $msg = t('If you restore this content, it will return to the following pages: @pages', ['@pages' => FilteredMarkup::create(implode(', ', $names))]);
      }

      $form['mm_catlist'] = array(
        '#type' => 'value',
        '#value' => array(),
      );
      $form['mm_catlist_restricted'] = array(
        '#type' => 'value',
        '#value' => $node->recycle_from_mmtids
      );
      // Needed for mm_content_node_is_recycled()
      $form['recycle_date'] = array(
        '#type' => 'value',
        '#value' => $node->recycle_date
      );
      $form['mm_catlist_readonly'] = array(
        '#type' => 'container',
        '#markup' => t('The list of pages cannot be changed because this content is in the recycle bin.'),
        '#description' => $msg,
        '#group' => 'mm_categories',
      );
    }
    else {  // !recycled
      $nid = $node->id();

      if ($nid) {
        foreach (mm_content_get(mm_content_get_by_nid($nid)) as $r) {
          if (mm_content_user_can($r->mmtid, Constants::MM_PERMS_APPLY)) {
            $mmlist[$r->mmtid] = mm_content_get_name($r);
          }
          else {
            $mmlist_restricted[] = $r->mmtid;
            $mmlist_restricted_link[] = array(':link' => mm_content_get_mmtid_url($r->mmtid), '@title' => mm_content_get_name($r));
          }
        }
      }

      _mm_ui_form_array_merge($form, 'mm_categories', array(
        '#type' => 'details',
        '#title' => t('Pages'),
        '#group' => 'advanced',
        '#weight' => 120,
      ));
      $form['mm_catlist_restricted'] = array(
        '#type' => 'value',
        '#value' => $mmlist_restricted,
      );
      if ($mmlist_restricted) {
        $links = array('@plur' => count($mmlist_restricted) == 1 ? t('page') : t('pages:') . ' ');
        $msg = '<span style="color:red">This content will also appear on the @plur ';
        foreach ($mmlist_restricted_link as $index => $link) {
          if ($index > 0) {
            $msg .= ', ';
          }
          $msg .= '<a href=":link' . $index . '">@title' . $index . '</a>';
          $links[':link' . $index] = $link[':link'];
          $links['@title' . $index] = $link['@title'];
        }
        $msg .= '. You do not have permission to change this fact.</span>';
        $form['mm_catlist_warning'] = array(
          '#type' => 'container',
          '#description' => t($msg, $links),
          '#group' => 'mm_categories',
        );
      }
      $form['mm_catlist'] = array(
        '#type' => 'mm_catlist',
        '#description' => t('Choose additional pages where this content will appear.'),
        '#mm_list_popup_start' => implode('/', $mmtids),
        '#default_value' => $mmlist,
        '#group' => 'mm_categories',
      );
    }   // if (not) recycled

    $everyone = FALSE;
    $grouplist = array();
    $select = $db->select('mm_node_write', 'nw');
    $select->leftJoin('mm_tree', 't', 'nw.gid = t.mmtid');
    $select->fields('nw', array('gid'))
      ->fields('t', array('uid'));
    $select->condition('nw.gid', 0, '>=');
    $select->condition('nw.nid', $node->id());
    $select->orderBy('t.name');
    $result = $select->execute();
    foreach ($result as $r) {
      if ($r->gid == 0) {
        $everyone = TRUE;
      }
      else {
        $members = mm_content_get_users_in_group($r->gid, '<br />', FALSE, 20, TRUE, $form);
        if ($members == '') {
          $members = t('(none)');
        }
        $grouplist[$r->gid]['name'] = mm_content_get_name($r->gid);
        if (!empty($r->uid)) {
          $owner_display = ['#type' => 'username', '#account' => User::load($r->uid)];
          $owner_display = \Drupal::service('renderer')->render($owner_display);
        }
        else {
          $owner_display = t('not available');
        }
        $group_info_message = mm_get_setting(mm_content_is_vgroup($r->gid) ? 'vgroup.group_info_message' : 'group.group_info_message');
        $group_info_message = '<div id="mmgroupinfo-' . $r->gid . '" class="hidden"><p>' . t($group_info_message, array('@gid' => $r->gid, '@owner' => $owner_display)) . '</p></div>';
        $group_info_link = !empty($group_info_message) ? Link::fromTextAndUrl(t('Group information'), Url::fromRoute('<current>', [], array(
          'fragment' => 'mmgroupinfo-' . $r->gid,
          'external' => TRUE,
          'attributes' => array(
            'id' => mm_ui_modal_dialog([], $form),
            'title' => t('Information about this group'),
          )
        )))->toString() : '';
        $grouplist[$r->gid]['members'] = (mm_content_user_can($r->gid, Constants::MM_PERMS_WRITE) ? Link::fromTextAndUrl(t('Edit this group'), Url::fromRoute('monster_menus.handle_page_settings', ['mm_tree' => $r->gid]))->toString() . ' | ' : '') . $group_info_message . $group_info_link . '<br />' . $members;
      }
    }

    $userlist = array();
    $select = $db->select('mm_node_write', 'nw')
      ->fields('nw', array('gid'));
    $select->condition('nw.gid', 0, '<');
    $select->condition('nw.nid', $node->id());
    $result = $select->execute();
    if ($r = $result->fetchObject()) {
      $users = mm_content_get_users_in_group($r->gid, NULL, TRUE, Constants::MM_UI_MAX_USERS_IN_GROUP);
      if (!is_null($users)) $userlist = $users;
    }

    if (count($userlist) || count($grouplist)) {
      $everyone = FALSE;
    }
    $uid = $node->getOwnerId();

    _mm_ui_node_form_perms($form, $userlist, $grouplist, $everyone, node_get_type_label($node), $uid, NULL, $form_state->isRebuilding());
  }
  else {    // new node
    if ($mmtids || $mmlist) {
      $tree = mm_content_get($this_mmtid);
      $mmlist = array($this_mmtid => mm_content_get_name($tree));
      mm_content_get_default_node_perms($this_mmtid, $temp_grouplist, $userlist, Constants::MM_UI_MAX_USERS_IN_GROUP);

      $grouplist = array();
      foreach ($temp_grouplist as $gid => $name) {
        $members = mm_content_get_users_in_group($gid, '<br />', FALSE, 20, TRUE, $form);
        if ($members == '') $members = t('(none)');
        $grouplist[$gid]['name'] = $name;
        if (!empty($tree->uid)) {
          $owner_display = ['#type' => 'username', '#account' => User::load($tree->uid)];
          $owner_display = \Drupal::service('renderer')->render($owner_display);
        }
        else {
          $owner_display = t('not available');
        }
        $group_info_message = mm_get_setting(mm_content_is_vgroup($gid) ? 'vgroup.group_info_message' : 'group.group_info_message');
        $group_info_message = '<div id="mmgroupinfo-' . $gid . '" class="hidden"><p>' . t($group_info_message, array('@gid' => $gid, '@owner' => $owner_display)) . '</p></div>';
        $group_info_link = !empty($group_info_message) ? Link::fromTextAndUrl(t('Group information'), Url::fromRoute('<current>', [], array(
          'fragment' => 'mmgroupinfo-' . $gid,
          'external' => TRUE,
          'attributes' => array(
            'id' => mm_ui_modal_dialog([], $form),
            'title' => t('Information about this group'),
          )
        )))->toString() : '';
        $grouplist[$gid]['members'] = (mm_content_user_can($gid, Constants::MM_PERMS_WRITE) ? Link::fromTextAndUrl(t('Edit this group'), Url::fromRoute('monster_menus.handle_page_settings', ['mm_tree' => $gid]))->toString() . ' | ' : '') . $group_info_message . $group_info_link . '<br />' . $members;
      }

      $uid = \Drupal::currentUser()->id();
      $everyone = in_array(Constants::MM_PERMS_APPLY, explode(',', $tree->default_mode));
      $node->show_node_info = $tree->node_info;

      $node->comment = $tree->comment;
      if (mm_get_setting('comments.finegrain_readability')) {
        $node->comments_readable = mm_content_resolve_cascaded_setting('comments_readable', $this_mmtid, $cascaded_at, $cascaded_parent);
      }
      else {
        $node->comments_readable = '';
      }

      _mm_ui_form_array_merge($form, 'mm_categories', array(
        '#type' => 'details',
        '#title' => t('Pages'),
        '#group' => 'advanced',
        '#weight' => 120,
      ));
      $form['mm_catlist'] = array(
        '#type' => 'mm_catlist',
        '#description' => t('Choose additional pages where this content will appear.'),
        '#mm_list_popup_start' => implode('/', $mmtids),
        '#default_value' => $mmlist,
        '#group' => 'mm_categories',
      );
      $form['mm_catlist_restricted'] = array(
        '#type' => 'value',
        '#value' => $mmlist_restricted,
      );

      _mm_ui_node_form_perms($form, $userlist, $grouplist, $everyone, node_get_type_label($node), $uid, t('The default settings are taken from the settings of the page.') . ' ', $form_state->isRebuilding());
      $form['#mm_redirect'] = mm_content_get_mmtid_url($this_mmtid);
    }
  }   // if existing/new node

  if (!$form_state->get('is_mm_search') && DefaultController::menuAccessSolverByMMTID($this_mmtid)) {
    module_load_include('inc', 'monster_menus', 'mm_ui_solver');
    mm_ui_solver_link($form['settings_perms'], $this_mmtid);
  }

  $form['this_mmtid'] = array(
    '#type' => 'value',
    '#value' => $this_mmtid
  );

  $form['mm_catlist_restricted_link'] = array(
    '#type' => 'value',
    '#value' => $mmlist_restricted_link
  );

  unset($form['menu']);
  unset($form['promote']);
  // FIXME: test to see if this is still needed
//  if (mm_module_exists('pathauto')) {
//    // The path module's alias entry field doesn't work correctly. It seems to
//    // be a problem with the language field.
//    $form['path']['alias']['#access'] = FALSE;
//    unset($form['path']['pathauto']['#description']);
//  }
//  else {
  unset($form['path']);
//  }

  _mm_ui_form_array_merge($form, 'mm_appearance', array(
    '#type' => 'details',
    '#title' => t('Appearance'),
    '#group' => 'advanced',
    '#weight' => 123,
  ));
  if ($form_id == 'redirect_node_form') {   // redirector
    unset($form['comment_settings']);
    $form['comment'] = array(// comments = 'Disabled'
      '#type' => 'value',
      '#value' => 0,
    );
    $form['options']['#access'] = FALSE;
    unset($form['actions']['preview']);
    $form['sticky']['#access'] = FALSE;
  }
  else {
    if (isset($form['comment_settings']['comment']) && !$node->id()) {
      $form['comment_settings']['comment']['#default_value'] = $node->comment;
    }
    // provide alternate 'sticky' checkbox

    if (isset($form['sticky']['widget']['value'])) {
      $form['sticky']['#group'] = 'mm_appearance';
      $form['sticky']['widget']['value']['#title'] = t('Sticky at top of page');
      $form['sticky']['widget']['value']['#description'] = t('Content that is "sticky" will stay at the top of the page, even after other content is added. But the content\'s owner must be listed under %who (see %perm, under the %set tab) in order for this setting to take effect.', array('%who' => t('Who can delete this page or change its settings'), '%perm' => t('Permissions'), '%set' => t('Settings')));
    }
  }

  $toggle = $node_type_storage = \Drupal::entityTypeManager()->getStorage('node_type')->load($node->getType())->displaySubmitted();
  if (\Drupal::currentUser()->hasPermission('show/hide post information') && $toggle) {
    $form['mm_appearance']['show_node_info'] = array(
      '#type' => 'select',
      '#title' => t('Attribution style'),
      '#options' => _mm_ui_node_info_values($form['mm_appearance']),
      '#default_value' => isset($node->show_node_info) ? $node->show_node_info : 0,
      '#group' => 'mm_appearance',
    );
  }
  else {
    if (!$toggle && $node->isNew()) {
      // Always default to off for new nodes when disabled in all nodes of
      // this type. This way, if enabled later on for all nodes of this type,
      // the end result won't immediately change.
      $node->show_node_info = 0;
    }
    $form['show_node_info'] = array(
      '#type' => 'value',
      '#value' => isset($node->show_node_info) ? $node->show_node_info : 0,
      '#group' => 'mm_appearance',
    );
  }

  _mm_ui_form_array_merge($form, 'publishing', array(
    '#type' => 'details',
    '#title' => t('Publishing'),
    '#group' => 'advanced',
    '#weight' => 125,
  ));
  $form['status']['#group'] = 'publishing';
  // The weight of this field gets reset internally, so instead make sure that
  // MM's fields come after it.
  $weight = $form['status']['#weight'];
  $form['status']['#access'] = $form_state->get('is_mm_search') || !empty($form['status']['#access']);
  unset($form['options']);

  $publish_on = !empty($node->publish_on) ? DrupalDateTime::createFromTimestamp($node->publish_on) : '';
  $unpublish_on = !empty($node->unpublish_on) ? DrupalDateTime::createFromTimestamp($node->unpublish_on) : '';
  $t_now = array('%time' => mm_format_date(mm_request_time(), 'short'));
  _mm_ui_form_array_merge($form, 'publish_on', array(
    '#type' => 'datetime',
    '#title' => t('Publish on'),
    '#default_value' => $publish_on,
    '#date_increment' => 60,
    '#description' => t('Format: %time. Leave blank to disable scheduled publishing.', $t_now),
    '#group' => 'publishing',
    '#weight' => ++$weight,
  ));
  _mm_ui_form_array_merge($form, 'unpublish_on', array(
    '#type' => 'datetime',
    '#title' => t('Unpublish on'),
    '#default_value' => $unpublish_on,
    '#date_increment' => 60,
    '#description' => t('Format: %time. Leave blank to disable scheduled unpublishing.', $t_now),
    '#group' => 'publishing',
    '#weight' => ++$weight,
  ));

  _mm_ui_form_array_merge($form, 'set_change_date', array(
    '#type' => 'checkbox',
    '#title' => t('Use <em>Publish on</em> date for attributions'),
    '#default_value' => isset($node->set_change_date) ? $node->set_change_date : '',
    '#description' => t('Use the date above instead of the content\'s last modified date when showing <em>Submitted on [date]</em> or <em>Submitted by [user] on [date]</em>'),
    '#group' => 'publishing',
    '#weight' => ++$weight,
  ));

  if (isset($form['author'])) {
    if (\Drupal::currentUser()->hasPermission('administer all menus')) {
      // Admin users don't need the set author option, they should use "Who can edit/delete"
      $form['mm_appearance']['uid'] = array(
        '#type' => 'item',
        '#input' => FALSE,
        '#title' => t('Authored by'),
        '#markup' => t('<p>To change the author, set the %owner in the %where section.</p>',
          array(
            '%owner' => t('Owner'),
            '%where' => t('Who can edit or delete this content')
          )),
        '#group' => 'mm_appearance',
      );
      unset($form['uid']);
    }
    else {
      $form['uid']['#access'] = FALSE;
    }
    $form['author']['#group'] = 'mm_appearance';
    $form['created']['#group'] = 'mm_appearance';
  }

  if (isset($form['field_comments'])) {
    if ($finegrain = mm_get_setting('comments.finegrain_readability')) {
      $form['field_comments']['widget'][0]['comments_readable'] = array(
        '#type' => 'select',
        '#title' => t('Who can read comments'),
        '#default_value' => isset($node->field_comments[0]->getValue()['comments_readable']) ? $node->field_comments[0]->getValue()['comments_readable'] : (isset($node->comments_readable) ? $node->comments_readable : ''),
        '#options' => _mm_ui_comment_read_setting_values(t('(use default setting)')),
        '#weight' => -1,
      );
      $form['field_comments']['widget'][0]['status']['#type'] = 'select';
      $form['field_comments']['widget'][0]['status']['#title'] = t('Who can add comments');
      unset($form['field_comments']['widget'][0]['status']['#title_display']);
    }
    $can_affect_comments = \Drupal::currentUser()->hasPermission('enable/disable comments') || \Drupal::currentUser()->hasPermission('administer comments');
    $form['field_comments']['#access'] = $can_affect_comments || $finegrain;
    $form['field_comments']['widget'][0]['status']['#access'] = $can_affect_comments;
    $form['field_comments']['widget'][0]['status']['#options'] = _mm_ui_comment_write_setting_values();
  }

  if (!$form_state->get('is_mm_search')) {
    $form['actions']['required-note'] = array('#weight' => 10000, '#markup' => t('<div class="requiredfields"><span class="form-required"></span> denotes required fields</div>'));
  }

  if (isset($form['#mm_redirect'])) {
    // Note: This cannot be inlined because serialization will fail.
    $form['#validate'][] = '_mm_ui_node_form_redirect';
  }

  // Change the "Save and keep published/unpublish" dropbutton into a single
  // submit button because we use a checkbox in the Publishing tab.

  // Note: This cannot be inlined because serialization will fail.
  $form['actions']['submit']['#submit'][] = '_mm_ui_node_form_submit';
  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#submit' => $form['actions']['submit']['#submit'],
    '#value' => t('Save'),
    '#weight' => 0,
  ];
  unset($form['actions']['publish']);
  unset($form['actions']['unpublish']);

  // Add JS code to update summaries in vertical tabs
  _mm_ui_add_summary_js($form);   // Initialize summaries.
  foreach (array('mm_appearance', 'publishing', 'settings_perms') as $id) {
    if (isset($form[$id]['#type']) && $form[$id]['#type'] == 'details') {
      _mm_ui_add_summary_js($form, $id);
    }
  }

  mm_static($form, 'mm_categories_summary', isset($form['mm_catlist_restricted']['#value']) ? count($form['mm_catlist_restricted']['#value']) : 0);
}

function _mm_ui_node_form_submit($form, FormStateInterface $form_state) {
  /** @var NodeInterface $node */
  $node = $form_state->getFormObject()->getEntity();
  $node->setPublished($form_state->getValue('status'));
}

function _mm_ui_node_form_redirect($form, FormStateInterface $form_state) {
  if (isset($form['#mm_redirect'])) {
    if ($form_state->getTriggeringElement()['#id'] == 'edit-preview') {
      // Remove the destination parameter so that the user isn't redirected
      // there.
      \Drupal::request()->query->remove('destination');
    }
    else {
      // The node form code always redirects to node/NN, so this is the only
      // good way to make it go to the page instead.
      $form_state->disableRedirect()
          ->setResponse(new RedirectResponse($form['#mm_redirect']->toString(), 303));
    }
  }
}

function monster_menus_form_user_role_form_alter(&$form) {
  _mm_ui_form_array_merge($form, 'mm_group', array(
    '#type' => 'details',
    '#title' => t('Add group members'),
    '#description' => t('The members of the chosen Monster Menus group will be added to this role.'),
    '#open' => TRUE));
  $current = isset($form['id']['#default_value']) ? Role::load($form['id']['#default_value']) : Role::create();
  $form['mm_group']['mm_gid'] = array(
    '#type' => 'mm_grouplist',
    '#mm_list_popup_start' => mm_content_groups_mmtid(),
    '#mm_list_max' => 1,
    '#default_value' => isset($current->mm_gid) ? array($current->mm_gid => mm_content_get_name($current->mm_gid)) : NULL,
  );
  $form['mm_group']['mm_exclude'] = array(
    '#type' => 'radios',
    '#options' => array(
      0 => t('Add all users <b>in</b> this group'),
      1 => t('Add all users <b>not in</b> this group'),
    ),
    '#default_value' => (int) !empty($current->mm_exclude)
  );
  $form['actions']['submit']['#validate'][] = function($form, FormStateInterface $form_state) {
    // Translate array key into single value.
    $form_state->setValue('mm_gid', mm_ui_mmlist_key0($form_state->getValue('mm_gid')));
  };
}

/**
 * Entity builder to copy $form_state values into the Node object.
 *
 * @param string $entity_type_id
 *   The entity type identifier.
 * @param NodeInterface $node
 *   The node updated with the submitted values.
 * @param array $form
 *   The complete form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @see \Drupal\node\NodeForm::form()
 */
function _monster_menus_node_builder($entity_type_id, NodeInterface $node, array $form, FormStateInterface $form_state) {
  $fields = [
    'all_values_group',
    'all_values_user',
    'mm_catlist',
    'mm_catlist_restricted',
    'mm_catlist_restricted_link',
    'node-everyone',
    'owner',
    'publish_on',
    'set_change_date',
    'show_node_info',
    'unpublish_on',
  ];
  $values = $form_state->getValues();
  foreach ($fields as $field) {
    if (isset($values[$field])) {
      if (is_a($values[$field], '\Drupal\Core\Datetime\DrupalDateTime')) {
        $node->$field = $values[$field]->format('U');
      }
      else {
        $node->$field = $values[$field];
      }
    }
    else {
      unset($node->$field);
    }
  }
}

/**
 * Implements hook_preprocess_node().
 */
function monster_menus_preprocess_node(&$variables) {
  /** @var NodeInterface $node */
  $node = $variables['node'];

  // Disable the attribution ("submitted by X on Y") line if _mm_render_pages
  // sets the no_attribution flag in the node or if the node itself has
  // show_attribution set and FALSE
  $variables['display_submitted'] = FALSE;
  $variables['submitted'] = '';
  $variables['user_picture'] = '';

  if (isset($node->show_node_info) && $node->show_node_info >= 1 && $node->show_node_info <= 3 && empty($node->no_attribution)) {
    // Unlike Drupal core, show the date of the last change, not the creation date
    $date = $node->getChangedTime();
    if (!empty($node->set_change_date) && $node->publish_on > 0) $date = $node->publish_on;
    if (!empty($date)) {
      $data = array(
        '@username' => $variables['author_name'],
        '@datetime' => mm_format_date($date),
      );
      switch ($node->show_node_info) {
        case 1:
          $variables['submitted'] = t('Submitted by @username', $data);
          break;

        case 2:
          $variables['submitted'] = t('Submitted on @datetime', $data);
          break;

        case 3:
          $variables['submitted'] = t('Submitted by @username on @datetime', $data);
          break;
      }
      $variables['display_submitted'] = TRUE;
    }
  }
}

// Kept for backward compatibility. Usually no longer needed, now that
// _mm_ui_process_mmlist handles this automatically.
function _mm_ui_split_mmlist($str) {
  if (is_array($str)) {
    $matches = array();
    foreach ($str as $key => $val) {
      $matches[] = array(NULL, $key, $val);
    }
    return $matches;
  }
}

// Return the first array key of an array. Useful when retrieving the first key
// from an mm_list value array.
function mm_ui_mmlist_key0($arr) {
  if (!is_array($arr)) return;
  reset($arr);
  if (count($arr)) {
    return key($arr);
  }
}

/**
 * Parse the hidden data field generated by an mm_repeatlist form element into a
 * set of arrays.
 *
 * @param string $str
 *   Form data to parse
 * @param int $per_row
 *   The number of data elements per row (set)
 * @return array[]
 *   An array of arrays. Each inner array represents one set of data entered by
 *   the user.
 */
function mm_ui_parse_repeatlist($str, $per_row) {
  if ($per_row <= 0 || !preg_match_all('/\{:(.*?):\}/', $str, $matches, PREG_PATTERN_ORDER)) return array();
  return array_chunk($matches[1], $per_row);
}

function mm_ui_validate_sometimes_required($elt, $value, FormStateInterface $form_state, $message = NULL) {
  if (empty($value) && $value !== '0') {
    $form_state->setError($elt, empty($message) ? t('@name field is required.', array('@name' => $elt['#title'])) : t($message));
    return FALSE;
  }
  return TRUE;
}

/**
 * Get a list of possible variable substitutions
 *
 * @param int $weight
 *   Weight of the resulting form element
 * @param array $list
 *   Array of human-readable field names to which the help text applies
 * @param array $xvars
 *   Optional array of system variables which are allowed
 * @return array
 *   A form element containing the help text
 */
function mm_ui_vars_help($weight, $list, $xvars = NULL) {
  $uid = Database::getConnection()->select('users', 'u')
    ->fields('u', array('uid'))
    ->condition('u.uid', 1, '>')
    ->range(0, 1)
    ->execute()->fetchField();
  if (!$uid) $uid = \Drupal::currentUser()->id();

  $vars = array();
  foreach (User::load($uid) as $k => $v)
    if (is_scalar($v)) $vars[] = '${' . $k . '}';

  sort($vars);
  $vars = join("\n", $vars);

  foreach ($list as &$l)
    $l = '<em class="placeholder">' . Html::escape($l) . '</em>';
  $last = array_pop($list);
  $list = join(', ', $list) . t(' or ') . $last;

  $ret = array(
    '#type' => 'details',
    '#title' => t('Variable substitution'),
    '#weight' => $weight,
    '#open' => FALSE,
    '#description' => t('The variables below can be inserted into the fields @list.<br />Variables which describe the current user:',
        array('@list' => $list)) . "<pre>$vars</pre>",
  );
  if (count($xvars)) {
    sort($xvars);
    $xvars = '${' . join("}\n\${", $xvars) . '}';
    $ret['#description'] .= t('System variables:<pre>@xvars</pre>', array('@xvars' => $xvars));
  }
  return $ret;
}

function mm_ui_mmlist_labels() {
  return array(t('Move to top'), t('Move up'), t('Delete this row'),
      t('Move to bottom'), t('Move down'), t('Edit'));
}

/**
 * Escape certain sequences for output as part of Javascript code
 *
 * @param $string
 *   The code to be escaped
 * @return
 *   The escaped code
 */
function mm_ui_js_escape($string) {
  return str_replace(
    array("\r", "\n", '<', '>', '&', '{', '}', '"'),
    array('', '', '\x3c', '\x3e', '\x26', '&#123;', '&#125;', '&quot;'),
    addslashes($string));
}

/**
 * Implements hook_theme().
 */
function monster_menus_theme() {
  $themes = array(
    'mm_catlist' => array(
        'variables' => ['mm_list_instance' => '', 'mm_list_tag' => '', 'mm_list_class' => '']),
    'tooltip' => array(
        'file' => 'mm_theme.inc',
        'variables' => array('text' => NULL, 'title' => NULL, 'tip' => NULL, 'html' => FALSE)),
    'mm_repeatlist_div' => array(
        'file' => 'mm_theme.inc',
        'variables' => array('elt' => NULL)),
    'mm_repeatlist' => array(
        'variables' => ['mm_list_instance' => '', 'mm_list_class' => '', 'mm_list_desc' => '']),
    'mm_help_radio' => array(
        'render element' => 'element'),
    'mm_help_radios' => array(
        'render element' => 'element'),
    'mm_ui_mark_yesno' => array(
        'file' => 'mm_theme.inc',
        'variables' => array('yes' => FALSE)),
    'mm_archive_header' => array(
        'file' => 'mm_theme.inc',
        'variables' => array('frequency' => NULL, 'date' => NULL)),
    'mm_archive' => array(
        'file' => 'mm_theme.inc',
        'variables' => array('list' => NULL, 'frequency' => NULL,
          'this_mmtid' => NULL, 'main_mmtid' => NULL, 'archive_mmtid' => NULL,
          'date' => NULL)),
    'mm_tree_menu' => array(
        'file' => 'mm_theme.inc',
        'variables' => array('menu_name' => NULL, 'items' => [], 'attributes' => [])),
    'mm_autocomplete_desc' => array(
        'variables' => array()),
    'mm_browser_bookmark_add' => array(
        'variables' => array('name' => '', 'mmtid' => 0, 'base_path' => base_path(), 'mm_path' => drupal_get_path('module', 'monster_menus'))),
  );

  return $themes;
}

/**
 * Return "(untitled)" when a node's title is empty
 *
 * @param string $title
 *   The title
 * @return string
 *   The original title, trimmed, or "(untitled)"
 */
function mm_ui_fix_node_title($title) {
  $title = trim($title);
  if (empty($title)) return t('(untitled)');
  return $title;
}

/**
 * Hide a node's title when it is surrounded by [brackets]
 *
 * @param string $title
 *   The title
 * @return string
 *   The (possibly hidden) title
 */
function mm_ui_hide_node_title($title) {
  return preg_replace('/^\[.*?\]$/', '', $title);
}

/**
 * Get a list of times of day
 *
 * @param int $start
 *   If set, the starting number of minutes past midnight
 * @param int $end
 *   If set, the ending number of minutes past midnight
 * @param int $inc
 *   If set, the increment, in minutes, between times; the smallest supported
 *   number is 30 minutes
 * @return array
 *   An array of times, indexed by the number of minutes past midnight
 */
function mm_ui_hour_list($start = NULL, $end = NULL, $inc = NULL) {
  $out = array(0 => t('midnight'), 30 => t('12:30 AM'), 60 => t('1:00 AM'), 90 => t('1:30 AM'), 120 => t('2:00 AM'), 150 => t('2:30 AM'), 180 => t('3:00 AM'), 210 => t('3:30 AM'), 240 => t('4:00 AM'), 270 => t('4:30 AM'), 300 => t('5:00 AM'), 330 => t('5:30 AM'), 360 => t('6:00 AM'), 390 => t('6:30 AM'), 420 => t('7:00 AM'), 450 => t('7:30 AM'), 480 => t('8:00 AM'), 510 => t('8:30 AM'), 540 => t('9:00 AM'), 570 => t('9:30 AM'), 600 => t('10:00 AM'), 630 => t('10:30 AM'), 660 => t('11:00 AM'), 690 => t('11:30 AM'), 720 => t('noon'), 750 => t('12:30 PM'), 780 => t('1:00 PM'), 810 => t('1:30 PM'), 840 => t('2:00 PM'), 870 => t('2:30 PM'), 900 => t('3:00 PM'), 930 => t('3:30 PM'), 960 => t('4:00 PM'), 990 => t('4:30 PM'), 1020 => t('5:00 PM'), 1050 => t('5:30 PM'), 1080 => t('6:00 PM'), 1110 => t('6:30 PM'), 1140 => t('7:00 PM'), 1170 => t('7:30 PM'), 1200 => t('8:00 PM'), 1230 => t('8:30 PM'), 1260 => t('9:00 PM'), 1290 => t('9:30 PM'), 1320 => t('10:00 PM'), 1350 => t('10:30 PM'), 1380 => t('11:00 PM'), 1410 => '11:30 PM');

  foreach (array_keys($out) as $key) {
    if ($inc && ($key % $inc) != 0)
      unset($out[$key]);

    if (!is_null($start) && !is_null($end) && ($key < $start || $key >= $end))
      unset($out[$key]);
  }
  return $out;
}

/**
 * Get a list of long week day names
 *
 * @return array
 *   An array of weekday names, starting with Sunday at element 0
 */
function mm_ui_day_list() {
  if (function_exists('date_week_days')) return date_week_days(TRUE);
  return array(t('Sunday'), t('Monday'), t('Tuesday'), t('Wednesday'), t('Thursday'), t('Friday'), t('Saturday'));
}

// ****************************************************************************
// * Private functions start here
// ****************************************************************************

function _mm_ui_levels() {
  $list[-1] = t('(show all levels)');
  $list[0] = t('(show only the currently selected level)');
  for ($i = 1; $i <= 10; $i++)
    $list[$i] = $i;

  return $list;
}

/**
 * Validation common to copying/moving and editing/inserting a tree entry
 *
 * @param int $mmtid
 *   The tree ID of the entry being copied/movied/edited
 * @param int $test_mmtid
 *   The tree ID of the entry's parent (when editing) or new parent (when
 *   copying/moving/inserting)
 * @param FormStateInterface $form_state
 *   The form state
 * @param array $form_values
 *   The $form_state['values'] array
 * @param bool $is_new
 *   Set to TRUE if the operation is creating a new entry (copy/move/insert)
 * @return int
 *   0 upon critical error, otherwise 1
 */
function _mm_ui_validate_entry($mmtid, $test_mmtid, FormStateInterface $form_state, $form_values, $is_new) {
  $x = mm_ui_strings($is_group = mm_content_is_group($test_mmtid));

  $test_tree = mm_content_get_tree($test_mmtid, array(Constants::MM_GET_TREE_DEPTH => 1, Constants::MM_GET_TREE_FILTER_HIDDEN => TRUE));

  if (!$test_mmtid || count($test_tree) < ($is_new ? 1 : 2) || !$is_new && $test_tree[1]->parent != $test_tree[0]->mmtid) {
    $form_state->setErrorByName('', t('Unexpected tree structure'));
    return 0;
  }

  $name = trim($form_values['name']);
  if (!\Drupal::currentUser()->hasPermission('administer all menus') && $name != '' && $name[0] == '.') {
    $form_state->setErrorByName('name',
        t('@thing names starting with a dot (.) are reserved for administrators.', $x));
  }

  $alias = empty($form_values['alias']) ? '' : trim($form_values['alias']);
  if (preg_match('/^[-\.\w]*$/', $alias) == 0) {
    $form_state->setErrorByName('alias',
        t('You have entered one or more invalid characters in the URL name.'));
  }
  else {
    $reserved = preg_grep('{^' . preg_quote($alias) . '$}i', mm_content_reserved_aliases());
    if ($reserved) {
      $form_state->setErrorByName('alias',
          t('The URL name %alias is not allowed. Please try changing it slightly.',
            array('%alias' => $alias)));
    }
    else if (mm_content_alias_conflicts($alias, $test_mmtid, FALSE)) {
      $form_state->setErrorByName('alias',
        t('The URL name %alias is not allowed at this location in the tree. Please try changing it slightly.',
          array('%alias' => $alias)));
    }
  }

  array_shift($test_tree);
  foreach ($test_tree as $entry) {
    if ($is_new || $entry->mmtid != $mmtid) {
      if (strcasecmp($entry->name, $name) == 0) {
        $x['%name'] = $entry->name;
        $form_state->setErrorByName('name',
            t('A @thing named %name already exists at this level of the tree.', $x));
      }

      if ($alias != '' && strcasecmp($entry->alias, $alias) == 0) {
        $x['%name'] = $entry->name;
        $x['%alias'] = $entry->alias;
        $form_state->setErrorByName('alias',
            t('The @thing named %name is already using the URL name %alias at this level of the tree.', $x));
      }
    }
  }

  return 1;
}

function _mm_ui_verify_userlist(FormStateInterface $form_state, $form_elem, $elt_name) {
  if (is_array($form_elem)) {
    foreach ($form_elem as $uid => $name)
      if (mm_content_uid2name($uid) === FALSE)
        $form_state->setErrorByName($elt_name, t('There is no user with uid=@uid and name=@name.', array('@uid' => $uid, '@name' => $name)));
  }
  elseif (is_numeric($form_elem)) {
    $uid = intval($form_elem);
    if (mm_content_uid2name($uid) === FALSE)
      $form_state->setErrorByName($elt_name, t('There is no user with uid=@uid.', array('@uid' => $uid)));
  }
}

/**
 * Return various versions of strings to be used in UI messages
 *
 * @param $is_group
 *   If TRUE, return strings related to groups
 * @return
 *   An associative array of strings, suitable to be passed to t()
 */
function mm_ui_strings($is_group) {
  $x['@thing'] =     $is_group ? t('group')      : t('page');
  $x['@things'] =    $is_group ? t('groups')     : t('pages');
  $x['@thingpos'] =  $is_group ? t('group\'s')   : t('page\'s');
  $x['@subthing'] =  $is_group ? t('sub-group')  : t('sub-page');
  $x['@subthings'] = $is_group ? t('sub-groups') : t('sub-pages');
  return $x;
}

function _mm_ui_userlist_setup($users, &$form, $form_id, $title, $single, $desc, $other_name = '', $large_group = FALSE) {
  if (!\Drupal::currentUser()->hasPermission('access user profiles')) {
    $form['no-add'] = _mm_ui_no_add_user();
  }
  elseif (!is_null($users)) {
    $form["$form_id-choose"] = array(
      '#type' => 'textfield',
      '#title' => $single ? t('Choose the owner') : t('Add a user'),
      '#autocomplete_route_name' => 'monster_menus.autocomplete',
      '#description' => mm_autocomplete_desc(),
      '#size' => 30,
      '#maxlength' => 40,
    );

    $form[$form_id] = array(
      '#type' => 'mm_userlist',
      '#description' => $desc,
      '#title' => $title,
      '#default_value' => $users,
      '#required' => $single,
      '#mm_list_autocomplete_name' => "$form_id-choose",
      '#mm_list_min' => $single,
      '#mm_list_max' => $single,
      '#mm_list_other_name' => $other_name,
      '#mm_list_initial_focus' => "$form_id-choose",
    );
    if ($single) {
      $form[$form_id]['#mm_list_submit_on_add'] = TRUE;
    }
  }
  else {
    if ($large_group) {
      $form["$form_id-add"] = array(
        mm_ui_add_user_subform($form, 'mmUserAddUsers', t('Add members to this group'), t('Members to add:'), t('Add members to group'), 'Drupal.mmGroupAddUser'),
      );
      $headers = _mm_ui_userlist_get_headers();
      $output = '<table cellpadding="0" cellspacing="0" border="0" class="tablesorter" id="mm-user-datatable-' . str_replace('_', '-', $form_id) . '" width="100%">';
      $output .= '<thead><tr>';
      foreach ($headers as $value) {
        $class = empty($value) ? ' class="no-sort"' : '';
        $output .= '<th' . $class . '>' . $value . '</th>';
      }
      $output .= '</tr></thead>';
      $output .= '<tbody><tr><td colspan="' . count($headers) . '" class="dataTables_empty">' . t('Loading data from server') . '</td></tr></tbody>';
      $output .= '</table>';
      $form['#attached']['library'][] = 'monster_menus/dataTables';
      $form[$form_id . '-additional'] = array(
        '#markup' => $output,
      );
      $form[$form_id . '-use-large-group'] = array(
        '#type' => 'hidden',
        '#value' => 'yes',
        '#process' => array('mm_ui_process_large_group'),
      );
    }
    else {
      $form[$form_id . '-additional'] = array('#markup' => t('There are more members in this group than can be displayed. The list can only be edited by uploading a CSV file.'));
    }
  }
}

function mm_ui_add_user_subform(&$form, $id, $link_text, $title, $button_text, $click_func_name, $owner_uid = NULL, $owner_name = NULL) {
  static $instance = 0;
  $link_id = mm_ui_modal_dialog(array('minWidth' => 550, 'minHeight' => 400), $form);
  // To avoid ending up with nested forms, the subform must be added outside of
  // the main form.
  /** @noinspection PhpMethodParametersCountMismatchInspection */
  $array = [
    ['#markup' => "<div id=\"$id-$instance\" class=\"hidden\"><div class=\"mm-add-users\">"],
    'subform' => \Drupal::formBuilder()->getForm('\Drupal\monster_menus\Form\AddGroupUsersForm', $instance, $link_id, $title, $button_text, $click_func_name, $owner_uid, $owner_name),
    ['#markup' => '</div></div>'],
  ];
  mm_add_page_footer([
    '#type' => 'html_tag',
    '#tag' => 'script',
    '#value' => \Drupal::service('renderer')->render($array),
    '#attributes' => array('class' => 'mm-template', 'type' => 'text/template'),
  ]);

  $result = Link::fromTextAndUrl($link_text, Url::fromRoute('<current>', [], [
    'fragment' => $id . '-' . $instance++,
    'external' => TRUE,
    'attributes' => ['id' => $link_id, 'title' => t('Add a user')],
  ]))->toRenderable();
  $result['#id'] = $link_id;
  return $result;
}

function mm_ui_process_large_group($element, &$form_state = NULL, $form = NULL) {
  if (!\Drupal::request()->request->all()) { // This will write group data even if the group fails validation, but none of the information passed seems to offer an alternative
    // Copy group data to the temp table
    $token = $form['mm_form_token']['#value'];
    $session_id = session_id();
    $gid = $form['path']['#value'];

    $db = Database::getConnection();
    $db->delete('mm_group_temp')
      ->condition('gid', $gid)
      ->condition('sessionid', $session_id)
      ->condition('token', $token)
      ->execute();

    if (empty($form['is_new']['#value'])) {
      $select = $db->select('mm_group', 'g');
      $select->addField('g', 'gid');
      $select->addField('g', 'uid');
      $select->addExpression(':session_id', 'sessionid', array(':session_id' => $session_id));
      $select->addExpression(':token', 'token', array(':token' => $token));
      $select->addExpression(':expire', 'expire', array(':expire' => mm_request_time() + 24 * 60 * 60));
      $select->condition('g.gid', $gid);
      $db->insert('mm_group_temp')
        ->from($select)
        ->execute();
    }
  }
  return $element;
}

/**
 * Get the headers for the datatable used in large group management
 */
function _mm_ui_userlist_get_headers() {
  $headers = mm_module_invoke_all_array('mm_large_group_header', array());
  if (empty($headers)) {
    return array(t('Username'), '');
  }
  return $headers;
}

function _mm_ui_is_user_home(&$item) {
  if ($item->parent == mm_content_users_mmtid()) {
    $item->is_user_home = TRUE;
    $item->flags['limit_alias'] = '';
    $item->flags['limit_delete'] = '';
    $item->flags['limit_hidden'] = '';
    $item->flags['limit_location'] = '';
    $item->flags['limit_move'] = '';
    $item->flags['limit_name'] = '';
    $item->flags['limit_write'] = '';
  }
}

function mm_ui_owner_desc($form, $x, $item_uid, $is_search = FALSE) {
  if ($is_search || \Drupal::currentUser()->hasPermission('administer all menus')) {
    $msg = t('The owner always has full access.', $x);
    if (isset($form['#id']) && $form['#id'] == 'node-form') $msg .= ' ' . t('The owner is also publicly visible as the submitter.');
    return array(mm_ui_uid2name($item_uid), $msg, FALSE);
  }

  $msg = '';
  if (\Drupal::currentUser()->id() != $item_uid) {
    $owner = User::load($item_uid);
    if ($owner) {
      $x['@name'] = mm_ui_uid2name($owner->id());
      $msg = t('This @thing is owned by @name, who always has full access to it.', $x);
    }
  }
  else {
    $msg = t('As the owner of this @thing, you always have full access to it.', $x);
  }

  return array(mm_ui_uid2name($item_uid), $msg, TRUE);
}

function mm_ui_uid2name($uid, $link = FALSE) {
  if (is_null($uid)) {
    return t('No user');
  }
  if (($owner = mm_content_uid2name($uid, 'fmlu')) !== FALSE) {
    if ($link) {
      return Link::createFromRoute($owner, 'entity.user.canonical', ['user' => $uid])->toString();
    }
    return $owner;
  }
  return t('Unknown user #@uid', array('@uid' => $uid));
}

function mm_ui_mm_nodelist_setup(&$mmlist, $nid, $mmtid = NULL) {
  if (empty($nid)) {
    $mmlist['0/0'] = '';
    return;
  }

  $mmtids = mm_content_get_by_nid($nid);
  if (!empty($mmtid) && in_array($mmtid, $mmtids)) {
    $tree = mm_content_get($mmtid);
  }
  else {
    $tree = $mmtids ? mm_content_get($mmtids[0]) : FALSE;
  }

  if ($tree) {
    $mmlist[$tree->mmtid . '/' . $nid] = Node::load($nid)->label();
  }
  else {
    $mmlist['0/' . $nid] = '';
  }
}

function mm_ui_flags_info() {
  $predefined = array();
  // We need the module name, so don't use module_invoke_all()
  foreach (mm_module_implements('mm_tree_flags') as $module) {
    $result = call_user_func($module . '_mm_tree_flags');
    if (isset($result) && is_array($result)) {
      $predefined[$module] = $result;
    }
  }
  ksort($predefined);
  return $predefined;
}

/**
 * Produce code to instantiate a modal dialog.
 *
 * @param array $settings
 *   If NULL, the most recently used dialog instance ID is returned.
 *
 *   If the string 'init' is passed, the dialog system is initialized for future
 *   use by other code without actually defining a dialog.
 *
 *   Otherwise, a new dialog is instantiated using any settings supplied. The
 *   settings are an associative array of values corresponding to the Options
 *   list found in the jquery.ui.dialog documentation:
 *   @see http://api.jqueryui.com/dialog
 *
 *   These additional settings are supported:
 *   - fullSize (FALSE)
 *     If TRUE, the dialog will take up most of the browser window.
 *   - iframe (FALSE)
 *     If TRUE, the dialog will be opened within an IFRAME tag.
 *
 *   This function is typically used when creating a link which opens a modal
 *   dialog. If the referring <a> tag contains an href option with a full URL,
 *   that location is shown within the dialog. If, instead, the href is only a
 *   fragment ("#something"), then that fragment is treated as a selector for
 *   the pre-existing DOM object to show within the dialog.
 *
 *   To create a link which opens a modal dialog that loads its content from a
 *   URL use the return value from this function for the ID of the URL or link
 *   tag, like so:
 *
 *      $array = [];
 *      $link = Link::fromTextAndUrl(
 *        t('Open a dialog'),
 *        Url::fromRoute('some/path', [], [
 *          'attributes' => [
 *            'id' => mm_ui_modal_dialog([], $array),
 *          ],
 *          'external' => TRUE,
 *        ])
 *      );
 *      // The render array $array must now be incorporated into the output.
 *
 *   Or, when using the Form API:
 *
 *     $array = [];
 *     $array['link'] = [
 *       '#type' => 'link',
 *       '#title' => t('Open a dialog'),
 *       '#url' => Url::fromRoute('some/path', [], [
 *         'attributes' => [
 *           'id' => mm_ui_modal_dialog([], $array),
 *         ],
 *         'external' => TRUE,
 *       ]),
 *     ];
 *
 *   Do the same thing using a full-window iframe:
 *
 *     $array = [];
 *     $array['link'] = [
 *       '#type' => 'link',
 *       '#title' => t('Open a dialog'),
 *       '#url' => Url::fromRoute('some/path', [], [
 *         'attributes' => [
 *           'id' => mm_ui_modal_dialog([
 *             'iframe' => TRUE,
 *             'fullSize' => TRUE
 *           ], $array),
 *         ],
 *         'external' => TRUE,
 *       ]),
 *     ];
 *
 *   To create a link which opens a modal dialog that refers to a pre-existing,
 *   hidden DIV with a particular ID:
 *
 *     $array[0] = ['#markup' =>
 *         '<div id="my-dialog" class="hidden">This is a test.</div>'];
 *     $array['link'] = [
 *       '#type' => 'link',
 *       '#title' => t('Open a dialog'),
 *       '#url' => Url::fromRoute('<current>', [], [
 *         'attributes' => [
 *           'id' => mm_ui_modal_dialog([], $array),
 *         ],
 *         'external' => TRUE,
 *         'fragment' => 'my-dialog',
 *       ]),
 *     ];
 * @param array &$array
 *   The render array to which libraries and settings will be attached.
 * @return string|null
 *   Either the most recently used dialog instance ID (when $settings is NULL),
 *   or the ID of the newly-created instance.
 */
function mm_ui_modal_dialog($settings = array(), &$array = array()) {
  static $instance = 0, $did_init;

  if (is_null($settings)) {
    return 'mm-dialog-' . ($instance - 1);
  }
  mm_add_library($array, 'modal_dialog');
  if ($settings === 'init') {
    if (empty($did_init)) {
      $array['#attached']['drupalSettings']['MM']['MMDialog'] = [];
      $did_init = TRUE;
    }
  }
  else {
    $id = "mm-dialog-$instance";
    $array['#attached']['drupalSettings']['MM']['MMDialog'][$instance++] = $settings;
    return $id;
  }
}

function mm_ui_js_link_no_href(array $attributes, $text, $raw_text = FALSE) {
  $text_format = $raw_text ? 'text | raw' : 'text';
  return [
    '#type' => 'inline_template',
    '#template' => '<a href="#"{{ attributes }}>{{ ' . $text_format. ' }}</a>',
    '#context' => [
      'attributes' => new Attribute($attributes),
      'text' => $text,
    ]
  ];
}

function mm_ui_tabledrag_sample() {
  $sample = [
    '#type' => 'inline_template',
    '#template' => '<a name="sample"{{ attributes }}><div class="handle">&nbsp;</div></a>',
    '#context' => [
      'attributes' => new Attribute([
        'class' => ['tabledrag-handle tabledrag-handle-sample'],
        'title' => t('Sample of drag handle'),
      ]),
    ]
  ];
  return FilteredMarkup::create(\Drupal::service('renderer')->renderRoot($sample));
}

/**
 * Generate node permissions form elements.
 *
 * @param &$form
 *   The form element
 */
function mm_ui_node_permissions(&$form) {
  $form['indiv_tbl']['#type'] = 'value';
  $form['indiv_tbl']['#value'] = array(
    'title' => t('Individuals'),
    'action' => mm_ui_add_user_subform($form, 'settings-perms-indiv-add', t('add'), t('User(s) to add to permissions:'), t('Add users to permissions'), 'Drupal.MMSettingsPermsAddUsers'),
  );

  foreach (array('indiv_tbl', 'groups_tbl') as $type) {
    $kids = array();
    foreach ($form[$type] as $key => $item) {
      if (is_array($item) && isset($item['#mm_delete_link'])) {
        if (!isset($users)) {
          $users = $item['#mm_users'];
        }

        if ($type == 'indiv_tbl') {
          if (!isset($item_uid)) {
            $item_uid = $item['#mm_owner']['uid'];
          }

          if (isset($form['everyone']['owner'])) {
            $form['everyone']['owner']['#value'] = $item_uid;
          }

          if (!isset($item['#mm_owner']['show']) || !empty($item['#mm_owner']['show'])) {
            list($name, $msg, $owner_readonly) = mm_ui_owner_desc($form, array('@thing' => $item['#mm_owner']['type']), $item_uid);
            $kids[] = _mm_ui_perms_table_row(
              'user',
              'owner',
              t('<span class="settings-perms-owner-prefix">Owner: </span><span class="settings-perms-owner-name">@name</span>', array('@name' => $name)),
              $msg,
              $owner_readonly ? NULL : mm_ui_add_user_subform($form, 'settings-perms-indiv-owner', t('change'), t('Owner:'), t('Change the owner'), 'Drupal.MMSettingsPermsOwner', $item_uid, $name)
            );

            if (!$owner_readonly) {
              $kids[] = array(
                '#type' => 'hidden',
                '#name' => 'uid',
                '#value' => $item_uid,
                '#id' => "uid-$item_uid",
              );
            }
          }

          if (is_array($users)) {
            foreach ($users as $uid => $name) {
              $obj = array(
                "user-w-$uid" => array(
                  '#id' => "user-w-$uid",
                  '#name' => "user-w-$uid",
                  '#type' => 'hidden',
                  '#value' => $uid,
                ),
                array(
                  '#type' => 'item',
                  '#input' => FALSE,
                  '#markup' => $name,
                ),
              );
              $kids[] = _mm_ui_perms_table_row('user', $uid, $obj, '', !empty($form['#readonly']) ? NULL : $item['#mm_delete_link']);
            }
          }

          if (empty($form['#readonly'])) {
            // Empty row to be used when adding new users
            $obj = array(
              'user-w-new' => array(
                '#attributes' => array('class' => array('user-w-new')),
                '#name' => 'user-w-new',
                '#type' => 'hidden',
                '#value' => 0,
              ),
              array(
                '#type' => 'item',
                '#input' => FALSE,
                '#markup' => '<div class="mm-permissions-user-new form-item"> </div>',
              ),
            );
            $kids[] = _mm_ui_perms_table_row('user', 'new', $obj, '', $item['#mm_delete_link']);
          }
        }
        else {
          if (!isset($groups)) {
            $groups = $item['#mm_groups'];
          }

          if (is_array($groups)) {
            foreach ($groups as $mmtid => $data) {
              $temp_elem = $item['mm_groups_elem'];
              $temp_elem[0]['#title'] = $data['name'];
              $temp_elem[0][0]['#markup'] = '<div class="form-item">' . $data['members'] . '</div>';
              $temp_elem[0]["group-w-$mmtid"] = array(
                '#id' => "group-w-$mmtid",
                '#name' => "group-w-$mmtid",
                '#type' => 'hidden',
                '#value' => $mmtid,
              );
              $kids[] = _mm_ui_perms_table_row('group', $mmtid, $temp_elem, '', !empty($form['#readonly']) ? NULL : $item['#mm_delete_link']);
            }
          }

          if (empty($form['#readonly'])) {
            // Empty row to be used when adding new groups
            $temp_elem = $item['mm_groups_elem'];
            $temp_elem[0]['#title'] = ' ';
            $temp_elem[0][0]['#attributes'] = array('class' => array('mm-permissions-group-new'));
            $temp_elem[0][0]['#markup'] = '<div class="mm-permissions-group-new form-item"> </div>';
            $temp_elem[0]['group-w-new'] = array(
              '#id' => 'group-w-new',
              '#name' => 'group-w-new',
              '#type' => 'hidden',
              '#value' => 0,
            );
            unset($form['groups_tbl'][$key]['mm_groups_elem']);
            $kids[] = _mm_ui_perms_table_row('group', 'new', $temp_elem, '', !empty($form['#readonly']) ? NULL : $item['#mm_delete_link']);
          }
        }
      }
      elseif (is_numeric($key)) {
        $kids[] = $item;
      }
      else {
        $kids[$key] = $item;
      }
    }
    $form[$type] = $kids;
  }

  mm_ui_permissions($form);
}

function mm_ui_permissions(&$form) {
  $rows = array();
  foreach (Element::children($form) as $section_id) {
    $form["#$section_id"] = count($rows);
    $colspan = 1;
    if (isset($form[$section_id]['#value']['types']) && is_array($form[$section_id]['#value']['types'])) {
      $colspan = count($form[$section_id]['#value']['types']) + 1;
    }

    // Begin a header row with the title.
    $row = [
      '#attributes' => ['class' => ['mm-permissions-header-row']],
    ];
    if (isset($form[$section_id]['#value']['title'])) {
      $row[] = [
        '#wrapper_attributes' => ['header' => TRUE, 'class' => ['first-col']],
        '#markup' => new FormattableMarkup('<h2>@text</h2>', ['@text' => $form[$section_id]['#value']['title']]),
      ];
    }

    if (isset($form[$section_id]['#value']['types']) && is_array($form[$section_id]['#value']['types']) && !empty($form[$section_id]['#value']['headings'])) {
      // Add headers above the checkboxes.
      foreach ($form[$section_id]['#value']['types'] as $vals) {
        $row[] = [
          '#wrapper_attributes' => ['header' => TRUE],
          '#markup' => new FormattableMarkup('<h4>@text</h4>', ['@text' => $vals[0]]),
        ];
      }
      // Empty header above the actions
      $row[] = [
        '#wrapper_attributes' => ['header' => TRUE],
        '#markup' => '',
      ];
    }
    else {
      // Add empty headers.
      $row = array_merge($row, array_fill(0, $colspan, [
        '#wrapper_attributes' => ['header' => TRUE],
        '#markup' => '',
      ]));
    }
    $rows[] = $row;

    // Add the data rows.
    foreach (Element::children($form[$section_id]) as $row_id) {
      if ($children = Element::children($form[$section_id][$row_id])) {
        $row = !empty($form[$section_id][$row_id]['#mm_is_data_row']) ? ['#attributes' => ['class' => ['mm-permissions-data-row']]] : [];
        // One checkbox per column
        foreach ($children as $item_id) {
          $item = $form[$section_id][$row_id][$item_id];
          if (!isset($item['#id']) && !is_numeric($item_id)) {
            $item['#id'] = $item_id;
          }
          $row[$item_id] = $item;
        }
        // Add empty cells, as needed.
        $row = array_merge($row, array_fill(0, $colspan - count($children) + 1, ['#markup' => '']));
        $rows[] = $row;
      }
    }

    if (empty($form['#readonly']) && isset($form[$section_id]['#value']['action'])) {
      // A link by itself on the line, most likely "add"
      $rows[] = array(
        ['#wrapper_attributes' => ['colspan' => $colspan], '#markup' => ''],
        [$form[$section_id]['#value']['action']],
      );
    }

    unset($form[$section_id]);
  }

  mm_static($form, 'settings_perms');
  $form['#type'] = 'table';
  $form['#tree'] = FALSE;
  $form += $rows;
  $form['#attributes'] = ['class' => ['mm-permissions']];
}

/**
 * Generate the node reorder table.
 *
 * @param &$form
 *   The form element
 */
function mm_ui_node_reorder(&$form) {
  $regions = isset($form['regions']['#value']) ? $form['regions']['#value'] : array();
  $header = array(t('Title'), t('Type'), t('Weight'), t('Region'));

  $make_table = function($table_id, $caption, $rows, $region_targetable) use ($header, $regions) {
    $table = [
      '#header' => $header,
      '#caption' => $caption,
      '#type' => 'table',
      '#attributes' => ['id' => $table_id],
      '#tabledrag' => [],
    ];
    foreach ($regions as $region => $desc) {
      if (!empty($rows[$region]) || !empty($region_targetable[$region])) {
        $table['#tabledrag'][] = [
          'action' => 'match',
          'relationship' => 'sibling',
          'group' => 'nodes-region-select',
          'subgroup' => 'nodes-region-' . $region,
        ];
        $table['#tabledrag'][] = [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'nodes-weight',
          'subgroup' => 'nodes-weight-' . $region,
        ];
        $table[] = [
          '#attributes' => ['class' => ['region-title', "region-title-$region"], 'no_striping' => TRUE],
          ['#wrapper_attributes' => ['colspan' => 5], '#markup' => Html::escape($desc)],
        ];
        $table[] = [
          '#attributes' => ['class' => ['region-message', "region-$region-message", !empty($rows[$region]) ? 'region-populated' : 'region-empty'], 'no_striping' => TRUE],
          ['#wrapper_attributes' => ['colspan' => 5], '#markup' => t('<em>There is no content in this region.</em>')],
        ];
        if (!empty($rows[$region])) {
          $table = array_merge($table, $rows[$region]);
        }
      }
    }
    return $table;
  };

  $rendered_rows = $region_targetable = [[], []];
  if (isset($form['nodes'])) {
    foreach (Element::children($form['nodes']) as $id) {
      $element = &$form['nodes'][$id];

      // Add special classes to be used for tabledrag.js.
      $element['nid']['#attributes']['class'][] = 'menu-nid';
      $region = $element['region']['#default_value'];
      $element['region']['#attributes']['class'] = array('nodes-region-select', 'nodes-region-' . $region);
      $element['weight']['#attributes']['class'] = array('nodes-weight', 'nodes-weight-' . $region);
      $element['weight']['#attributes']['class'][] = isset($element['stuck']) ? 'sticky-weight' : 'unsticky-weight';

      $row = array_intersect_key($element, ['title' => 1, 'type' => 1, 'region' => 1, 'weight' => 1, 'nid' => 1, '#attributes' => 1]);
      $row['#attributes']['class'][] = 'draggable';
      $sticky = isset($element['stuck']) ? 1 : 0;
      $rendered_rows[$sticky][$region][$id] = $row;
      foreach (array_keys($element['region']['#options']) as $region) {
        $region_targetable[$sticky][$region] = TRUE;
      }
    }
  }

  $output = [];
  if ($rendered_rows[1]) {
    $caption = $rendered_rows[0] ? t('<h2>Sticky content</h2>') : '';
    $output[] = $make_table('mm-reorder1', $caption, $rendered_rows[1], $region_targetable[1]);
  }
  if ($rendered_rows[0]) {
    $caption = $rendered_rows[1] ? t('<h2>Non-sticky content</h2>') : '';
    $output[] = $make_table('mm-reorder2', $caption, $rendered_rows[0], $region_targetable[0]);
  }
  $form['nodes'] = $output;
}

function mm_ui_settings_perms_add_group_link(&$form) {
  $_mmlist_instance = &drupal_static('_mmlist_instance', 0);
  $elts = \Drupal::service('element_info')->getInfo('mm_grouplist');
  $popup_URL = mm_content_groups_mmtid();
  return [
    '#type' => 'inline_template',
    '#template' => '<a href="{{ url }}" id="{{ id }}" title="{{ title }}" onclick="return Drupal.MMSettingsPermsAddGroup(this)">{{ text }}</a>',
    '#context' => [
      'url' => Url::fromRoute($elts['#mm_list_route'], [], ['query' => ['_path' => "$popup_URL-" . Groups::BROWSER_MODE_GROUP . "-$_mmlist_instance-" . $elts['#mm_list_enabled'] . '-' . $elts['#mm_list_selectable'] . '/' . $popup_URL]]),
      'id' => mm_ui_modal_dialog(array('iframe' => TRUE, 'fullSize' => TRUE, 'resizable' => FALSE, 'draggable' => FALSE, 'dialogClass' => 'mm-browser-dialog'), $form),
      'title' => t('Add a group to the permissions'),
      'text' => t('add'),
    ]
  ];
}

function _mm_ui_node_form_perms(&$form, $userlist, $grouplist, $everyone, $item_type, $item_uid, $new_msg = NULL, $rebuild = FALSE) {
  // don't allow non-admins to permit everyone for write
  $allow_ev = \Drupal::currentUser()->hasPermission('administer all menus');

  _mm_ui_form_array_merge($form, 'settings_perms', array(
    '#type' => 'details',
    '#title' => t('Who can edit or delete this content'),
    '#group' => 'advanced',
    '#weight' => 121,
  ));
  $form['settings_perms']['table']['#type'] = 'table';
  $form['settings_perms']['table']['#weight'] = 21;
  $form['settings_perms']['table']['#perms'] = [
    'allow_everyone' => $allow_ev,
    'everyone' => $everyone,
    'users' => $userlist,
    'groups' => $grouplist,
  ];

  if ($allow_ev) {
    $form['settings_perms']['table']['everyone'] = array(
      '#type' => 'value',
      '#value' => array(
        'title' => t('Everyone'),
        'headings' => TRUE,
      ),
    );
    $checkbox = array(
      'node-everyone' => array(
        '#type' => 'checkbox',
        '#title' => t('Everyone can edit or delete this content'),
        '#default_value' => $everyone,
      ),
      'owner' => array(
        '#type' => 'hidden',
        '#default_value' => $item_uid,
      ),
    );
    $form['settings_perms']['table']['everyone'][] = _mm_ui_perms_table_row('', 'others_w', $checkbox, '');
  }

  $form['settings_perms']['table']['indiv_tbl'] = array();
  if (empty($is_search)) {
    $form['settings_perms']['table']['indiv_tbl'][] = array(
      '#type' => 'markup',
      '#mm_delete_link' => mm_ui_js_link_no_href(['title' => t('Remove this user'), 'onclick' => 'return Drupal.MMSettingsPermsDelete(this)'], t('delete')),
      '#mm_users' => $userlist,
      '#mm_owner' => array(
        'type' => $item_type,
        'uid' => $item_uid,
      ),
    );

    $form['settings_perms']['all_values_user'] = array(
      '#type' => 'hidden',
      '#attributes' => array('class' => 'mm-permissions-all-values-user'),
      '#default_value' => $userlist ? Constants::MM_PERMS_WRITE . implode(Constants::MM_PERMS_WRITE, array_keys($userlist)) : '',   // default value, in case JS is disabled
    );
  }

  $form['settings_perms']['table']['groups_tbl'] = array(
    '#type' => 'value',
    '#value' => array(
      'title' => t('Groups'),
      'action' => mm_ui_settings_perms_add_group_link($form),
    )
  );
  if (empty($is_search)) {
    $elem = array(
      array(
        '#type' => 'details',
        '#open' => FALSE,
      )
    );
    $form['settings_perms']['table']['groups_tbl'][] = array(
      '#type' => 'markup',
      '#mm_delete_link' => mm_ui_js_link_no_href(['title' => t('Remove this group'), 'onclick' => 'return Drupal.MMSettingsPermsDelete(this)'], t('delete')),
      '#mm_groups' => $grouplist,
      'mm_groups_elem' => $elem,
    );

    $form['settings_perms']['all_values_group'] = array(
      '#type' => 'hidden',
      '#attributes' => array('class' => 'mm-permissions-all-values-group'),
      '#default_value' => $grouplist ? Constants::MM_PERMS_WRITE . implode(Constants::MM_PERMS_WRITE, array_keys($grouplist)) : '',   // default value, in case JS is disabled
    );
  }

  mm_ui_node_permissions($form['settings_perms']['table']);
}

function _mm_ui_perms_table_row($elem_name, $elem_id, $name, $msg = '', $action = NULL, $types = array(), $x = NULL, $checks = array()) {
  $out = array('#mm_is_data_row' => is_numeric($elem_id));
  $out[] = is_array($name) ? $name : array(
    '#type' => 'item',
    '#input' => FALSE,
    '#markup' => "<div class=\"mm-permissions-$elem_name-$elem_id\">$name</div>",
    '#description' => $msg,
    '#description_display' => 'after',
  );
  $chk = 0;
  foreach (array_keys($types) as $mode) {
    if (!is_bool($checks[$chk])) {
      $out[] = array();
    }
    else {
      $att = array('title' => t($types[$mode][1], $x));
      if ($checks[$chk + 1]) {
        $att['class'] = array('mm-permissions-disabled');
      }
      $out["$elem_name-$mode-$elem_id"] = array(
        '#type' => 'checkbox',
        '#default_value' => $checks[$chk],
        '#disabled' => $checks[$chk + 1],
        '#attributes' => $att,
        '#name' => "$elem_name-$mode-$elem_id",
      );
    }
    $chk += 2;
  }
  $out[] = is_string($action) ? array('#type' => 'item', '#input' => FALSE, '#markup' => $action) : $action;
  return $out;
}

function _mm_ui_delete_node_groups(NodeInterface $node, $everyone) {
  $db = Database::getConnection();
  $txn = $db->startTransaction();
  try {
    // ad-hoc and (maybe) "everyone"
    //
    // This is happening within a transaction, so it's OK (and faster) to use
    // separate queries.
    $gids = mm_retry_query('SELECT gid FROM {mm_node_write} WHERE nid = :nid AND gid '. ($everyone ? '<= 0' : '< 0'), array(':nid' => $node->id()))->fetchCol();
    if ($gids) {
      mm_retry_query($db->delete('mm_group')
        ->condition('gid', $gids, 'IN'));
      mm_retry_query($db->delete('mm_node_write')
        ->condition('gid', $gids, 'IN'));
    }

    // everything else
    $delete = $db->delete('mm_node_write')
      ->condition('nid', $node->id());
    if ($everyone)
      $delete->condition('gid', 0, '<>');
    mm_retry_query($delete);
  }
  catch (\Exception $e) {
    $txn->rollback();
    // Repeat the exception, but with the location below.
    throw new \Exception($e->getMessage());
  }
}

function _mm_ui_recycle_page_list($mmtids, &$names, &$msg, $can = FALSE) {
  $names = array();
  foreach (mm_content_get($mmtids) as $tree) {
    $link = Link::fromTextAndUrl(mm_content_get_name($tree), mm_content_get_mmtid_url($tree->mmtid))->toString();
    if ($can) {
      $perms = mm_content_user_can($tree->mmtid);
      if (!$perms[Constants::MM_PERMS_APPLY]) {
        $msg = t('You do not have permission to restore this content to the page it originally came from, @link.', array('@link' => $link));
        return FALSE;
      }
      if ($perms[Constants::MM_PERMS_IS_RECYCLED]) {
        $msg = t('This content cannot be restored because the page it originally came from, @link, is also in the recycle bin. You must restore that page first.', array('@link' => $link));
        return FALSE;
      }
    }
    $names[] = $link;
  }

  if (!$names) {
    $msg = t('This content is not associated with a page. It will be visible only by its direct web address.<p>Are you sure you want to restore it?</p>');
  }
  elseif (count($names) == 1) {
    $msg = t('Are you sure you want to restore this content to the page @link?', array('@link' => $names[0]));
  }
  else {
    $msg = t('Are you sure you want to restore this content to the following pages: @pages', ['@pages' => FilteredMarkup::create(implode(', ', $names))]);
  }

  return TRUE;
}

function _mm_ui_node_info_values(&$form) {
  mm_add_js_setting($form, 'mmNodeInfo', array(t('none'), t('name'), t('date'), t('name/date')));

  return array(
    0 => t('(none)'),
    1 => t('Submitted by [username]'),
    2 => t('Submitted on [date]'),
    3 => t('Submitted by [username] on [date]'),
  );
}

function _mm_ui_date_to_string($value) {
  if (isset($value)) {
    if (is_numeric($value)) {
      if ($value == 0) return '';
      return mm_format_date($value, 'custom', 'Y-m-d H:i:s');
    }
    return $value;
  }
  return '';
}

function _mm_ui_comment_write_setting_values() {
  return array(
    t('Disabled: No comments allowed'),
    t('Read-only: Existing comments can be read, no new ones can be added'),
    t('Read/Write: All logged-in users can add comments'));
}

function _mm_ui_comment_read_setting_values($blank) {
  $out = array('' => $blank);
  foreach (mm_get_setting('comments.readable_labels') as $label) {
    $out[$label['perm']] = $label['desc'];
  }
  return $out;
}

// Set form arrays in such a way that content added beforehand in other modules'
// hook_form_alter() is preserved
function _mm_ui_form_array_merge(&$form, $element, $value) {
  $form[$element] = isset($form[$element]) && is_array($form[$element]) ? array_merge($value, $form[$element]) : $value;
}

function _mm_ui_form_parse_perms(FormStateInterface $form_state, $form_vals, $validate, $instance_suffix = '') {
  $form_vals = $form_vals ?: (object) $form_state->getValues();
  $limit_write_not_admin = isset($form_vals->limit_write_not_admin);

  $groups = array();
  if (!empty($form_vals->{"all_values_group$instance_suffix"})) {
    preg_match_all('/(\w)(\d+)/', $form_vals->{"all_values_group$instance_suffix"}, $matches);
    $i = 0;
    foreach ($matches[1] as $short) {
      if (!$limit_write_not_admin || $short != Constants::MM_PERMS_WRITE) {   // can't use MM_PERMS_WRITE if limit_write is set
        if ($group = mm_content_get(intval($matches[2][$i]))) {
          $name = mm_content_get_name($group);
          if ($validate) {
            if (!mm_content_user_can($group->mmtid, Constants::MM_PERMS_APPLY))
              $form_state->setErrorByName('', t('You do not have permission to use the group %grp.', array('%grp' => $name)));
            $groups[$short][] = $group->mmtid;
          }
          else {
            $groups[$group->mmtid]['modes'][] = $short;
            $groups[$group->mmtid]['name'] = $name;
            if (!isset($groups[$group->mmtid]['members'])) {
              $groups[$group->mmtid]['members'] = mm_content_get_users_in_group($group->mmtid, '<br />', FALSE, 20, TRUE);
            }
          }
        }
      }
      $i++;
    }
  }

  $users = array();
  if (!empty($form_vals->{"all_values_user$instance_suffix"})) {
    preg_match_all('/(\w)(\d+)/', $form_vals->{"all_values_user$instance_suffix"}, $matches);
    $i = 0;
    foreach ($matches[1] as $short) {
      if (!$limit_write_not_admin || $short != Constants::MM_PERMS_WRITE) {   // can't use MM_PERMS_WRITE if limit_write is set
        if (User::load($uid = intval($matches[2][$i]))) {
          if ($validate) $users[$short][] = $uid;
          else {
            $users[$uid]['modes'][] = $short;
            $users[$uid]['name'] = mm_content_uid2name($uid);
          }
        }
      }
      $i++;
    }
  }

  if ($limit_write_not_admin) {
    // Replace data protected by limit_write flag
    $db = Database::getConnection();
    $select = $db->select('mm_tree', 't');
    $select->join('mm_tree_access', 'a', 'a.mmtid = t.mmtid');
    $select->join('mm_tree', 't2', 't2.mmtid = a.gid');
    $select->fields('a', array('gid'));
    $select->addExpression('t2.mmtid', 't2_mmtid');
    $select->condition('a.mmtid', $form_vals->path)
      ->condition('a.gid', 0, '>=')
      ->condition('a.mode', Constants::MM_PERMS_WRITE);
    $result = $select->execute();
    foreach ($result as $r) {
      $name = mm_content_get_name($r->t2_mmtid);
      if ($validate) {
        $groups[Constants::MM_PERMS_WRITE][] = $r->gid;
      }
      else {
        $groups[$r->gid]['modes'][] = Constants::MM_PERMS_WRITE;
        $groups[$r->gid]['name'] = $name;
        if (!isset($groups[$r->gid]['members'])) {
          $groups[$r->gid]['members'] = mm_content_get_users_in_group($r->gid, '<br />', FALSE, 20, TRUE);
        }
      }
    }

    $gids = array();
    $select = $db->select('mm_tree', 't');
    $select->join('mm_tree_access', 'a', 'a.mmtid = t.mmtid');
    $select->fields('a', array('gid'))
      ->condition('a.mmtid', $form_vals->path)
      ->condition('a.gid', 0, '<')
      ->condition('a.mode', Constants::MM_PERMS_WRITE);
    $result = $select->execute();
    foreach ($result as $r) {
      $gids[] = $r->gid;
    }

    if ($gids) {
      $users_in_groups = mm_content_get_users_in_group($gids, NULL, FALSE, 0);
      if (!is_null($users_in_groups)) {
        foreach ($users_in_groups as $uid => $usr) {
          if (is_numeric($uid) && $uid >= 0) {
            if ($validate) $users[Constants::MM_PERMS_WRITE][] = $uid;
            else {
              $users[$uid]['modes'][] = Constants::MM_PERMS_WRITE;
              $users[$uid]['name'] = $usr;
            }
          }
        }
      }
    }
  }

  $default_modes = array();
  foreach (array(Constants::MM_PERMS_WRITE, Constants::MM_PERMS_SUB, Constants::MM_PERMS_APPLY, Constants::MM_PERMS_READ) as $mode) {
    if (!empty($form_vals->{"group-$mode-everyone$instance_suffix"})) {
      $default_modes[] = $mode;
    }
  }

  return array($groups, $users, $default_modes);
}

function mm_ui_is_search() {
  // Some things are done differently when forms are being built for the
  // search/replace feature. Assume that if mm_search_form() exists at all,
  // it must be because the search form elements are being generated.
  return function_exists('mm_search_form');
}

function _mm_ui_add_summary_js(&$form, $id = '') {
  // Do not add code if the search interface is being generated.
  if (mm_ui_is_search()) return;

  if ($id) {
    mm_static($form, $id . '_summary');
  }
//  else {
//    mm_add_js_setting($form, 'summaryFuncs', []);
//  }
}

function _mm_ui_no_add_user() {
  return array(
    '#markup' => t('<p>You do not have permission to choose users.</p>'),
  );
}
