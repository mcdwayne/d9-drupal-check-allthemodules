<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\ReorderMenuForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Entity\MMTree;

class ReorderMenuForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_ui_menu_reorder';
  }

  public function buildForm(array $form, FormStateInterface $form_state, MMTree $mm_tree = NULL) {
    $params = [
      Constants::MM_GET_TREE_DEPTH => 1,
      Constants::MM_GET_TREE_FILTER_NORMAL => TRUE,
      Constants::MM_GET_TREE_FILTER_USERS => TRUE,
      Constants::MM_GET_TREE_RETURN_BLOCK => TRUE,
      Constants::MM_GET_TREE_RETURN_PERMS => TRUE,
    ];
    $tree = mm_content_get_tree($mm_tree->id(), $params);
    // Skip root
    array_shift($tree);
    $tree = array_filter($tree, function ($item) {
      return !$item->hidden && (empty($item->bid) || $item->bid == Constants::MM_MENU_BID || $item->bid == Constants::MM_MENU_UNSET) && $item->name != Constants::MM_ENTRY_NAME_RECYCLE;
    });

    $form['#tree'] = TRUE;
    $count = count($tree);
    if ($count > 1) {
      $form['actions'] = ['#type' => 'actions'];
      if ($count > Constants::MM_UI_MAX_REORDER_ITEMS) {
        $form['empty_menu'] = ['#markup' => $this->t('<p>There are too many @subthings of this @thing to make reordering feasible.</p>', mm_ui_strings(FALSE))];
      }
      else {
        $form['prefix'] = [
          '#markup' => $this->t('<div id="help">To reorder an item, grab the @sample and drag it to a new location. Be sure to <em>Save configuration</em> when done.</div>', ['@sample' => mm_ui_tabledrag_sample()]),
          '#weight' => -1,
        ];
        $form['suffix'] = [
          '#markup' => $this->t('<p></p><div id="help"><p>If a custom menu order is being used, any new items you add will appear at the top of the list. You will most likely have to return here to change their location.</p><p>Use the <em>Reorder Z to A and save</em> button to order the menu reverse-alphabetically.</p><p>Use the <em>Remove custom ordering and save</em> button to revert to the default, A to Z order. Any new items added later on will appear in their correct location, alphabetically.</p></div>'),
          '#weight' => 1000,
        ];

        $form['table'] = [
          '#type' => 'table',
          '#tabledrag' => [[
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'menu-weight',
          ]],
          '#attached' => ['library' => ['monster_menus/mm_css']],
        ];
        $new_weight = 1;
        foreach ($tree as $data) {
          $id = 'mmtid:' . $data->mmtid;
          $form['table'][$id]['#attributes'] = ['class' => ['menu-enabled']];
          $form['table'][$id]['title']['#mm_orig_name'] = $form['table'][$id]['title']['#plain_text'] = mm_content_get_name($data);
          $form['table'][$id]['weight'] = [
            '#type' => 'weight',
            '#delta' => $count,
            '#default_value' => $new_weight++,
            '#attributes' => ['class' => ['menu-weight']],
          ];
          $form['table'][$id]['mmtid'] = [
            '#type' => 'hidden',
            '#value' => $data->mmtid,
            '#attributes' => ['class' => ['menu-mmtid']],
            '#wrapper_attributes' => ['class' => ['hidden']],
          ];
          $form['table'][$id]['#attributes']['class'][] = 'draggable';
        }

        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Save configuration'),
        ];
        $form['actions']['spacer'] = ['#markup' => '&nbsp;&nbsp;&nbsp;&nbsp;'];
        $form['actions']['reorder_desc'] = [
          '#type' => 'submit',
          '#value' => $this->t('Reorder Z to A and save'),
        ];
      }
      $form['actions']['reorder_asc'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove custom ordering and save'),
      ];
    }
    elseif ($count == 1) {
      $form['empty_menu'] = ['#markup' => $this->t('There is only one visible @subthing of this @thing, so it cannot be reordered.', mm_ui_strings(FALSE))];
    }
    else {
      $form['empty_menu'] = ['#markup' => $this->t('There are no visible @subthings to reorder.', mm_ui_strings(FALSE))];
    }
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->set(['mm', 'result'], []);
    $first = TRUE;
    foreach ($form_state->getValue('table') as $entry) {
      // Make sure these are integers
      if (is_array($entry) && isset($entry['mmtid']) && intval($entry['mmtid'])) {
        // Make sure there is only one parent for all (meaning, nothing has moved)
        $parent = mm_content_get_parent(intval($entry['mmtid']));
        if ($first) {
          // Speed up mm_content_get_parent() by pre-fetching the rest of the kids
          mm_content_get_tree($parent, [
            Constants::MM_GET_TREE_DEPTH => 1,
            Constants::MM_GET_TREE_ADD_TO_CACHE => TRUE,
          ]);
          $first = FALSE;
        }
        if (isset($parent) && (!isset($last_parent) || $parent == $last_parent)) {
          $last_parent = $parent;
          // Don't store if 'Remove custom ordering' was clicked
          if ($form_state->getTriggeringElement()['#id'] != 'edit-actions-reorder-asc') {
            $index = 'mmtid:' . $entry['mmtid'];
            $form_state->set(['mm', 'result', $index], [
              'mmtid' => $entry['mmtid'],
              'name' => $form['table'][$index]['title']['#mm_orig_name'],
              'weight' => $entry['weight'],
            ]);
          }
        }
        else {
          $form_state->setErrorByName('', $this->t('The menu structure seems to have changed while you were editing it. Please try again.'));
          return;
        }
      }
    }

    if (isset($last_parent)) {
      if (!mm_content_user_can($last_parent, Constants::MM_PERMS_WRITE)) {
        $form_state->setErrorByName('', $this->t('You do not have permission to modify this menu.'));
      }
      else {
        $form_state->set(['mm', 'parent'], $last_parent);
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->get(['mm', 'parent'])) {
      $parent = $form_state->get(['mm', 'parent']);
      // Reset all children, including hidden and recycle bins.
      // If 'Remove custom ordering' was clicked, this is all we need to do.
      mm_content_update_quick(['weight' => 0], ['parent' => $parent], $parent, FALSE);
      $result = $form_state->get(['mm', 'result']);
      if ($result) {
        if ($form_state->getTriggeringElement()['#id'] == 'edit-actions-reorder-desc') {
          // Z to A sorting
          usort($result, function($arr1, $arr2) {
            return strnatcasecmp($arr2['name'], $arr1['name']);
          });
        }
        else {
          usort($result, ['\Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
        }

        $weight = 1;
        foreach ($result as $array) {
          mm_content_update_quick(['weight' => $weight++], [
            'mmtid' => $array['mmtid'],
            'parent' => $parent,
          ], $parent, FALSE);
        }
      }
      // Make sure the page draws with the new order
      mm_content_clear_caches($parent);
      if ($form_state->getTriggeringElement()['#id'] == 'edit-actions-reorder-asc') {
        \Drupal::messenger()->addStatus($this->t('Custom ordering has been removed.'));
      }
      else {
        \Drupal::messenger()->addStatus($this->t('The menu has been reordered.'));
      }
    }
  }

  static public function access(MMTree $mm_tree) {
    $mmtid = $mm_tree->id();
    $perms = mm_content_user_can($mmtid);
    if ($mmtid > 0 && !$perms[Constants::MM_PERMS_IS_RECYCLED] && $perms[Constants::MM_PERMS_WRITE] && $mmtid != mm_content_users_mmtid() && !mm_content_is_group($mmtid) && !mm_content_is_node_content_block($mmtid) && !mm_content_is_archive($mmtid)) {
      return AccessResult::allowedIf(\Drupal::currentUser()->hasPermission('administer all menus') || mm_content_resolve_cascaded_setting('allow_reorder', $mmtid, $reorder_at, $reorder_parent));
    }
    return AccessResult::forbidden();
  }

}
