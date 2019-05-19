<?php
/**
 * @file
 * Contains \Drupal\article\Plugin\Block\SignedNodesAgreementBlock.
 */

namespace Drupal\signed_nodes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides an "agreement form" block.
 *
 * @Block(
 *   id = "signed_nodes_user_agreement_form_1",
 *   admin_label = @Translation("Signed Nodes User Agreement Form"),
 *   category = @Translation("Signed Nodes")
 * )
 */

class SignedNodesAgreementBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $node = \Drupal::routeMatch()->getParameter('node');
    
    if ($node instanceof \Drupal\node\NodeInterface) {
      $nid = $node->id();
      $snid = db_query("SELECT snid FROM {signed_nodes} where nid = :nid and year = :year", array(':nid' => $nid, ':year' => date('Y')))->fetchField();

      $account = \Drupal::currentUser();
      $uid = $account->id();

      if ($snid && $uid > 0) {
        $agreed = db_query("SELECT 1 FROM {signed_nodes_user} where snid = :snid and uid = :uid", array(':snid' => $snid, ':uid' => $uid))->fetchField();

        if (!$agreed) {
          $form = \Drupal::formBuilder()->getForm('Drupal\signed_nodes\Form\SignedNodesUserAgreementForm', $snid);
          $build['form'] = $form;
        }
        else {
          $build['#markup'] = t('You have signed the agreement attached to this node.');
        }
        return $build;
      }
    }
  }

   /**
   * {@inheritdoc}
   */
   public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }
}