<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\DeleteNodeConfirmForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\monster_menus\Constants;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteContentConfirmForm extends ConfirmFormBase {

  use SetDestinationTrait;

  private $question, $description, $confirm_text, $cancel_url;

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
    return 'mm_ui_content_delete_confirm';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    list ($tree, $x, $is_group, $mmtids, $del_perm, $kids, $nodes, $excl_nodes) = $form_state->getBuildInfo()['args'];
    $form['mmtid'] = array('#type' => 'value', '#value' => $tree[0]->mmtid);
    $this->description = '';

    if ($tree[0]->perms[Constants::MM_PERMS_IS_RECYCLE_BIN]) {
      $n = array('@pages' => $kids, '@nodes' => count($nodes));
      if ($kids && $nodes) {
        $this->description = $this->t('There are currently @pages page(s) and @nodes piece(s) of content in this recycle bin. ', $n);
      }
      elseif ($kids) {
        $this->description = $this->t('There are currently @pages page(s) in this recycle bin. ', $n);
      }
      elseif ($nodes) {
        $this->description = $this->t('There are currently @nodes piece(s) of content in this recycle bin. ', $n);
      }
      $this->description .= $this->t('Are you sure you want to empty it?');
      $this->confirm_text = $this->t('Empty Bin');
      $this->cancel_url = mm_content_get_mmtid_url($tree[0]->mmtid);
    }
    else {  // !MM_PERMS_IS_RECYCLE_BIN
      if (!$kids) {
        $this->description = $del_perm ?
          $this->t('The @thing %name will be permanently deleted.', $x) :
          $this->t('The @thing %name will be moved to the recycle bin.', $x);
      }
      elseif ($kids == 1) {
        $this->description = $del_perm ?
          $this->t('The @thing %name and its @subthing %sub will be permanently deleted.', $x) :
          $this->t('The @thing %name and its @subthing %sub will be moved to the recycle bin.', $x);
      }
      else {
        $this->description = $del_perm ?
          $this->t('The @thing %name and its @num @subthings will be permanently deleted.', $x) :
          $this->t('The @thing %name and its @num @subthings will be moved to the recycle bin.', $x);
      }

      if ($is_group) {
        $num_mmtids = $this->database->select('mm_tree_access', 'a')
          ->fields('a', array('mmtid'))
          ->distinct()
          ->condition('a.gid', $mmtids, 'IN')
          ->countQuery()->execute()->fetchField();
        $num_nodes = $this->database->select('mm_node_write', 'w')
          ->fields('w', array('nid'))
          ->distinct()
          ->condition('w.gid', $mmtids, 'IN')
          ->countQuery()->execute()->fetchField();
        $x['@mmtids'] = $num_mmtids;
        $x['@nodes'] = $num_nodes;
        if ($num_mmtids && $num_nodes) $this->description .= ' ' . $this->t('The permissions of @mmtids page(s) and @nodes piece(s) of content will be affected.', $x);
        elseif ($num_mmtids) $this->description .= ' ' . $this->t('The permissions of @mmtids page(s) will be affected.', $x);
        elseif ($num_nodes) $this->description .= ' ' . $this->t('The permissions of @nodes piece(s) of content will be affected.', $x);
      }
      elseif ($del_perm) {   // !$is_group
        if ($nodes) {
          if (!$excl_nodes) {
            $this->description .= '<p>' . $this->t('Any page contents appear on other pages as well, and will therefore not be permanently deleted.') . '</p>';
          }
          elseif (!mm_content_recycle_enabled() && $this->currentUser()->hasPermission('bypass node access')) {
            $x['@kidthings'] = $kids ? $x['@things'] : $x['@thing'];
            $x['@nodes'] = count($excl_nodes);
            $form['delnodes'] = array(
              '#type' => 'checkbox',
              '#title' => $this->t('Also permanently delete the @nodes piece(s) of content appearing on the @kidthings, which are not assigned elsewhere', $x)
            );
          }
          else {
            $can_del = 0;
            foreach (Node::loadMultiple($excl_nodes) as $node) {
              if ($node->access('delete')) {
                $can_del++;
              }
            }
            $this->description .= '<p>';
            if ($can_del == count($excl_nodes)) {
              if (!mm_content_recycle_enabled()) {
                $this->description .= $this->t('All contents on the page(s) not present on other pages will be permanently deleted.');
              }
              else {
                $this->description .= $this->t('All contents on the page(s) will be permanently deleted.');
              }
            }
            elseif (!mm_content_recycle_enabled()) {
              $this->description .= $this->t('Any contents you have permission to delete and are not present on other pages will be permanently deleted.');
            }
            else {
              $this->description .= $this->t('Any contents you have permission to delete will be permanently deleted.');
            }
            $this->description .= '</p>';
          }
        }
      }

      $this->confirm_text = $this->t('Delete');
      $this->cancel_url = 'mm/' . $tree[0]->mmtid . '/settings';
    }   // !MM_PERMS_IS_RECYCLE_BIN

    $form['del_perm'] = array('#type' => 'value', '#value' => $del_perm);

    if ($del_perm) {
      $this->question = $tree[0]->perms[Constants::MM_PERMS_IS_RECYCLE_BIN] ? $this->t('Are you sure you want to empty this recycle bin?') : $this->t('Permanently delete %name?', $x);
      $this->description .= ' ' . $this->t('This action cannot be undone.');
    }
    else {
      $this->question = $this->t('Move %name to the recycle bin?', $x);
    }

    $this->setDestination($this->cancel_url);
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mmtid = $form_state->getValue('mmtid');

    $tree = mm_content_get_tree($mmtid, array(Constants::MM_GET_TREE_RETURN_PERMS => TRUE, Constants::MM_GET_TREE_RETURN_FLAGS => TRUE));
    if (!$tree) {
      $form_state->setErrorByName('', $this->t('Node not found.'));
    }

    if (!$this->currentUser()->hasPermission('administer all menus')) {
      if (isset($tree[0]->flags['limit_delete'])) {
        throw new AccessDeniedHttpException();
      }

      $x = mm_ui_strings($tree[0]->is_group);
      foreach ($tree as $t) {
        if ($form_state->getValue('del_perm') && !$t->perms[Constants::MM_PERMS_WRITE] && !$t->perms[Constants::MM_PERMS_IS_RECYCLE_BIN] || isset($t->flags['limit_delete'])) {
          $x['%name'] = mm_content_get_name($t);
          $form_state->setErrorByName('', $this->t('You cannot delete this @thing because you do not have permission to delete the @subthing %name', $x));
        }
      }
    }

    if ($mmtid == mm_home_mmtid()) {
      $x['%name'] = mm_content_get_name($tree[0]);
      $form_state->setErrorByName('', $this->t('The %name @thing cannot be deleted.', $x));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm')) {
      $parent = mm_content_get_parent($mmtid = $form_state->getValue('mmtid'));

      if ($form_state->getValue('del_perm')) {
        $pars = mm_content_get_parents($mmtid);
        $err = mm_content_delete($mmtid, mm_content_recycle_enabled() || !empty($form_state->getValue('delnodes')), TRUE);
        if (!$err) {
          // Try to delete the bin, but only bother trying if it's this entry's immediate parent.
          if (count($pars) && mm_content_user_can($pars[count($pars) - 1], Constants::MM_PERMS_IS_RECYCLE_BIN)) {
            $err = mm_content_delete_bin($pars[count($pars) - 1]);
            $parent = count($pars) >= 2 ? $pars[count($pars) - 2] : mm_home_mmtid();
          }
        }
      }
      else {
        $err = mm_content_move_to_bin($mmtid);
      }

      if (is_string($err)) {
        \Drupal::messenger()->addError($this->t($err));
      }
      else {
        mm_set_form_redirect_to_mmtid($form_state, $parent);
        return;
      }
    }
    mm_set_form_redirect_to_mmtid($form_state);
  }

}
