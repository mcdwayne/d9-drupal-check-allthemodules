<?php

namespace Drupal\notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class SkipForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notify_skip_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'notify.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('notify.settings');
    // Fetch list of nodes and comments scheduled for notification
    list ($res_nodes, $res_comms, $res_nopub, $res_copub, $res_nounp, $res_counp) = _notify_select_content();

    // Get the nodes and comments queued.
    $count = 0;
    $nodes = $comments = array();
    // Ordinary nodes
    foreach ($res_nodes as $row) {
      $nodes[$row->nid] = \Drupal::entityTypeManager()->getStorage('node')->load($row->nid);
      $count++;
    }
    // Ordinary comments
    if ($res_comms) {
      foreach ($res_nopub as $row) {
        if (!isset($nodes[$row->nid])) {
          $nodes[$row->nid] = \Drupal::entityTypeManager()->getStorage('node')->load($row->nid);
          $count++;
        }
      }
      foreach ($res_comms as $row) {
        $comment = \Drupal\comment\Entity\Comment::load($row->cid);
        $comments[$comment->get('entity_id')->target_id][$row->cid] = $comment;
        $count++;
      }
      foreach ($res_copub as $row) {
        if (!isset($comments[$row->nid][$row->cid])) {
          $comments[$row->get('entity_id')->target_id][$row->cid] = \Drupal\comment\Entity\Comment::load($row->cid);
          $count++;
        }
      }
    }
    // Published nodes in unpublished queue
    foreach ($res_nopub as $row) {
      if (!isset($nodes[$row->nid])) {
        $nodes[$row->nid] = \Drupal::entityTypeManager()->getStorage('node')->load($row->nid);
        $count++;
      }
    }
    // Unpublished nodes in unpublished queue
    foreach ($res_nounp as $row) {
      if (!isset($nodes[$row->nid])) {
        $nodes[$row->nid] = \Drupal::entityTypeManager()->getStorage('node')->load($row->nid);
        $count++;
      }
    }
    $form = array();

    $form['#tree'] = TRUE;
    $form['info'] = array(
      '#markup' => '<p>' . t('The following table shows all messages that are candidates for bulk notifications:' . '</p>'),
    );

    $skpnodes = $config->get('notify_skip_nodes', array());
    $skpcomts = $config->get('notify_skip_comments', array());
    $ii = 0;
    $entities = array();
    foreach ($nodes as $node) {
      $ii++;
      $entities[$ii] = array();
      $entities[$ii]['nid'] = array(
        '#markup' => $node->id(),
      );
      $entities[$ii]['cid'] = array(
        '#markup' => '-',
      );

      $entities[$ii]['created'] = array(
        '#markup' => \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'short'),
      );
      $entities[$ii]['updated'] = array(
        '#markup' => ($node->getChangedTime() != $node->getCreatedTime()) ? \Drupal::service('date.formatter')->format($node->getChangedTime(), 'short') : '-',
      );
      $entities[$ii]['title'] = array(
        '#markup' => $node->label(),
      );
      $flag = in_array($node->id(), $skpnodes) ? 1 : 0;
      $entities[$ii]['dist'] = array(
        '#type' => 'checkbox',
        '#default_value' => $flag,
      );
    }
    foreach ($comments as $thread) {
      foreach ($thread as $comment) {
        $ii++;
        $entities[$ii] = array();
        $entities[$ii]['nid'] = array(
          '#markup' => $comment->get('entity_id')->target_id,
        );
        $entities[$ii]['cid'] = array(
          '#markup' => $comment->id(),
        );
        $entities[$ii]['created'] = array(
          '#markup' => \Drupal::service('date.formatter')->format($comment->getCreatedTime(), 'short'),
        );
        $entities[$ii]['updated'] = array(
          '#markup' => ($comment->getChangedTime() != $comment->getCreatedTime()) ? \Drupal::service('date.formatter')->format($comment->getChangedTime(), 'short') : '-',
        );
        $entities[$ii]['title'] = array(
          '#markup' => $comment->label(),
        );
        $flag = in_array($comment->id(), $skpcomts) ? 1 : 0;
        $entities[$ii]['dist'] = array(
          '#type' => 'checkbox',
          '#default_value' => $flag,
        );
      }
    }
    $form['entities'] = $entities;
    $batch_remain = count($config->get('notify_users', array()));
    if ($batch_remain) {
      $form['info2'] = array(
        '#markup' => '<p>' . t('Please note that the list above may be out of sync.  Saving an altered list of skip flags is disabled as long as notifications are being processed.') . '</p> ',
      );
    }
    else {
      $form['info2'] = array(
        '#markup' => '<p>' . t('To flag that <em>no</em> notification about a particular message should be sent, check the checkbox in the &#8220;Skip&#8220; column. Press &#8220;Save settings&#8220; to save the flags.') . '</p> ',
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_values = $form_state->getCompleteForm();

    $nodes = array();
    $comts = array();
    if (isset($values['entities']) && $values['entities']) {
      foreach ($values['entities'] as $dist => $ii) {
        if ($ii['dist']) {
          $nid = $form_values['entities'][$dist]['nid']['#markup'];
          $cid = $form_values['entities'][$dist]['cid']['#markup'];
          if ('-' == $cid) {
            array_push($nodes, (int)$nid);
          }
          else {
            array_push($comts, (int)$cid);
          }
        }
      }

      $this->config('notify.settings')
        ->set('notify_skip_nodes', $nodes)
        ->set('notify_skip_comments', $comts)
        ->save();
    }

    drupal_set_message(t('Skip flags saved.'));
  }

}
