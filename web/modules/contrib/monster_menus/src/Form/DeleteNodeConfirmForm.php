<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\DeleteNodeConfirmForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\filter\Render\FilteredMarkup;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Entity\MMTree;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\node\Entity\Node;

class DeleteNodeConfirmForm extends ConfirmFormBase {

  use SetDestinationTrait;

  private $question, $description, $confirm_text, $cancel_url;

  /**
   * @inheritDoc
   */
  public function getQuestion() {
    return $this->question;
  }

  /**
   * @inheritDoc
   */
  public function getCancelUrl() {
    return $this->cancel_url;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->confirm_text;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_ui_node_delete_confirm';
  }

  public function buildForm(array $form, FormStateInterface $form_state, MMTree $mm_tree = NULL, NodeInterface $node = NULL) {
    $mmtid = $mm_tree ? $mm_tree->id() : NULL;
    $title = mm_ui_fix_node_title($node->label());
    if (!mm_content_user_can_node($node, Constants::MM_PERMS_APPLY)) {
      throw new AccessDeniedHttpException();
    }

    $names = array();
    $other_bins = 0;
    if ($current_pages = mm_content_get_by_nid($node->id())) {
      if (!in_array($mmtid, $current_pages)) {
        $mmtid = $current_pages[0];
      }

      foreach (mm_content_get($current_pages) as $tree) {
        if ($tree->mmtid != $mmtid && mm_content_user_can($tree->mmtid, Constants::MM_PERMS_IS_RECYCLED)) {
          $other_bins++;
        }
        else {
          $names[$tree->mmtid] = Link::fromTextAndUrl(mm_content_get_name($tree), mm_content_get_mmtid_url($tree->mmtid))->toString();
        }
      }
    }

    $permanent = mm_content_node_is_recycled($node, $mmtid) || !mm_content_recycle_enabled();
    $form['nid'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];
    $form['permanent'] = [
      '#type' => 'value',
      '#value' => $permanent,
    ];
    if (isset($mmtid)) {
      $form['mmtid'] = [
        '#type' => 'value',
        '#value' => $mmtid,
      ];
    }

    $this->confirm_text = $this->t('Delete');
    if ($permanent) {
      $other_pages = count($names) - 1;
      if ($other_bins || $other_pages) {
        $this->confirm_text = $this->t('Remove');
        $this->question = $this->t('Permanently remove %title?', ['%title' => $title]);
        $this->description = $this->t('Are you sure you want to permanently remove %title from this page?', ['%title' => $title]);
        if ($other_bins && $other_pages) {
          $this->description .= ' ' . $this->t('(It will continue to appear on @pages other page(s) and to exist in @bins recycle bin(s).)', ['@pages' => $other_pages, '@bins' => $other_bins]);
        }
        else {
          if ($other_bins) {
            $this->description .= ' ' . $this->t('(It will continue to exist in @bins recycle bin(s).)', ['@bins' => $other_bins]);
          }
          else {
            $this->description .= ' ' . $this->t('(It will continue to appear on @pages other page(s).)', ['@pages' => $other_pages]);
          }
        }
      }
      else {
        $this->question = $this->t('Permanently delete %title?', ['%title' => $title]);
        $this->description = $this->t('Are you sure you want to permanently delete %title?', ['%title' => $title]);
      }
    }
    else {
      $this->question = $this->t('Move %title to the recycle bin?', ['%title' => $title]);
      if (empty($mmtid) || empty($names[$mmtid])) {
        $this->description = $this->t('Are you sure you want to move %title to the recycle bin?', ['%title' => $title]);
      }
      else {
        if (count($names) == 1) {
          $this->description = $this->t('Are you sure you want to move %title from @link to the recycle bin?', ['%title' => $title, '@link' => $names[$mmtid]]);
        }
        else if ($names) {
          $this->description = $this->t('%title appears on multiple pages: @pages', ['%title' => $title, '@pages' => FilteredMarkup::create(implode(', ', $names))]);
          $form['mode'] = [
            '#type' => 'radios',
            '#options' => [
              'one' => $this->t('Move from <strong>just this page</strong> to the recycle bin'),
              'all' => $this->t('Move from <strong>all pages</strong> to the recycle bin'),
            ],
            '#default_value' => 'one',
            '#weight' => 0.5,
          ];
        }
      }
    }

    $this->cancel_url = $mmtid ? mm_content_get_mmtid_url($mmtid) : Url::fromRoute('entity.node.canonical', ['node' => $node->id()]);
    $this->setDestination($this->cancel_url);

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && ($node = Node::load($form_state->getValue('nid'))) && $node->id()) {
      $redirect = $page_mmtid = $form_state->getValue('mmtid');

      $err = '';
      if ($form_state->getValue('permanent')) {
        if ($page_mmtid) {
          if (mm_content_is_recycle_bin($page_mmtid)) {
            $redirect = mm_content_get_parent($page_mmtid);
          }
          if (array_diff(mm_content_get_by_nid($node->id()), [$page_mmtid])) {
            // If the node is on other pages, just remove the reference.
            mm_content_remove_node_from_page($node->id(), $page_mmtid);
          }
          else {
            $node->delete();
          }
        }
        else {
          $node->delete();
        }
      }
      else {
        $nids = $page_mmtid && $form_state->getValue('mode') === 'one' ? [$node->id() => [$page_mmtid]] : $node->id();
        $err = mm_content_move_to_bin(NULL, $nids);
      }

      if (!empty($err) && is_string($err)) {
        \Drupal::messenger()->addError($this->t($err));
        return;
      }

      if (!$redirect) {
        $redirect = is_numeric($err) ? mm_content_get_parent($err) : mm_home_mmtid();
      }
      $form_state->setRedirectUrl(mm_content_get_mmtid_url($redirect));
    }
  }

  static public function access(NodeInterface $node, AccountInterface $user = NULL) {
    return AccessResult::allowedIf(static::canDelete($node, $user));
  }

  static public function canDelete(NodeInterface $node, AccountInterface $user = NULL) {
    $user = $user ?: \Drupal::currentUser();
    return $node->access('delete', $user) && mm_content_user_can_node($node, Constants::MM_PERMS_APPLY, $user) && (mm_content_node_is_recycled($node, Constants::MM_NODE_RECYCLED_MMTID_CURR) ? $user->hasPermission('delete permanently') : TRUE);
  }

  static public function getMenuTitle(NodeInterface $node) {
    return mm_content_node_is_recycled($node, Constants::MM_NODE_RECYCLED_MMTID_CURR) ? 'Delete permanently' : 'Delete';
  }

}
