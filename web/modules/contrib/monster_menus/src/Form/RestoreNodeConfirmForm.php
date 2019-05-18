<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\RestoreNodeConfirmForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Entity\MMTree;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class RestoreNodeConfirmForm extends ConfirmFormBase {

  use SetDestinationTrait;

  private $description, $cancel_url;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'restore_node_confirm';
  }

  /**
   * @inheritDoc
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to restore this content?');
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
    return $this->t('Restore');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  public function buildForm(array $form, FormStateInterface $form_state, MMTree $mm_tree = NULL, NodeInterface $node = NULL) {
    $mmtid = $mm_tree->id();
    $from = mm_content_get_parent($mmtid);
    if (!_mm_ui_recycle_page_list([$from], $names, $this->description, TRUE)) {
      $form['error'] = ['#markup' => $this->description];
      return $form;
    }
    $form['nid'] = ['#type' => 'value', '#value' => $node->id()];
    $form['bin_mmtid'] = ['#type' => 'value', '#value' => $mmtid];
    $form['return'] = ['#type' => 'value', '#value' => $from];

    $this->cancel_url = mm_content_get_mmtid_url($mmtid);
    $this->setDestination($this->cancel_url);

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && ($node = Node::load($form_state->getValue('nid'))) && $node->id()) {
      $err = mm_content_move_from_bin(NULL, $node, $form_state->getValue('bin_mmtid'));
      if (is_string($err)) {
        \Drupal::messenger()->addStatus($err);
      }
      if ($goto = $form_state->getValue('return')) {
        $form_state->setRedirectUrl(mm_content_get_mmtid_url($goto));
      }
    }
  }

  /**
   * Test to see if the current user has permission to restore the given node
   * to the given page from a recycle bin.
   *
   * @param MMTree $mm_tree
   * @param NodeInterface $node
   * @param AccountInterface $user
   * @return AccessResult
   */
  static public function access(MMTree $mm_tree, NodeInterface $node, AccountInterface $user = NULL) {
    $mmtid = $mm_tree->id();
    // If the current page is the contextual.render route, use the referer
    // to determine the associated MM page.
    $current_page = mm_active_menu_item();
    if (isset($current_page->mmtid)) {
      // Add an extra check in case the referer is spoofed.
      if (mm_content_user_can($current_page->mmtid, Constants::MM_PERMS_WRITE, $user)) {
        $mmtid = $current_page->mmtid;
      }
    }
    return AccessResult::allowedIf(static::canRestore($mmtid, $node, $user));
  }

  /**
   * Test to see if the current user has permission to restore the given node
   * to the given page from a recycle bin.
   *
   * @param int $mmtid
   * @param NodeInterface $node
   * @param AccountInterface $user
   * @return bool
   */
  static public function canRestore($mmtid, NodeInterface $node, AccountInterface $user = NULL) {
    return mm_content_node_is_recycled($node, $mmtid) && $node->access('delete', $user) && mm_content_user_can($mmtid, Constants::MM_PERMS_IS_RECYCLE_BIN, $user);
  }

}
